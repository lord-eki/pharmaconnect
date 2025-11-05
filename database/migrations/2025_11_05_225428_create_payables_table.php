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
        Schema::create('payables', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('vendor_id')->constrained('users'); 
            $table->enum('vendor_type', ['supplier', 'physician']);
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['mpesa', 'bank_transfer', 'check'])->nullable();
            $table->string('gateway_reference')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payables');
    }
};
