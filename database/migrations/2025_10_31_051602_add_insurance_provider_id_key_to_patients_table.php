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
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('insurance_provider_id')->nullable()->after('physician_id')->constrained('insurance_providers')->nullOnDelete();

            $table->index('insurance_provider_id');
        });
    }

};
