<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
             $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); 
            $table->string('group')->default('general'); 
            $table->string('label')->nullable();       
            $table->text('description')->nullable();
            $table->timestamps();
        });

         DB::table('settings')->insert([
            'key'         => 'delivery_fee',
            'value'       => '100.00',
            'type'        => 'float',
            'group'       => 'delivery',
            'label'       => 'Delivery Fee (KES)',
            'description' => 'Flat delivery fee charged per prescription. Appears as the last line item on every order sent to the first supplier.',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

};
