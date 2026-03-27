<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\PurchaseOrderFinalApproved;
use App\Notifications\PurchaseOrderInitialApproved;
use App\Notifications\PurchaseOrderInitialRejected;
use App\Notifications\PurchaseOrderNeedsFinalApproval;
use App\Notifications\PurchaseOrderNeedsInitialApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return response()->json(PurchaseOrder::with(['purchaseOrderItems.item', 'responsibleStock', 'supplier', 'parent', 'children.supplier', 'children.purchaseOrderItems.item'])->orderBy('date', 'desc')->get());
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
                if (! $itemId && isset($itemData['new_item_name'])) {
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

            $hrManagers = User::where('role', 'hr_manager')->get();
            if ($hrManagers->isNotEmpty()) {
                Notification::send($hrManagers, new PurchaseOrderNeedsInitialApproval($purchaseOrder));
            }

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
                if (! $itemId && isset($itemData['new_item_name'])) {
                    // Check if item was already created
                    $existingItem = \App\Models\Item::where('designation', $itemData['new_item_name'])->first();
                    if ($existingItem) {
                        $itemId = $existingItem->id;

                        // Update image if new one provided
                        $imageField = "item_image_{$index}";
                        if ($request->hasFile($imageField)) {
                            $existingItem->update([
                                'image_path' => $request->file($imageField)->store('items', 'public'),
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
            'action' => 'required|in:approve,reject',
        ]);

        $status = $validated['action'] === 'approve' ? 'initial_approved' : 'rejected';
        $purchaseOrder->update(['status' => $status]);

        $stockManager = $purchaseOrder->responsibleStock;

        if ($validated['action'] === 'approve') {
            $financeManagers = User::where('role', 'finance_manager')->get();
            if ($financeManagers->isNotEmpty()) {
                Notification::send($financeManagers, new PurchaseOrderNeedsFinalApproval($purchaseOrder));
            }
        }

        if ($stockManager) {
            if ($validated['action'] === 'approve') {
                Notification::send($stockManager, new PurchaseOrderInitialApproved($purchaseOrder, $request->user()->name));
            } else {
                Notification::send($stockManager, new PurchaseOrderInitialRejected($purchaseOrder, $request->user()->name));
            }
        }

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

            $hrManagers = User::where('role', 'hr_manager')->get();
            if ($hrManagers->isNotEmpty()) {
                Notification::send($hrManagers, new PurchaseOrderNeedsFinalApproval($purchaseOrder));
            }

            $financeManagers = User::where('role', 'finance_manager')->get();
            if ($financeManagers->isNotEmpty()) {
                Notification::send($financeManagers, new PurchaseOrderNeedsFinalApproval($purchaseOrder));
            }

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
            'proposal_id' => 'required|exists:purchase_order_suppliers,id',
        ]);

        $proposal = $purchaseOrder->proposals()->where('id', $validated['proposal_id'])->first();

        if (! $proposal) {
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
                'total_amount' => $proposal->price,
            ]);

            DB::commit();

            $stockManager = $purchaseOrder->responsibleStock;
            if ($stockManager) {
                Notification::send($stockManager, new PurchaseOrderFinalApproved($purchaseOrder, $request->user()->name));
            }

            return response()->json($purchaseOrder->load('proposals'));
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function split(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'initial_approved') {
            return response()->json(['error' => 'Order must be initially approved before splitting'], 400);
        }

        if ($purchaseOrder->children()->exists()) {
            return response()->json(['error' => 'This order has already been split'], 400);
        }

        $validated = $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.supplier_id' => 'required|exists:suppliers,id',
            'assignments.*.items' => 'required|array|min:1',
            'assignments.*.items.*.item_id' => 'required|exists:items,id',
            'assignments.*.items.*.quantity' => 'required|numeric|min:0.01',
            'assignments.*.items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $allItemIds = collect($validated['assignments'])->flatMap(function ($assignment) {
            return collect($assignment['items'])->pluck('item_id');
        });

        $poItemIds = $purchaseOrder->purchaseOrderItems()->pluck('item_id');

        $missingItems = $poItemIds->diff($allItemIds);
        if ($missingItems->isNotEmpty()) {
            $items = \App\Models\Item::whereIn('id', $missingItems)->pluck('designation')->toArray();

            return response()->json(['error' => 'All items must be assigned to a supplier. Missing: '.implode(', ', $items)], 400);
        }

        DB::beginTransaction();
        try {
            $newPOs = [];

            foreach ($validated['assignments'] as $assignment) {
                $supplier = Supplier::find($assignment['supplier_id']);
                $items = $assignment['items'];

                $totalAmount = collect($items)->sum(function ($item) {
                    return $item['quantity'] * $item['unit_price'];
                });

                $newPO = PurchaseOrder::create([
                    'date' => $purchaseOrder->date,
                    'id_responsible_stock' => $purchaseOrder->id_responsible_stock,
                    'status' => 'pending_initial_approval',
                    'parent_id' => $purchaseOrder->id,
                    'supplier_id' => $supplier->id,
                    'supplier' => $supplier->name,
                    'total_amount' => $totalAmount,
                ]);

                foreach ($items as $itemData) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $newPO->id,
                        'item_id' => $itemData['item_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                    ]);
                }

                $newPOs[] = $newPO;
            }

            $purchaseOrder->update(['status' => 'split']);

            $hrManagers = User::where('role', 'hr_manager')->get();
            if ($hrManagers->isNotEmpty()) {
                foreach ($newPOs as $po) {
                    Notification::send($hrManagers, new PurchaseOrderNeedsInitialApproval($po));
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Purchase order split successfully',
                'original_po' => $purchaseOrder->fresh(),
                'new_purchase_orders' => PurchaseOrder::with(['purchaseOrderItems.item', 'supplier', 'responsibleStock'])
                    ->whereIn('id', collect($newPOs)->pluck('id'))->get(),
            ]);
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
