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
        Schema::table('payables', function (Blueprint $table) {
        DB::table('payables')
            ->where('payment_method', 'check')
            ->update(['payment_method' => 'cheque']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            //
        });
    }
};
