<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;

test('invoice calculates total amount correctly from items', function () {
    $invoice = Invoice::factory()->create();
    
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'quantity' => 2,
        'unit_price' => 50, // Subtotal 100
    ]);
    
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'quantity' => 3,
        'unit_price' => 20, // Subtotal 60
    ]);

    // Refresh model to load relationships
    $invoice->load('invoiceItems');
    
    expect((float)$invoice->total_amount)->toBe(160.0);
});

test('invoice has a type cast for date', function () {
    $invoice = Invoice::factory()->create(['date' => '2025-01-01']);
    
    expect($invoice->date)->toBeInstanceOf(Illuminate\Support\Carbon::class);
});
