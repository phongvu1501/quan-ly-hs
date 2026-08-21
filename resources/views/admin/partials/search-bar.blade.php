@php
    $searchPlaceholder = $searchPlaceholder ?? 'Tìm kiếm dữ liệu';
    $searchAction = $searchAction ?? request()->url();
    $searchFilters = request()->except(['q', 'page']);
    $searchClearUrl = $searchFilters === [] ? $searchAction : $searchAction . '?' . http_build_query($searchFilters);
@endphp

<div class="admin-search-wrap">
    <form method="GET" action="{{ $searchAction }}" class="admin-search">
        @foreach ($searchFilters as $filterName => $filterValue)
            @if (is_scalar($filterValue))
                <input type="hidden" name="{{ $filterName }}" value="{{ $filterValue }}">
            @endif
        @endforeach
        <label class="visually-hidden" for="admin-search-input">Tìm kiếm</label>
        <i data-lucide="search" aria-hidden="true"></i>
        <input id="admin-search-input" type="search" name="q" value="{{ request('q') }}"
            placeholder="{{ $searchPlaceholder }}" autocomplete="off">
        @if (request()->filled('q'))
            <a class="admin-search-clear" href="{{ $searchClearUrl }}" aria-label="Xóa tìm kiếm" title="Xóa tìm kiếm"><i
                    data-lucide="x"></i></a>
        @endif
        <button type="submit" class="btn btn-sm btn-primary"><i data-lucide="search"></i><span>Tìm kiếm</span></button>
    </form>
</div>
