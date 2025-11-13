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
            $table->decimal('supplier_price', 10, 2)->nullable()->after('unit_price'); // Price from supplier
            $table->decimal('markup_amount', 10, 2)->default(0)->after('supplier_price'); // Markup added
            $table->decimal('supplier_total', 10, 2)->nullable()->after('total_price');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('supplier_price', 10, 2)->nullable()->after('unit_price'); // Price supplier gets
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('supplier_total', 10, 2)->nullable()->after('total_amount'); // What we pay supplier
            $table->decimal('markup_total', 10, 2)->default(0)->after('supplier_total'); // Total markup
            // total_amount already exists - this is what insurance/patient sees
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
