<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('pending_initial_approval', 'initial_approved', 'pending_final_approval', 'final_approved', 'rejected', 'ordered', 'delivered', 'invoiced') DEFAULT 'pending_initial_approval'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('pending_initial_approval', 'initial_approved', 'pending_final_approval', 'final_approved', 'rejected', 'ordered', 'delivered') DEFAULT 'pending_initial_approval'");
    }
};
