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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->json('parameters');
            $table->string('file_path')->nullable();
            $table->integer('file_size')->nullable();
            $table->enum('status', ['generating', 'completed', 'failed'])->default('generating');
            $table->foreignId('generated_by')->constrained('users');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('download_count')->default(0);
            $table->timestamps();

            $table->index(['type']);
            $table->index(['status']);
            $table->index(['generated_by']);
            $table->index(['generated_at']);
            $table->index(['expires_at']);
        });
    }

};
