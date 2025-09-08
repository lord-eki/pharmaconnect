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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physician_id')->constrained('users');
            $table->string('patient_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('county')->nullable();
            $table->string('city')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('insurance_number')->nullable();
            $table->string('insurance_provider')->nullable();
            $table->text('allergies')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['physician_id']);
            $table->index(['phone']);
            $table->index(['insurance_number']);
            $table->index(['county', 'city']);
            $table->index(['is_active']);
            
            $table->fullText(['first_name', 'last_name', 'phone']);
        });
    }

};
