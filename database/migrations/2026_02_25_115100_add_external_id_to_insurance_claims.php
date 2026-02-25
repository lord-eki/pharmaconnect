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
            $table->unsignedBigInteger('external_order_id')->nullable()->after('prescription_id');
            $table->foreign('external_order_id')->references('id')->on('external_orders')->nullOnDelete();

            $table->unsignedBigInteger('patient_id')->nullable()->change();

            $table->unsignedBigInteger('prescription_id')->nullable()->change();
        });
    }


};
