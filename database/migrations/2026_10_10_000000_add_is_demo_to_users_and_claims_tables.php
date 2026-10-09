<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_demo')) {
                $table->boolean('is_demo')->default(false)->index();
            }
        });

        Schema::table('claims', function (Blueprint $table) {
            if (!Schema::hasColumn('claims', 'is_demo')) {
                $table->boolean('is_demo')->default(false)->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            if (Schema::hasColumn('claims', 'is_demo')) {
                $table->dropColumn('is_demo');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_demo')) {
                $table->dropColumn('is_demo');
            }
        });
    }
};
