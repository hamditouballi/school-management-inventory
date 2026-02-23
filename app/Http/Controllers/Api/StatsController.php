<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BonDeSortie;
use App\Models\Invoice;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function consumption(Request $request)
    {
        $months = $request->get('months', 12);

        $data = BonDeSortie::select(
            DB::raw('DATE_FORMAT(date, "%Y-%m") as month'),
            DB::raw('SUM(quantity) as total_quantity')
        )
            ->where('date', '>=', now()->subMonths($months))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json($data);
    }

    public function consumptionByDepartment()
    {
        $data = BonDeSortie::join('requests', 'bon_de_sorties.request_id', '=', 'requests.id')
            ->join('users', 'requests.user_id', '=', 'users.id')
            ->join('departments', 'users.department_id', '=', 'departments.id')
            ->select('departments.name as department', DB::raw('SUM(bon_de_sorties.quantity) as total_quantity'))
            ->groupBy('departments.name')
            ->get();

        return response()->json($data);
    }

    public function spending(Request $request)
    {
        $months = $request->get('months', 12);

        $data = Invoice::select(
            DB::raw('DATE_FORMAT(date, "%Y-%m") as month'),
            DB::raw('SUM(price * quantity) as total_spent')
        )
            ->where('date', '>=', now()->subMonths($months))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json($data);
    }

    public function topItems(Request $request)
    {
        $limit = $request->get('limit', 10);

        $data = BonDeSortie::join('items', 'bon_de_sorties.item_id', '=', 'items.id')
            ->select('items.designation', DB::raw('SUM(bon_de_sorties.quantity) as total_consumed'))
            ->groupBy('items.id', 'items.designation')
            ->orderBy('total_consumed', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($data);
    }

    public function lowStock(Request $request)
    {
        $threshold = $request->get('threshold', 1);

        $items = Item::where('quantity', '<', $threshold)
            ->orderBy('quantity', 'asc')
            ->get();

        return response()->json($items);
    }

    public function dashboard()
    {
        return response()->json([
            'total_items' => Item::count(),
            'low_stock_items' => Item::where('quantity', '<', 1)->count(),
            'pending_requests' => \App\Models\Request::where('status', 'pending')->count(),
            'pending_purchase_orders' => \App\Models\PurchaseOrder::where('status', 'pending_hr')->count(),
            'total_spent_this_month' => Invoice::whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->sum(DB::raw('price * quantity')),
        ]);
    }
}
