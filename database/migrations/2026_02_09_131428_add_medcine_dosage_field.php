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
        Schema::table('prescription_items', function (Blueprint $table) {
              // Add measurement type field to distinguish discrete vs volume-based
            $table->enum('measurement_type', ['discrete', 'volume'])
                ->default('discrete')
                ->comment('discrete: tablets/capsules, volume: syrups/injections');
            
            // Add volume per unit for volume-based medicines
            $table->decimal('volume_per_unit', 8, 2)
                ->nullable()
                ->after('measurement_type')
                ->comment('Volume in ml per bottle/vial (e.g., 100ml, 200ml)');
            
            // Add unit of measurement
            $table->string('unit_of_measurement', 20)
                ->nullable()
                ->after('volume_per_unit')
                ->comment('ml, tablets, capsules, etc.');
            
            // Add index for better query performance
            $table->index('measurement_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            //
        });
    }
};
