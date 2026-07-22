<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the database migrations to build the complete relational schema dynamically.
     */
    public function up(): void {
        
        // 1. Create 'users' table first so it can be referenced by foreign keys later
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id'); // Primary Key
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['Staff', 'Finance', 'Manager'])->default('Staff');
            $table->timestamps(); // Tracks created_at and updated_at metadata logs
        });

        // 2. Create 'claims' table containing all transactional, OCR, and mileage metrics
        Schema::create('claims', function (Blueprint $table) {
            $table->id('claim_id'); // Primary Key
            $table->unsignedBigInteger('user_id'); // Foreign Key Column
            $table->string('title');
            $table->string('claim_type')->default('Receipt'); // Mode identification: 'Receipt' or 'Mileage'
            
            // Core Document Analytics & Location Fields
            $table->string('merchant_name')->nullable();
            $table->string('location_address')->nullable();
            $table->string('receipt_invoice_no')->nullable();
            $table->date('transaction_date')->nullable(); // Required tracking parameter for DashboardController lines
            $table->string('payment_method')->default('Cash');
            $table->text('business_purpose')->nullable();
            
            // Relational AI Data Model Properties
            $table->string('receipt_image_path');
            $table->longText('extracted_raw_text')->nullable(); // Stores raw string data from Google Cloud Vision OCR
            $table->string('predicted_category')->default('Unassigned');
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->string('status')->default('Pending');
            
            // Logistics & Mileage Parameters Mapping
            $table->decimal('mileage_km', 8, 2)->nullable();
            $table->string('vehicle_type')->nullable(); // Mode tracking: 'Car' or 'Motorcycle'
            $table->string('start_location')->nullable();
            $table->string('destination_location')->nullable();
            $table->string('vehicle_plate_number')->nullable();

            $table->timestamps(); // Tracks created_at and updated_at system logs

            // Establish Referential Integrity Constraint
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id('vehicle_id');
            $table->string('plate_number')->unique(); // Unique index to prevent duplicate asset registry
            $table->string('brand_model');             // e.g., Volvo S60, SYM VF3i
            $table->enum('vehicle_type', ['Car', 'Motorcycle']); // Determines allowance scaling thresholds
            $table->integer('engine_capacity')->nullable();     // e.g., 2000 cc, 185 cc
            $table->enum('status', ['Active', 'Inactive'])->default('Active'); // Allocation availability flag
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations by dropping tables in reverse order.
     */
    public function down(): void {
        Schema::dropIfExists('claims');
        Schema::dropIfExists('users');
    }
};