<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('prescriptions', function (Blueprint $table) {
        // Remove this line if admin_message already exists
        // $table->text('admin_message')->nullable()->after('qr_code_path');

        // Keep only this if not yet added
        if (!Schema::hasColumn('prescriptions', 'qr_code_path')) {
            $table->string('qr_code_path')->nullable()->after('file_path');
        }
    });
}

public function down(): void {
    Schema::table('prescriptions', function (Blueprint $table) {
        $table->dropColumn('qr_code_path');
        // Do not drop admin_message if it wasn't added here
        // $table->dropColumn('admin_message');
    });
}

};
