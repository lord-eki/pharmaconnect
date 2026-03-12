<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        if (! Schema::hasTable('insurance_form_templates')) {

            Schema::create('insurance_form_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('insurance_provider_id')
                    ->constrained('insurance_providers')
                    ->onDelete('cascade');
                $table->string('template_name');
                $table->string('template_path')->nullable();
                $table->string('template_type')->default('pdf'); // pdf, docx, html
                $table->json('template_config')->nullable();
                $table->string('version')->default('1.0');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_form_templates');
    }
};
