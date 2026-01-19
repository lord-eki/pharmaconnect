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

                $table->dropColumn((['logo', 'header', 'footer']));

            if (! Schema::hasColumn('insurance_providers', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('website');
            }
            if (! Schema::hasColumn('insurance_providers', 'header_text')) {
                $table->text('header_text')->nullable()->after('logo_path');
            }
            if (! Schema::hasColumn('insurance_providers', 'footer_text')) {
                $table->text('footer_text')->nullable()->after('header_text');
            }
            if (! Schema::hasColumn('insurance_providers', 'primary_color')) {
                $table->string('primary_color')->default('#000000')->after('footer_text');
            }
            if (! Schema::hasColumn('insurance_providers', 'secondary_color')) {
                $table->string('secondary_color')->default('#666666')->after('primary_color');
            }
        });
    }

};
