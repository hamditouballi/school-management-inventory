<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BonDeLivraison;
use App\Models\BonDeLivraisonItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BonDeLivraisonController extends Controller
{
    public function index(PurchaseOrder $purchaseOrder)
    {
        $bonDeLivraisons = $purchaseOrder->bonDeLivraisons()
            ->with(['items.purchaseOrderItem.item', 'responsibleStock'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($bonDeLivraisons);
    }

    public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        $items = $request->has('items') && is_string($request->input('items'))
            ? json_decode($request->input('items'), true)
            : $request->input('items');

        $request->merge(['items' => $items]);

        $validated = $request->validate([
            'date' => 'required|date',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();
        try {
            $filePath = null;
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('bon-de-livraisons', 'public');
            }

            $bonDeLivraison = BonDeLivraison::create([
                'purchase_order_id' => $purchaseOrder->id,
                'date' => $validated['date'],
                'file_path' => $filePath,
                'notes' => $validated['notes'] ?? null,
                'id_responsible_stock' => $request->user()->id,
                'status' => 'confirmed',
            ]);

            foreach ($validated['items'] as $itemData) {
                $poItem = PurchaseOrderItem::find($itemData['purchase_order_item_id']);

                BonDeLivraisonItem::create([
                    'bon_de_livraison_id' => $bonDeLivraison->id,
                    'purchase_order_item_id' => $itemData['purchase_order_item_id'],
                    'quantity' => $itemData['quantity'],
                ]);

                $currentFinal = floatval($poItem->final_quantity ?? 0);
                $newFinal = $currentFinal + floatval($itemData['quantity']);
                $poItem->update(['final_quantity' => $newFinal]);

                if ($poItem->item) {
                    $poItem->item->increment('quantity', floatval($itemData['quantity']));
                }
            }

            if ($purchaseOrder->status === 'final_approved') {
                $purchaseOrder->update(['status' => 'partially_delivered']);
            }

            DB::commit();

            return response()->json(
                $bonDeLivraison->load(['items.purchaseOrderItem.item', 'responsibleStock']),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(BonDeLivraison $bonDeLivraison)
    {
        return response()->json(
            $bonDeLivraison->load(['items.purchaseOrderItem.item', 'responsibleStock', 'purchaseOrder'])
        );
    }

    public function confirm(Request $request, BonDeLivraison $bonDeLivraison)
    {
        if ($bonDeLivraison->status !== 'pending') {
            return response()->json(['error' => 'This delivery note has already been processed'], 400);
        }

        if ($bonDeLivraison->id_responsible_stock !== $request->user()->id) {
            return response()->json(['error' => 'Only the uploader can confirm this delivery note'], 403);
        }

        $purchaseOrder = $bonDeLivraison->purchaseOrder;

        DB::beginTransaction();
        try {
            foreach ($bonDeLivraison->items as $item) {
                $poItem = $item->purchaseOrderItem;

                $currentFinal = floatval($poItem->final_quantity ?? 0);
                $newFinal = $currentFinal + floatval($item->quantity);
                $poItem->update(['final_quantity' => $newFinal]);

                if ($poItem->item) {
                    $poItem->item->increment('quantity', floatval($item->quantity));
                }
            }

            $bonDeLivraison->update(['status' => 'confirmed']);

            if ($purchaseOrder->status === 'final_approved') {
                $purchaseOrder->update(['status' => 'partially_delivered']);
            }

            DB::commit();

            return response()->json($bonDeLivraison->load(['items.purchaseOrderItem.item', 'responsibleStock']));
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
