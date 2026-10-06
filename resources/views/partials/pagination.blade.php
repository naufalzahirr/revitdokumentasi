@if($paginator->hasPages())
<nav class="pagination" aria-label="Halaman pengguna"><span class="muted small">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} pengguna</span><div>
@if($paginator->onFirstPage())<span class="button secondary disabled">Sebelumnya</span>@else<a class="button secondary" href="{{ $paginator->previousPageUrl() }}">Sebelumnya</a>@endif
<span class="page-number">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
@if($paginator->hasMorePages())<a class="button secondary" href="{{ $paginator->nextPageUrl() }}">Berikutnya</a>@else<span class="button secondary disabled">Berikutnya</span>@endif
</div></nav>
@endif
