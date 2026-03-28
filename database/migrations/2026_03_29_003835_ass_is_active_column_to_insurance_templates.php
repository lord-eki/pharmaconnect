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
        if (! Schema::hasColumn('insurance_form_templates', 'is_active')) {
            Schema::table('insurance_form_templates', function (Blueprint $table) {
                $table->boolean('is_active')->default(false);
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_form_templates', function (Blueprint $table) {
            //
        });
    }
};
