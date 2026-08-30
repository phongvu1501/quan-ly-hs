<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use App\Services\PermissionResolver;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar_path',
        'password',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'jwt_version',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'jwt_version' => 'integer',
        ];
    }

    /**
     * Các role của user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Kiểm tra user có role hay không.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->where('slug', $role)
            ->exists();
    }

    /**
     * Get the permission slugs that can satisfy a permission check.
     *
     * A resource manage permission covers the four standard CRUD actions,
     * but those actions never imply manage.
     *
     * @return list<string>
     */
    public static function permissionCandidates(string $permission): array
    {
        return PermissionResolver::candidates($permission);
    }

    /**
     * Check whether any assigned role grants the requested permission.
     */
    public function hasPermission(string $permission): bool
    {
        return app(PermissionResolver::class)->userHas($this, $permission);
    }

    /**
     * Get the unique permission slugs granted by every assigned role.
     */
    public function permissionSlugs(): Collection
    {
        return app(PermissionResolver::class)->userSlugs($this);
    }
}
