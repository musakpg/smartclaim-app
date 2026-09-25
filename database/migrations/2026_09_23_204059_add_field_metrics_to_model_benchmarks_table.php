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
        Schema::table('model_benchmarks', function (Blueprint $table) {
            $table->string('actual_merchant')->nullable();
            $table->string('extracted_merchant')->nullable();
            $table->boolean('is_merchant_correct')->default(false);

            $table->date('actual_date')->nullable();
            $table->date('extracted_date')->nullable();
            $table->boolean('is_date_correct')->default(false);

            $table->string('actual_tax_invoice')->nullable();
            $table->string('extracted_tax_invoice')->nullable();
            $table->boolean('is_tax_invoice_correct')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_benchmarks', function (Blueprint $table) {
            $table->dropColumn([
                'actual_merchant', 'extracted_merchant', 'is_merchant_correct',
                'actual_date', 'extracted_date', 'is_date_correct',
                'actual_tax_invoice', 'extracted_tax_invoice', 'is_tax_invoice_correct'
            ]);
        });
    }
};
