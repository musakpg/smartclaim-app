<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('mileage_rates', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_type'); // 'Car' atau 'Motorcycle'
            $table->integer('min_km');
            $table->integer('max_km')->nullable();
            $table->decimal('rate', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mileage_rates');
    }
};
