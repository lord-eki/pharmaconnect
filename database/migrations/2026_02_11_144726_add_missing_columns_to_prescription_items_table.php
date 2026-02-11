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
            if (!Schema::hasColumn('prescription_items', 'dose_amount')) {
                $table->decimal('dose_amount', 10, 2)->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('prescription_items', 'frequency_per_day')) {
                $table->integer('frequency_per_day')->nullable()->after('dose_amount')->nullable();
            }
            if (!Schema::hasColumn('prescription_items', 'duration_days')) {
                $table->integer('duration_days')->nullable()->after('frequency_per_day');
            }
            if (!Schema::hasColumn('prescription_items', 'total_volume_required')) {
                $table->decimal('total_volume_required', 10, 2)->nullable()->after('duration_days');
            }
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
