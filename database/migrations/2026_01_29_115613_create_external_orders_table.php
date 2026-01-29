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
        Schema::create('external_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('insurance_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            
            // Recipient information
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->string('recipient_email')->nullable();
            
            // Delivery information
            $table->text('delivery_address');
            $table->string('delivery_county')->nullable();
            $table->string('delivery_city')->nullable();
            $table->decimal('delivery_latitude', 10, 8)->nullable();
            $table->decimal('delivery_longitude', 11, 8)->nullable();
            
            // Order details
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['draft', 'submitted', 'processing', 'fulfilled', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->string('reference_number')->nullable(); 
            
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('insurance_provider_id');
            $table->index('created_by_user_id');
            $table->index('status');
            $table->index('ordered_at');
        });
    }

  
};
