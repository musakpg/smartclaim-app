<?php

namespace App\Http\Controllers;

use App\Models\ExpensePolicy;
use App\Models\MileageRate;

class PolicyConfigController extends Controller
{
    /**
     * View company expense policies and mileage allowance ceilings.
     */
    public function policyIndex()
    {
        $mileageRates = collect();
        if (class_exists(MileageRate::class)) {
            $carRate = MileageRate::whereRaw('LOWER(vehicle_type) = ?', ['car'])->latest()->first();
            $motorRate = MileageRate::whereRaw('LOWER(vehicle_type) = ?', ['motorcycle'])->latest()->first();

            if ($carRate) {
                $mileageRates->push($carRate);
            }
            if ($motorRate) {
                $mileageRates->push($motorRate);
            }
        }

        $expensePolicies = class_exists(ExpensePolicy::class)
            ? ExpensePolicy::where('is_active', true)->get()
            : collect();

        return view('policy.index', compact('expensePolicies', 'mileageRates'));
    }

    /**
     * Mileage rates settings view.
     */
    public function mileageRatesIndex()
    {
        return view('manager.mileage_rates');
    }

    /**
     * Expense categories settings view.
     */
    public function expenseCategoriesIndex()
    {
        return view('manager.expense_categories');
    }
}
