<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expense_policies', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->unique();
            $table->decimal('monthly_budget_cap', 10, 2)->default(500.00);
            $table->decimal('max_single_claim_limit', 10, 2)->default(150.00);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Tambah column policy_violation ke table claims jika belum ada
        if (Schema::hasTable('claims')) {
            Schema::table('claims', function (Blueprint $table) {
                if (!Schema::hasColumn('claims', 'is_policy_violation')) {
                    $table->boolean('is_policy_violation')->default(false);
                }
                if (!Schema::hasColumn('claims', 'policy_violation_reason')) {
                    $table->string('policy_violation_reason')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_policies');
        if (Schema::hasTable('claims')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->dropColumn(['is_policy_violation', 'policy_violation_reason']);
            });
        }
    }
};