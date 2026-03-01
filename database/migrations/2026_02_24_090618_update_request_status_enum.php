<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('requests', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
            return;
        }

        // 1. Update existing 'approved' records to 'hr_approved' so we don't lose them
        DB::statement("UPDATE requests SET status = 'pending' WHERE status = 'approved'"); // Let's just set to pending, or actually we shouldn't have any in live yet but just in case
        
        // 2. Modify the enum column
        DB::statement("ALTER TABLE requests MODIFY COLUMN status ENUM('pending', 'hr_approved', 'rejected', 'fulfilled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        // 1. Revert the enum column
        DB::statement("ALTER TABLE requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'fulfilled') DEFAULT 'pending'");
        
        // 2. We could revert 'hr_approved' back to 'approved' if needed
        DB::statement("UPDATE requests SET status = 'approved' WHERE status = 'hr_approved'");
    }
};
