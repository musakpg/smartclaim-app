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
        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'payload')) {
                $table->dropColumn('payload');
            }
            $table->unsignedBigInteger('claim_id')->nullable()->after('user_id');
            $table->json('old_values')->nullable()->after('action');
            $table->json('new_values')->nullable()->after('old_values');
            $table->text('user_agent')->nullable()->after('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['claim_id', 'old_values', 'new_values', 'user_agent']);
            $table->json('payload')->nullable()->after('action');
        });
    }
};
