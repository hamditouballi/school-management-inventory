<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BonDeSortie;
use App\Models\Item;
use Illuminate\Http\Request;

class BonDeSortieController extends Controller
{
    public function index(Request $request)
    {
        $query = BonDeSortie::with(['item', 'request.user.department', 'responsibleStock']);

        if ($request->user()->role === 'hr_manager') {
            return response()->json($query->orderBy('date', 'desc')->get());
        }

        if (in_array($request->user()->role, ['stock_manager'])) {
            return response()->json($query->orderBy('date', 'desc')->get());
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_id' => 'required|exists:requests,id',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|numeric|min:0.01',
            'date' => 'required|date',
        ]);

        $bonDeSortie = BonDeSortie::create([
            'request_id' => $validated['request_id'],
            'item_id' => $validated['item_id'],
            'quantity' => $validated['quantity'],
            'date' => $validated['date'],
            'id_responsible_stock' => $request->user()->id,
        ]);

        $item = Item::find($validated['item_id']);
        $item->decrement('quantity', $validated['quantity']);

        return response()->json($bonDeSortie->load(['item', 'request', 'responsibleStock']), 201);
    }

    public function show(BonDeSortie $bonDeSortie)
    {
        return response()->json($bonDeSortie->load(['item', 'request.user.department', 'responsibleStock']));
    }

    public function update(Request $request, BonDeSortie $bonDeSortie)
    {
        if ($request->user()->role !== 'stock_manager') {
            return response()->json(['error' => 'Only stock manager can update bon de sortie'], 403);
        }

        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'date' => 'required|date',
        ]);

        $oldQuantity = $bonDeSortie->quantity;
        $newQuantity = $validated['quantity'];

        $bonDeSortie->update([
            'quantity' => $validated['quantity'],
            'date' => $validated['date'],
        ]);

        $item = Item::find($bonDeSortie->item_id);

        if ($newQuantity > $oldQuantity) {
            $diff = $newQuantity - $oldQuantity;
            $item->decrement('quantity', $diff);
        } elseif ($newQuantity < $oldQuantity) {
            $diff = $oldQuantity - $newQuantity;
            $item->increment('quantity', $diff);
        }

        return response()->json($bonDeSortie->load(['item', 'request', 'responsibleStock']));
    }

    public function destroy(Request $request, BonDeSortie $bonDeSortie)
    {
        if ($request->user()->role !== 'stock_manager') {
            return response()->json(['error' => 'Only stock manager can delete bon de sortie'], 403);
        }

        $item = Item::find($bonDeSortie->item_id);
        $item->increment('quantity', $bonDeSortie->quantity);

        $bonDeSortie->delete();

        return response()->json(['message' => 'Bon de sortie deleted successfully']);
    }
}
