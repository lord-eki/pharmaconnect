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
        Schema::table('deliveries', function (Blueprint $table) {
            // Add prescription_id as the primary relationship
            $table->foreignId('prescription_id')->nullable()->after('delivery_number')
                ->constrained('prescriptions')->onDelete('cascade');
            
            // Make order_id nullable since we'll have multiple orders per delivery
            $table->foreignId('order_id')->nullable()->change();
            
            // Add consolidated pickup info
            $table->json('pickup_locations')->nullable()->after('pickup_address')
                ->comment('Array of pickup locations for multiple suppliers');
            
            // Add order tracking
            $table->json('order_statuses')->nullable()->after('status')
                ->comment('Track individual order pickup statuses');
        });

        // Create pivot table for delivery-order relationship
        Schema::create('delivery_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->enum('pickup_status', ['pending', 'picked_up', 'failed'])->default('pending');
            $table->timestamp('picked_up_at')->nullable();
            $table->text('pickup_notes')->nullable();
            $table->timestamps();
            
            $table->unique(['delivery_id', 'order_id']);
        });


    }


};
