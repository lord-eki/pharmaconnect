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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('quotation_id')->constrained();
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('prescription_id')->constrained();
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->timestamp('ordered_at');
            $table->timestamp('expected_delivery')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['quotation_id']);
            $table->index(['supplier_id']);
            $table->index(['prescription_id']);
            $table->index(['status']);
            $table->index(['ordered_at']);
        });
    }

};
