<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
public function up()
{
    Schema::table('prescriptions', function (Blueprint $table) {
        // Modify the existing status enum to include 'completed'
        $table->enum('status', ['pending', 'approved', 'partially_approved', 'declined', 'completed'])
              ->default('pending')
              ->change();
        
        // Add completed_at timestamp
        $table->timestamp('completed_at')->nullable()->after('updated_at');
    });
}

public function down()
{
    Schema::table('prescriptions', function (Blueprint $table) {
        // Revert status enum to original values
        $table->enum('status', ['pending', 'approved', 'partially_approved', 'declined'])
              ->default('pending')
              ->change();
        
        // Drop completed_at column
        $table->dropColumn('completed_at');
    });
}
};
