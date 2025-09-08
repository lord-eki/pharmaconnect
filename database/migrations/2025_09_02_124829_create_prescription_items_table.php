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
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained();
            $table->integer('quantity');
            $table->text('dosage_instructions');
            $table->integer('duration_days')->nullable();
            $table->string('frequency')->nullable();
            $table->decimal('unit_price', 8, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->enum('status', ['pending', 'quoted', 'ordered', 'fulfilled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['prescription_id']);
            $table->index(['medicine_id']);
            $table->index(['status']);
        });
    }

};
