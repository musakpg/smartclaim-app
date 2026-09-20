<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add banking fields to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('email');
                $table->string('bank_account_no')->nullable()->after('bank_name');
                $table->string('bank_account_holder')->nullable()->after('bank_account_no');
            }
        });

        // 2. Add payout tracking fields to claims table
        Schema::table('claims', function (Blueprint $table) {
            if (!Schema::hasColumn('claims', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('status');
                $table->timestamp('reimbursed_at')->nullable()->after('payment_reference');
                $table->unsignedBigInteger('reimbursed_by')->nullable()->after('reimbursed_at');

                $table->foreign('reimbursed_by')->references('user_id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_no', 'bank_account_holder']);
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['reimbursed_by']);
            $table->dropColumn(['payment_reference', 'reimbursed_at', 'reimbursed_by']);
        });
    }
};