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
        // Document Categories Table
        Schema::create('document_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });

        // Documents Table
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->unique();
            $table->foreignId('category_id')->constrained('document_categories');
            $table->string('document_type'); // claim_form, invoice, delivery_note, receipt, credit_note, prescription, etc.
            $table->string('title');
            $table->text('description')->nullable();

            // File Information
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->string('file_hash')->nullable(); // for duplicate detection

            // Related Entities
            $table->foreignId('prescription_id')->nullable()->constrained('prescriptions')->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('insurance_claim_id')->nullable()->constrained('insurance_claims')->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('insurance_provider_id')->nullable()->constrained('insurance_providers')->onDelete('set null');
            $table->foreignId('patient_id')->nullable()->constrained('patients')->onDelete('set null');

            // Upload Information
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamp('uploaded_at');

            // Verification
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();

            // Document Status
            $table->enum('status', ['active', 'archived', 'deleted'])->default('active');
            $table->boolean('is_locked')->default(false);

            // Metadata
            $table->json('metadata')->nullable(); 
            $table->json('tags')->nullable();

            // Version Control
            $table->integer('version')->default(1);
            $table->foreignId('parent_document_id')->nullable()->constrained('documents')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('document_number');
            $table->index('document_type');
            $table->index(['prescription_id', 'order_id', 'insurance_claim_id']);
            $table->index('uploaded_by');
            $table->index('verification_status');
            $table->index('status');
            $table->index('uploaded_at');
            $table->index('file_hash');
        });

        // Document Access Logs Table
        Schema::create('document_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->string('action'); // view, download, print, share, edit, delete
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('accessed_at');
            $table->timestamps();

            $table->index(['document_id', 'user_id']);
            $table->index('action');
            $table->index('accessed_at');
        });

        // Document Shares Table
        Schema::create('document_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('shared_by')->constrained('users');
            $table->foreignId('shared_with')->constrained('users');
            $table->enum('permission', ['view', 'download', 'edit'])->default('view');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'shared_with']);
            $table->index('shared_by');
            $table->index('is_active');
        });

        // Document Comments Table
        Schema::create('document_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->text('comment');
            $table->foreignId('parent_id')->nullable()->constrained('document_comments')->onDelete('cascade');
            $table->timestamps();

            $table->index('document_id');
            $table->index('user_id');
        });

        // Document Version History Table
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->integer('version_number');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->foreignId('created_by')->constrained('users');
            $table->text('change_notes')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'version_number']);
        });

        // Claim Forms Table for online claim capture
        Schema::create('claim_forms', function (Blueprint $table) {
            $table->id();
            $table->string('form_number')->unique();
            $table->foreignId('prescription_id')->constrained('prescriptions');
            $table->foreignId('insurance_provider_id')->constrained('insurance_providers');
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('physician_id')->constrained('users');

            // Form Data
            $table->json('form_data'); 
            $table->text('diagnosis');
            $table->text('treatment_notes')->nullable();

            // Submission Details
            $table->enum('submission_type', ['online', 'manual'])->default('online');
            $table->enum('status', ['draft', 'submitted', 'processing', 'approved', 'rejected'])->default('draft');
            $table->timestamp('submitted_at')->nullable();

            // Attached Document
            $table->foreignId('document_id')->nullable()->constrained('documents')->onDelete('set null');

            // Signatures for digital signatures
            $table->text('physician_signature')->nullable();
            $table->text('patient_signature')->nullable();
            $table->timestamp('signed_at')->nullable();

            $table->timestamps();

            $table->index('form_number');
            $table->index('prescription_id');
            $table->index('insurance_provider_id');
            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edbms_management_tables');
    }
};
