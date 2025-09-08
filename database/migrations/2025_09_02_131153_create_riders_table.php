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
        Schema::create('riders', function (Blueprint $table) {
           $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rider_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->string('license_number')->unique();
            $table->enum('vehicle_type', ['motorcycle', 'car', 'bicycle', 'van']);
            $table->string('vehicle_registration');
            $table->string('base_county');
            $table->string('base_city');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available')->default(true);
            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('total_deliveries')->default(0);
            $table->timestamps();

            $table->index(['base_county', 'base_city']);
            $table->index(['is_active', 'is_available']);
            $table->index(['rating']);
        });
    }

};
