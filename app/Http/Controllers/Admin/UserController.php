<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $roleId = $request->filled('role_id') ? (int) $request->input('role_id') : null;
        $users = User::with(['roles', 'teacherProfile', 'studentProfile'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('roles', fn ($role) => $role->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"));
            }))
            ->when($roleId, fn ($query) => $query->whereHas('roles', fn ($role) => $role->whereKey($roleId)))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        $teachers = Teacher::whereNull('user_id')->orderBy('full_name')->get();
        $students = Student::whereNull('user_id')->orderBy('full_name')->get();

        return view('admin.users.create', compact('roles', 'teachers', 'students'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'teacher_id' => ['nullable', Rule::exists('teachers', 'id')->where(fn ($query) => $query->whereNull('user_id'))],
            'student_id' => ['nullable', Rule::exists('students', 'id')->where(fn ($query) => $query->whereNull('user_id'))],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'avatar_path' => $request->file('avatar')?->store('avatars/users', 'public'),
            ]);
            $user->roles()->sync($validated['roles'] ?? []);
            $this->syncProfiles($user, $validated['teacher_id'] ?? null, $validated['student_id'] ?? null);
        });

        return to_route('admin.users.index')->with('success', 'Đã tạo tài khoản.');
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $user->load('roles', 'teacherProfile', 'studentProfile');
        $teachers = Teacher::where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))->orderBy('full_name')->get();
        $students = Student::where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))->orderBy('full_name')->get();

        return view('admin.users.edit', compact('user', 'roles', 'teachers', 'students'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'change_password' => ['nullable', 'boolean'],
            'password' => ['nullable', 'required_if:change_password,1', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'teacher_id' => ['nullable', Rule::exists('teachers', 'id')->where(fn ($query) => $query->where(fn ($inner) => $inner->whereNull('user_id')->orWhere('user_id', $user->id)))],
            'student_id' => ['nullable', Rule::exists('students', 'id')->where(fn ($query) => $query->where(fn ($inner) => $inner->whereNull('user_id')->orWhere('user_id', $user->id)))],
        ]);

        DB::transaction(function () use ($request, $validated, $user): void {
            $passwordChanged = ! empty($validated['password']);
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                ...($request->hasFile('avatar') ? ['avatar_path' => $this->replaceAvatar($request, $user->avatar_path)] : []),
                ...(!empty($validated['password']) ? ['password' => Hash::make($validated['password']), 'must_change_password' => false] : []),
            ]);
            if ($passwordChanged) {
                $user->increment('jwt_version');
            }
            $user->roles()->sync($validated['roles'] ?? []);
            $this->syncProfiles($user, $validated['teacher_id'] ?? null, $validated['student_id'] ?? null);
        });

        return to_route('admin.users.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    public function destroy(User $user)
    {
        abort_if($user->is(auth()->user()), 422, 'Không thể xoá tài khoản đang đăng nhập.');

        if ($user->avatar_path) Storage::disk('public')->delete($user->avatar_path);
        $user->roles()->detach();
        $user->delete();

        return back()->with('success', 'Đã xoá tài khoản.');
    }

    private function replaceAvatar(Request $request, ?string $oldPath): string
    {
        if ($oldPath) Storage::disk('public')->delete($oldPath);
        return $request->file('avatar')->store('avatars/users', 'public');
    }

    private function syncProfiles(User $user, ?int $teacherId, ?int $studentId): void
    {
        Teacher::where('user_id', $user->id)
            ->when($teacherId, fn ($query) => $query->whereKeyNot($teacherId))
            ->update(['user_id' => null]);
        Student::where('user_id', $user->id)
            ->when($studentId, fn ($query) => $query->whereKeyNot($studentId))
            ->update(['user_id' => null]);

        if ($teacherId) Teacher::whereKey($teacherId)->update(['user_id' => $user->id]);
        if ($studentId) Student::whereKey($studentId)->update(['user_id' => $user->id]);
    }
}
