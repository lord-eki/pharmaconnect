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
        DB::statement("ALTER TABLE `payables` MODIFY `payment_method` VARCHAR(50) NULL");
        DB::table('payables')
            ->where('payment_method', 'check')
            ->update(['payment_method' => 'cheque']);

        DB::statement("ALTER TABLE `payables` MODIFY `payment_method` ENUM('mpesa','bank_transfer','cheque') NULL");   
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
