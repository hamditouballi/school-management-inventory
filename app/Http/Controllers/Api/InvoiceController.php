<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        return response()->json(Invoice::with(['responsibleFinance', 'purchaseOrderItem.item', 'invoiceItems'])->orderBy('date', 'desc')->get());
    }

    public function store(Request $request)
    {
        // Handle items from JSON string (FormData submission)
        $items = $request->has('items') && is_string($request->input('items')) 
            ? json_decode($request->input('items'), true) 
            : $request->input('items');
        
        $request->merge(['items' => $items]);
        
        $validated = $request->validate([
            'supplier' => 'required|string',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|exists:items,id',
            'items.*.item_name' => 'required|string',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'id_purchase_order_item' => 'nullable|exists:purchase_order_items,id',
            'file_path' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        \DB::beginTransaction();
        try {
            $invoiceData = [
                'supplier' => $validated['supplier'],
                'date' => $validated['date'],
                'id_responsible_finance' => $request->user()->id,
                'id_purchase_order' => $validated['purchase_order_id'] ?? null,
                'id_purchase_order_item' => $validated['id_purchase_order_item'] ?? null,
                'file_path' => $validated['file_path'] ?? null,
            ];

            if ($request->hasFile('image')) {
                $invoiceData['image_path'] = $request->file('image')->store('invoices', 'public');
            }

            $invoice = Invoice::create($invoiceData);

            // Create invoice items and add/update inventory
            foreach ($validated['items'] as $index => $itemData) {
                // Handle per-item image upload
                $imagePath = null;
                $imageField = "item_image_{$index}";
                if ($request->hasFile($imageField)) {
                    $imagePath = $request->file($imageField)->store('invoice_items', 'public');
                }
                
                // Create invoice item
                \App\Models\InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_name' => $itemData['item_name'],
                    'description' => $itemData['description'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'] ?? 'unit',
                    'unit_price' => $itemData['unit_price'],
                    'image_path' => $imagePath,
                ]);
                
                // Add/update item in items table
                if (isset($itemData['item_id']) && $itemData['item_id']) {
                    // Existing item - update quantity
                    $existingItem = \App\Models\Item::find($itemData['item_id']);
                    if ($existingItem) {
                        $existingItem->increment('quantity', $itemData['quantity']);
                        // Update image if new one provided
                        if ($imagePath) {
                            $existingItem->update(['image_path' => $imagePath]);
                        }
                    }
                } else {
                    // New item - check by name first
                    $existingItem = \App\Models\Item::where('designation', $itemData['item_name'])->first();
                    
                    if ($existingItem) {
                        // Item with same name exists - increase quantity
                        $existingItem->increment('quantity', $itemData['quantity']);
                        if ($imagePath) {
                            $existingItem->update(['image_path' => $imagePath]);
                        }
                    } else {
                        // Create completely new item
                        \App\Models\Item::create([
                            'designation' => $itemData['item_name'],
                            'description' => $itemData['description'] ?? null,
                            'quantity' => $itemData['quantity'],
                            'price' => $itemData['unit_price'],
                            'unit' => $itemData['unit'] ?? 'unit',
                            'low_stock_threshold' => 50,
                            'image_path' => $imagePath,
                        ]);
                    }
                }
            }

            \DB::commit();
            return response()->json($invoice->load(['responsibleFinance', 'purchaseOrderItem', 'invoiceItems']), 201);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(Invoice $invoice)
    {
        return response()->json($invoice->load(['responsibleFinance', 'purchaseOrderItem.item', 'invoiceItems']));
    }

    public function update(Request $request, Invoice $invoice)
    {
        // Handle items from JSON string (FormData submission)
        $items = $request->has('items') && is_string($request->input('items')) 
            ? json_decode($request->input('items'), true) 
            : $request->input('items');
        
        $request->merge(['items' => $items]);
        
        $validated = $request->validate([
            'supplier' => 'required|string',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|exists:items,id',
            'items.*.item_name' => 'required|string',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        \DB::beginTransaction();
        try {
            // Update invoice basic info
            $invoice->update([
                'supplier' => $validated['supplier'],
                'date' => $validated['date'],
            ]);

            // Handle main invoice image
            if ($request->hasFile('image')) {
                $invoice->update(['image_path' => $request->file('image')->store('invoices', 'public')]);
            }

            // Delete old invoice items
            $invoice->invoiceItems()->delete();

            // Create new invoice items
            foreach ($validated['items'] as $index => $itemData) {
                // Handle per-item image upload
                $imagePath = null;
                $imageField = "item_image_{$index}";
                if ($request->hasFile($imageField)) {
                    $imagePath = $request->file($imageField)->store('invoice_items', 'public');
                }
                
                // Create invoice item
                \App\Models\InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_name' => $itemData['item_name'],
                    'description' => $itemData['description'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'] ?? 'unit',
                    'unit_price' => $itemData['unit_price'],
                    'image_path' => $imagePath,
                ]);
            }

            \DB::commit();
            return response()->json($invoice->load(['responsibleFinance', 'invoiceItems']));
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return response()->json(['message' => 'Invoice deleted successfully']);
    }
}
