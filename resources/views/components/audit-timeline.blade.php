{{-- ============================================================
     INTERACTIVE VISUAL AUDIT TRAIL TIMELINE
     Event-Sourcing Lifecycle Component for SmartClaim
     Renders forensic timeline from audit_logs array on activeClaim
     Falls back to synthesizing milestones from claim timestamps
     if audit_logs is empty (for legacy claims).
     ============================================================ --}}

<div class="mt-4 border-t border-slate-100 pt-4" x-data="{
    get timelineNodes() {
        const claim = activeClaim;
        const logs = (claim.audit_logs && claim.audit_logs.length > 0) ? claim.audit_logs : [];

        // Action-to-label mapping for human-readable display
        const actionLabels = {
            'CLAIM_SUBMITTED':    { label: 'Claim Submitted',           icon: 'fa-paper-plane',       color: 'blue'  },
            'FRAUD_ANALYZED':     { label: 'AI Fraud Analysis',          icon: 'fa-robot',             color: 'purple'},
            'CLAIM_Pre-Approved': { label: 'Finance Verified',           icon: 'fa-check-circle',      color: 'cyan'  },
            'CLAIM_Approved':     { label: 'Manager Approved',           icon: 'fa-stamp',             color: 'green' },
            'CLAIM_Rejected':     { label: 'Claim Rejected',             icon: 'fa-circle-xmark',      color: 'red'   },
            'PAYMENT_DISBURSED':  { label: 'Payment Disbursed',          icon: 'fa-money-bill-transfer','color': 'emerald'},
            'PAYMENT_DISBURSED_BATCH': { label: 'Batch Payment Disbursed', icon: 'fa-money-bill-transfer', color: 'emerald'},
            'VOUCHER_PDF_GENERATED': { label: 'PDF Voucher Generated',   icon: 'fa-file-pdf',          color: 'rose'  },
        };

        if (logs.length > 0) {
            // Map real audit logs to enriched nodes
            return logs.map(log => {
                const meta = actionLabels[log.action] || { label: log.action, icon: 'fa-clock', color: 'slate' };
                const nv = log.new_values || {};
                let snippet = '';
                if (log.action === 'FRAUD_ANALYZED') {
                    snippet = nv.risk_score !== undefined
                        ? `Risk Score: ${nv.risk_score}% • Flags: ${nv.flag_count || 0}${nv.top_flag ? ' • Top: ' + nv.top_flag : ''}`
                        : '';
                } else if (log.action === 'CLAIM_SUBMITTED') {
                    snippet = `Mode: ${nv.submission_mode || claim.claim_type} • Amount: RM ${parseFloat(nv.amount || claim.amount || 0).toFixed(2)}`;
                } else if (log.action === 'CLAIM_Approved' || log.action === 'CLAIM_Pre-Approved' || log.action === 'CLAIM_Rejected') {
                    snippet = nv.remarks ? `Remarks: ${nv.remarks}` : (nv.signed_by ? `Signed by: ${nv.signed_by}` : '');
                } else if (log.action === 'PAYMENT_DISBURSED' || log.action === 'PAYMENT_DISBURSED_BATCH') {
                    snippet = nv.payment_reference ? `Bank Ref: ${nv.payment_reference}` : (nv.batch_reference ? `Batch Ref: ${nv.batch_reference}` : '');
                }
                return {
                    ...meta,
                    action: log.action,
                    snippet,
                    actor: log.user_name || 'System',
                    actor_role: log.user_role || 'System',
                    timestamp: log.created_at,
                    is_real: true,
                };
            });
        }

        // Fallback: synthesize lifecycle milestones from claim timestamps (for legacy claims)
        const nodes = [];
        if (claim.created_at) {
            nodes.push({
                label: 'Claim Submitted', icon: 'fa-paper-plane', color: 'blue',
                snippet: `Mode: ${claim.claim_type || 'Receipt'} • Amount: RM ${parseFloat(claim.amount || 0).toFixed(2)}`,
                actor: claim.user_name || 'Staff', actor_role: claim.user_role || 'Staff',
                timestamp: claim.created_at, is_real: false,
            });
        }

        const pendingUpcoming = !['Pre-Approved','Approved','Rejected','Reimbursed'].includes(claim.status);
        nodes.push({
            label: 'Finance Review', icon: 'fa-check-circle', color: pendingUpcoming ? 'grey' : 'cyan',
            snippet: claim.status === 'Pre-Approved' || claim.status === 'Approved' || claim.status === 'Reimbursed' ? 'Verified and escalated to Manager' : 'Awaiting Finance Officer review',
            actor: 'Finance Officer', actor_role: 'Finance',
            timestamp: null, is_real: false, is_upcoming: pendingUpcoming
        });

        const managerPending = !['Approved','Rejected','Reimbursed'].includes(claim.status);
        nodes.push({
            label: claim.status === 'Rejected' ? 'Manager Rejected' : 'Manager Approval', icon: claim.status === 'Rejected' ? 'fa-circle-xmark' : 'fa-stamp',
            color: claim.status === 'Rejected' ? 'red' : (managerPending ? 'grey' : 'green'),
            snippet: claim.status === 'Approved' || claim.status === 'Reimbursed' ? 'Claim authorized for payment' : (claim.status === 'Rejected' ? 'Claim rejected' : 'Awaiting Manager decision'),
            actor: 'Manager', actor_role: 'Manager',
            timestamp: null, is_real: false, is_upcoming: managerPending
        });

        const disbursedPending = claim.status !== 'Reimbursed';
        nodes.push({
            label: 'Payment Disbursed', icon: 'fa-money-bill-transfer', color: disbursedPending ? 'grey' : 'emerald',
            snippet: claim.payment_reference ? `Bank Ref: ${claim.payment_reference}` : (disbursedPending ? 'Pending Finance disbursement' : 'Payment settled'),
            actor: 'Finance Officer', actor_role: 'Finance',
            timestamp: claim.paid_at || null, is_real: false, is_upcoming: disbursedPending
        });

        return nodes;
    },
    formatTimestamp(ts) {
        if (!ts) return null;
        const d = new Date(ts);
        if (isNaN(d.getTime())) return null;
        const now = new Date();
        const diffMs = now - d;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHrs = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHrs / 24);
        const abs = d.toLocaleString('en-MY', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });
        let rel = '';
        if (diffMins < 1) rel = 'just now';
        else if (diffMins < 60) rel = diffMins + ' min ago';
        else if (diffHrs < 24) rel = diffHrs + ' hr ago';
        else rel = diffDays + ' day' + (diffDays > 1 ? 's' : '') + ' ago';
        return `${abs} (${rel})`;
    },
    colorClasses(color, type) {
        const map = {
            blue:    { dot: 'bg-blue-500 border-blue-200',    badge: 'bg-blue-50 text-blue-700 border-blue-100',   line: 'bg-blue-200'    },
            purple:  { dot: 'bg-purple-500 border-purple-200', badge: 'bg-purple-50 text-purple-700 border-purple-100', line: 'bg-purple-200' },
            cyan:    { dot: 'bg-cyan-500 border-cyan-200',     badge: 'bg-cyan-50 text-cyan-700 border-cyan-100',   line: 'bg-cyan-200'    },
            green:   { dot: 'bg-emerald-500 border-emerald-200',badge: 'bg-emerald-50 text-emerald-700 border-emerald-100', line: 'bg-emerald-200'},
            red:     { dot: 'bg-rose-500 border-rose-200',     badge: 'bg-rose-50 text-rose-700 border-rose-100',   line: 'bg-rose-200'    },
            emerald: { dot: 'bg-emerald-600 border-emerald-200',badge: 'bg-emerald-50 text-emerald-800 border-emerald-100', line: 'bg-emerald-200'},
            rose:    { dot: 'bg-rose-400 border-rose-100',     badge: 'bg-rose-50 text-rose-600 border-rose-100',   line: 'bg-rose-100'    },
            grey:    { dot: 'bg-slate-200 border-slate-100',   badge: 'bg-slate-50 text-slate-400 border-slate-100', line: 'bg-slate-100'  },
            slate:   { dot: 'bg-slate-400 border-slate-200',   badge: 'bg-slate-50 text-slate-600 border-slate-100', line: 'bg-slate-200'  },
        };
        return (map[color] || map.slate)[type] || '';
    }
}">
    {{-- Section header --}}
    <h4 class="text-[10px] uppercase font-bold tracking-wider text-slate-400 mb-4 flex items-center gap-1.5">
        <i class="fa-solid fa-timeline text-slate-300"></i>
        Forensic Event-Sourced Audit Trail
    </h4>

    {{-- Timeline container --}}
    <div class="relative space-y-0">
        <template x-for="(node, i) in timelineNodes" :key="i">
            <div class="flex gap-3">
                {{-- Left: dot + connector line --}}
                <div class="flex flex-col items-center">
                    {{-- Milestone dot --}}
                    <div class="flex-shrink-0 w-7 h-7 rounded-full border-2 flex items-center justify-center shadow-xs z-10"
                        :class="colorClasses(node.color, 'dot')">
                        <i class="fa-solid text-white text-[9px]" :class="node.icon"></i>
                    </div>
                    {{-- Connector line (not for last item) --}}
                    <template x-if="i < timelineNodes.length - 1">
                        <div class="w-px flex-1 mt-1 mb-1 min-h-[24px]"
                            :class="node.is_upcoming ? 'border-l-2 border-dashed border-slate-200' : colorClasses(node.color, 'line')">
                        </div>
                    </template>
                </div>

                {{-- Right: event card --}}
                <div class="flex-1 pb-4">
                    <div class="rounded-xl border p-3 transition-all"
                        :class="node.is_upcoming ? 'bg-slate-50/50 border-slate-100 opacity-60' : 'bg-white border-slate-200 shadow-3xs'">

                        {{-- Stage title + badge --}}
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <span class="text-xs font-black text-slate-800 leading-tight" x-text="node.label"></span>
                            <span class="flex-shrink-0 text-[8px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border"
                                :class="colorClasses(node.color, 'badge')"
                                x-text="node.is_upcoming ? 'Upcoming' : (node.is_real ? 'Logged' : 'Estimated')">
                            </span>
                        </div>

                        {{-- Actor --}}
                        <div class="flex items-center gap-1.5 mb-1.5">
                            <i class="fa-solid fa-user-shield text-[9px] text-slate-300"></i>
                            <span class="text-[10px] font-semibold text-slate-500"
                                x-text="'By ' + node.actor + (node.actor_role && node.actor_role !== 'System' ? ' (' + node.actor_role.charAt(0).toUpperCase() + node.actor_role.slice(1) + ')' : '')">
                            </span>
                        </div>

                        {{-- Timestamp --}}
                        <template x-if="node.timestamp && formatTimestamp(node.timestamp)">
                            <div class="flex items-center gap-1 mb-1.5">
                                <i class="fa-regular fa-clock text-[9px] text-slate-300"></i>
                                <span class="text-[10px] text-slate-400 font-mono" x-text="formatTimestamp(node.timestamp)"></span>
                            </div>
                        </template>
                        <template x-if="!node.timestamp && node.is_upcoming">
                            <div class="flex items-center gap-1 mb-1.5">
                                <i class="fa-regular fa-clock text-[9px] text-slate-300"></i>
                                <span class="text-[10px] text-slate-300 italic">Pending action</span>
                            </div>
                        </template>

                        {{-- Metadata snippet --}}
                        <template x-if="node.snippet">
                            <div class="mt-1.5 p-2 rounded-lg border text-[10px] font-medium leading-relaxed"
                                :class="node.is_upcoming ? 'bg-slate-50 border-slate-100 text-slate-400' : 'bg-slate-50/70 border-slate-100 text-slate-600'">
                                <i class="fa-solid fa-circle-info text-[8px] text-slate-400 mr-1"></i>
                                <span x-text="node.snippet"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        {{-- Empty state --}}
        <template x-if="timelineNodes.length === 0">
            <div class="text-center py-4 text-slate-400 text-xs">
                <i class="fa-solid fa-timeline mb-2 text-slate-200 text-xl block"></i>
                No audit events recorded yet.
            </div>
        </template>
    </div>

    {{-- Forensic Checksum / Data Integrity Stamp --}}
    <template x-if="activeClaim.forensic_hash">
        <div class="mt-3 p-2.5 rounded-xl border border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100/60 flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-slate-800 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-shield-halved text-[10px] text-emerald-400"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[8px] font-black uppercase tracking-widest text-slate-400 mb-0.5">
                    Immutable Audit Log • System Verified Integrity
                </p>
                <p class="font-mono text-[9px] text-slate-500 truncate"
                    x-text="'SHA-256: ' + (activeClaim.forensic_hash || '').substring(0, 40) + '...'">
                </p>
            </div>
            <span class="flex-shrink-0 text-[8px] font-black uppercase tracking-wider text-emerald-600 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded-full">
                <i class="fa-solid fa-lock text-[7px] mr-0.5"></i>SEALED
            </span>
        </div>
    </template>
</div>
