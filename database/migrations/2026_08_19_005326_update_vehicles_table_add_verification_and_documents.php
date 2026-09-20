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
        Schema::table('vehicles', function (Blueprint $table) {
            // Document Verification Paths
            $table->string('grant_document_path')->nullable()->after('roadtax_expiry');
            $table->string('roadtax_document_path')->nullable()->after('grant_document_path');

            // Approval Lifecycle Constraints
            $table->enum('approval_status', ['Pending', 'Approved', 'Rejected'])->default('Pending')->after('status');
            $table->text('rejection_reason')->nullable()->after('approval_status');

            // Manager Approval Trail
            $table->unsignedBigInteger('approved_by')->nullable()->after('rejection_reason');
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            // Roadtax Renewal Lifecycle Tracking
            $table->enum('roadtax_renewal_status', ['None', 'Pending_Review'])->default('None')->after('approved_at');

            // Foreign Key Constraint to Users table (Manager ID)
            $table->foreign('approved_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'grant_document_path',
                'roadtax_document_path',
                'approval_status',
                'rejection_reason',
                'approved_by',
                'approved_at',
                'roadtax_renewal_status',
            ]);
        });
    }
};