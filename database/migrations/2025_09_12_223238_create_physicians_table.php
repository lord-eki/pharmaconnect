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
        Schema::create('physicians', function (Blueprint $table) {
           $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Professional Information
            $table->string('license_number')->unique();
            $table->date('license_expiry_date')->nullable();
            $table->string('medical_council_registration')->nullable(); // KMPDC registration
            $table->string('specialization')->nullable();
            $table->integer('years_experience')->nullable();
            $table->enum('qualification_level', ['diploma', 'degree', 'masters', 'phd', 'fellowship'])->nullable();
            
            // Practice Information
            $table->string('practice_name')->nullable();
            $table->text('practice_address')->nullable();
            $table->string('county')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('practice_phone')->nullable();
            $table->string('practice_email')->nullable();
            $table->enum('practice_type', ['private', 'public', 'ngo', 'faith_based'])->nullable();
            
            // Verification & Compliance
            $table->enum('verification_status', ['pending', 'verified', 'rejected', 'suspended'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('verification_notes')->nullable();
            $table->string('document_path')->nullable(); // uploaded verification documents
            
            // Commission & Financial
            $table->decimal('commission_rate', 5, 2)->default(5.00); // percentage
            $table->decimal('total_commissions_earned', 12, 2)->default(0);
            $table->integer('total_prescriptions')->default(0);
            $table->integer('total_fulfilled_prescriptions')->default(0);
            
            // Professional Settings
            $table->json('prescription_preferences')->nullable(); // default dosage forms, etc.
            $table->boolean('allow_generic_substitution')->default(true);
            $table->boolean('require_patient_consent')->default(true);
            $table->json('notification_preferences')->nullable();
            
            // Status & Availability
            $table->boolean('is_active')->default(true);
            $table->boolean('accepting_prescriptions')->default(true);
            $table->time('practice_start_time')->nullable();
            $table->time('practice_end_time')->nullable();
            $table->json('working_days')->nullable(); // array of weekdays
            
            $table->timestamps();
            
            // Indexes
            $table->unique('user_id');
            $table->index('license_number');
            $table->index('verification_status');
            $table->index(['county', 'city']);
            $table->index('specialization');
            $table->index(['is_active', 'accepting_prescriptions']);
            $table->index('total_prescriptions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physicians');
    }
};
