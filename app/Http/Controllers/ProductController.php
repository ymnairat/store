<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:products.view')->only(['index', 'show']);
        $this->middleware('permission:products.create')->only(['create', 'store']);
        $this->middleware('permission:products.edit')->only(['edit', 'update']);
        $this->middleware('permission:products.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = Product::with('teams');

        // Filter by user teams if not admin
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            $userTeams = $user->teams()->pluck('teams.id')->toArray();
            if (count($userTeams) > 0) {
                $query->whereHas('teams', function($q) use ($userTeams) {
                    $q->whereIn('teams.id', $userTeams);
                });
            }
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $products = $query->get();
        $teams = Team::all();

        return view('products.index', compact('products', 'teams'));
    }

    public function create()
    {
        $teams = Team::all();
        return view('products.form', compact('teams'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:products,code',
            'category' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
        ]);

        $product = Product::create($validated);

        if ($request->has('team_ids')) {
            $product->teams()->sync($request->team_ids);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المنتج بنجاح',
                'product' => $product->load('teams')
            ]);
        }

        return redirect()->route('products.index')->with('success', 'تم إضافة المنتج بنجاح');
    }

    public function show(Product $product)
    {
        $product->load('teams');
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $teams = Team::all();
        $product->load('teams');
        return view('products.form', compact('product', 'teams'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:products,code,' . $product->id,
            'category' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
        ]);

        $product->update($validated);

        if ($request->has('team_ids')) {
            $product->teams()->sync($request->team_ids);
        } else {
            $product->teams()->detach();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المنتج بنجاح',
                'product' => $product->load('teams')
            ]);
        }

        return redirect()->route('products.index')->with('success', 'تم تحديث المنتج بنجاح');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف المنتج بنجاح'
            ]);
        }

        return redirect()->route('products.index')->with('success', 'تم حذف المنتج بنجاح');
    }
}

