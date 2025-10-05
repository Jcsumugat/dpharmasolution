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
        Schema::table('prescriptions', function (Blueprint $table) {
            // File hash for exact duplicate detection
            $table->string('file_hash', 64)->nullable()->after('file_size');
            $table->index('file_hash'); // Index for fast lookup

            // Perceptual hash for visual similarity detection
            $table->string('perceptual_hash', 64)->nullable()->after('file_hash');
            $table->index('perceptual_hash');

            // OCR extracted text for content matching
            $table->text('extracted_text')->nullable()->after('perceptual_hash');
            $table->fulltext('extracted_text'); // Full-text search index

            // Optional prescription metadata (can be manually entered or OCR extracted)
            $table->string('prescription_number')->nullable()->after('extracted_text');
            $table->string('doctor_name')->nullable()->after('prescription_number');
            $table->date('prescription_issue_date')->nullable()->after('doctor_name');
            $table->date('prescription_expiry_date')->nullable()->after('prescription_issue_date');

            // Duplicate detection status
            $table->enum('duplicate_check_status', ['pending', 'verified', 'duplicate', 'suspicious'])->default('pending')->after('prescription_expiry_date');
            $table->unsignedBigInteger('duplicate_of_id')->nullable()->after('duplicate_check_status'); // References another prescription ID
            $table->decimal('similarity_score', 5, 2)->nullable()->after('duplicate_of_id'); // 0.00 to 100.00
            $table->timestamp('duplicate_checked_at')->nullable()->after('similarity_score');

            // Add foreign key for duplicate_of_id
            $table->foreign('duplicate_of_id')->references('id')->on('prescriptions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['duplicate_of_id']);

            // Drop indexes
            $table->dropIndex(['file_hash']);
            $table->dropIndex(['perceptual_hash']);
            $table->dropFullText(['extracted_text']);

            // Drop columns
            $table->dropColumn([
                'file_hash',
                'perceptual_hash',
                'extracted_text',
                'prescription_number',
                'doctor_name',
                'prescription_issue_date',
                'prescription_expiry_date',
                'duplicate_check_status',
                'duplicate_of_id',
                'similarity_score',
                'duplicate_checked_at'
            ]);
        });
    }
};
