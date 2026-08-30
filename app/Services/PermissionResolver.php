<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

final class PermissionResolver
{
    /** @var list<string> */
    public const CRUD_ACTIONS = ['view', 'create', 'edit', 'delete'];

    /**
     * Return the permission rows that can grant the requested action.
     *
     * `resource.manage` is an explicit full-CRUD override. No other
     * permission implies another action, and malformed/empty permissions
     * never grant access.
     *
     * @return list<string>
     */
    public static function candidates(string $permission): array
    {
        $permission = trim($permission);
        if ($permission === '') {
            return [];
        }

        $parts = explode('.', $permission, 2);
        $resource = $parts[0] ?? '';
        $action = $parts[1] ?? null;
        if ($resource === '' || $action === null || $action === '') {
            return [$permission];
        }

        return in_array($action, self::CRUD_ACTIONS, true)
            ? [$permission, "{$resource}.manage"]
            : [$permission];
    }

    public function userHas(User $user, string $permission): bool
    {
        $candidates = self::candidates($permission);
        if ($candidates === []) {
            return false;
        }

        return $user->roles()
            ->whereHas('permissions', fn ($query) => $query->whereIn('permissions.slug', $candidates))
            ->exists();
    }

    public function roleHas(Role $role, string $permission): bool
    {
        $candidates = self::candidates($permission);
        if ($candidates === []) {
            return false;
        }

        return $role->permissions()
            ->whereIn('permissions.slug', $candidates)
            ->exists();
    }

    /** @return Collection<int, string> */
    public function userSlugs(User $user): Collection
    {
        return $user->roles()
            ->with('permissions:id,slug')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('slug'))
            ->filter(fn (mixed $slug) => is_string($slug) && trim($slug) !== '')
            ->map(fn (string $slug) => trim($slug))
            ->unique()
            ->values();
    }

    /** @param Collection<int, string> $grantedSlugs */
    public function slugsGrant(Collection $grantedSlugs, string $permission): bool
    {
        $candidates = self::candidates($permission);
        if ($candidates === []) {
            return false;
        }

        return $grantedSlugs->intersect($candidates)->isNotEmpty();
    }
}
