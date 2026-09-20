<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_learning_feedbacks', function (Blueprint $table) {
            $table->id();

            // Explicit foreign key mapping to 'user_id' on 'users' table
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();

            $table->string('merchant_name')->nullable();
            $table->string('predicted_category');
            $table->string('corrected_category');
            $table->longText('raw_text_sample')->nullable();
            $table->json('extracted_keywords')->nullable();
            $table->boolean('is_applied')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_learning_feedbacks');
    }
};