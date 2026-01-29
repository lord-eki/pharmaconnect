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
            $table->foreignId('quotation_id')->nullable()->change();
            $table->foreignId('prescription_id')->nullable()->change();
        });

        Schema::table('order_items', function(Blueprint  $table){
            $table->foreignId('quotation_item_id')->nullable()->change();
        });
    }

    
};
