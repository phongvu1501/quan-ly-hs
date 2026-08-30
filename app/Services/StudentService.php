<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class StudentService
{
    private const PROFILE_FIELDS = [
        'full_name', 'email', 'phone', 'date_of_birth', 'gender', 'address',
        'birth_place', 'admission_year', 'status', 'notes',
    ];

    private const HISTORY_RELATIONS = [
        'enrollments' => 'lịch sử xếp lớp',
        'scores' => 'điểm',
        'attendance' => 'điểm danh',
        'subjectResults' => 'kết quả môn học',
        'conductEvaluations' => 'hạnh kiểm',
        'comments' => 'nhận xét',
        'examResults' => 'kết quả kỳ thi',
        'feeAssessments' => 'công nợ học phí',
        'payments' => 'thanh toán học phí',
        'feePolicies' => 'chính sách học phí',
    ];

    public function __construct(private readonly EnrollmentService $enrollments) {}

    /** @param array<string, mixed> $input */
    public function create(array $input): Student
    {
        $newAvatarPath = $this->storeAvatar($input['avatar'] ?? null);

        try {
            return DB::transaction(function () use ($input, $newAvatarPath): Student {
                $student = Student::create(Arr::only($input, self::PROFILE_FIELDS) + [
                    'avatar_path' => $newAvatarPath,
                ]);
                $this->enrollments->enroll(
                    $student,
                    isset($input['academic_year_id']) ? (int) $input['academic_year_id'] : null,
                    isset($input['class_id']) ? (int) $input['class_id'] : null,
                );
                $this->syncGuardians($student, $input['guardians'] ?? []);
                $this->createAccount($student);

                return $student->fresh(['user']);
            });
        } catch (Throwable $exception) {
            if ($newAvatarPath) {
                Storage::disk('public')->delete($newAvatarPath);
            }
            throw $exception;
        }
    }

    /** @param array<string, mixed> $input */
    public function update(Student $student, array $input): Student
    {
        $this->assertStatusTransition($student->status, (string) $input['status']);
        $newAvatarPath = $this->storeAvatar($input['avatar'] ?? null);
        $oldAvatarPath = $student->avatar_path;

        try {
            $updated = DB::transaction(function () use ($student, $input, $newAvatarPath): Student {
                $locked = Student::query()->lockForUpdate()->findOrFail($student->id);
                $oldStatus = $locked->status;
                $attributes = Arr::only($input, self::PROFILE_FIELDS);
                if ($newAvatarPath) {
                    $attributes['avatar_path'] = $newAvatarPath;
                }
                $locked->update($attributes);

                if ($locked->user) {
                    $locked->user->update(array_filter([
                        'name' => $locked->full_name,
                        'email' => $locked->email,
                        'avatar_path' => $newAvatarPath ?: $locked->user->avatar_path,
                    ], fn (mixed $value) => $value !== null));
                }

                $this->enrollments->enroll(
                    $locked,
                    isset($input['academic_year_id']) ? (int) $input['academic_year_id'] : null,
                    isset($input['class_id']) ? (int) $input['class_id'] : null,
                );
                if ($oldStatus !== $locked->status && $oldStatus === Student::STATUS_STUDYING) {
                    $this->enrollments->closeForTerminalStatus($locked, $locked->status, today());
                }
                $this->syncGuardians($locked, $input['guardians'] ?? []);

                return $locked->fresh(['user']);
            });
        } catch (Throwable $exception) {
            if ($newAvatarPath) {
                Storage::disk('public')->delete($newAvatarPath);
            }
            throw $exception;
        }

        if ($newAvatarPath && $oldAvatarPath && $oldAvatarPath !== $newAvatarPath) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

        return $updated;
    }

    public function delete(Student $student): void
    {
        $avatarPath = $student->avatar_path;
        DB::transaction(function () use ($student): void {
            $locked = Student::query()->lockForUpdate()->findOrFail($student->id);
            $blockers = $this->deletionBlockers($locked);
            if ($blockers !== []) {
                throw ValidationException::withMessages([
                    'student' => 'Không thể xóa học sinh vì hồ sơ đã phát sinh dữ liệu học vụ hoặc tài chính. Vui lòng thay đổi trạng thái học sinh thay vì xóa hồ sơ.',
                ]);
            }

            $user = $locked->user;
            $locked->guardians()->detach();
            $locked->delete();
            if ($user) {
                $studentRoleId = Role::where('slug', 'student')->value('id');
                if ($studentRoleId) {
                    $user->roles()->detach($studentRoleId);
                }
            }
        });

        if ($avatarPath) {
            Storage::disk('public')->delete($avatarPath);
        }
    }

    /** @return list<string> */
    public function deletionBlockers(Student $student): array
    {
        $blockers = [];
        foreach (self::HISTORY_RELATIONS as $relation => $label) {
            if ($student->{$relation}()->exists()) {
                $blockers[] = $label;
            }
        }

        return $blockers;
    }

    /** @return list<string> */
    public static function historyExistenceColumns(): array
    {
        return collect(array_keys(self::HISTORY_RELATIONS))
            ->map(fn (string $relation) => "{$relation} as has_history_{$relation}")
            ->all();
    }

    public static function decorateDeletionEligibility(Collection $students): void
    {
        $students->each(function (Student $student): void {
            $hasHistory = collect(array_keys(self::HISTORY_RELATIONS))->contains(
                fn (string $relation) => (bool) $student->getAttribute("has_history_{$relation}")
            );
            $student->setAttribute('can_delete', ! $hasHistory);
        });
    }

    /** @return array<string, mixed> */
    public function detailData(Student $student): array
    {
        $student->load([
            'user:id,name,email,avatar_path',
            'guardians',
            'enrollments' => fn ($query) => $query
                ->with(['academicYear', 'class.grade'])
                ->orderByDesc('academic_year_id'),
        ])->loadCount(array_keys(self::HISTORY_RELATIONS));
        $currentYearId = AcademicYear::where('is_current', true)->value('id');
        $currentEnrollment = $student->enrollments
            ->first(fn ($enrollment) => (int) $enrollment->academic_year_id === (int) $currentYearId)
            ?? $student->enrollments->first();

        return [
            'student' => $student,
            'currentEnrollment' => $currentEnrollment,
            'recentScores' => $student->scores()->with(['subject', 'semester'])->latest('assessed_on')->limit(8)->get(),
            'recentExamResults' => $student->examResults()->with('exam.subject')->latest()->limit(5)->get(),
            'recentFeeAssessments' => $student->feeAssessments()->latest('assessment_date')->limit(5)->get(),
            'attendanceSummary' => $student->attendance()
                ->selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total', 'status'),
        ];
    }

    private function assertStatusTransition(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }
        $allowed = [
            Student::STATUS_STUDYING => [
                Student::STATUS_SICK_LEAVE, Student::STATUS_RESERVED,
                Student::STATUS_DROPPED, Student::STATUS_GRADUATED,
            ],
            Student::STATUS_SICK_LEAVE => [
                Student::STATUS_STUDYING, Student::STATUS_RESERVED,
                Student::STATUS_DROPPED, Student::STATUS_GRADUATED,
            ],
            Student::STATUS_RESERVED => [
                Student::STATUS_STUDYING, Student::STATUS_SICK_LEAVE,
                Student::STATUS_DROPPED, Student::STATUS_GRADUATED,
            ],
            Student::STATUS_DROPPED => [],
            Student::STATUS_GRADUATED => [],
        ];
        if (! in_array($to, $allowed[$from] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'Không thể chuyển trạng thái học sinh từ "'.(Student::STATUS_LABELS[$from] ?? $from).'" sang "'.(Student::STATUS_LABELS[$to] ?? $to).'".',
            ]);
        }
    }

    private function createAccount(Student $student): User
    {
        $user = User::create([
            'name' => $student->full_name,
            'email' => $student->email,
            'password' => Hash::make('123456'),
            'must_change_password' => true,
            'avatar_path' => $student->avatar_path,
        ]);
        $role = Role::where('slug', 'student')->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);
        $student->user()->associate($user)->save();

        return $user;
    }

    /** @param array<string, mixed> $rows */
    private function syncGuardians(Student $student, array $rows): void
    {
        $existing = $student->guardians()->get()->keyBy('pivot.relationship');
        $primaryRelationship = filled(data_get($rows, 'father.full_name')) ? 'father' : 'mother';
        $fields = [
            'full_name', 'date_of_birth', 'phone', 'email', 'occupation',
            'religion', 'workplace', 'address', 'notes',
        ];

        foreach (['father', 'mother'] as $relationship) {
            $payload = collect($rows[$relationship] ?? [])->only($fields)
                ->map(fn (mixed $value) => filled($value) ? $value : null)->all();
            $guardian = $existing->get($relationship);
            if (! collect($payload)->contains(fn (mixed $value) => filled($value))) {
                if ($guardian) {
                    $student->guardians()->detach($guardian->id);
                }
                continue;
            }
            if (blank($payload['full_name'] ?? null)) {
                throw ValidationException::withMessages([
                    "guardians.{$relationship}.full_name" => 'Vui lòng nhập họ tên phụ huynh khi có thông tin liên hệ.',
                ]);
            }

            $guardian ??= new Guardian();
            $guardian->fill($payload)->save();
            $student->guardians()->syncWithoutDetaching([
                $guardian->id => [
                    'relationship' => $relationship,
                    'is_primary' => $relationship === $primaryRelationship,
                ],
            ]);
            $student->guardians()->updateExistingPivot($guardian->id, [
                'relationship' => $relationship,
                'is_primary' => $relationship === $primaryRelationship,
            ]);
        }
    }

    private function storeAvatar(mixed $avatar): ?string
    {
        return $avatar instanceof UploadedFile ? $avatar->store('avatars/students', 'public') : null;
    }
}
