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
        Schema::table('supplier_medicines', function (Blueprint $table) {
            $table->index(['medicine_id', 'is_available', 'stock_quantity'],
                'idx_medicine_available_stock');
            $table->index(['medicine_id', 'unit_price'], 'idx_medicine_price');
            $table->index(['is_available', 'stock_quantity'], 'idx_available_stock');
        });

        Schema::table('prescription_items', function (Blueprint $table) {
            $table->index(['prescription_id', 'medicine_id'], 'idx_prescription_medicine');
        });

        Schema::table('medicine_interactions', function (Blueprint $table) {
            $table->index(['medicine_id', 'interacting_medicine_id'],
                'idx_medicine_interaction');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_status_date');
            $table->index(['physician_id', 'status'], 'idx_physician_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
