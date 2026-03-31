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
        Schema::create('proposition_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('proposition_order')->default(0);
            $table->timestamps();

            $table->index(['purchase_order_id', 'proposition_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposition_groups');
    }
};
