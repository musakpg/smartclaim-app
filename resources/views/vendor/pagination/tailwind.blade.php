@if ($paginator->hasPages() || $paginator->total() > 0)
    <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-bold w-full">
        <div class="text-slate-400 font-medium leading-relaxed mb-1 sm:mb-0">
            @if ($paginator->total() > 0)
                Showing <span class="text-slate-700 font-bold">{{ $paginator->firstItem() }}</span>
                to <span class="text-slate-700 font-bold">{{ $paginator->lastItem() }}</span>
                of <span class="text-slate-700 font-bold">{{ $paginator->total() }}</span> records
            @else
                Showing <span class="text-slate-700 font-bold">0</span> records
            @endif
        </div>

        @if ($paginator->hasPages())
            <div class="flex items-center gap-1.5">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span class="px-3 py-2 border rounded-xl flex items-center gap-1.5 opacity-50 cursor-not-allowed border-slate-200 text-slate-400">
                        <i class="fa-solid fa-chevron-left text-[10px]"></i> Previous
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                        class="px-3 py-2 border rounded-xl transition-all flex items-center gap-1.5 cursor-pointer border-slate-200 hover:border-slate-400 text-slate-700 bg-white shadow-2xs">
                        <i class="fa-solid fa-chevron-left text-[10px]"></i> Previous
                    </a>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="w-8 h-8 flex items-center justify-center text-xs font-bold text-slate-400">{{ $element }}</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                    class="w-8 h-8 border rounded-xl transition-all text-xs font-bold bg-[#0f172a] text-white border-[#0f172a] flex items-center justify-center shadow-xs">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}"
                                    class="w-8 h-8 border rounded-xl transition-all text-xs font-bold bg-white text-slate-600 border-slate-200 hover:border-slate-400 flex items-center justify-center shadow-2xs">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                        class="px-3 py-2 border rounded-xl transition-all flex items-center gap-1.5 cursor-pointer border-slate-200 hover:border-slate-400 text-slate-700 bg-white shadow-2xs">
                        Next <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                @else
                    <span class="px-3 py-2 border rounded-xl flex items-center gap-1.5 opacity-50 cursor-not-allowed border-slate-200 text-slate-400">
                        Next <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </span>
                @endif
            </div>
        @endif
    </nav>
@endif
