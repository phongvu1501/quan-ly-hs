@props(['paginator'])

@if ($paginator->total() > 0)
    <div class="pagination-shell">
        <span class="pagination-summary">
            Hiển thị {{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }}
            trong {{ number_format($paginator->total()) }} kết quả
        </span>
        @if ($paginator->hasPages())
            {{ $paginator->links('pagination::bootstrap-5') }}
        @endif
    </div>
@endif
