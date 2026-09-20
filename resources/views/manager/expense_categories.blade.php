<!DOCTYPE html>
<html lang="en"
    x-data="{ isMobileSidebarOpen: false, isAuditingOpen: false, isAdminOpen: true, activeSubTab: 'expense_categories' }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Expense Categories Administration</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex min-h-screen">

        @include('layouts.partials.manager-sidebar')

        <main class="flex-1 p-4 md:p-8 max-w-4xl mx-auto w-full pb-24 lg:pb-8 overflow-hidden">
            <div class="space-y-6">
                <div class="border-b border-slate-200 pb-5">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Expense Categories</h1>
                    <p class="text-xs md:text-sm text-slate-500">Manage structure classification arrays mapped natively
                        to AI OCR parsing nodes.</p>
                </div>

                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-xs md:text-sm font-bold text-slate-800"><i
                                class="fa-solid fa-layer-group text-blue-500 mr-1"></i> Active System Categories</h3>
                        <span
                            class="px-2.5 py-1 bg-slate-100 text-slate-700 font-bold text-[10px] md:text-[11px] rounded-lg">5
                            Categories Active</span>
                    </div>

                    <div class="divide-y divide-slate-50 text-xs font-semibold text-slate-700">
                        <div class="py-3.5 flex items-center justify-between gap-4">
                            <span class="flex items-center gap-2 truncate"><i
                                    class="fa-solid fa-bowl-food text-slate-400 w-4"></i> Meals & Entertainment</span>
                            <span
                                class="px-2.5 py-0.5 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-md font-bold text-[9px] whitespace-nowrap shrink-0">AI
                                MODEL ACTIVE</span>
                        </div>
                        <div class="py-3.5 flex items-center justify-between gap-4">
                            <span class="flex items-center gap-2 truncate"><i
                                    class="fa-solid fa-plane text-slate-400 w-4"></i> Travel</span>
                            <span
                                class="px-2.5 py-0.5 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-md font-bold text-[9px] whitespace-nowrap shrink-0">AI
                                MODEL ACTIVE</span>
                        </div>
                        <div class="py-3.5 flex items-center justify-between gap-4">
                            <span class="flex items-center gap-2 truncate"><i
                                    class="fa-solid fa-bus text-slate-400 w-4"></i> Transportation</span>
                            <span
                                class="px-2.5 py-0.5 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-md font-bold text-[9px] whitespace-nowrap shrink-0">AI
                                MODEL ACTIVE</span>
                        </div>
                        <div class="py-3.5 flex items-center justify-between gap-4">
                            <span class="flex items-center gap-2 truncate"><i
                                    class="fa-solid fa-paperclip text-slate-400 w-4"></i> Office Supplies</span>
                            <span
                                class="px-2.5 py-0.5 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-md font-bold text-[9px] whitespace-nowrap shrink-0">AI
                                MODEL ACTIVE</span>
                        </div>
                        <div class="py-3.5 flex items-center justify-between gap-4">
                            <span class="flex items-center gap-2 truncate"><i
                                    class="fa-solid fa-gas-pump text-slate-400 w-4"></i> Fuel / Automotive</span>
                            <span
                                class="px-2.5 py-0.5 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-md font-bold text-[9px] whitespace-nowrap shrink-0">AI
                                MODEL ACTIVE</span>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-[#0f172a] text-slate-300 p-4 md:p-5 rounded-3xl border border-slate-800 space-y-2 shadow-xs">
                    <h4
                        class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-1.5 text-emerald-400">
                        <i class="fa-solid fa-brain-circuit"></i> AI NLP Categorization Rules Baseline
                    </h4>
                    <p class="text-xs leading-relaxed opacity-90">Tokenized keywords parsed from Google Cloud Vision OCR
                        pass through TF-IDF arrays. Incoming items match these 5 corporate blueprints dynamically to
                        keep company accounting workflows uncorrupted.</p>
                </div>
            </div>
        </main>
    </div>
</body>

</html>