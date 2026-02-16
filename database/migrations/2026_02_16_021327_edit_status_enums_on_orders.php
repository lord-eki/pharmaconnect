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
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'mysql') {
            // For MySQL, we need to alter the ENUM
            DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM(
                'pending_review',
                'sent_to_supplier',
                'pending',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
                'pending_reassignment',
                'needs_manual_assignment'
            ) NOT NULL DEFAULT 'pending_review'");
        } else {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('status', 50)->default('pending_review')->change();
            });
        }
    }

   
};
