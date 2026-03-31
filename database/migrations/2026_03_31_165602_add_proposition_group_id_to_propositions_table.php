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
        Schema::table('propositions', function (Blueprint $table) {
            $table->uuid('proposition_group_id')->nullable()->after('purchase_order_id');
            $table->unsignedInteger('proposition_order')->default(0)->after('proposition_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propositions', function (Blueprint $table) {
            $table->dropColumn(['proposition_group_id', 'proposition_order']);
        });
    }
};
