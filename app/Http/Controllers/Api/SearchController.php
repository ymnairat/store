<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SearchController extends Controller
{
    /**
     * Search products with filters
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $user = $request->user();
        $userTeams = $user->teams()->pluck('teams.id')->toArray();
        
        $query = Product::with('teams');
        
        // Filter by user teams
        if (count($userTeams) > 0) {
            $query->whereHas('teams', function($q) use ($userTeams) {
                $q->whereIn('teams.id', $userTeams);
            });
        }
        
        // Search by name or code
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        
        // Filter by team
        if ($request->has('team_id')) {
            $query->whereHas('teams', function($q) use ($request) {
                $q->where('teams.id', $request->get('team_id'));
            });
        }
        
        $products = $query->get();
        
        return response()->json($products);
    }

    /**
     * Search inventory with advanced filters
     */
    public function searchInventory(Request $request): JsonResponse
    {
        $user = $request->user();
        $userTeams = $user->teams()->pluck('teams.id')->toArray();
        
        $productId = $request->query('product_id');
        $warehouseId = $request->query('warehouse_id');
        $teamId = $request->query('team_id');
        $date = $request->query('date'); // Date to check inventory at
        
        // Build base query
        $baseQuery = Transaction::select([
                'product_id',
                'warehouse_id',
                DB::raw('SUM(CASE WHEN type = "in" THEN quantity ELSE -quantity END) as quantity')
            ])
            ->groupBy('product_id', 'warehouse_id');
        
        // Filter by date if provided (inventory at specific date)
        if ($date) {
            $baseQuery->whereDate('created_at', '<=', $date);
        }
        
        if ($productId) {
            $baseQuery->where('product_id', $productId);
        }
        
        if ($warehouseId) {
            $baseQuery->where('warehouse_id', $warehouseId);
        }
        
        $results = $baseQuery->get();
        $inventory = [];
        
        foreach ($results as $result) {
            $product = Product::with('teams')->find($result->product_id);
            $warehouse = Warehouse::with('teams')->find($result->warehouse_id);
            
            if (!$product || !$warehouse) {
                continue;
            }
            
            // Filter by user teams
            if (count($userTeams) > 0) {
                $productTeams = $product->teams()->pluck('teams.id')->toArray();
                $warehouseTeams = $warehouse->teams()->pluck('teams.id')->toArray();
                
                $productAccess = count(array_intersect($userTeams, $productTeams)) > 0;
                $warehouseAccess = count(array_intersect($userTeams, $warehouseTeams)) > 0;
                
                if (!$productAccess || !$warehouseAccess) {
                    continue;
                }
            }
            
            // Filter by specific team if provided
            if ($teamId) {
                $productTeams = $product->teams()->pluck('teams.id')->toArray();
                if (!in_array($teamId, $productTeams)) {
                    continue;
                }
            }
            
            $quantity = (float) $result->quantity;
            
            $inventory[] = [
                'productId' => $result->product_id,
                'warehouseId' => $result->warehouse_id,
                'quantity' => $quantity,
                'product' => $product,
                'warehouse' => $warehouse,
            ];
        }
        
        return response()->json($inventory);
    }

    /**
     * Advanced search for transactions
     */
    public function searchTransactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $userTeams = $user->teams()->pluck('teams.id')->toArray();
        
        $query = Transaction::with(['product', 'warehouse'])
            ->orderBy('created_at', 'desc');
        
        // Filter by user teams - including team transfers
        if (count($userTeams) > 0) {
            $user = $request->user();
            $query->where(function($q) use ($userTeams, $user) {
                // الحالة 1: الحركات العادية - فقط إذا كان المستخدم هو المنشئ أو يشارك فريق مع المنشئ
                // (فقط إذا لم تكن حركة نقل بين فِرق)
                $q->where(function($subQuery) use ($userTeams, $user) {
                    // الحركات العادية فقط (بدون نقل بين فِرق)
                    $subQuery->whereNull('from_team_id')->whereNull('to_team_id')
                        ->where(function($q0) use ($userTeams, $user) {
                            // إما المستخدم هو المنشئ
                            $q0->where('created_by', $user->id)
                                // أو يشارك فريق مع المنشئ والمنتج والمخزن من الفِرق المشتركة
                                ->orWhere(function($q1) use ($userTeams) {
                                    // التحقق من أن المنشئ يشارك فريق مع المستخدم الحالي
                                    $q1->whereHas('createdBy', function($q2) use ($userTeams) {
                                        $q2->whereHas('teams', function($q3) use ($userTeams) {
                                            $q3->whereIn('teams.id', $userTeams);
                                        });
                                    })
                                    // والمنتج والمخزن من فِرق المستخدم
                                    ->whereHas('product', function($q2) use ($userTeams) {
                                        $q2->whereHas('teams', function($q3) use ($userTeams) {
                                            $q3->whereIn('teams.id', $userTeams);
                                        });
                                    })->whereHas('warehouse', function($q2) use ($userTeams) {
                                        $q2->whereHas('teams', function($q3) use ($userTeams) {
                                            $q3->whereIn('teams.id', $userTeams);
                                        });
                                    });
                                });
                        });
                });
                
                // الحالة 2: حركات النقل - الفريق المصدر (from_team_id) من فِرق المستخدم
                // (يظهر النقص في أغراض فريق المستخدم)
                $q->orWhere(function($subQuery) use ($userTeams) {
                    $subQuery->whereIn('from_team_id', $userTeams);
                });
                
                // الحالة 3: حركات النقل - الفريق الوجهة (to_team_id) من فِرق المستخدم
                // (يظهر الدخول لأغراض فريق المستخدم)
                $q->orWhere(function($subQuery) use ($userTeams) {
                    $subQuery->whereIn('to_team_id', $userTeams);
                });
            });
        }
        
        // Search by product name or code
        if ($request->has('product_search')) {
            $search = $request->get('product_search');
            $query->whereHas('product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        
        // Filter by warehouse
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->get('warehouse_id'));
        }
        
        // Filter by transaction type
        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }
        
        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->get('start_date'));
        }
        
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->get('end_date'));
        }
        
        // Filter by serial number
        if ($request->has('serial_number')) {
            $query->where('serial_number', 'like', "%{$request->get('serial_number')}%");
        }
        
        $transactions = $query->get();
        
        return response()->json($transactions);
    }

    /**
     * Get warehouse details with full statistics
     */
    public function getWarehouseDetails(Request $request, $warehouseId): JsonResponse
    {
        $user = $request->user();
        $userTeams = $user->teams()->pluck('teams.id')->toArray();
        
        $warehouse = Warehouse::with('teams')->findOrFail($warehouseId);
        
        // Check access
        if (count($userTeams) > 0) {
            $warehouseTeams = $warehouse->teams()->pluck('teams.id')->toArray();
            $warehouseAccess = count(array_intersect($userTeams, $warehouseTeams)) > 0;
            
            if (!$warehouseAccess) {
                return response()->json(['error' => 'غير مصرح لك بالوصول إلى هذا المخزن'], 403);
            }
        }
        
        // Get inventory statistics
        $inventoryQuery = Transaction::select([
                'product_id',
                DB::raw('SUM(CASE WHEN type = "in" THEN quantity ELSE -quantity END) as quantity')
            ])
            ->where('warehouse_id', $warehouseId)
            ->groupBy('product_id');
        
        $inventoryResults = $inventoryQuery->get();
        
        $totalProducts = 0;
        $totalQuantity = 0;
        $products = [];
        
        foreach ($inventoryResults as $result) {
            $product = Product::with('teams')->find($result->product_id);
            if (!$product) continue;
            
            $quantity = (float) $result->quantity;
            if ($quantity > 0) {
                $totalProducts++;
                $totalQuantity += $quantity;
                $products[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                ];
            }
        }
        
        // Get transaction statistics
        $transactionsQuery = Transaction::where('warehouse_id', $warehouseId);
        
        if (count($userTeams) > 0) {
            $transactionsQuery->whereHas('product', function($q) use ($userTeams) {
                $q->whereHas('teams', function($q2) use ($userTeams) {
                    $q2->whereIn('teams.id', $userTeams);
                });
            });
        }
        
        $totalTransactions = $transactionsQuery->count();
        $totalIn = $transactionsQuery->where('type', 'in')->sum('quantity');
        $totalOut = $transactionsQuery->where('type', 'out')->sum('quantity');
        
        return response()->json([
            'warehouse' => $warehouse,
            'statistics' => [
                'total_products' => $totalProducts,
                'total_quantity' => $totalQuantity,
                'total_transactions' => $totalTransactions,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
            ],
            'products' => $products,
        ]);
    }
}
