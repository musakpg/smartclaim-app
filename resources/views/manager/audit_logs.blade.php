<!DOCTYPE html>
<html lang="en"
    x-data="{ isMobileSidebarOpen: false, isAuditingOpen: false, isAdminOpen: true, activeSubTab: 'audit_logs' }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - System Audit Logs</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex min-h-screen">

        @include('layouts.partials.manager-sidebar')

        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full overflow-hidden">
            <div class="space-y-6">
                <div class="border-b border-slate-200 pb-5">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">System Audit Trails</h1>
                    <p class="text-xs md:text-sm text-slate-500">Immutable forensic security trace streams safeguarding
                        corporate accounting structures.</p>
                </div>

                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 gap-2">
                        <h3 class="text-xs md:text-sm font-bold text-slate-800 truncate"><i
                                class="fa-solid fa-shield-check text-blue-500 mr-1"></i> Security Event Stream</h3>
                        <span
                            class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-100 font-bold text-[10px] rounded-lg flex items-center gap-1 shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span> Logging Engine
                            Active
                        </span>
                    </div>

                    <div class="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
                        <table class="w-full text-left border-collapse text-xs min-w-[750px] sm:min-w-full">
                            <thead>
                                <tr
                                    class="border-b border-slate-100 text-slate-400 font-bold tracking-wide uppercase bg-slate-50/50 rounded-xl">
                                    <th class="py-3 px-4">Timestamp Log</th>
                                    <th class="py-3 px-4">Operator Node</th>
                                    <th class="py-3 px-4">Action Segment Event</th>
                                    <th class="py-3 px-4 font-mono">Payload Metadata Scope</th>
                                    <th class="py-3 px-4 text-center">IP Address</th>
                                </tr>
                                </tbody>
                            <tbody class="divide-y divide-slate-50 text-slate-700 font-medium font-mono">
                                @forelse($auditLogs as $log)
                                <tr class="hover:bg-slate-50/60 transition-all text-[11px]">
                                    <td class="py-3.5 px-4 text-slate-500 font-sans font-semibold whitespace-nowrap">
                                        {{ $log->created_at->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="py-3.5 px-4 font-sans font-bold text-slate-900 whitespace-nowrap">
                                        {{ $log->user ? $log->user->name : 'System' }}
                                    </td>
                                    <td class="py-3.5 px-4 font-bold whitespace-nowrap
                                        @if(str_contains($log->action, 'APPROVE')) text-emerald-600
                                        @elseif(str_contains($log->action, 'SUBMIT')) text-blue-600
                                        @elseif(str_contains($log->action, 'UPDATE') || str_contains($log->action, 'EDIT')) text-amber-600
                                        @elseif(str_contains($log->action, 'REJECT')) text-red-600
                                        @else text-slate-600 @endif">
                                        @if(str_contains($log->action, 'APPROVE'))
                                            <i class="fa-solid fa-circle-check text-[9px] mr-1"></i>
                                        @elseif(str_contains($log->action, 'SUBMIT'))
                                            <i class="fa-solid fa-cloud-arrow-up text-[9px] mr-1"></i>
                                        @elseif(str_contains($log->action, 'UPDATE') || str_contains($log->action, 'EDIT'))
                                            <i class="fa-solid fa-sliders text-[9px] mr-1"></i>
                                        @elseif(str_contains($log->action, 'REJECT'))
                                            <i class="fa-solid fa-circle-xmark text-[9px] mr-1"></i>
                                        @else
                                            <i class="fa-solid fa-bolt text-[9px] mr-1"></i>
                                        @endif
                                        {{ $log->action }}
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-500 truncate max-w-[200px]"
                                        title='{{ json_encode(["claim_id" => $log->claim_id, "new" => $log->new_values, "old" => $log->old_values]) }}'>
                                        {{ json_encode(["claim_id" => $log->claim_id, "new" => $log->new_values, "old" => $log->old_values]) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center text-slate-400 font-sans whitespace-nowrap">
                                        {{ $log->ip_address }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400 font-sans">No security events logged yet.</td>
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
</body>

</html>