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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('rule_type', ['markup_percentage', 'fixed_amount', 'tier_based', 'volume_discount']);
            $table->json('conditions');
            $table->decimal('markup_percentage', 5, 2)->nullable();
            $table->decimal('fixed_amount', 8, 2)->nullable();
            $table->decimal('minimum_margin', 5, 2)->nullable();
            $table->decimal('maximum_margin', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->index(['rule_type']);
            $table->index(['is_active']);
            $table->index(['priority']);
            $table->index(['valid_from', 'valid_until']);
        });
    }

};
