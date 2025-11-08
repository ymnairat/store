<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $warehouses = Warehouse::all();
        $teams = \App\Models\Team::all();
        return view('search.index', compact('warehouses', 'teams'));
    }

    public function searchProducts(Request $request)
    {
        $query = Product::with('teams');
        
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('team_id')) {
            $query->whereHas('teams', function($q) use ($request) {
                $q->where('teams.id', $request->team_id);
            });
        }
        
        $products = $query->get();
        
        if ($request->expectsJson()) {
            return response()->json($products);
        }
        
        return view('search.results', ['type' => 'products', 'results' => $products]);
    }

    public function searchInventory(Request $request)
    {
        $query = DB::table('transactions')
            ->select([
                'product_id',
                'warehouse_id',
                DB::raw('SUM(CASE WHEN type = "in" THEN quantity ELSE -quantity END) as quantity')
            ])
            ->groupBy('product_id', 'warehouse_id')
            ->having('quantity', '>', 0);
        
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        $inventory = $query->get();
        
        // Load relationships
        $productIds = $inventory->pluck('product_id')->unique();
        $warehouseIds = $inventory->pluck('warehouse_id')->unique();
        
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $warehouses = Warehouse::whereIn('id', $warehouseIds)->get()->keyBy('id');
        
        $results = $inventory->map(function($item) use ($products, $warehouses) {
            return (object)[
                'product' => $products->get($item->product_id),
                'warehouse' => $warehouses->get($item->warehouse_id),
                'quantity' => $item->quantity
            ];
        });
        
        if ($request->expectsJson()) {
            return response()->json($results->values());
        }
        
        return view('search.results', ['type' => 'inventory', 'results' => $results]);
    }

    public function searchTransactions(Request $request)
    {
        $query = Transaction::with(['product', 'warehouse']);
        
        if ($request->has('product_search')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->product_search}%")
                  ->orWhere('code', 'like', "%{$request->product_search}%");
            });
        }
        
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('serial_number')) {
            $query->where('serial_number', 'like', "%{$request->serial_number}%");
        }
        
        $transactions = $query->orderBy('created_at', 'desc')->get();
        
        if ($request->expectsJson()) {
            return response()->json($transactions);
        }
        
        return view('search.results', ['type' => 'transactions', 'results' => $transactions]);
    }
}

