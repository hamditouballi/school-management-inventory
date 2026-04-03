<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['responsibleFinance', 'purchaseOrderItem.item', 'invoiceItems'])
            ->orderBy('date', 'desc');

        if ($request->has('purchase_order_id')) {
            $query->where('id_purchase_order', $request->purchase_order_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        // Handle items from JSON string (FormData submission)
        $items = $request->has('items') && is_string($request->input('items'))
            ? json_decode($request->input('items'), true)
            : $request->input('items');

        // Handle bon_de_livraison_ids from JSON string
        $bonDeLivraisonIds = $request->has('bon_de_livraison_ids') && is_string($request->input('bon_de_livraison_ids'))
            ? json_decode($request->input('bon_de_livraison_ids'), true)
            : $request->input('bon_de_livraison_ids');

        $request->merge(['items' => $items, 'bon_de_livraison_ids' => $bonDeLivraisonIds]);

        // Validation rules
        $validationRules = [
            'supplier' => 'nullable|string',
            'type' => 'required|in:incoming,return',
            'date' => 'required|date',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'id_purchase_order_item' => 'nullable|exists:purchase_order_items,id',
            'file_path' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'bon_de_livraison_ids' => 'nullable|array',
            'bon_de_livraison_ids.*' => 'exists:bon_de_livraisons,id',
        ];

        // If no bon_de_livraison_ids, require items (legacy/manual mode)
        if (empty($bonDeLivraisonIds)) {
            $validationRules['items'] = 'required|array|min:1';
            $validationRules['items.*.item_name'] = 'required_without:items.*.item_id|string';
            $validationRules['items.*.item_id'] = 'nullable|exists:items,id';
            $validationRules['items.*.description'] = 'nullable|string';
            $validationRules['items.*.quantity'] = 'required|numeric|min:0.01';
            $validationRules['items.*.unit'] = 'nullable|string';
            $validationRules['items.*.unit_price'] = 'required|numeric|min:0';
            $validationRules['items.*.image_path'] = 'nullable|string';
        }

        $validated = $request->validate($validationRules);

        // Check if bon de livraison IDs are already used in other invoices
        if (! empty($bonDeLivraisonIds)) {
            $alreadyUsed = [];
            $allInvoices = Invoice::whereNotNull('bon_de_livraison_ids')->get();

            foreach ($allInvoices as $existingInvoice) {
                $existingIds = is_string($existingInvoice->bon_de_livraison_ids)
                    ? json_decode($existingInvoice->bon_de_livraison_ids, true)
                    : $existingInvoice->bon_de_livraison_ids;

                $intersection = array_intersect($bonDeLivraisonIds, $existingIds ?? []);
                if (! empty($intersection)) {
                    $alreadyUsed = array_merge($alreadyUsed, $intersection);
                }
            }

            if (! empty($alreadyUsed)) {
                return response()->json([
                    'error' => 'Delivery note(s) already linked to another invoice',
                    'already_used_ids' => array_values(array_unique($alreadyUsed)),
                ], 422);
            }
        }

        \DB::beginTransaction();
        try {
            $invoiceData = [
                'type' => $validated['type'],
                'supplier' => $validated['supplier'] ?? null,
                'date' => $validated['date'],
                'id_responsible_finance' => $request->user()->id,
                'id_purchase_order' => $validated['purchase_order_id'] ?? null,
                'id_purchase_order_item' => $validated['id_purchase_order_item'] ?? null,
                'file_path' => $validated['file_path'] ?? null,
            ];

            // Store bon de livraison IDs
            if (! empty($bonDeLivraisonIds)) {
                $invoiceData['bon_de_livraison_ids'] = $bonDeLivraisonIds;

                // Auto-fill supplier from bon de livraison if not provided
                if (empty($invoiceData['supplier'])) {
                    $firstBonDeLivraison = \App\Models\BonDeLivraison::with('purchaseOrder.supplier')
                        ->find($bonDeLivraisonIds[0]);
                    if ($firstBonDeLivraison && $firstBonDeLivraison->purchaseOrder) {
                        $invoiceData['supplier'] = $firstBonDeLivraison->purchaseOrder->supplier?->name
                            ?? $firstBonDeLivraison->purchaseOrder->supplier
                            ?? null;
                    }
                }
            }

            // Update PO status from 'delivered' to 'invoiced'
            if (isset($validated['purchase_order_id']) && $validated['purchase_order_id']) {
                $purchaseOrder = \App\Models\PurchaseOrder::find($validated['purchase_order_id']);
                if ($purchaseOrder && $purchaseOrder->status === 'delivered') {
                    $purchaseOrder->update(['status' => 'invoiced']);
                }
            }

            if ($request->hasFile('image')) {
                $invoiceData['image_path'] = $request->file('image')->store('invoices', 'public');
            }

            $invoice = Invoice::create($invoiceData);

            // If linked to bon de livraisons, create invoice items from them
            if (! empty($bonDeLivraisonIds)) {
                $bonDeLivraisons = \App\Models\BonDeLivraison::with([
                    'items.purchaseOrderItem.item',
                    'purchaseOrder.supplier',
                ])->whereIn('id', $bonDeLivraisonIds)->get();

                foreach ($bonDeLivraisons as $bonDeLivraison) {
                    foreach ($bonDeLivraison->items as $bdlItem) {
                        $poItem = $bdlItem->purchaseOrderItem;
                        $item = $poItem?->item;

                        // Create invoice item
                        \App\Models\InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'item_name' => $item?->designation ?? $poItem?->new_item_name ?? 'Unknown Item',
                            'description' => $item?->description ?? null,
                            'quantity' => $bdlItem->quantity,
                            'unit' => $item?->unit ?? 'unit',
                            'unit_price' => $poItem?->unit_price ?? 0,
                            'image_path' => $item?->image_path ?? null,
                        ]);

                        // Add/update item in items table (only for incoming type)
                        if ($validated['type'] === 'incoming' && $item) {
                            $item->increment('quantity', $bdlItem->quantity);
                        } elseif ($validated['type'] === 'return' && $item) {
                            $item->decrement('quantity', $bdlItem->quantity);
                        }
                    }
                }
            } else {
                // Legacy/manual mode - create invoice items from submitted data
                foreach ($validated['items'] as $index => $itemData) {
                    $imagePath = $itemData['image_path'] ?? null;
                    $imageField = "item_image_{$index}";
                    if ($request->hasFile($imageField)) {
                        $imagePath = $request->file($imageField)->store('items', 'public');
                    }

                    \App\Models\InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'item_name' => $itemData['item_name'] ?? (\App\Models\Item::find($itemData['item_id'] ?? null)?->designation ?? 'Unknown Item'),
                        'description' => $itemData['description'] ?? null,
                        'quantity' => $itemData['quantity'],
                        'unit' => $itemData['unit'] ?? 'unit',
                        'unit_price' => $itemData['unit_price'],
                        'image_path' => $imagePath,
                    ]);

                    if (isset($itemData['item_id']) && $itemData['item_id']) {
                        $existingItem = \App\Models\Item::find($itemData['item_id']);
                        if ($existingItem) {
                            if ($validated['type'] === 'return') {
                                $existingItem->decrement('quantity', $itemData['quantity']);
                            } else {
                                $existingItem->increment('quantity', $itemData['quantity']);
                            }
                            if ($imagePath) {
                                $existingItem->update(['image_path' => $imagePath]);
                            }
                        }
                    } else {
                        $existingItem = \App\Models\Item::where('designation', $itemData['item_name'])->first();

                        if ($existingItem) {
                            if ($validated['type'] === 'return') {
                                $existingItem->decrement('quantity', $itemData['quantity']);
                            } else {
                                $existingItem->increment('quantity', $itemData['quantity']);
                            }
                            if ($imagePath) {
                                $existingItem->update(['image_path' => $imagePath]);
                            }
                        } else {
                            $initialQuantity = $validated['type'] === 'return' ? -$itemData['quantity'] : $itemData['quantity'];
                            \App\Models\Item::create([
                                'designation' => $itemData['item_name'],
                                'description' => $itemData['description'] ?? null,
                                'quantity' => $initialQuantity,
                                'price' => $itemData['unit_price'],
                                'unit' => $itemData['unit'] ?? 'unit',
                                'low_stock_threshold' => 50,
                                'image_path' => $imagePath,
                            ]);
                        }
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
            'type' => 'required|in:incoming,return',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|exists:items,id',
            'items.*.item_name' => 'required|string',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.image_path' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        \DB::beginTransaction();
        try {
            // Update invoice basic info
            $invoice->update([
                'supplier' => $validated['supplier'],
                'type' => $validated['type'],
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
                $imagePath = $itemData['image_path'] ?? null;
                $imageField = "item_image_{$index}";
                if ($request->hasFile($imageField)) {
                    $imagePath = $request->file($imageField)->store('items', 'public');
                }

                // Create invoice item
                \App\Models\InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_name' => $itemData['item_name'] ?? (\App\Models\Item::find($itemData['item_id'] ?? null)?->designation ?? 'Unknown Item'),
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
