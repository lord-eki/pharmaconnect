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
        Schema::create('medicine_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interacting_medicine_id')->constrained('medicines')->cascadeOnDelete();
            $table->enum('interaction_type', ['minor', 'moderate', 'major']);
            $table->text('description');
            $table->text('clinical_significance')->nullable();
            $table->timestamps();

            $table->index(['medicine_id']);
            $table->index(['interacting_medicine_id']);
            $table->index(['interaction_type']);
            $table->unique(['medicine_id', 'interacting_medicine_id']);
        });
    }
};
