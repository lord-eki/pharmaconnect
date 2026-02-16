<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_rejected')->default(false)->after('status');
            $table->text('rejection_reason')->nullable()->after('is_rejected');
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            
            // Track reassignment history
            $table->unsignedInteger('reassignment_count')->default(0)->after('rejected_by');
            $table->unsignedBigInteger('original_supplier_id')->nullable()->after('reassignment_count');
            $table->json('reassignment_history')->nullable()->after('original_supplier_id');
            
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
        });
    
    }

};
