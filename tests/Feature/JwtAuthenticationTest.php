<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwtAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_login_issues_a_jwt_that_authenticates_bearer_requests(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->json('token');

        $this->assertCount(3, explode('.', $token));
        $this->withToken($token)->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_logout_invalidates_the_previously_issued_jwt(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->json('token');

        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_valid_jwt_cookie_does_not_loop_between_login_and_dashboard(): void
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $permission = Permission::firstOrCreate(
            ['slug' => 'student.dashboard'],
            ['name' => 'Student dashboard']
        );
        $role->permissions()->syncWithoutDetaching($permission);
        $user->roles()->attach($role);
        Student::create([
            'user_id' => $user->id,
            'full_name' => 'JWT Student',
            'status' => 'studying',
        ]);

        $jwt = app(JwtService::class);
        $token = $jwt->issue($user);

        $this->withUnencryptedCookie($jwt->cookieName(), $token)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));

        $this->withUnencryptedCookie($jwt->cookieName(), $token)
            ->get(route('dashboard'))
            ->assertRedirect(route('student.dashboard'));

        $this->withUnencryptedCookie($jwt->cookieName(), $token)
            ->get(route('student.dashboard'))
            ->assertOk();
    }
}
