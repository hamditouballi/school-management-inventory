<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Proposition;
use App\Models\PropositionGroup;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\PurchaseOrderFinalApproved;
use App\Notifications\PurchaseOrderInitialApproved;
use App\Notifications\PurchaseOrderInitialRejected;
use App\Notifications\PurchaseOrderNeedsFinalApproval;
use App\Notifications\PurchaseOrderNeedsInitialApproval;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return response()->json(PurchaseOrder::with([
            'purchaseOrderItems.item',
            'purchaseOrderItems.proposition.supplier',
            'purchaseOrderItems.approver',
            'responsibleStock',
            'supplier',
            'propositions.supplier',
            'propositions.item',
            'propositionGroups.propositions.supplier',
            'propositionGroups.item',
            'bonDeLivraisons',
        ])->orderBy('date', 'desc')->get());
    }

    public function store(Request $request)
    {
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
            $purchaseOrder = PurchaseOrder::create([
                'date' => $validated['date'],
                'id_responsible_stock' => $request->user()->id,
                'status' => 'pending_initial_approval',
            ]);

            foreach ($validated['items'] as $index => $itemData) {
                $itemId = $itemData['item_id'] ?? null;

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
                    'init_quantity' => $itemData['quantity'],
                    'unit_price' => 0,
                    'state' => 'pending',
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
        return response()->json($purchaseOrder->load([
            'purchaseOrderItems.item',
            'purchaseOrderItems.proposition.supplier',
            'purchaseOrderItems.approver',
            'responsibleStock',
            'supplier',
            'propositions',
            'propositionGroups.propositions.supplier',
            'propositionGroups.item',
        ]));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending_initial_approval') {
            return response()->json(['error' => 'Can only update pending initial approval orders'], 400);
        }

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
            $purchaseOrder->update([
                'date' => $validated['date'],
            ]);

            $purchaseOrder->purchaseOrderItems()->delete();

            foreach ($validated['items'] as $index => $itemData) {
                $itemId = $itemData['item_id'] ?? null;

                if (! $itemId && isset($itemData['new_item_name'])) {
                    $existingItem = \App\Models\Item::where('designation', $itemData['new_item_name'])->first();
                    if ($existingItem) {
                        $itemId = $existingItem->id;
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
                    'init_quantity' => $itemData['quantity'],
                    'unit_price' => 0,
                    'state' => 'pending',
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
            'status' => 'required|in:pending_initial_approval,initial_approved,pending_final_approval,final_approved,rejected,partially_delivered,delivered',
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

    public function addPropositions(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'initial_approved') {
            return response()->json(['error' => 'Order must be initially approved before adding propositions'], 400);
        }

        $validated = $request->validate([
            'proposals' => 'required|array|min:1',
            'proposals.*.supplier_id' => 'required|exists:suppliers,id',
            'proposals.*.item_id' => 'required|exists:items,id',
            'proposals.*.quantity' => 'required|numeric|min:0.01',
            'proposals.*.unit_price' => 'required|numeric|min:0',
            'proposals.*.notes' => 'nullable|string',
            'proposals.*.proposition_group_id' => 'nullable|string|uuid',
            'proposals.*.proposition_order' => 'nullable|integer|min:0',
        ]);

        $groupQtys = [];
        foreach ($validated['proposals'] as $proposal) {
            $groupId = $proposal['proposition_group_id'] ?? $proposal['item_id'];
            if (! isset($groupQtys[$groupId])) {
                $groupQtys[$groupId] = 0;
            }
            $groupQtys[$groupId] += $proposal['quantity'];
        }

        foreach ($groupQtys as $groupId => $totalQty) {
            $itemId = collect($validated['proposals'])
                ->firstWhere('proposition_group_id', $groupId)['item_id'] ?? null;

            if (! $itemId) {
                $itemId = $groupId;
            }

            $poItem = $purchaseOrder->purchaseOrderItems()->where('item_id', $itemId)->first();
            if ($poItem && $totalQty > $poItem->init_quantity) {
                $item = $poItem->item;

                return response()->json([
                    'error' => "Quantity for item '{$item->designation}' exceeds requested amount. Requested: {$poItem->init_quantity}, Proposed: {$totalQty}",
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $createdPropositions = [];
            $processedGroups = [];

            foreach ($validated['proposals'] as $propositionData) {
                $groupId = $propositionData['proposition_group_id'] ?? null;
                $order = $propositionData['proposition_order'] ?? 0;

                if (! $groupId) {
                    $groupId = (string) \Illuminate\Support\Str::uuid();
                }

                if (! isset($processedGroups[$groupId])) {
                    $existingGroup = PropositionGroup::where('id', $groupId)
                        ->where('purchase_order_id', $purchaseOrder->id)
                        ->first();

                    if (! $existingGroup) {
                        $existingGroup = PropositionGroup::create([
                            'id' => $groupId,
                            'purchase_order_id' => $purchaseOrder->id,
                            'item_id' => $propositionData['item_id'],
                            'proposition_order' => $order,
                        ]);
                    }

                    $processedGroups[$groupId] = $existingGroup;
                }

                $proposition = Proposition::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'supplier_id' => $propositionData['supplier_id'],
                    'item_id' => $propositionData['item_id'],
                    'quantity' => $propositionData['quantity'],
                    'unit_price' => $propositionData['unit_price'],
                    'notes' => $propositionData['notes'] ?? null,
                    'proposition_group_id' => $groupId,
                    'proposition_order' => $order,
                ]);
                $createdPropositions[] = $proposition;
            }

            $purchaseOrder->update(['status' => 'pending_final_approval']);

            DB::commit();

            $hrManagers = User::where('role', 'hr_manager')->get();
            if ($hrManagers->isNotEmpty()) {
                Notification::send($hrManagers, new PurchaseOrderNeedsFinalApproval($purchaseOrder));
            }

            return response()->json([
                'purchase_order' => $purchaseOrder->load('propositionGroups.propositions.supplier', 'propositionGroups.item', 'propositions.supplier'),
                'proposition_groups' => PropositionGroup::where('purchase_order_id', $purchaseOrder->id)
                    ->with('propositions.supplier', 'item')
                    ->orderBy('proposition_order')
                    ->get(),
            ], 201);
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
            'selected_group_ids' => 'required|array',
            'selected_group_ids.*' => 'required|string|uuid',
        ]);

        $poItemIds = $purchaseOrder->purchaseOrderItems()->pluck('item_id')->toArray();

        $selectedGroups = PropositionGroup::whereIn('id', $validated['selected_group_ids'])
            ->where('purchase_order_id', $purchaseOrder->id)
            ->get();

        if ($selectedGroups->isEmpty()) {
            return response()->json(['error' => 'No valid groups selected'], 400);
        }

        $selectedGroupItemIds = $selectedGroups->pluck('item_id')->toArray();
        $missingItems = array_diff($poItemIds, $selectedGroupItemIds);

        if (! empty($missingItems)) {
            return response()->json(['error' => 'Select a proposal for all items in the purchase order'], 422);
        }

        $allPropositions = Proposition::whereIn('proposition_group_id', $validated['selected_group_ids'])
            ->where('purchase_order_id', $purchaseOrder->id)
            ->get();

        if ($allPropositions->isEmpty()) {
            return response()->json(['error' => 'No propositions found for selected groups'], 400);
        }

        DB::beginTransaction();
        try {
            $purchaseOrder->purchaseOrderItems()->delete();

            $totalAmount = 0;

            foreach ($allPropositions as $proposition) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'item_id' => $proposition->item_id,
                    'init_quantity' => $proposition->quantity,
                    'final_quantity' => $proposition->quantity,
                    'unit_price' => $proposition->unit_price,
                    'state' => 'approved',
                    'approved_by' => $request->user()->id,
                    'proposition_id' => $proposition->id,
                ]);

                $totalAmount += $proposition->quantity * $proposition->unit_price;
            }

            $purchaseOrder->update([
                'status' => 'final_approved',
                'total_amount' => $totalAmount,
            ]);

            DB::commit();

            $stockManager = $purchaseOrder->responsibleStock;
            if ($stockManager) {
                Notification::send($stockManager, new PurchaseOrderFinalApproved($purchaseOrder, $request->user()->name));
            }

            return response()->json($purchaseOrder->load([
                'purchaseOrderItems.item',
                'purchaseOrderItems.proposition.supplier',
                'purchaseOrderItems.approver',
            ]));
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function rejectPropositions(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending_final_approval') {
            return response()->json(['error' => 'Invalid status for rejecting propositions'], 400);
        }

        DB::beginTransaction();
        try {
            Proposition::where('purchase_order_id', $purchaseOrder->id)->delete();
            PropositionGroup::where('purchase_order_id', $purchaseOrder->id)->delete();

            $purchaseOrder->update(['status' => 'rejected']);

            DB::commit();

            $stockManager = $purchaseOrder->responsibleStock;
            if ($stockManager) {
                Notification::send($stockManager, new PurchaseOrderInitialRejected($purchaseOrder, $request->user()->name));
            }

            return response()->json($purchaseOrder);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getSuppliersForPOItems(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'initial_approved') {
            return response()->json(['error' => 'Order must be initially approved'], 400);
        }

        $itemIds = $purchaseOrder->purchaseOrderItems()->pluck('item_id')->filter()->toArray();

        $suppliers = Supplier::with(['items' => function ($query) use ($itemIds) {
            $query->whereIn('items.id', $itemIds);
        }])->get()->filter(function ($supplier) {
            return $supplier->items->isNotEmpty();
        })->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'items' => $supplier->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'designation' => $item->designation,
                        'unit_price' => $item->pivot->unit_price,
                    ];
                }),
            ];
        });

        return response()->json($suppliers);
    }

    public function markDelivered(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'final_approved' && $purchaseOrder->status !== 'partially_delivered') {
            return response()->json(['error' => 'Order must be approved before marking as delivered'], 400);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.final_quantity' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['items'] as $itemData) {
                $poItem = PurchaseOrderItem::find($itemData['purchase_order_item_id']);
                $poItem->update([
                    'final_quantity' => $itemData['final_quantity'],
                    'state' => 'delivered',
                ]);

                $item = $poItem->item;
                if ($item) {
                    $item->increment('quantity', $itemData['final_quantity']);
                }
            }

            $purchaseOrder->update(['status' => 'ordered']);

            DB::commit();

            return response()->json($purchaseOrder->load('purchaseOrderItems.item'));
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $nonDeletableStatuses = ['ordered', 'partially_delivered', 'delivered'];
        if (in_array($purchaseOrder->status, $nonDeletableStatuses)) {
            return response()->json(['error' => 'Cannot delete delivered orders'], 400);
        }

        DB::beginTransaction();
        try {
            $status = $purchaseOrder->status;

            // Reverse inventory for final_approved POs (where items were received)
            if ($status === 'final_approved') {
                $purchaseOrder->items()
                    ->where('state', 'approved')
                    ->where('final_quantity', '>', 0)
                    ->each(function ($item) {
                        $poItem = PurchaseOrderItem::find($item->pivot->id);
                        if ($poItem) {
                            Item::where('id', $poItem->item_id)
                                ->decrement('quantity', $poItem->final_quantity);
                        }
                    });
            }

            // Delete related records
            Invoice::where('id_purchase_order', $purchaseOrder->id)->delete();
            DB::table('notifications')->whereJsonContains('data->purchase_order_id', $purchaseOrder->id)->delete();

            // Delete PO (cascade handles purchase_order_items, propositions, proposition_groups)
            $purchaseOrder->delete();

            DB::commit();

            return response()->json(['message' => 'Purchase order deleted successfully']);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
