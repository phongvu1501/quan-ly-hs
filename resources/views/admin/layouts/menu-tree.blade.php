@foreach($menus as $menu)
    @php
        $children = $menu->visibleChildren ?? collect();
        $icon = $menu->icon && !str_contains($menu->icon, ' ') ? $menu->icon : ($children->isNotEmpty() ? 'folder' : 'circle');
        $active = $menu->route && \Illuminate\Support\Facades\Route::has($menu->route)
            ? request()->routeIs($menu->route)
            : request()->is('workspace/'.$menu->slug);
    @endphp
    @if ($children->isNotEmpty())
        <details class="nav-group" data-sidebar-key="menu-{{ $menu->id }}" @if ($active || $children->contains(fn ($child) => request()->is('workspace/'.$child->slug))) open @endif>
            <summary class="nav-group-toggle">
                <span class="nav-item-label"><i data-lucide="{{ $icon }}"></i><span>{{ $menu->name }}</span></span>
                <i class="nav-chevron" data-lucide="chevron-right"></i>
            </summary>
            <div class="nav-children">
                @include('admin.layouts.menu-tree', ['menus' => $children])
            </div>
        </details>
    @else
        <a href="{{ $menu->url() }}" @class(['active' => $active])>
            <i data-lucide="{{ $icon }}"></i>
            <span>{{ $menu->name }}</span>
        </a>
    @endif
@endforeach
