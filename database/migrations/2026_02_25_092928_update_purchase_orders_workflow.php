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
        // Update existing statuses to the initial step (pending_hr -> pending_initial_approval)
        DB::table('purchase_orders')->where('status', 'pending_hr')->update(['status' => 'pending_initial_approval']);
        DB::table('purchase_orders')->where('status', 'approved_hr')->update(['status' => 'initial_approved']);
        // If there were any rejected, they map cleanly to just rejected
        DB::table('purchase_orders')->where('status', 'rejected_hr')->update(['status' => 'rejected']);
        
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->string('status')->default('pending_initial_approval')->change();
            });
        } else {
            // Alter the ENUM
            DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('pending_initial_approval', 'initial_approved', 'pending_final_approval', 'final_approved', 'rejected', 'ordered') DEFAULT 'pending_initial_approval'");
        }

        // Make supplier nullable
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('supplier')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('purchase_orders')->where('status', 'pending_initial_approval')->update(['status' => 'pending_hr']);
        DB::table('purchase_orders')->where('status', 'initial_approved')->update(['status' => 'approved_hr']);
        DB::table('purchase_orders')->where('status', 'rejected')->update(['status' => 'rejected_hr']);

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('supplier')->nullable()->change();
        });

        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('pending_hr', 'approved_hr', 'rejected_hr', 'ordered') DEFAULT 'pending_hr'");
    }
};
