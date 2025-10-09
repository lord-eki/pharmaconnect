<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physician_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('prescription_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('commission_rate', 5, 2)->nullable(); // e.g. 12.50%
            $table->decimal('gross_amount', 12, 2)->nullable();
            $table->decimal('commission_amount', 12, 2)->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
        });
    }

 
};
