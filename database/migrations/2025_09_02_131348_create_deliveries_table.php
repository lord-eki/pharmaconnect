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
        Schema::create('deliveries', function (Blueprint $table) {
           $table->id();
            $table->string('delivery_number')->unique();
            $table->foreignId('order_id')->constrained();
            $table->foreignId('rider_id')->nullable()->constrained()->nullOnDelete();
            $table->text('pickup_address');
            $table->text('delivery_address');
            $table->decimal('pickup_latitude', 10, 8)->nullable();
            $table->decimal('pickup_longitude', 11, 8)->nullable();
            $table->decimal('delivery_latitude', 10, 8)->nullable();
            $table->decimal('delivery_longitude', 11, 8)->nullable();
            $table->decimal('estimated_distance_km', 6, 2)->nullable();
            $table->decimal('delivery_fee', 8, 2);
            $table->enum('status', ['pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('scheduled_pickup')->nullable();
            $table->timestamp('actual_pickup')->nullable();
            $table->timestamp('estimated_delivery')->nullable();
            $table->timestamp('actual_delivery')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->string('proof_of_delivery')->nullable();
            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['rider_id']);
            $table->index(['status']);
            $table->index(['scheduled_pickup']);
            $table->index(['estimated_delivery']);
            $table->index(['pickup_latitude', 'pickup_longitude']);
            $table->index(['delivery_latitude', 'delivery_longitude']);
        });
    }

};
