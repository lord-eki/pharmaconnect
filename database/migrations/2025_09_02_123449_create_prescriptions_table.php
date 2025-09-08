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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('prescription_number')->unique();
            $table->foreignId('physician_id')->constrained('users');
            $table->foreignId('patient_id')->constrained();
            $table->text('diagnosis')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'processing', 'fulfilled', 'cancelled'])->default('draft');
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->boolean('insurance_covered')->default(false);
            $table->timestamp('prescribed_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();

            $table->index(['physician_id']);
            $table->index(['patient_id']);
            $table->index(['status']);
            $table->index(['prescribed_at']);
        });
    }

};
