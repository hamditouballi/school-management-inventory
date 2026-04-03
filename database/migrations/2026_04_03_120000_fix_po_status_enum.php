<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('pending_initial_approval', 'initial_approved', 'pending_final_approval', 'final_approved', 'rejected', 'partially_delivered', 'delivered', 'invoiced') DEFAULT 'pending_initial_approval'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('pending_initial_approval', 'initial_approved', 'pending_final_approval', 'final_approved', 'rejected', 'partially_delivered', 'delivered', 'invoiced') DEFAULT 'pending_initial_approval'");
    }
};
