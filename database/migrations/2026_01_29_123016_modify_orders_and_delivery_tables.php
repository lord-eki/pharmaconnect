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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('external_order_id')->nullable()->after('prescription_id')->constrained()->nullOnDelete();
            $table->index('external_order_id');
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->foreignId('external_order_id')->nullable()->after('prescription_id')->constrained()->nullOnDelete();
            $table->index('external_order_id');
        });

    }

 
};
