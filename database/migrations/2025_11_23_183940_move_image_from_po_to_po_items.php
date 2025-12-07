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
        // Add image_path to purchase_order_items
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('unit_price');
        });
        
        // Remove image_path from purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore image_path to purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('total_amount');
        });
        
        // Remove image_path from purchase_order_items
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
