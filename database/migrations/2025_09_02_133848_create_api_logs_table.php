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
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method');
            $table->string('url');
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->integer('response_status')->nullable();
            $table->integer('response_time')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['method']);
            $table->index(['response_status']);
            $table->index(['created_at']);
            $table->index(['url']);
        });
    }


};
