@props(['paginator'])
@if($paginator->hasPages())<nav {{ $attributes }} aria-label="Pagination">{{ $paginator->onEachSide(1)->links() }}</nav>@endif
