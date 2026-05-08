@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-center">
        <ul class="inline-flex items-center space-x-2">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li aria-disabled="true" aria-label="Previous">
                    <span class="inline-flex items-center justify-center rounded-md px-3 py-2 text-sm text-ink-400 bg-[var(--color-superficie-suave)]">&laquo;</span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center rounded-md px-3 py-2 text-sm text-ink-700 hover:bg-[var(--color-fondo-claro)]">&laquo;</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li aria-disabled="true"><span class="inline-flex items-center justify-center rounded-md px-3 py-2 text-sm text-ink-500">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li aria-current="page">
                                <span class="ss-pagination-active">{{ $page }}</span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $url }}" class="inline-flex items-center justify-center rounded-md px-3 py-2 text-sm text-ink-700 hover:bg-[var(--color-fondo-claro)]">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center rounded-md px-3 py-2 text-sm text-ink-700 hover:bg-[var(--color-fondo-claro)]">&raquo;</a>
                </li>
            @else
                <li aria-disabled="true" aria-label="Next">
                    <span class="inline-flex items-center justify-center rounded-md px-3 py-2 text-sm text-ink-400 bg-[var(--color-superficie-suave)]">&raquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
