<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            if (!Schema::hasColumn('claims', 'receipt_image_hash')) {
                $table->string('receipt_image_hash')->nullable()->after('receipt_image_path');
            }
            if (!Schema::hasColumn('claims', 'risk_score')) {
                $table->unsignedTinyInteger('risk_score')->default(0)->after('is_policy_violation');
            }
            if (!Schema::hasColumn('claims', 'fraud_flags')) {
                $table->json('fraud_flags')->nullable()->after('risk_score');
            }
            if (!Schema::hasColumn('claims', 'exif_date_taken')) {
                $table->dateTime('exif_date_taken')->nullable()->after('fraud_flags');
            }
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn(['receipt_image_hash', 'risk_score', 'fraud_flags', 'exif_date_taken']);
        });
    }
};