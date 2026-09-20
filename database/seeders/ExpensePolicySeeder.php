<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExpensePolicy;

class ExpensePolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'category_name' => 'Meals & Entertainment',
                'monthly_budget_cap' => 300.00,
                'max_single_claim_limit' => 60.00,
                'is_active' => true,
                'description' => 'Staff refreshment & client meeting meals cap.'
            ],
            [
                'category_name' => 'Fuel / Automotive',
                'monthly_budget_cap' => 450.00,
                'max_single_claim_limit' => 120.00,
                'is_active' => true,
                'description' => 'Official outstation & operational fleet fuel quota.'
            ],
            [
                'category_name' => 'Office Supplies',
                'monthly_budget_cap' => 200.00,
                'max_single_claim_limit' => 100.00,
                'is_active' => true,
                'description' => 'Stationery, printing & office essentials ceiling.'
            ],
            [
                'category_name' => 'Accommodations',
                'monthly_budget_cap' => 600.00,
                'max_single_claim_limit' => 250.00,
                'is_active' => true,
                'description' => 'Hotel lodging allowance for official company travel.'
            ],
        ];

        foreach ($policies as $policy) {
            ExpensePolicy::updateOrCreate(
                ['category_name' => $policy['category_name']],
                $policy
            );
        }
    }
}