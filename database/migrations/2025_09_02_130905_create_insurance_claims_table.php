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
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number')->unique();
            $table->foreignId('prescription_id')->constrained();
            $table->foreignId('insurance_provider_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->string('policy_number');
            $table->decimal('claimed_amount', 10, 2);
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->decimal('deductible_amount', 10, 2)->default(0);
            $table->enum('status', ['submitted', 'under_review', 'approved', 'rejected', 'paid'])->default('submitted');
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['prescription_id']);
            $table->index(['insurance_provider_id']);
            $table->index(['patient_id']);
            $table->index(['policy_number']);
            $table->index(['status']);
            $table->index(['submitted_at']);
        });
    }

};
