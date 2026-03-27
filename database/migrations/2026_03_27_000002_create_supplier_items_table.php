<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();

            $table->unique(['supplier_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_items');
    }
};
