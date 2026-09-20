<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the database migrations to build the complete relational schema dynamically.
     */
    public function up(): void
    {

        // 1. Create 'users' table first
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id'); // Primary Key
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['Staff', 'Finance', 'Manager'])->default('Staff');
            $table->timestamps();
        });

        // 2. Create 'vehicles' table (Diletak SEBELUM claims supaya boleh dirujuk oleh foreign key)
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id('vehicle_id'); // Primary Key
            $table->unsignedBigInteger('user_id')->nullable(); // NULL jika kenderaan milik syarikat Aero Art
            $table->string('plate_number')->unique();
            $table->string('brand_model'); // cth: Perodua Myvi, Toyota Hiace
            $table->enum('vehicle_type', ['Car', 'Motorcycle'])->default('Car');
            $table->integer('engine_capacity')->default(1500); // Penting untuk kadar pengiraan mileage
            $table->date('roadtax_expiry')->nullable(); // Pengesahan kelayakan roadtax
            $table->enum('ownership_type', ['personal', 'company'])->default('personal');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();

            // Foreign Key ke Users
            $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
        });

        // 3. Create 'claims' table
        Schema::create('claims', function (Blueprint $table) {
            $table->id('claim_id'); // Primary Key
            $table->unsignedBigInteger('user_id'); // Foreign Key ke Users
            $table->unsignedBigInteger('vehicle_id')->nullable(); // Foreign Key ke Vehicles (DITAMBAH DI SINI)
            $table->string('title');
            $table->string('claim_type')->default('Receipt'); // 'Receipt' or 'Mileage'

            // Core Document Analytics & Location Fields
            $table->string('merchant_name')->nullable();
            $table->string('location_address')->nullable();
            $table->string('receipt_invoice_no')->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('payment_method')->default('Cash');
            $table->text('business_purpose')->nullable();

            // Relational AI Data Model Properties
            $table->string('receipt_image_path')->nullable(); // Nullable jika claim jenis mileage
            $table->longText('extracted_raw_text')->nullable();
            $table->string('predicted_category')->default('Unassigned');
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->string('status')->default('Pending');
            $table->date('estimated_payout_date')->nullable(); // DITAMBAH DI SINI (Penjejakan SLA ala courier)

            // Logistics & Mileage Parameters Mapping
            $table->decimal('mileage_km', 8, 2)->nullable();
            $table->string('vehicle_type')->nullable();
            $table->string('start_location')->nullable();
            $table->string('destination_location')->nullable();
            $table->string('vehicle_plate_number')->nullable();

            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations by dropping tables in reverse order.
     */
    public function down(): void
    {
        Schema::dropIfExists('claims');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('users');
    }
};