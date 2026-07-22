<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->string('operator_name'); // Captures the name of the manager executing the task
            $table->string('action_event');  // e.g., VEHICLE_CREATE, VEHICLE_UPDATE, VEHICLE_DELETE
            $table->string('plate_index');   // The unique license plate target node
            $table->string('description');   // Detailed forensic text log
            $table->string('ip_address');    // Network identifier capture
            $table->timestamps();            // Automatically yields created_at timestamps
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_logs');
    }
};