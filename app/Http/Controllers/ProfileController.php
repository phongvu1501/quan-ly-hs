<?php

namespace App\Http\Controllers;

use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function __construct(private readonly JwtService $jwt) {}

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

        $passwordChanged = ! empty($data['password']);
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            ...($request->hasFile('avatar') ? ['avatar_path' => $this->replaceAvatar($request, $user->avatar_path)] : []),
            ...(!empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
            ...($passwordChanged ? ['must_change_password' => false] : []),
        ]);

        $response = back()->with('success', 'Đã cập nhật hồ sơ cá nhân.');
        if ($passwordChanged) {
            $user->increment('jwt_version');
            $response->withCookie(cookie(
                $this->jwt->cookieName(),
                $this->jwt->issue($user->refresh()),
                $this->jwt->ttl(),
                '/',
                config('session.domain'),
                (bool) config('session.secure'),
                true,
                false,
                config('session.same_site', 'lax')
            ));
        }

        return $response;
    }

    private function replaceAvatar(Request $request, ?string $oldPath): string
    {
        if ($oldPath) Storage::disk('public')->delete($oldPath);
        return $request->file('avatar')->store('avatars/users', 'public');
    }
}
