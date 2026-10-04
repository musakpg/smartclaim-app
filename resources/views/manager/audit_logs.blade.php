<!DOCTYPE html>
<html lang="en"
    x-data="{ isMobileSidebarOpen: false, isAuditingOpen: false, isAdminOpen: true, activeSubTab: 'audit_logs', payloadModalOpen: false, modalData: null, rawJsonOpen: false }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - System Audit Logs</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex flex-col lg:flex-row min-h-screen">

        @include('layouts.partials.manager-sidebar')

        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full overflow-hidden">
            <div class="space-y-6">
                <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div>
                        <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">System Audit Trails</h1>
                    <p class="text-xs md:text-sm text-slate-500">Immutable forensic security trace streams safeguarding
                        corporate accounting structures.</p>
                    </div>
                    <div class="hidden lg:flex items-center gap-3">
                        <x-system-clock />
                    </div>
                </div>

                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                    <div class="flex flex-col md:flex-row items-center justify-between border-b border-slate-100 pb-4 gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs md:text-sm font-bold text-slate-800 truncate"><i
                                    class="fa-solid fa-shield-check text-blue-500 mr-1"></i> Security Event Stream</h3>
                            <span
                                class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-100 font-bold text-[10px] rounded-lg flex items-center gap-1 shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span> Logging Engine Active
                            </span>
                        </div>
                        
                        <form method="GET" action="{{ route('manager.audit_logs') }}" class="flex items-center gap-2 w-full md:w-auto">
                            <input type="hidden" name="category" value="{{ request('category') }}">
                            <div class="relative w-full md:w-64">
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search user or action..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 focus:border-blue-500 rounded-lg outline-none text-xs text-slate-700 font-medium">
                            </div>
                            <button type="submit" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition">Search</button>
                            @if(request('search') || request('category'))
                                <a href="{{ route('manager.audit_logs') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-bold transition text-center whitespace-nowrap">Clear Filters</a>
                            @endif
                        </form>
                    </div>

                    <!-- Category Filter Tabs -->
                    <div class="flex overflow-x-auto hide-scrollbar gap-2 pb-2">
                        <a href="{{ route('manager.audit_logs', ['search' => request('search')]) }}" class="px-4 py-2 rounded-lg text-xs font-bold whitespace-nowrap transition-colors {{ !request('category') ? 'bg-slate-800 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">All Logs</a>
                        <a href="{{ route('manager.audit_logs', ['category' => 'claims', 'search' => request('search')]) }}" class="px-4 py-2 rounded-lg text-xs font-bold whitespace-nowrap transition-colors {{ request('category') === 'claims' ? 'bg-slate-800 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">Claims & Payouts</a>
                        <a href="{{ route('manager.audit_logs', ['category' => 'system', 'search' => request('search')]) }}" class="px-4 py-2 rounded-lg text-xs font-bold whitespace-nowrap transition-colors {{ request('category') === 'system' ? 'bg-slate-800 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">System Configurations</a>
                        <a href="{{ route('manager.audit_logs', ['category' => 'security', 'search' => request('search')]) }}" class="px-4 py-2 rounded-lg text-xs font-bold whitespace-nowrap transition-colors {{ request('category') === 'security' ? 'bg-slate-800 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">Security & Auth</a>
                    </div>

                    <div class="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
                        <table class="w-full text-left border-collapse text-xs min-w-[750px] sm:min-w-full">
                            <thead>
                                <tr
                                    class="border-b border-slate-100 text-slate-400 font-bold tracking-wide uppercase bg-slate-50/50 rounded-xl">
                                    <th class="py-3 px-4">Timestamp Log</th>
                                    <th class="py-3 px-4">Operator Node</th>
                                    <th class="py-3 px-4">Action Event</th>
                                    <th class="py-3 px-4 font-mono w-1/3">Payload Metadata</th>
                                    <th class="py-3 px-4 text-center">IP Address</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-slate-700 font-medium font-mono">
                                @forelse($auditLogs as $log)
                                @php
                                    $actionLower = strtolower($log->action);
                                    if(str_contains($actionLower, 'created')) {
                                        $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                        $icon = 'fa-plus';
                                    } elseif(str_contains($actionLower, 'deleted') || str_contains($actionLower, 'reject') || str_contains($actionLower, 'fraud')) {
                                        $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                                        $icon = 'fa-trash';
                                    } elseif(str_contains($actionLower, 'updated') || str_contains($actionLower, 'edit')) {
                                        $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                                        $icon = 'fa-sliders';
                                    } else {
                                        $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                                        $icon = 'fa-bolt';
                                    }
                                    
                                    // Make model name readable
                                    $modelName = $log->model_type ? class_basename($log->model_type) : ($log->claim_id ? 'Claim' : 'System');
                                @endphp
                                <tr class="hover:bg-slate-50/60 transition-all text-[11px]" x-data="{ payloadOpen: false }">
                                    <td class="py-3.5 px-4 text-slate-500 font-sans font-semibold whitespace-nowrap align-top">
                                        <div class="flex flex-col">
                                            <span class="text-slate-800">{{ $log->created_at->format('Y-m-d') }}</span>
                                            <span class="text-[10px]">{{ $log->created_at->format('H:i:s') }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 font-sans font-bold text-slate-900 whitespace-nowrap align-top">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center shrink-0 text-[10px] text-slate-600">
                                                <i class="fa-solid fa-user"></i>
                                            </div>
                                            <span>{{ $log->user ? $log->user->name . ' (' . (empty($log->user->role) || strtolower($log->user->role) === 'staff' ? 'Claimant' : ucfirst($log->user->role)) . ')' : 'System Automated' }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 font-bold whitespace-nowrap align-top">
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border {{ $badgeClass }}">
                                            <i class="fa-solid {{ $icon }}"></i>
                                            <span class="uppercase tracking-wider text-[9px]">{{ $log->action }}</span>
                                        </div>
                                        <div class="mt-1 text-[10px] text-slate-500 font-sans ml-1">
                                            {{ $modelName }} #{{ $log->model_id ?? $log->claim_id ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-500 align-top">
                                        <div class="flex flex-col gap-2">
                                            <div class="font-sans text-xs">
                                                @if(str_contains($actionLower, 'created'))
                                                    Created new {{ $modelName }}.
                                                @elseif(str_contains($actionLower, 'updated'))
                                                    Modified {{ count($log->new_values ?? []) }} fields in {{ $modelName }}.
                                                @elseif(str_contains($actionLower, 'deleted'))
                                                    Removed {{ $modelName }}.
                                                @else
                                                    {{ $log->action }} action performed.
                                                @endif
                                            </div>
                                            @if($log->old_values || $log->new_values)
                                            @php
                                                $diff = [];
                                                $oVals = $log->old_values ?? [];
                                                $nVals = $log->new_values ?? [];
                                                if (is_string($oVals)) $oVals = json_decode($oVals, true) ?? [];
                                                if (is_string($nVals)) $nVals = json_decode($nVals, true) ?? [];
                                                $allKeys = array_unique(array_merge(array_keys($oVals), array_keys($nVals)));
                                                foreach($allKeys as $key) {
                                                    if (in_array($key, ['updated_at', 'created_at', 'deleted_at'])) continue;
                                                    $o = $oVals[$key] ?? null;
                                                    $n = $nVals[$key] ?? null;
                                                    if ($o !== $n) {
                                                        $diff[] = ['field' => $key, 'old' => $o, 'new' => $n];
                                                    }
                                                }
                                            @endphp
                                            <button type="button" 
                                                @click="modalData = {
                                                    action: '{{ addslashes($log->action) }}',
                                                    model: '{{ addslashes($modelName) }}',
                                                    id: '{{ $log->model_id ?? $log->claim_id ?? '-' }}',
                                                    diff: {{ json_encode($diff) }},
                                                    rawOld: {{ json_encode($log->old_values) }},
                                                    rawNew: {{ json_encode($log->new_values) }}
                                                }; payloadModalOpen = true; rawJsonOpen = false;" 
                                                class="text-[10px] font-bold text-blue-600 hover:text-blue-800 self-start flex items-center gap-1 cursor-pointer">
                                                <i class="fa-solid fa-code-compare"></i> <span>View Changes</span>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center text-slate-400 font-sans whitespace-nowrap align-top">
                                        <div class="flex flex-col items-center">
                                            <span class="font-mono text-[10px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-600">{{ $log->ip_address ?: '127.0.0.1' }}</span>
                                            <span class="text-[9px] mt-1 truncate w-24" title="{{ $log->user_agent }}">{{ Str::limit($log->user_agent ?: 'CLI', 15) }}</span>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400 font-sans">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <i class="fa-solid fa-shield-halved text-4xl text-slate-200 mb-2"></i>
                                            <span class="text-sm text-slate-500 font-bold">No security events logged yet</span>
                                            <span class="text-xs text-slate-400">System actions will appear here in real-time.</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-100/50 bg-slate-50/50">
                        {{ $auditLogs->links() }}
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Audit Modification Details Modal -->
    <div x-show="payloadModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4" x-cloak>
        <div @click.away="payloadModalOpen = false" class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-800">
                    <i class="fa-solid fa-file-invoice mr-2 text-blue-500"></i>
                    Audit Modification Details - <span x-text="modalData?.action"></span> on <span x-text="modalData?.model"></span> #<span x-text="modalData?.id"></span>
                </h3>
                <button @click="payloadModalOpen = false" class="text-slate-400 hover:text-rose-500 transition-colors cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <template x-if="modalData?.diff && modalData.diff.length > 0">
                    <div class="border border-slate-200 rounded-lg overflow-hidden mb-6">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                                    <th class="py-2 px-4 font-bold w-1/3">Field Name</th>
                                    <th class="py-2 px-4 font-bold w-1/3">Previous Value</th>
                                    <th class="py-2 px-4 font-bold w-1/3">New Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="change in modalData.diff" :key="change.field">
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="py-2.5 px-4 font-mono font-medium text-slate-700" x-text="change.field"></td>
                                        <td class="py-2.5 px-4 font-mono text-rose-600 bg-rose-50/30 line-through decoration-rose-300" x-text="typeof change.old === 'object' ? JSON.stringify(change.old) : (change.old ?? 'null')"></td>
                                        <td class="py-2.5 px-4 font-mono text-emerald-600 bg-emerald-50/30" x-text="typeof change.new === 'object' ? JSON.stringify(change.new) : (change.new ?? 'null')"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
                <template x-if="!modalData?.diff || modalData.diff.length === 0">
                    <div class="text-center py-8 text-slate-500 text-sm bg-slate-50 rounded-lg border border-slate-100 mb-6 font-bold">
                        No substantive field changes detected.
                    </div>
                </template>

                <!-- Technical Inspection -->
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <button @click="rawJsonOpen = !rawJsonOpen" class="w-full px-4 py-3 bg-slate-50 flex items-center justify-between text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
                        <span><i class="fa-solid fa-code mr-1.5 text-slate-400"></i> View Raw JSON Payload</span>
                        <i class="fa-solid" :class="rawJsonOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                    <div x-show="rawJsonOpen" x-collapse class="p-4 bg-slate-900 border-t border-slate-200">
                        <template x-if="modalData?.rawOld">
                            <div class="mb-4">
                                <div class="text-[10px] text-slate-400 mb-1 uppercase tracking-wider font-bold">Old Values:</div>
                                <pre class="text-[11px] text-rose-300 overflow-x-auto whitespace-pre-wrap" x-text="JSON.stringify(modalData.rawOld, null, 2)"></pre>
                            </div>
                        </template>
                        <template x-if="modalData?.rawNew">
                            <div>
                                <div class="text-[10px] text-slate-400 mb-1 uppercase tracking-wider font-bold">New Values:</div>
                                <pre class="text-[11px] text-emerald-300 overflow-x-auto whitespace-pre-wrap" x-text="JSON.stringify(modalData.rawNew, null, 2)"></pre>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end">
                <button @click="payloadModalOpen = false" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-lg transition-colors cursor-pointer">Close</button>
            </div>
        </div>
    </div>
</body>

</html>
