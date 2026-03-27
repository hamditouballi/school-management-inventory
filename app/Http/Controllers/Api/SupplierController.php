<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            ->whereIn('status', ['pending_initial_approval', 'initial_approved', 'pending_final_approval', 'final_approved', 'ordered'])
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

        return response()->json($suppliers);
    }
}
