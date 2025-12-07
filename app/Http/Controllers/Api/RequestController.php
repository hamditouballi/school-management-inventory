<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;
use App\Models\RequestItem;
use App\Models\BonDeSortie;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $query = RequestModel::with(['user.department', 'requestItems.item']);

        // Filter by role
        if ($request->user()->role === 'teacher') {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->orderBy('dateCreated', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity_requested' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();
        try {
            $requestModel = RequestModel::create([
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'dateCreated' => now(),
            ]);

            foreach ($validated['items'] as $item) {
                RequestItem::create([
                    'request_id' => $requestModel->id,
                    'item_id' => $item['item_id'],
                    'quantity_requested' => $item['quantity_requested'],
                ]);
            }

            DB::commit();

            return response()->json($requestModel->load(['requestItems.item', 'user']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(RequestModel $request)
    {
        return response()->json($request->load(['requestItems.item', 'user.department', 'bonDeSorties']));
    }

    public function updateStatus(Request $request, RequestModel $requestModel)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,fulfilled',
        ]);

        $requestModel->update(['status' => $validated['status']]);

        return response()->json($requestModel);
    }

    public function fulfill(Request $request, RequestModel $requestModel)
    {
        if ($requestModel->status !== 'approved') {
            return response()->json(['error' => 'Request must be approved before fulfillment'], 400);
        }

        DB::beginTransaction();
        try {
            $insufficientItems = [];

            foreach ($requestModel->requestItems as $requestItem) {
                $item = $requestItem->item;

                if ($item->quantity < $requestItem->quantity_requested) {
                    $insufficientItems[] = [
                        'item' => $item->designation,
                        'available' => $item->quantity,
                        'requested' => $requestItem->quantity_requested,
                    ];
                } else {
                    // Decrease stock
                    $item->decrement('quantity', $requestItem->quantity_requested);

                    // Create Bon de Sortie
                    BonDeSortie::create([
                        'request_id' => $requestModel->id,
                        'item_id' => $item->id,
                        'quantity' => $requestItem->quantity_requested,
                        'date' => now()->toDateString(),
                        'id_responsible_stock' => $request->user()->id,
                    ]);
                }
            }

            if (empty($insufficientItems)) {
                $requestModel->update(['status' => 'fulfilled']);
                DB::commit();
                return response()->json(['message' => 'Request fulfilled successfully', 'request' => $requestModel]);
            } else {
                DB::rollBack();
                return response()->json([
                    'error' => 'Insufficient stock for some items',
                    'insufficient_items' => $insufficientItems,
                    'suggestion' => 'Create a Purchase Order for these items',
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
