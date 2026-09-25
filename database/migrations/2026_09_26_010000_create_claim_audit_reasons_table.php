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
        // 1. Create claim_audit_reasons master lookup table
        if (!Schema::hasTable('claim_audit_reasons')) {
            Schema::create('claim_audit_reasons', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->enum('type', ['REVISION', 'REJECTION']);
                $table->string('title');
                $table->boolean('requires_remarks')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Add audit exception reason and remarks fields to claims table
        Schema::table('claims', function (Blueprint $table) {
            if (!Schema::hasColumn('claims', 'revision_reason')) {
                $table->string('revision_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('claims', 'rejection_reason')) {
                $table->string('rejection_reason')->nullable()->after('revision_reason');
            }
            if (!Schema::hasColumn('claims', 'remarks')) {
                $table->text('remarks')->nullable()->after('rejection_reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            if (Schema::hasColumn('claims', 'remarks')) {
                $table->dropColumn('remarks');
            }
            if (Schema::hasColumn('claims', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
            if (Schema::hasColumn('claims', 'revision_reason')) {
                $table->dropColumn('revision_reason');
            }
        });

        Schema::dropIfExists('claim_audit_reasons');
    }
};
