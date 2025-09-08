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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_item_id')->constrained();
            $table->foreignId('medicine_id')->constrained();
            $table->integer('quantity');
            $table->decimal('unit_price', 8, 2);
            $table->decimal('total_price', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'shipped', 'delivered'])->default('pending');
            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['quotation_item_id']);
            $table->index(['medicine_id']);
            $table->index(['status']);
        });
    }

};
