<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class Menu extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'route',
        'icon',
        'required_permission',
        'sort_order',
        'is_active',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_menu');
    }

    public function records(): HasMany
    {
        return $this->hasMany(DynamicRecord::class);
    }

    public function isVisibleTo(Collection $roleIds, Collection $permissionSlugs): bool
    {
        $roleVisible = $this->roles->pluck('id')->intersect($roleIds)->isNotEmpty();
        $permissionVisible = $this->required_permission
            && $permissionSlugs->contains($this->required_permission);

        return $roleVisible || $permissionVisible;
    }

    public function url(): string
    {
        if (blank($this->route)) {
            return route('dynamic.menus.show', ['menu' => $this->slug]);
        }

        if (filter_var($this->route, FILTER_VALIDATE_URL)) {
            return $this->route;
        }

        if (Route::has($this->route)) {
            try {
                return route($this->route);
            } catch (\Throwable) {
                // A route with required parameters cannot be rendered from a
                // menu row alone; use the generic workspace instead.
            }
        }

        if (str_starts_with($this->route, '/')) {
            return url($this->route);
        }

        return route('dynamic.menus.show', ['menu' => $this->slug]);
    }
}
