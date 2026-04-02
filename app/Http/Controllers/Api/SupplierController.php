<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::with(['supplierItems.item']);

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:suppliers,name',
            'contact_info' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $supplier = Supplier::create($validated);

        return response()->json($supplier, 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json($supplier->load(['supplierItems.item']));
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:suppliers,name,'.$supplier->id,
            'contact_info' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $supplier->update($validated);

        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $hasActivePOs = $supplier->purchaseOrders()
            ->whereIn('status', ['pending_initial_approval', 'initial_approved', 'pending_final_approval', 'final_approved', 'partially_delivered', 'delivered'])
            ->exists();

        if ($hasActivePOs) {
            return response()->json(['error' => 'Cannot delete supplier with active purchase orders'], 400);
        }

        $supplier->delete();

        return response()->json(['message' => 'Supplier deleted successfully']);
    }

    public function items(Supplier $supplier): JsonResponse
    {
        return response()->json($supplier->supplierItems()->with('item')->get());
    }

    public function addItem(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $existingItem = $supplier->supplierItems()->where('item_id', $validated['item_id'])->first();

        if ($existingItem) {
            return response()->json(['error' => 'Item already exists for this supplier. Use update to change price.'], 400);
        }

        $supplierItem = $supplier->supplierItems()->create($validated);

        return response()->json($supplierItem->load('item'), 201);
    }

    public function updateItem(Request $request, Supplier $supplier, int $itemId): JsonResponse
    {
        $supplierItem = $supplier->supplierItems()->where('item_id', $itemId)->firstOrFail();

        $validated = $request->validate([
            'unit_price' => 'required|numeric|min:0',
        ]);

        $supplierItem->update($validated);

        return response()->json($supplierItem->load('item'));
    }

    public function removeItem(Supplier $supplier, int $itemId): JsonResponse
    {
        $supplierItem = $supplier->supplierItems()->where('item_id', $itemId)->firstOrFail();

        $supplierItem->delete();

        return response()->json(['message' => 'Item removed from supplier']);
    }

    public function allWithItems(): JsonResponse
    {
        $suppliers = Supplier::with(['supplierItems.item'])->get();

        $formatted = $suppliers->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'items' => $supplier->supplierItems->map(function ($si) {
                    return [
                        'id' => $si->item_id,
                        'designation' => $si->item?->designation,
                        'unit_price' => $si->unit_price,
                    ];
                }),
            ];
        });

        return response()->json($formatted);
    }

    public function stats(Supplier $supplier): JsonResponse
    {
        $poItems = $supplier->purchaseOrderItems()
            ->with(['purchaseOrder', 'purchaseOrder.purchaseOrderItems'])
            ->get();

        $pos = $poItems->pluck('purchaseOrder')->filter()->unique('id')->values();

        $totalOrdered = $poItems->sum(fn ($item) => floatval($item->init_quantity) * floatval($item->unit_price));

        $supplierItemIds = $poItems->pluck('id');

        $totalDelivered = \App\Models\BonDeLivraisonItem::whereIn('purchase_order_item_id', $supplierItemIds)
            ->whereHas('bonDeLivraison', fn ($q) => $q->where('status', 'confirmed'))
            ->get()
            ->sum(fn ($item) => floatval($item->quantity) * floatval($item->purchaseOrderItem->unit_price ?? 0));

        $pending = $totalOrdered - $totalDelivered;

        $deliveriesCount = \App\Models\BonDeLivraison::whereIn('id', function ($query) use ($supplierItemIds) {
            $query->select('bon_de_livraison_id')
                ->from('bon_de_livraison_items')
                ->whereIn('purchase_order_item_id', $supplierItemIds);
        })
            ->where('status', 'confirmed')
            ->count();

        $totalOrders = $pos->count();
        $itemsCount = $poItems->count();
        $avgOrderValue = $totalOrders > 0 ? $pos->avg('total_amount') : 0;

        // Monthly spending - use delivered amounts
        $monthlySpending = DB::table('bon_de_livraison_items as bli')
            ->join('bon_de_livraisons as bl', 'bl.id', '=', 'bli.bon_de_livraison_id')
            ->join('purchase_order_items as poi', 'poi.id', '=', 'bli.purchase_order_item_id')
            ->join('propositions as p', 'p.id', '=', 'poi.proposition_id')
            ->join('purchase_orders as po', 'po.id', '=', 'poi.purchase_order_id')
            ->select(DB::raw("DATE_FORMAT(po.date, '%Y-%m') as month"), DB::raw('SUM(bli.quantity * poi.unit_price) as total'))
            ->where('p.supplier_id', $supplier->id)
            ->where('bl.status', 'confirmed')
            ->where('po.date', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $statusBreakdown = $pos->groupBy('status')
            ->map(fn ($group) => $group->count())
            ->toArray();

        // Recent orders with supplier-specific amounts
        $recentOrders = collect($pos->take(5)->values())->map(function ($order) use ($supplier) {
            // Get supplier's items from this PO
            $supplierItems = $order->purchaseOrderItems()
                ->whereHas('proposition', fn ($q) => $q->where('supplier_id', $supplier->id))
                ->get();

            // Calculate ordered amount
            $orderedAmount = $supplierItems->sum(fn ($item) => floatval($item->init_quantity) * floatval($item->unit_price));

            // Calculate delivered amount from confirmed bon de livraisons
            $supplierItemIds = $supplierItems->pluck('id');
            $deliveredAmount = \App\Models\BonDeLivraisonItem::whereIn('purchase_order_item_id', $supplierItemIds)
                ->whereHas('bonDeLivraison', fn ($q) => $q->where('status', 'confirmed'))
                ->get()
                ->sum(fn ($item) => floatval($item->quantity) * floatval($item->purchaseOrderItem->unit_price ?? 0));

            return [
                'id' => $order->id,
                'status' => $order->status,
                'date' => $order->date,
                'total_amount' => $order->total_amount,
                'supplier_ordered' => $orderedAmount,
                'supplier_delivered' => $deliveredAmount,
                'supplier_pending' => $orderedAmount - $deliveredAmount,
                'items' => $supplierItems->map(function ($poItem) {
                    return [
                        'item_id' => $poItem->item_id,
                        'item_name' => $poItem->item?->designation,
                        'item_image' => $poItem->item?->image_path,
                        'init_quantity' => $poItem->init_quantity,
                        'final_quantity' => $poItem->final_quantity,
                        'unit_price' => $poItem->unit_price,
                    ];
                }),
            ];
        });

        $supplierItemIds = $supplier->supplierItems()->pluck('item_id');

        $priceComparison = \App\Models\SupplierItem::whereIn('item_id', $supplierItemIds)
            ->where('supplier_id', '!=', $supplier->id)
            ->with(['item', 'supplier'])
            ->get()
            ->groupBy('item_id')
            ->map(function ($items) use ($supplier) {
                $item = $items->first()->item;
                $supplierPrice = $supplier->supplierItems()->where('item_id', $item->id)->first()?->unit_price ?? 0;
                $minPrice = $items->min('unit_price');
                $isCheapest = $supplierPrice <= $minPrice;

                $otherPrices = $items->map(fn ($si) => [
                    'supplier' => $si->supplier->name,
                    'price' => $si->unit_price,
                ])->values();

                return [
                    'item' => $item->designation,
                    'your_price' => $supplierPrice,
                    'best_price' => $minPrice,
                    'is_cheapest' => $isCheapest,
                    'other_prices' => $otherPrices,
                ];
            })
            ->values();

        return response()->json([
            'supplier' => $supplier,
            'total_orders' => $totalOrders,
            'total_ordered' => $totalOrdered,
            'total_delivered' => $totalDelivered,
            'total_pending' => $pending,
            'deliveries_count' => $deliveriesCount,
            'items_count' => $itemsCount,
            'avg_order_value' => $avgOrderValue,
            'monthly_spending' => $monthlySpending,
            'status_breakdown' => $statusBreakdown,
            'recent_orders' => $recentOrders,
            'price_comparison' => $priceComparison,
        ]);
    }
}
