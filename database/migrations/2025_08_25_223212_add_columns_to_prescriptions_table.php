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
            $table->boolean('is_encrypted')->default(false)->after('id');
            $table->string('original_filename')->nullable()->after('is_encrypted');
            $table->string('file_mime_type')->nullable()->after('original_filename');
            $table->bigInteger('file_size')->nullable()->after('file_mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn([
                'is_encrypted',
                'original_filename',
                'file_mime_type',
                'file_size',
            ]);
        });
    }
};
