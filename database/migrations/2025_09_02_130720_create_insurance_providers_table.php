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
        Schema::create('insurance_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('registration_number')->unique();
            $table->string('contact_person');
            $table->string('phone');
            $table->string('email');
            $table->text('address');
            $table->string('website')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('api_endpoint')->nullable();
            $table->text('api_key')->nullable(); 
            $table->timestamps();

            $table->index(['is_active']);
        });
    }

};
