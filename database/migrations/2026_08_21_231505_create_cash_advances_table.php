<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_advances', function (Blueprint $table) {
            $table->id('advance_id');

            // Explicit foreign key mapping to 'user_id' on 'users' table
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();

            $table->string('title');
            $table->text('purpose');
            $table->decimal('requested_amount', 10, 2);
            $table->decimal('settled_amount', 10, 2)->default(0.00);
            $table->decimal('remaining_balance', 10, 2)->default(0.00);
            $table->date('required_date');
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Settled'])->default('Pending');
            $table->text('manager_remarks')->nullable();
            $table->timestamps();
        });

        // Tambah column advance_id pada table claims untuk penyelarasan tolakan
        Schema::table('claims', function (Blueprint $table) {
            if (!Schema::hasColumn('claims', 'cash_advance_id')) {
                $table->unsignedBigInteger('cash_advance_id')->nullable()->after('claim_type');
                $table->foreign('cash_advance_id')->references('advance_id')->on('cash_advances')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['cash_advance_id']);
            $table->dropColumn('cash_advance_id');
        });
        Schema::dropIfExists('cash_advances');
    }
};