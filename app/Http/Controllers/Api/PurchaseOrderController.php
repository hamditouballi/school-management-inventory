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
        return response()->json(PurchaseOrder::with(['purchaseOrderItems.item', 'responsibleStock', 'proposals'])->orderBy('date', 'desc')->get());
    }

    public function store(Request $request)
    {
        // Handle items from JSON string (FormData submission)
        $items = $request->has('items') && is_string($request->input('items')) 
            ? json_decode($request->input('items'), true) 
            : $request->input('items');
        
        $request->merge(['items' => $items]);
        
            $validated = $request->validate([
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|exists:items,id',
            'items.*.new_item_name' => 'required_without:items.*.item_id|string',
            'items.*.unit' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = 0;

            $purchaseOrder = PurchaseOrder::create([
                'date' => $validated['date'],
                'id_responsible_stock' => $request->user()->id,
                'status' => 'pending_initial_approval',
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
                        'price' => 0,
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
                    'unit_price' => 0,
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
        return response()->json($purchaseOrder->load(['purchaseOrderItems.item', 'responsibleStock', 'proposals']));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending_initial_approval') {
            return response()->json(['error' => 'Can only update pending initial approval orders'], 400);
        }

        // Handle items from JSON string (FormData submission)
        $items = $request->has('items') && is_string($request->input('items')) 
            ? json_decode($request->input('items'), true) 
            : $request->input('items');
        
        $request->merge(['items' => $items]);

        $validated = $request->validate([
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|exists:items,id',
            'items.*.new_item_name' => 'required_without:items.*.item_id|string',
            'items.*.unit' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();
        try {
            // Calculate new total
            $totalAmount = 0;

            // Update PO details
            $purchaseOrder->update([
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
                            'price' => 0,
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
                    'unit_price' => 0,
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
            'status' => 'required|in:pending_initial_approval,initial_approved,pending_final_approval,final_approved,rejected,ordered',
        ]);

        $purchaseOrder->update(['status' => $validated['status']]);
        return response()->json($purchaseOrder);
    }

    public function initialApproval(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending_initial_approval') {
            return response()->json(['error' => 'Invalid status for initial approval'], 400);
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,reject'
        ]);

        $status = $validated['action'] === 'approve' ? 'initial_approved' : 'rejected';
        $purchaseOrder->update(['status' => $status]);

        return response()->json($purchaseOrder);
    }

    public function addProposals(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'initial_approved') {
            return response()->json(['error' => 'Order must be initially approved before adding proposals'], 400);
        }

        // Decode JSON if it's sent as string
        $proposals = $request->has('proposals') && is_string($request->input('proposals')) 
            ? json_decode($request->input('proposals'), true) 
            : $request->input('proposals');

        $request->merge(['proposals' => $proposals]);

        $validated = $request->validate([
            'proposals' => 'required|array|min:1',
            'proposals.*.supplier_name' => 'required|string',
            'proposals.*.price' => 'required|numeric|min:0',
            'proposals.*.quality_rating' => 'nullable|string',
            'proposals.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['proposals'] as $proposalData) {
                $purchaseOrder->proposals()->create($proposalData);
            }

            $purchaseOrder->update(['status' => 'pending_final_approval']);
            DB::commit();

            return response()->json($purchaseOrder->load('proposals'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function finalApproval(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending_final_approval') {
            return response()->json(['error' => 'Invalid status for final approval'], 400);
        }

        $validated = $request->validate([
            'proposal_id' => 'required|exists:purchase_order_suppliers,id'
        ]);

        $proposal = $purchaseOrder->proposals()->where('id', $validated['proposal_id'])->first();

        if (!$proposal) {
            return response()->json(['error' => 'Proposal does not belong to this purchase order'], 400);
        }

        DB::beginTransaction();
        try {
            // Mark proposal as selected
            $proposal->update(['is_selected' => true]);

            // Update main PO record with final supplier details
            $purchaseOrder->update([
                'status' => 'final_approved',
                'supplier' => $proposal->supplier_name,
                'total_amount' => $proposal->price
            ]);

            DB::commit();
            return response()->json($purchaseOrder->load('proposals'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending_initial_approval') {
            return response()->json(['error' => 'Cannot delete approved/rejected orders'], 400);
        }

        $purchaseOrder->delete();
        return response()->json(['message' => 'Purchase order deleted successfully']);
    }
}
