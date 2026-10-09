@if(auth()->check() && auth()->user()->is_demo)
    <div class="bg-amber-500 text-black px-4 py-1.5 flex items-center justify-between text-xs font-semibold tracking-wide shadow-sm z-50">
        <div class="flex items-center space-x-2">
            <span class="bg-black text-amber-400 text-[10px] uppercase font-black px-1.5 py-0.5 rounded tracking-wider">Demo Mode</span>
            <span>Demo Mode (Read-Only) — Viewing isolated sample environment.</span>
        </div>
        <span class="text-[11px] opacity-90 hidden sm:inline">Protected Showcase Data</span>
    </div>
@endif
