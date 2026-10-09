@auth
    @if(auth()->user()->is_demo)
        <!-- Demo Mode In-Page Banner -->
        <div class="bg-gradient-to-r from-amber-500 via-amber-600 to-amber-500 text-slate-950 font-medium px-4 py-2.5 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-sm rounded-2xl mb-4 border border-amber-600">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-slate-950 text-amber-300">DEMO MODE</span>
                <span class="font-bold">Demo Mode Active (Read-Only) &mdash; Viewing isolated showcase metrics & data.</span>
            </div>
            <div class="hidden sm:flex items-center gap-2 text-[11px] font-semibold text-slate-900">
                <i class="fa-solid fa-shield-halved text-slate-950"></i> Protected Sandbox Environment
            </div>
        </div>
    @endif
@endauth
