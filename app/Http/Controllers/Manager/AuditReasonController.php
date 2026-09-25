<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ClaimAuditReason;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditReasonController extends Controller
{
    /**
     * Display the audit exception codes dashboard partitioned by type.
     */
    public function index()
    {
        $revisionReasons = ClaimAuditReason::where('type', 'REVISION')->orderBy('title')->get();
        $rejectionReasons = ClaimAuditReason::where('type', 'REJECTION')->orderBy('title')->get();

        return view('manager.audit_reasons', compact('revisionReasons', 'rejectionReasons'));
    }

    /**
     * Store a newly created audit exception code.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:REVISION,REJECTION',
            'code' => 'nullable|string|max:50|unique:claim_audit_reasons,code',
            'requires_remarks' => 'nullable|boolean',
        ]);

        $code = $request->filled('code')
            ? Str::upper(Str::slug($request->input('code'), '_'))
            : Str::upper(Str::slug(Str::limit($request->input('title'), 25, ''), '_'));

        // Ensure unique fallback
        $originalCode = $code;
        $counter = 1;
        while (ClaimAuditReason::where('code', $code)->exists()) {
            $code = "{$originalCode}_{$counter}";
            $counter++;
        }

        ClaimAuditReason::create([
            'code' => $code,
            'type' => $request->input('type'),
            'title' => $request->input('title'),
            'requires_remarks' => $request->boolean('requires_remarks', false),
            'is_active' => true,
        ]);

        return redirect()->route('manager.audit_reasons')->with('success', 'Audit exception code created successfully.');
    }

    /**
     * Update an existing audit exception code.
     */
    public function update(Request $request, $id)
    {
        $reason = ClaimAuditReason::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'requires_remarks' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $reason->update([
            'title' => $request->input('title'),
            'requires_remarks' => $request->boolean('requires_remarks', false),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('manager.audit_reasons')->with('success', "Audit exception code '{$reason->code}' updated successfully.");
    }

    /**
     * Toggle the active status of an audit exception code.
     */
    public function toggle($id)
    {
        $reason = ClaimAuditReason::findOrFail($id);
        $reason->is_active = !$reason->is_active;
        $reason->save();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => $reason->is_active,
                'message' => "Reason status updated to " . ($reason->is_active ? 'Active' : 'Inactive')
            ]);
        }

        return redirect()->route('manager.audit_reasons')->with('success', "Audit exception code '{$reason->code}' status toggled.");
    }

    /**
     * Remove an audit exception code.
     */
    public function destroy($id)
    {
        $reason = ClaimAuditReason::findOrFail($id);
        $code = $reason->code;
        $reason->delete();

        return redirect()->route('manager.audit_reasons')->with('success', "Audit exception code '{$code}' deleted successfully.");
    }
}
