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
        Schema::create('vehicle_assignments', function (Blueprint $table) {
            $table->id('assignment_id'); // Primary Key
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('user_id');
            $table->dateTime('checkout_at'); // Masa mula guna
            $table->dateTime('checkin_at')->nullable(); // Masa pulang (NULL = masih guna)
            $table->string('purpose')->nullable();
            $table->timestamps();

            // Pautkan ke Primary Key yang betul secara eksplisit
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles')->cascadeOnDelete();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_assignments');
    }
};
