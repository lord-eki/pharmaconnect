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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->foreignId('prescription_id')->constrained();
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'sent', 'accepted', 'rejected', 'expired'])->default('pending');
            $table->timestamp('valid_until');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['prescription_id']);
            $table->index(['status']);
            $table->index(['valid_until']);
        });
    }

};
