<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('claim_items', function (Blueprint $table) {
            $table->id();
            // Foreign key linking back to the parent claim
            $table->unsignedBigInteger('claim_id');
            $table->foreign('claim_id')
                  ->references('claim_id')
                  ->on('claims')       
                  ->onDelete('cascade'); 

            $table->string('item_name');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_items');
    }
};