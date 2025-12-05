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
        Schema::table('insurance_providers', function (Blueprint $table) {
            $table->dropColumn(['form_header', 'form_footer', 'required_fields','logo_path','form_template']);
            $table->json('template')->nullable();
            $table->string('logo')->nullable();
            $table->text('header')->nullable();
            $table->text('footer')->nullable();
            $table->json('fields_required')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_providers', function (Blueprint $table) {
            //
        });
    }
};
