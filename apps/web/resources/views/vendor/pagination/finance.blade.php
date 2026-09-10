@if ($paginator->hasPages())
    <nav class="finance-pagination" role="navigation" aria-label="Navigasi halaman transaksi">
        <p>Menampilkan <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong> dari <strong>{{ $paginator->total() }}</strong> transaksi</p>
        <div class="pagination-pages">
            @if ($paginator->onFirstPage())<span class="pagination-nav disabled" aria-disabled="true" aria-label="Halaman sebelumnya">‹</span>@else<a class="pagination-nav" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">‹</a>@endif
            @foreach ($elements as $element)
                @if (is_string($element))<span class="pagination-ellipsis" aria-hidden="true">{{ $element }}</span>@endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())<span class="pagination-page active" aria-current="page">{{ $page }}</span>@else<a class="pagination-page" href="{{ $url }}" aria-label="Buka halaman {{ $page }}">{{ $page }}</a>@endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())<a class="pagination-nav" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">›</a>@else<span class="pagination-nav disabled" aria-disabled="true" aria-label="Halaman berikutnya">›</span>@endif
        </div>
    </nav>
@endif
