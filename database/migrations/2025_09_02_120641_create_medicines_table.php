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
        Schema::create('medicines', function (Blueprint $table) {
       $table->id();
            $table->foreignId('category_id')->constrained('medicine_categories')->cascadeOnDelete();
            $table->string('generic_name');
            $table->string('brand_name')->nullable();
            $table->string('strength');
            $table->string('dosage_form');
            $table->string('pack_size');
            $table->string('manufacturer');
            $table->text('active_ingredients');
            $table->text('description')->nullable();
            $table->text('usage_instructions')->nullable();
            $table->text('side_effects')->nullable();
            $table->text('contraindications')->nullable();
            $table->text('storage_requirements')->nullable();
            $table->boolean('prescription_required')->default(true);
            $table->boolean('controlled_substance')->default(false);
            $table->string('ppb_registration_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category_id']);
            $table->index(['generic_name']);
            $table->index(['brand_name']);
            $table->index(['prescription_required']);
            $table->index(['is_active']);
            $table->index(['ppb_registration_number']);
            
            $table->fullText(['generic_name', 'brand_name', 'active_ingredients']);
        });
    }

};
