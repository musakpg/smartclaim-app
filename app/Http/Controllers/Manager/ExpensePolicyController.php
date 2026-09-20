<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ExpensePolicy;
use Illuminate\Http\Request;

class ExpensePolicyController extends Controller
{
    /**
     * Display corporate expense policies and monthly budget caps.
     */
    public function index()
    {
        $policies = ExpensePolicy::orderBy('category_name', 'asc')->get();
        return view('manager.expense-policies', compact('policies'));
    }

    /**
     * Update budget cap and single-claim threshold for a specific category.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'monthly_budget_cap' => 'required|numeric|min:0',
            'max_single_claim_limit' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
            'description' => 'nullable|string|max:255',
        ]);

        $policy = ExpensePolicy::findOrFail($id);
        $policy->update([
            'monthly_budget_cap' => $request->input('monthly_budget_cap'),
            'max_single_claim_limit' => $request->input('max_single_claim_limit'),
            'is_active' => (bool) $request->input('is_active'),
            'description' => $request->input('description'),
        ]);

        return redirect()->back()->with('success', "Policy parameters for '{$policy->category_name}' updated successfully.");
    }
}