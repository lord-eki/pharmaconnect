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
        Schema::create('suppliers', function (Blueprint $table) {
          $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('registration_number')->unique();
            $table->string('license_number')->unique();
            $table->string('contact_person');
            $table->string('phone');
            $table->string('email');
            $table->text('address');
            $table->string('county');
            $table->string('city');
            $table->string('postal_code')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('tax_pin')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('fulfillment_sla_hours')->default(24);
            $table->timestamps();

            $table->index(['county', 'city']);
            $table->index(['is_verified', 'is_active']);
            $table->index(['rating']);
        });
    }

};
