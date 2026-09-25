<!-- Live GMT+8 System Clock -->
<div 
    x-data="{
        timeStr: '',
        dateStr: '',
        updateClock() {
            const now = new Date();
            // Format time in MYT (Asia/Kuala_Lumpur)
            this.timeStr = now.toLocaleTimeString('en-GB', {
                timeZone: 'Asia/Kuala_Lumpur',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
            // Format date in MYT
            this.dateStr = now.toLocaleDateString('en-MY', {
                timeZone: 'Asia/Kuala_Lumpur',
                weekday: 'short',
                day: 'numeric',
                month: 'short'
            });
        }
    }"
    x-init="updateClock(); setInterval(() => updateClock(), 1000)"
    class="flex items-center gap-1.5 sm:gap-2 px-2 sm:px-3 py-1 sm:py-1.5 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-600 shadow-2xs select-none"
    title="Official System Time (MYT - GMT+8)"
>
    <div class="flex items-center gap-1.5 text-xs font-medium">
        <i class="fa-regular fa-clock text-emerald-500 animate-pulse text-xs"></i>
        <!-- Date display (hidden on very small tablets, shown on larger screens) -->
        <span class="hidden md:inline text-slate-500" x-text="dateStr"></span>
        <span class="hidden md:inline text-slate-300">•</span>
        <!-- Time with MYT timezone badge -->
        <span class="text-[11px] sm:text-xs font-mono font-semibold text-slate-800" x-text="timeStr"></span>
        <span class="text-[9px] sm:text-[10px] font-bold px-1 sm:px-1.5 py-0.5 rounded bg-slate-200/70 text-slate-600">MYT</span>
    </div>
</div>
