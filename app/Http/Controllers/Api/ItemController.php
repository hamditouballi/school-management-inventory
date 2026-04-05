<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        return response()->json(Item::with('category')->get());
    }

    public function categories()
    {
        return response()->json(Category::all());
    }

    public function store(Request $request)
    {
        $this->authorize('create', Item::class);
        $validated = $request->validate([
            'designation' => 'required|string',
            'description' => 'nullable|string',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('items', 'public');
        } elseif ($request->has('phone_upload_path')) {
            $validated['image_path'] = $request->input('phone_upload_path');
        }
        unset($validated['image']);

        $item = Item::create($validated);

        return response()->json($item->load('category'), 201);
    }

    public function show(Item $item)
    {
        return response()->json($item->load('category'));
    }

    public function update(Request $request, Item $item)
    {
        $this->authorize('update', $item);
        $validated = $request->validate([
            'designation' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'quantity' => 'sometimes|required|numeric|min:0',
            'unit' => 'sometimes|required|string',
            'category_id' => 'nullable|exists:categories,id',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($item->image_path && \Storage::disk('public')->exists($item->image_path)) {
                \Storage::disk('public')->delete($item->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('items', 'public');
        } elseif ($request->has('phone_upload_path')) {
            // Delete old image if exists
            if ($item->image_path && \Storage::disk('public')->exists($item->image_path)) {
                \Storage::disk('public')->delete($item->image_path);
            }
            $validated['image_path'] = $request->input('phone_upload_path');
        }
        unset($validated['image']);

        $item->update($validated);

        return response()->json($item->load('category'));
    }

    public function destroy(Item $item)
    {
        $this->authorize('delete', $item);
        // Reject all pending/approved requests for this item
        \App\Models\Request::whereHas('requestItems', function ($query) use ($item) {
            $query->where('item_id', $item->id);
        })
            ->whereIn('status', ['pending', 'approved'])
            ->update(['status' => 'rejected']);

        $item->delete();

        return response()->json(['message' => 'Item deleted successfully']);
    }
}
