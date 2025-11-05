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
        Schema::create('receivables', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->foreignId('prescription_id')->constrained('prescriptions');
            $table->foreignId('patient_id')->constrained('users');
            $table->foreignId('insurance_provider_id')->constrained('insurance_providers')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('payment_source', ['insurance', 'patient']);
            $table->enum('claim_status', ['draft', 'submitted', 'approved', 'rejected', 'paid'])->nullable();
            $table->string('claim_reference')->nullable();
            $table->date('claim_submitted_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('insurance_provider_id');
            $table->index('claim_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receivables');
    }
};
