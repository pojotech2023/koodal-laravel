@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col sm:flex-row items-center justify-between gap-4 py-3">
        {{-- Results count text --}}
        <div>
            <p class="text-sm text-ink-500">
                {!! __('Showing') !!}
                @if ($paginator->firstItem())
                    <span class="font-medium text-ink-800">{{ $paginator->firstItem() }}</span>
                    {!! __('to') !!}
                    <span class="font-medium text-ink-800">{{ $paginator->lastItem() }}</span>
                @else
                    {{ $paginator->count() }}
                @endif
                {!! __('of') !!}
                <span class="font-medium text-ink-800">{{ $paginator->total() }}</span>
                {!! __('results') !!}
            </p>
        </div>

        {{-- Pagination links --}}
        <div>
            <ul class="inline-flex items-center gap-1 flex-wrap justify-center list-none m-0 p-0" style="display:inline-flex;align-items:center;list-style:none;margin:0;padding:0;">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li aria-disabled="true" aria-label="{{ __('pagination.previous') }}" style="display:inline-flex;">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl border border-gray-200 text-gray-300 cursor-not-allowed text-sm" style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;">
                            <svg class="w-4 h-4" width="16" height="16" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true" style="width:1rem;height:1rem;display:inline-block;">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </li>
                @else
                    <li style="display:inline-flex;">
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}" class="inline-flex items-center justify-center w-8 h-8 rounded-xl border border-primary-100 bg-white text-ink-700 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 text-sm transition-colors" style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;">
                            <svg class="w-4 h-4" width="16" height="16" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true" style="width:1rem;height:1rem;display:inline-block;">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li aria-disabled="true" style="display:inline-flex;">
                            <span class="inline-flex items-center justify-center w-8 h-8 text-gray-500 text-sm" style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;">{{ $element }}</span>
                        </li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li aria-current="page" style="display:inline-flex;">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-primary-500 text-white font-medium text-sm shadow-sm" style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;background-image:linear-gradient(135deg,#E0245E,#96164C);color:#fff;">{{ $page }}</span>
                                </li>
                            @else
                                <li style="display:inline-flex;">
                                    <a href="{{ $url }}" class="inline-flex items-center justify-center w-8 h-8 rounded-xl border border-primary-100 bg-white text-ink-700 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 text-sm transition-colors" style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;">
                                        {{ $page }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li style="display:inline-flex;">
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}" class="inline-flex items-center justify-center w-8 h-8 rounded-xl border border-primary-100 bg-white text-ink-700 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 text-sm transition-colors" style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;">
                            <svg class="w-4 h-4" width="16" height="16" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true" style="width:1rem;height:1rem;display:inline-block;">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </li>
                @else
                    <li aria-disabled="true" aria-label="{{ __('pagination.next') }}" style="display:inline-flex;">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl border border-gray-200 text-gray-300 cursor-not-allowed text-sm" style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;">
                            <svg class="w-4 h-4" width="16" height="16" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true" style="width:1rem;height:1rem;display:inline-block;">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </li>
                @endif
            </ul>
        </div>
    </nav>
@endif
