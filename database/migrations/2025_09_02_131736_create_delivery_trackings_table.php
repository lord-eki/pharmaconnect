<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy', 6, 2)->nullable();
            $table->decimal('speed', 6, 2)->nullable();
            $table->integer('heading')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['delivery_id']);
            $table->index(['recorded_at']);
            $table->index(['latitude', 'longitude']);
        });
    }

};
