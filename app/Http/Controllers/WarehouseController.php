<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:warehouses.view')->only(['index', 'show', 'details']);
        $this->middleware('permission:warehouses.create')->only(['create', 'store']);
        $this->middleware('permission:warehouses.edit')->only(['edit', 'update']);
        $this->middleware('permission:warehouses.delete')->only(['destroy']);
    }

    public function index()
    {
        $query = Warehouse::with('teams');
        
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            $userTeams = $user->teams()->pluck('teams.id')->toArray();
            if (count($userTeams) > 0) {
                $query->whereHas('teams', function($q) use ($userTeams) {
                    $q->whereIn('teams.id', $userTeams);
                });
            }
        }
        
        $warehouses = $query->get();
        $teams = Team::all();
        
        return view('warehouses.index', compact('warehouses', 'teams'));
    }

    public function create()
    {
        $teams = Team::all();
        return view('warehouses.form', compact('teams'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'manager' => 'nullable|string|max:255',
            'manager_location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
        ]);

        $warehouse = Warehouse::create($validated);
        
        if ($request->has('team_ids')) {
            $warehouse->teams()->sync($request->team_ids);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المخزن بنجاح',
                'warehouse' => $warehouse->load('teams')
            ]);
        }

        return redirect()->route('warehouses.index')->with('success', 'تم إضافة المخزن بنجاح');
    }

    public function show(Warehouse $warehouse)
    {
        $warehouse->load('teams');
        return view('warehouses.show', compact('warehouse'));
    }

    public function details(Warehouse $warehouse)
    {
        $warehouse->load('teams');
        $inventory = \App\Models\Transaction::select([
                'product_id',
                'warehouse_id',
                \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN type = "in" THEN quantity ELSE -quantity END) as quantity')
            ])
            ->where('warehouse_id', $warehouse->id)
            ->groupBy('product_id', 'warehouse_id')
            ->having('quantity', '>', 0)
            ->with('product')
            ->get();
        
        $products = \App\Models\Product::all();
        
        return view('warehouses.details', compact('warehouse', 'inventory', 'products'));
    }

    public function edit(Warehouse $warehouse)
    {
        $teams = Team::all();
        $warehouse->load('teams');
        return view('warehouses.form', compact('warehouse', 'teams'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'manager' => 'nullable|string|max:255',
            'manager_location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
        ]);

        $warehouse->update($validated);
        
        if ($request->has('team_ids')) {
            $warehouse->teams()->sync($request->team_ids);
        } else {
            $warehouse->teams()->detach();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المخزن بنجاح',
                'warehouse' => $warehouse->load('teams')
            ]);
        }

        return redirect()->route('warehouses.index')->with('success', 'تم تحديث المخزن بنجاح');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف المخزن بنجاح'
            ]);
        }

        return redirect()->route('warehouses.index')->with('success', 'تم حذف المخزن بنجاح');
    }
}

