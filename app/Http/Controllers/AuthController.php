<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JwtService;
use App\Services\PortalAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private readonly JwtService $jwt) {}

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

        $user = User::where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            $remember = $request->boolean('remember');
            $token = $this->jwt->issue($user, $remember);
            Auth::guard('web')->setUser($user);
            $request->session()->regenerate();

            if ($user->must_change_password) {
                $response = redirect()->route('profile.show')->with('warning', 'Bạn phải đổi mật khẩu mặc định trước khi sử dụng hệ thống.');
            } else {
                $response = redirect()->to($this->dashboardUrl($user));
            }

            return $response->withCookie($this->jwtCookie($token, $remember));
        }

        return back()
            ->withErrors([
                'email' => 'Email hoặc mật khẩu không chính xác.',
            ])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $request->user()?->increment('jwt_version');
        Auth::guard('web')->forgetUser();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withCookie(cookie()->forget($this->jwt->cookieName()));
    }

    public function dashboardUrl(?User $user = null): string
    {
        $user ??= auth()->user();
        $canAccessBackOffice = app(PortalAccessService::class)->canAccessBackOffice($user);

        if ($canAccessBackOffice && $user->hasPermission('school.dashboard')) {
            return route('admin.school.dashboard');
        }

        if ($canAccessBackOffice && $user->hasPermission('finance.dashboard')) {
            return route('admin.finance.dashboard');
        }

        if ($user->teacherProfile && $user->hasPermission('homeroom.dashboard')) {
            return route('teacher.homeroom.dashboard');
        }

        if ($user->teacherProfile && $user->hasPermission('teacher.dashboard')) {
            return route('teacher.subject.dashboard');
        }

        if ($user->studentProfile && $user->hasPermission('student.dashboard')) {
            return route('student.dashboard');
        }

        if ($user->teacherProfile || $user->studentProfile) {
            return route('profile.show');
        }

        return route('dashboard');
    }

    private function jwtCookie(string $token, bool $remember)
    {
        return cookie(
            $this->jwt->cookieName(),
            $token,
            $this->jwt->ttl($remember),
            '/',
            config('session.domain'),
            (bool) config('session.secure'),
            true,
            false,
            config('session.same_site', 'lax')
        );
    }
}
