<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user()->load([
            'teacherProfile.subjects',
            'teacherProfile.homeroomClasses.grade',
            'studentProfile.enrollments.class.grade',
            'studentProfile.enrollments.academicYear',
        ]);

        return view('profile.show', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'change_password' => ['nullable', 'boolean'],
            'password' => ['nullable', 'required_if:change_password,1', 'string', 'min:8', 'confirmed'],
        ]);

        if ($user->must_change_password && blank($data['password'] ?? null)) {
            throw ValidationException::withMessages(['password' => 'Bạn phải đặt mật khẩu mới trước khi tiếp tục.']);
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            ...($request->hasFile('avatar') ? ['avatar_path' => $this->replaceAvatar($request, $user->avatar_path)] : []),
            ...(!empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
            ...(!empty($data['password']) ? ['must_change_password' => false] : []),
        ]);

        return back()->with('success', 'Đã cập nhật hồ sơ cá nhân.');
    }

    private function replaceAvatar(Request $request, ?string $oldPath): string
    {
        if ($oldPath) Storage::disk('public')->delete($oldPath);
        return $request->file('avatar')->store('avatars/users', 'public');
    }
}
