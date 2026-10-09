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
        if (!Schema::hasTable('ai_feedback')) {
            Schema::create('ai_feedback', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('claim_id')->nullable();
                $table->string('receipt_reference')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('field_name'); // 'category', 'amount', 'merchant', 'transaction_date'
                $table->text('predicted_value')->nullable();
                $table->text('actual_value')->nullable();
                $table->string('correction_status')->default('corrected_by_staff');
                $table->decimal('confidence_score', 5, 2)->nullable()->default(88.50);
                $table->longText('raw_text_sample')->nullable();
                $table->timestamps();

                $table->foreign('claim_id')->references('claim_id')->on('claims')->nullOnDelete();
                $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_feedback');
    }
};
