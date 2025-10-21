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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_reference')->unique();
            $table->foreignId('payer_id')->constrained('users');
            $table->foreignId('payee_id')->nullable()->constrained('users');
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->foreignId('prescription_id')->nullable()->constrained('prescriptions');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('KES');
            $table->enum('payment_method', ['mpesa', 'card', 'bank_transfer', 'cash', 'insurance']);
            $table->string('gateway_reference')->nullable(); // External payment gateway reference
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('payment_reference');
            $table->index('payer_id');
            $table->index('payee_id');
            $table->index('order_id');
            $table->index('prescription_id');
            $table->index('status');
            $table->index('payment_method');
            $table->index('processed_at');
        });
    }

   
};
