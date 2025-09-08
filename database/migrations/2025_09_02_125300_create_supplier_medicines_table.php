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
        Schema::create('supplier_medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained();
            $table->decimal('unit_price', 8, 2);
            $table->integer('stock_quantity')->default(0);
            $table->integer('minimum_order_quantity')->default(1);
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamp('last_updated');
            $table->timestamps();

            $table->unique(['supplier_id', 'medicine_id']);
            $table->index(['medicine_id']);
            $table->index(['unit_price']);
            $table->index(['is_available']);
            $table->index(['expiry_date']);
            $table->index(['last_updated']);
        });
    }

};
