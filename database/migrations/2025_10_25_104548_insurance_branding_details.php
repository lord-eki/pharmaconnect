<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('insurance_providers', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('website');
            $table->text('header_text')->nullable()->after('logo_path');
            $table->text('footer_text')->nullable()->after('header_text');
            $table->string('primary_color')->default('#000000')->after('footer_text');
            $table->string('secondary_color')->default('#666666')->after('primary_color');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_providers', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'header_text',
                'footer_text',
                'primary_color',
                'secondary_color'
            ]);
        });
    }
};