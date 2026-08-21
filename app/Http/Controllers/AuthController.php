<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('login')
            ->with('success', 'Đăng ký thành công! Hãy đăng nhập.');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if (Auth::user()->must_change_password) {
                return redirect()->route('profile.show')->with('warning', 'Bạn phải đổi mật khẩu mặc định trước khi sử dụng hệ thống.');
            }

            return redirect()->to($this->dashboardUrl(Auth::user()));
        }

        return back()
            ->withErrors([
                'email' => 'Email hoặc mật khẩu không chính xác.',
            ])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function dashboardUrl(?User $user = null): string
    {
        $user ??= auth()->user();

        if ($user->hasRole('admin') || $user->hasPermission('admin.access')) {
            return route('admin.school.dashboard');
        }

        if ($user->hasRole('homeroom_teacher') && $user->teacherProfile) {
            return route('teacher.homeroom.dashboard');
        }

        if ($user->hasRole('subject_teacher') && $user->teacherProfile) {
            return route('teacher.subject.dashboard');
        }

        if ($user->hasRole('teacher') && $user->teacherProfile) {
            return route('teacher.subject.dashboard');
        }

        if ($user->hasRole('student') && $user->studentProfile) {
            return route('student.dashboard');
        }

        if ($user->hasRole('homeroom_teacher') || $user->hasRole('subject_teacher') || $user->hasRole('teacher') || $user->hasRole('student')) {
            return route('profile.show');
        }

        return route('dashboard');
    }
}
