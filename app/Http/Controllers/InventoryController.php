<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:inventory.view')->only(['index']);
    }

    public function index(Request $request)
    {
        $warehouseId = $request->get('warehouseId');
        
        $query = DB::table('transactions')
            ->select([
                'product_id',
                'warehouse_id',
                DB::raw('SUM(CASE WHEN type = "in" THEN quantity ELSE -quantity END) as quantity')
            ])
            ->groupBy('product_id', 'warehouse_id')
            ->having('quantity', '>', 0);
        
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        
        // Filter by user teams
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            $userTeams = $user->teams()->pluck('teams.id')->toArray();
            if (count($userTeams) > 0) {
                $warehouseIds = \App\Models\Warehouse::whereHas('teams', function($q) use ($userTeams) {
                    $q->whereIn('teams.id', $userTeams);
                })->pluck('id')->toArray();
                
                if (count($warehouseIds) > 0) {
                    $query->whereIn('warehouse_id', $warehouseIds);
                } else {
                    $query->whereRaw('1 = 0'); // No access
                }
            }
        }
        
        $inventory = $query->get();
        
        // Load relationships
        $productIds = $inventory->pluck('product_id')->unique();
        $warehouseIds = $inventory->pluck('warehouse_id')->unique();
        
        $products = \App\Models\Product::whereIn('id', $productIds)->get()->keyBy('id');
        $warehouses = \App\Models\Warehouse::whereIn('id', $warehouseIds)->get()->keyBy('id');
        
        $inventory = $inventory->map(function($item) use ($products, $warehouses) {
            $item->product = $products->get($item->product_id);
            $item->warehouse = $warehouses->get($item->warehouse_id);
            return $item;
        });
        
        $warehousesList = \App\Models\Warehouse::all();
        
        return view('inventory.index', compact('inventory', 'warehousesList', 'warehouseId'));
    }
}

