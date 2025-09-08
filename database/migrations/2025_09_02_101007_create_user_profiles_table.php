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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('license_number')->nullable();
            $table->date('license_expiry_date')->nullable();
            $table->string('practice_name')->nullable();
            $table->text('practice_address')->nullable();
            $table->string('county')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('specialization')->nullable();
            $table->integer('years_experience')->nullable();
            $table->string('document_path')->nullable();
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('preferences')->nullable();
            $table->timestamps();

            $table->index(['license_number']);
            $table->index(['verification_status']);
            $table->index(['county', 'city']);
        });
    }

};
