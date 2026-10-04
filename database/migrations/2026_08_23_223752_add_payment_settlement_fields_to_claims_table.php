<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            if (!Schema::hasColumn('claims', 'payment_reference')) {
                $table->string('payment_reference')->nullable();
            }
            if (!Schema::hasColumn('claims', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
            if (!Schema::hasColumn('claims', 'payment_proof_path')) {
                $table->string('payment_proof_path')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('claims', 'payment_reference')) {
                $columnsToDrop[] = 'payment_reference';
            }
            if (Schema::hasColumn('claims', 'paid_at')) {
                $columnsToDrop[] = 'paid_at';
            }
            if (Schema::hasColumn('claims', 'payment_proof_path')) {
                $columnsToDrop[] = 'payment_proof_path';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};