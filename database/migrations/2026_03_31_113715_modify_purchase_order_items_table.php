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
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->string('state')->default('pending')->after('quantity');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('state');
            $table->renameColumn('quantity', 'init_quantity');
            $table->decimal('final_quantity', 10, 2)->nullable()->after('init_quantity');
            $table->foreignId('proposition_id')->nullable()->constrained('propositions')->onDelete('set null')->after('final_quantity');
            $table->decimal('unit_price', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_order_items', 'proposition_id')) {
                $table->dropForeign(['proposition_id']);
                $table->dropColumn('proposition_id');
            }
            if (Schema::hasColumn('purchase_order_items', 'final_quantity')) {
                $table->dropColumn('final_quantity');
            }
            if (Schema::hasColumn('purchase_order_items', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('purchase_order_items', 'state')) {
                $table->dropColumn('state');
            }
            if (Schema::hasColumn('purchase_order_items', 'init_quantity')) {
                $table->renameColumn('init_quantity', 'quantity');
            }
        });
    }
};
