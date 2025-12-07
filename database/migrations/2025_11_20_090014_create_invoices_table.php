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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('supplier');
            $table->text('description');
            $table->decimal('quantity', 10, 2);
            $table->decimal('price', 10, 2);
            $table->date('date');
            $table->foreignId('id_responsible_finance')->constrained('users')->onDelete('cascade');
            $table->foreignId('id_purchase_order_item')->nullable()->constrained('purchase_order_items')->onDelete('set null');
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
