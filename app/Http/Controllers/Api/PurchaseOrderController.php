<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return response()->json(PurchaseOrder::with(['purchaseOrderItems.item', 'responsibleStock'])->orderBy('date', 'desc')->get());
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
            'items.*.new_item_name' => 'required_without:items.*.item_id|string',
            'items.*.unit' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = collect($validated['items'])->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            $purchaseOrder = PurchaseOrder::create([
                'supplier' => $validated['supplier'],
                'date' => $validated['date'],
                'id_responsible_stock' => $request->user()->id,
                'status' => 'pending_hr',
                'total_amount' => $totalAmount,
            ]);

            foreach ($validated['items'] as $index => $itemData) {
                $itemId = $itemData['item_id'] ?? null;
                
                // If new item name is provided, create item in inventory with quantity 0
                if (!$itemId && isset($itemData['new_item_name'])) {
                    $itemImagePath = null;
                    $imageField = "item_image_{$index}";
                    if ($request->hasFile($imageField)) {
                        $itemImagePath = $request->file($imageField)->store('items', 'public');
                    }
                    
                    $newItem = \App\Models\Item::create([
                        'designation' => $itemData['new_item_name'],
                        'description' => '',
                        'quantity' => 0,
                        'price' => $itemData['unit_price'],
                        'unit' => $itemData['unit'] ?? 'unit',
                        'low_stock_threshold' => 50,
                        'image_path' => $itemImagePath,
                    ]);
                    $itemId = $newItem->id;
                }
                
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'item_id' => $itemId,
                    'new_item_name' => $itemData['new_item_name'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                ]);
            }

            DB::commit();
            return response()->json($purchaseOrder->load('purchaseOrderItems.item'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return response()->json($purchaseOrder->load(['purchaseOrderItems.item', 'responsibleStock']));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending_hr') {
            return response()->json(['error' => 'Can only update pending purchase orders'], 400);
        }

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
            'items.*.new_item_name' => 'required_without:items.*.item_id|string',
            'items.*.unit' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Calculate new total
            $totalAmount = collect($validated['items'])->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            // Update PO details
            $purchaseOrder->update([
                'supplier' => $validated['supplier'],
                'date' => $validated['date'],
                'total_amount' => $totalAmount,
            ]);

            // Delete old items
            $purchaseOrder->purchaseOrderItems()->delete();

            // Create new items
            foreach ($validated['items'] as $index => $itemData) {
                $itemId = $itemData['item_id'] ?? null;
                
                // If new item name is provided and item doesn't exist yet, create it
                if (!$itemId && isset($itemData['new_item_name'])) {
                    // Check if item was already created
                    $existingItem = \App\Models\Item::where('designation', $itemData['new_item_name'])->first();
                    if ($existingItem) {
                        $itemId = $existingItem->id;
                        
                        // Update image if new one provided
                        $imageField = "item_image_{$index}";
                        if ($request->hasFile($imageField)) {
                            $existingItem->update([
                                'image_path' => $request->file($imageField)->store('items', 'public')
                            ]);
                        }
                    } else {
                        $itemImagePath = null;
                        $imageField = "item_image_{$index}";
                        if ($request->hasFile($imageField)) {
                            $itemImagePath = $request->file($imageField)->store('items', 'public');
                        }
                        
                        $newItem = \App\Models\Item::create([
                            'designation' => $itemData['new_item_name'],
                            'description' => '',
                            'quantity' => 0,
                            'price' => $itemData['unit_price'],
                            'unit' => $itemData['unit'] ?? 'unit',
                            'low_stock_threshold' => 50,
                            'image_path' => $itemImagePath,
                        ]);
                        $itemId = $newItem->id;
                    }
                }
                
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'item_id' => $itemId,
                    'new_item_name' => $itemData['new_item_name'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                ]);
            }

            DB::commit();
            return response()->json($purchaseOrder->load('purchaseOrderItems.item'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending_hr,approved_hr,rejected_hr,ordered',
        ]);

        $purchaseOrder->update(['status' => $validated['status']]);
        return response()->json($purchaseOrder);
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending_hr') {
            return response()->json(['error' => 'Cannot delete approved/rejected orders'], 400);
        }

        $purchaseOrder->delete();
        return response()->json(['message' => 'Purchase order deleted successfully']);
    }
}
