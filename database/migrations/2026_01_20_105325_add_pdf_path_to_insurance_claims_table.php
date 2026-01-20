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
        Schema::table('insurance_claims', function (Blueprint $table) {
            $table->string('pdf_path')->nullable()->after('notes');
            $table->string('pdf_generated_at')->nullable()->after('pdf_path');
        });
    }

   
};
