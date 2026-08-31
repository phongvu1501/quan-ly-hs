<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

final class PortalAccessService
{
    /** @var list<string> */
    public const PORTAL_ROLE_SLUGS = [
        'student',
        'teacher',
        'homeroom_teacher',
        'subject_teacher',
    ];

    /** @var list<string> */
    public const BACK_OFFICE_ENTRY_PERMISSIONS = [
        'admin.access',
        'school.dashboard',
        'finance.dashboard',
    ];

    public function isPortalAccount(User $user): bool
    {
        $roles = $this->roles($user);

        return $roles->contains(fn (Role $role) => in_array($role->slug, self::PORTAL_ROLE_SLUGS, true))
            || $user->studentProfile()->exists()
            || $user->teacherProfile()->exists();
    }

    public function canAccessBackOffice(User $user): bool
    {
        $roles = $this->roles($user);
        $isPortalAccount = $roles->contains(
            fn (Role $role) => in_array($role->slug, self::PORTAL_ROLE_SLUGS, true)
        ) || $user->studentProfile()->exists() || $user->teacherProfile()->exists();

        if (! $isPortalAccount) {
            return true;
        }

        return $roles
            ->reject(fn (Role $role) => in_array($role->slug, self::PORTAL_ROLE_SLUGS, true))
            ->contains(fn (Role $role) => $role->permissions->contains(
                fn ($permission) => in_array($permission->slug, self::BACK_OFFICE_ENTRY_PERMISSIONS, true)
            ));
    }

    /** @return Collection<int, Role> */
    private function roles(User $user): Collection
    {
        return $user->roles()->with('permissions:id,slug')->get();
    }
}
