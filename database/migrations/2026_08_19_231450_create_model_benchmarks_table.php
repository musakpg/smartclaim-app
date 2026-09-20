<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('model_benchmarks', function (Blueprint $table) {
            $table->id('benchmark_id');
            $table->string('sample_name');
            $table->text('raw_ocr_payload');
            $table->string('actual_category');
            $table->string('predicted_category')->nullable();
            $table->decimal('actual_amount', 10, 2);
            $table->decimal('extracted_amount', 10, 2)->nullable();
            $table->boolean('is_category_correct')->default(false);
            $table->boolean('is_amount_correct')->default(false);
            $table->decimal('processing_time_ms', 8, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_benchmarks');
    }
};