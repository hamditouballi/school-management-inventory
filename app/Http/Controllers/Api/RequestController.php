<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BonDeSortie;
use App\Models\Request as RequestModel;
use App\Models\RequestItem;
use App\Models\User;
use App\Notifications\OtherHrApproved;
use App\Notifications\RequestApproved;
use App\Notifications\RequestNeedsApproval;
use App\Notifications\RequestRejected;
use App\Notifications\StockManagerNewApprovedRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $query = RequestModel::with(['user.department', 'requestItems.item']);

        if ($request->user()->role === 'director') {
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
                'pending_until' => now()->addHours(24),
            ]);

            foreach ($validated['items'] as $item) {
                RequestItem::create([
                    'request_id' => $requestModel->id,
                    'item_id' => $item['item_id'],
                    'quantity_requested' => $item['quantity_requested'],
                ]);
            }

            DB::commit();

            $this->notifyHrManagersNewRequest($requestModel);

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
            'status' => 'required|in:pending,hr_approved,rejected,fulfilled,received',
        ]);

        $newStatus = $validated['status'];
        $oldStatus = $requestModel->status;

        $requestModel->update(['status' => $newStatus]);

        if ($newStatus === 'hr_approved' && $oldStatus !== 'hr_approved') {
            $this->notifyOnApproval($requestModel, $request->user());
        } elseif ($newStatus === 'rejected' && $oldStatus !== 'rejected') {
            $this->notifyOnRejection($requestModel);
        }

        return response()->json($requestModel);
    }

    private function notifyHrManagersNewRequest(RequestModel $requestModel): void
    {
        $hrManagers = User::where('role', 'hr_manager')->get();

        Notification::send($hrManagers, new RequestNeedsApproval($requestModel));
    }

    private function notifyOnApproval(RequestModel $requestModel, User $approver): void
    {
        Notification::send($requestModel->user, new RequestApproved($requestModel));

        $otherHrManagers = User::where('role', 'hr_manager')
            ->where('id', '!=', $approver->id)
            ->get();

        if ($otherHrManagers->isNotEmpty()) {
            Notification::send($otherHrManagers, new OtherHrApproved($requestModel, $approver->name));
        }

        $stockManagers = User::where('role', 'stock_manager')->get();

        if ($stockManagers->isNotEmpty()) {
            Notification::send($stockManagers, new StockManagerNewApprovedRequest($requestModel, $approver->name));
        }
    }

    private function notifyOnRejection(RequestModel $requestModel): void
    {
        Notification::send($requestModel->user, new RequestRejected($requestModel));
    }

    public function fulfill(Request $request, RequestModel $requestModel)
    {
        if ($requestModel->status !== 'hr_approved') {
            return response()->json(['error' => 'Request must be HR approved before fulfillment'], 400);
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
                    $item->decrement('quantity', $requestItem->quantity_requested);

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

    public function confirmReceipt(Request $request, RequestModel $requestModel)
    {
        if ($request->user()->id !== $requestModel->user_id) {
            return response()->json(['error' => 'Only the requester can confirm receipt'], 403);
        }

        if ($requestModel->status !== 'fulfilled') {
            return response()->json(['error' => 'Request must be fulfilled before confirming receipt'], 400);
        }

        $requestModel->update([
            'status' => 'received',
            'confirmed_received_at' => now(),
        ]);

        return response()->json(['message' => 'Receipt confirmed successfully', 'request' => $requestModel]);
    }

    public function unconfirmed(Request $request)
    {
        if (! in_array($request->user()->role, ['stock_manager', 'finance_manager'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $requests = RequestModel::with(['user.department', 'requestItems.item'])
            ->where('status', 'fulfilled')
            ->whereNull('confirmed_received_at')
            ->orderBy('dateCreated', 'desc')
            ->get();

        return response()->json($requests);
    }

    public function myUnconfirmed(Request $request)
    {
        $requests = RequestModel::with(['user.department', 'requestItems.item'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'fulfilled')
            ->whereNull('confirmed_received_at')
            ->orderBy('dateCreated', 'desc')
            ->get();

        return response()->json($requests);
    }
}
