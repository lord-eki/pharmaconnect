<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `payments` MODIFY `payment_method` ENUM('mpesa','card','bank_transfer','cash','insurance','cheque') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `payments` MODIFY `payment_method` ENUM('mpesa','card','bank_transfer','cash',insurance') NOT NULL");
    }
};
