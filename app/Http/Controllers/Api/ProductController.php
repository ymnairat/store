<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Get all products (for transfer purposes - without team filtering)
     * يعرض جميع المنتجات بدون أي فلترة بالفِرق
     */
    public function getAllForTransfer(Request $request): JsonResponse
    {
        try {
            // الحصول على جميع المنتجات بدون أي فلترة
            // هذا مهم عند النقل بين الفِرق - المستخدم يجب أن يرى جميع المنتجات
            $products = Product::with('teams')->get();
            
            $userIds = [];
            foreach ($products as $product) {
                if ($product->teams && $product->teams->count() > 0) {
                    foreach ($product->teams as $team) {
                        if ($team->pivot && $team->pivot->created_by) {
                            $userIds[] = $team->pivot->created_by;
                        }
                    }
                }
            }
            $userIds = array_unique($userIds);
            $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');

            $products = $products->map(function($product) use ($users) {
                $productArray = $product->toArray();
                if ($product->teams && $product->teams->count() > 0) {
                    $productArray['teams'] = $product->teams->map(function($team) use ($users) {
                        $teamArray = $team->toArray();
                        $createdByUserId = $team->pivot && $team->pivot->created_by ? $team->pivot->created_by : null;
                        if ($createdByUserId && isset($users[$createdByUserId])) {
                            $createdByUser = $users[$createdByUserId];
                            $teamArray['added_by'] = [
                                'id' => $createdByUser->id,
                                'name' => $createdByUser->name
                            ];
                        } else {
                            $teamArray['added_by'] = null;
                        }
                        return $teamArray;
                    });
                } else {
                    $productArray['teams'] = [];
                }
                return $productArray;
            });

            return response()->json($products);
        } catch (\Exception $e) {
            \Log::error('Error in getAllForTransfer: ' . $e->getMessage());
            return response()->json([
                'error' => 'حدث خطأ في جلب المنتجات',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $userTeams = $user->teams()->pluck('teams.id')->toArray();
        
        // If user has teams, filter products by user teams
        if (count($userTeams) > 0) {
            $products = Product::with('teams')
                ->whereHas('teams', function($query) use ($userTeams) {
                    $query->whereIn('teams.id', $userTeams);
                })
                ->get();
        } else {
            // If user has no teams (admin), show all products
            $products = Product::with('teams')->get();
        }
        
        // Get all unique created_by user IDs
        $userIds = [];
        foreach ($products as $product) {
            foreach ($product->teams as $team) {
                if ($team->pivot->created_by) {
                    $userIds[] = $team->pivot->created_by;
                }
            }
        }
        $userIds = array_unique($userIds);
        
        // Eager load users in batch
        $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');
        
        // Format response to include team member info
        $products = $products->map(function($product) use ($users) {
            $productArray = $product->toArray();
            $productArray['teams'] = $product->teams->map(function($team) use ($users) {
                $teamArray = $team->toArray();
                // Get the user who created this relationship
                $createdByUserId = $team->pivot->created_by;
                if ($createdByUserId && isset($users[$createdByUserId])) {
                    $createdByUser = $users[$createdByUserId];
                    $teamArray['added_by'] = [
                        'id' => $createdByUser->id,
                        'name' => $createdByUser->name
                    ];
                } else {
                    $teamArray['added_by'] = null;
                }
                return $teamArray;
            });
            return $productArray;
        });
        
        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:products,code',
            'category' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
        ]);

        $product = Product::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'category' => $validated['category'] ?? null,
            'unit' => $validated['unit'] ?? 'قطعة',
            'price' => $validated['price'] ?? 0,
            'description' => $validated['description'] ?? null,
        ]);

        // Sync teams with created_by tracking
        if (isset($validated['team_ids']) && is_array($validated['team_ids'])) {
            $userId = $request->user()->id;
            $syncData = [];
            foreach ($validated['team_ids'] as $teamId) {
                $syncData[$teamId] = ['created_by' => $userId];
            }
            $product->teams()->sync($syncData);
        }

        // Load teams for response
        $product->load('teams');

        // Get created_by users
        $userIds = $product->teams->pluck('pivot.created_by')->filter()->unique()->toArray();
        $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');

        // Format response
        $productArray = $product->toArray();
        $productArray['teams'] = $product->teams->map(function($team) use ($users) {
            $teamArray = $team->toArray();
            $createdByUserId = $team->pivot->created_by;
            if ($createdByUserId && isset($users[$createdByUserId])) {
                $createdByUser = $users[$createdByUserId];
                $teamArray['added_by'] = [
                    'id' => $createdByUser->id,
                    'name' => $createdByUser->name
                ];
            } else {
                $teamArray['added_by'] = null;
            }
            return $teamArray;
        });

        return response()->json($productArray, 201);
    }

    public function show(string $id): JsonResponse
    {
        $product = Product::with('teams')->findOrFail($id);
        
        // Get created_by users
        $userIds = $product->teams->pluck('pivot.created_by')->filter()->unique()->toArray();
        $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');
        
        // Format response
        $productArray = $product->toArray();
        $productArray['teams'] = $product->teams->map(function($team) use ($users) {
            $teamArray = $team->toArray();
            $createdByUserId = $team->pivot->created_by;
            if ($createdByUserId && isset($users[$createdByUserId])) {
                $createdByUser = $users[$createdByUserId];
                $teamArray['added_by'] = [
                    'id' => $createdByUser->id,
                    'name' => $createdByUser->name
                ];
            } else {
                $teamArray['added_by'] = null;
            }
            return $teamArray;
        });
        
        return response()->json($productArray);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:255|unique:products,code,' . $id . ',id',
            'category' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
        ]);

        $product->update($validated);

        // Sync teams with created_by tracking
        if (array_key_exists('team_ids', $validated)) {
            $userId = $request->user()->id;
            $syncData = [];
            
            // Get existing relationships
            $existingTeams = $product->teams()->pluck('teams.id')->toArray();
            
            if (is_array($validated['team_ids']) && count($validated['team_ids']) > 0) {
                foreach ($validated['team_ids'] as $teamId) {
                    // Only set created_by for new relationships
                    if (!in_array($teamId, $existingTeams)) {
                        $syncData[$teamId] = ['created_by' => $userId];
                    } else {
                        // Keep existing relationship without updating created_by
                        $syncData[$teamId] = [];
                    }
                }
            }
            $product->teams()->sync($syncData);
        }

        // Load teams for response
        $product->load('teams');

        // Get created_by users
        $userIds = $product->teams->pluck('pivot.created_by')->filter()->unique()->toArray();
        $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');

        // Format response
        $productArray = $product->toArray();
        $productArray['teams'] = $product->teams->map(function($team) use ($users) {
            $teamArray = $team->toArray();
            $createdByUserId = $team->pivot->created_by;
            if ($createdByUserId && isset($users[$createdByUserId])) {
                $createdByUser = $users[$createdByUserId];
                $teamArray['added_by'] = [
                    'id' => $createdByUser->id,
                    'name' => $createdByUser->name
                ];
            } else {
                $teamArray['added_by'] = null;
            }
            return $teamArray;
        });

        return response()->json($productArray);
    }

    public function destroy(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['success' => true]);
    }

    public function searchByCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $product = Product::with('teams')->where('code', $request->code)->first();
        
        if (!$product) {
            return response()->json(['error' => 'المنتج غير موجود'], 404);
        }

        // Get created_by users
        $userIds = $product->teams->pluck('pivot.created_by')->filter()->unique()->toArray();
        $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');

        // Format response
        $productArray = $product->toArray();
        $productArray['teams'] = $product->teams->map(function($team) use ($users) {
            $teamArray = $team->toArray();
            $createdByUserId = $team->pivot->created_by;
            if ($createdByUserId && isset($users[$createdByUserId])) {
                $createdByUser = $users[$createdByUserId];
                $teamArray['added_by'] = [
                    'id' => $createdByUser->id,
                    'name' => $createdByUser->name
                ];
            } else {
                $teamArray['added_by'] = null;
            }
            return $teamArray;
        });

        return response()->json($productArray);
    }
}
