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
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prescription_item_id')->constrained();
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('supplier_medicine_id')->constrained();
            $table->integer('quantity');
            $table->decimal('unit_price', 8, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();

            $table->index(['quotation_id']);
            $table->index(['prescription_item_id']);
            $table->index(['supplier_id']);
        });
    }

};
