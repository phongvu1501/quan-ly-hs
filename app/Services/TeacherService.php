<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class TeacherService
{
    private const PROFILE_FIELDS = [
        'full_name', 'email', 'phone', 'date_of_birth', 'gender', 'address',
        'hire_date', 'specialization', 'status', 'notes',
    ];

    private const HISTORY_RELATIONS = [
        'homeroomAssignments' => 'lịch sử chủ nhiệm',
        'assignments' => 'phân công giảng dạy',
        'subjects' => 'năng lực môn học',
        'schedules' => 'thời khóa biểu',
        'examInvigilations' => 'phân công coi thi',
        'scores' => 'điểm đã nhập',
        'comments' => 'nhận xét học sinh',
        'salaryProfiles' => 'hồ sơ lương',
        'payrollInputs' => 'dữ liệu đầu vào lương',
        'teachingExtraRecords' => 'dữ liệu dạy thêm',
        'payrolls' => 'bảng lương',
        'payrollPayments' => 'thanh toán lương',
        'payrollAdjustments' => 'điều chỉnh lương',
    ];

    public function __construct(private readonly HomeroomAssignmentService $homerooms) {}

    /** @param array<string, mixed> $input */
    public function create(array $input): Teacher
    {
        $newAvatarPath = $this->storeAvatar($input['avatar'] ?? null);

        try {
            return DB::transaction(function () use ($input, $newAvatarPath): Teacher {
                $teacher = Teacher::create(Arr::only($input, self::PROFILE_FIELDS) + [
                    'avatar_path' => $newAvatarPath,
                ]);
                if ($teacher->isActive()) {
                    $this->syncProfessionalProfile($teacher, $input);
                }
                $this->createAccount($teacher, $input);

                return $teacher->fresh(['user', 'subjects']);
            });
        } catch (Throwable $exception) {
            if ($newAvatarPath) {
                Storage::disk('public')->delete($newAvatarPath);
            }
            throw $exception;
        }
    }

    /** @param array<string, mixed> $input */
    public function update(Teacher $teacher, array $input): Teacher
    {
        $this->assertStatusTransition($teacher->status, (string) $input['status']);
        $newAvatarPath = $this->storeAvatar($input['avatar'] ?? null);
        $oldAvatarPath = $teacher->avatar_path;

        try {
            $updated = DB::transaction(function () use ($teacher, $input, $newAvatarPath): Teacher {
                $locked = Teacher::query()->lockForUpdate()->findOrFail($teacher->id);
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

                if ($oldStatus === Teacher::STATUS_ACTIVE && ! $locked->isActive()) {
                    $this->deactivateResponsibilities($locked);
                } else {
                    $this->syncProfessionalProfile($locked, $input);
                }
                $this->syncPortalRoles($locked->user, $input, $locked);

                return $locked->fresh(['user', 'subjects']);
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

    public function delete(Teacher $teacher): void
    {
        $avatarPath = $teacher->avatar_path;
        DB::transaction(function () use ($teacher): void {
            $locked = Teacher::query()->lockForUpdate()->findOrFail($teacher->id);
            if ($this->deletionBlockers($locked) !== []) {
                throw ValidationException::withMessages([
                    'teacher' => 'Không thể xóa giáo viên vì hồ sơ đã phát sinh dữ liệu liên quan. Vui lòng chuyển trạng thái công tác thay vì xóa hồ sơ.',
                ]);
            }

            $user = $locked->user;
            $locked->delete();
            if ($user) {
                $managedRoleIds = Role::whereIn('slug', ['teacher', 'homeroom_teacher', 'subject_teacher'])->pluck('id');
                $user->roles()->detach($managedRoleIds);
            }
        });

        if ($avatarPath) {
            Storage::disk('public')->delete($avatarPath);
        }
    }

    /** @return list<string> */
    public function deletionBlockers(Teacher $teacher): array
    {
        $blockers = [];
        foreach (self::HISTORY_RELATIONS as $relation => $label) {
            if ($relation === 'homeroomAssignments' && ! Schema::hasTable('homeroom_assignments')) {
                continue;
            }
            if ($teacher->{$relation}()->exists()) {
                $blockers[] = $label;
            }
        }

        return $blockers;
    }

    /** @return list<string> */
    public static function historyExistenceColumns(): array
    {
        return collect(array_keys(self::HISTORY_RELATIONS))
            ->reject(fn (string $relation) => $relation === 'homeroomAssignments' && ! Schema::hasTable('homeroom_assignments'))
            ->map(fn (string $relation) => "{$relation} as has_history_{$relation}")
            ->all();
    }

    public static function decorateDeletionEligibility(Collection $teachers): void
    {
        $teachers->each(function (Teacher $teacher): void {
            $relations = collect(array_keys(self::HISTORY_RELATIONS))
                ->reject(fn (string $relation) => $relation === 'homeroomAssignments' && ! Schema::hasTable('homeroom_assignments'));
            $hasHistory = $relations->contains(
                fn (string $relation) => (bool) $teacher->getAttribute("has_history_{$relation}")
            );
            $teacher->setAttribute('can_delete', ! $hasHistory);
        });
    }

    /** @return array<string, mixed> */
    public function detailData(Teacher $teacher): array
    {
        $relations = [
            'user:id,name,email,avatar_path',
            'subjects' => fn ($query) => $query->orderBy('name'),
        ];
        if (Schema::hasTable('homeroom_assignments')) {
            $relations['homeroomAssignments'] = fn ($query) => $query
                ->with(['academicYear', 'class.grade'])->orderByDesc('academic_year_id');
        }
        $teacher->load($relations)->loadCount(
            collect(array_keys(self::HISTORY_RELATIONS))
                ->reject(fn (string $relation) => $relation === 'homeroomAssignments' && ! Schema::hasTable('homeroom_assignments'))
                ->all()
        );
        if (! Schema::hasTable('homeroom_assignments')) {
            $teacher->setRelation('homeroomAssignments', collect());
        }

        return [
            'teacher' => $teacher,
            'recentAssignments' => $teacher->assignments()
                ->with(['academicYear', 'semester', 'class.grade', 'subject'])
                ->latest('academic_year_id')->limit(10)->get(),
            'recentSchedules' => $teacher->schedules()
                ->with(['academicYear', 'semester', 'class', 'subject'])
                ->latest('academic_year_id')->limit(10)->get(),
            'recentPayrolls' => $teacher->payrolls()->latest('payroll_year')->latest('payroll_month')->limit(5)->get(),
        ];
    }

    /** @param array<string, mixed> $input */
    private function syncProfessionalProfile(Teacher $teacher, array $input): void
    {
        $subjectIds = collect($input['subjects'] ?? [])->map(fn (mixed $id) => (int) $id)->unique()->values();
        $requestedHomeroomIds = ! empty($input['is_homeroom_teacher'])
            ? collect($input['homeroom_class_ids'] ?? [])->map(fn (mixed $id) => (int) $id)->unique()->values()
            : collect();
        $needsYear = $subjectIds->isNotEmpty() || $requestedHomeroomIds->isNotEmpty();
        $academicYearId = isset($input['academic_year_id'])
            ? (int) $input['academic_year_id']
            : (int) AcademicYear::where('is_current', true)->value('id');
        if ($needsYear && ! $academicYearId) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'Hãy chọn năm học để lưu chuyên môn hoặc lớp chủ nhiệm.',
            ]);
        }
        if (! $academicYearId) {
            return;
        }

        $assignedSubjectIds = $teacher->assignments()
            ->where('academic_year_id', $academicYearId)
            ->pluck('subject_id')->map(fn (mixed $id) => (int) $id)->unique();
        if ($assignedSubjectIds->diff($subjectIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'subjects' => 'Không thể bỏ môn đã có lịch sử phân công trong năm học này.',
            ]);
        }

        if (! $teacher->isActive()) {
            return;
        }

        DB::table('teacher_subject')
            ->where('teacher_id', $teacher->id)
            ->where('academic_year_id', $academicYearId)
            ->delete();
        if ($subjectIds->isNotEmpty()) {
            $now = now();
            DB::table('teacher_subject')->insert($subjectIds->map(fn (int $subjectId) => [
                'teacher_id' => $teacher->id,
                'subject_id' => $subjectId,
                'academic_year_id' => $academicYearId,
                'is_primary' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        }

        $homeroomIds = $teacher->isActive() ? $requestedHomeroomIds : collect();
        $this->homerooms->syncTeacherYear($teacher, $academicYearId, $homeroomIds);
    }

    private function deactivateResponsibilities(Teacher $teacher): void
    {
        $teacher->assignments()
            ->where('status', 'active')
            ->update(['status' => 'inactive']);
        $this->homerooms->endAllForTeacher($teacher);
    }

    private function assertStatusTransition(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }
        $allowed = [
            Teacher::STATUS_ACTIVE => [Teacher::STATUS_ON_LEAVE, Teacher::STATUS_INACTIVE],
            Teacher::STATUS_ON_LEAVE => [Teacher::STATUS_ACTIVE, Teacher::STATUS_INACTIVE],
            Teacher::STATUS_INACTIVE => [],
        ];
        if (! in_array($to, $allowed[$from] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'Không thể chuyển trạng thái giáo viên từ "'.(Teacher::STATUS_LABELS[$from] ?? $from).'" sang "'.(Teacher::STATUS_LABELS[$to] ?? $to).'".',
            ]);
        }
    }

    /** @param array<string, mixed> $input */
    private function createAccount(Teacher $teacher, array $input): User
    {
        $user = User::create([
            'name' => $teacher->full_name,
            'email' => $teacher->email,
            'password' => Hash::make('123456'),
            'must_change_password' => true,
            'avatar_path' => $teacher->avatar_path,
        ]);
        $teacher->user()->associate($user)->save();
        $this->syncPortalRoles($user, $input, $teacher);

        return $user;
    }

    /** @param array<string, mixed> $input */
    private function syncPortalRoles(?User $user, array $input, Teacher $teacher): void
    {
        if (! $user) {
            return;
        }
        $managedRoles = Role::whereIn('slug', ['teacher', 'homeroom_teacher', 'subject_teacher'])->get();
        $desiredSlugs = ['teacher'];
        if ($teacher->isActive() && ! empty($input['is_homeroom_teacher']) && ! empty($input['homeroom_class_ids'])) {
            $desiredSlugs[] = 'homeroom_teacher';
        }
        if (! empty($input['subjects'])) {
            $desiredSlugs[] = 'subject_teacher';
        }
        $preservedRoleIds = $user->roles()->whereNotIn('roles.id', $managedRoles->pluck('id'))->pluck('roles.id');
        $desiredRoleIds = $managedRoles->whereIn('slug', $desiredSlugs)->pluck('id');
        $user->roles()->sync($preservedRoleIds->merge($desiredRoleIds)->unique()->values());
    }

    private function storeAvatar(mixed $avatar): ?string
    {
        return $avatar instanceof UploadedFile ? $avatar->store('avatars/teachers', 'public') : null;
    }
}
