<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $warehouseId = $request->query('warehouseId');
        
        // Get user's teams
        $userTeams = $user->teams()->pluck('teams.id')->toArray();

        // حساب المخزون حسب الفِرق
        // لكل منتج-مخزن، نحسب المخزون لكل فريق على حدة
        $allTransactions = Transaction::query();
        
        if ($warehouseId) {
            $allTransactions->where('warehouse_id', $warehouseId);
        }
        
        $transactions = $allTransactions->get();
        
        // تجميع المخزون حسب product_id, warehouse_id, team_id
        $inventoryMap = [];
        
        foreach ($transactions as $transaction) {
            $product = \App\Models\Product::find($transaction->product_id);
            if (!$product) continue;
            
            // تحديد الفريق لكل حركة
            $targetTeams = [];
            
            if ($transaction->type === 'in') {
                // للدخول
                if ($transaction->to_team_id) {
                    // نقل بين فِرق: الدخول للفريق الوجهة فقط
                    $targetTeams = [$transaction->to_team_id];
                } else {
                    // حركة عادية: نحسبها فقط لأول فريق مشترك بين المستخدم والمنتج
                    if ($transaction->created_by) {
                        $creator = \App\Models\User::find($transaction->created_by);
                        if ($creator) {
                            $creatorTeams = $creator->teams()->pluck('teams.id')->toArray();
                            $productTeams = $product->teams()->pluck('teams.id')->toArray();
                            // التقاطع بين فِرق المستخدم وفِرق المنتج
                            $intersect = array_intersect($creatorTeams, $productTeams);
                            // نستخدم أول فريق من التقاطع فقط (وليس جميع الفِرق)
                            if (!empty($intersect)) {
                                $targetTeams = [reset($intersect)];
                            } elseif (!empty($productTeams)) {
                                // إذا لم يكن هناك تقاطع، نستخدم أول فريق من المنتج (fallback)
                                $targetTeams = [reset($productTeams)];
                            } else {
                                $targetTeams = [];
                            }
                        } else {
                            // إذا لم نجد المستخدم، نستخدم أول فريق من المنتج
                            $productTeams = $product->teams()->pluck('teams.id')->toArray();
                            $targetTeams = !empty($productTeams) ? [reset($productTeams)] : [];
                        }
                    } else {
                        // إذا لم يكن هناك created_by، نستخدم أول فريق من المنتج
                        $productTeams = $product->teams()->pluck('teams.id')->toArray();
                        $targetTeams = !empty($productTeams) ? [reset($productTeams)] : [];
                    }
                }
            } else {
                // للخروج
                if ($transaction->from_team_id) {
                    // نقل بين فِرق: الخروج من الفريق المصدر فقط
                    $targetTeams = [$transaction->from_team_id];
                } else {
                    // حركة عادية: نحسبها فقط لأول فريق مشترك بين المستخدم والمنتج
                    if ($transaction->created_by) {
                        $creator = \App\Models\User::find($transaction->created_by);
                        if ($creator) {
                            $creatorTeams = $creator->teams()->pluck('teams.id')->toArray();
                            $productTeams = $product->teams()->pluck('teams.id')->toArray();
                            // التقاطع بين فِرق المستخدم وفِرق المنتج
                            $intersect = array_intersect($creatorTeams, $productTeams);
                            // نستخدم أول فريق من التقاطع فقط (وليس جميع الفِرق)
                            if (!empty($intersect)) {
                                $targetTeams = [reset($intersect)];
                            } elseif (!empty($productTeams)) {
                                // إذا لم يكن هناك تقاطع، نستخدم أول فريق من المنتج (fallback)
                                $targetTeams = [reset($productTeams)];
                            } else {
                                $targetTeams = [];
                            }
                        } else {
                            // إذا لم نجد المستخدم، نستخدم أول فريق من المنتج
                            $productTeams = $product->teams()->pluck('teams.id')->toArray();
                            $targetTeams = !empty($productTeams) ? [reset($productTeams)] : [];
                        }
                    } else {
                        // إذا لم يكن هناك created_by، نستخدم أول فريق من المنتج
                        $productTeams = $product->teams()->pluck('teams.id')->toArray();
                        $targetTeams = !empty($productTeams) ? [reset($productTeams)] : [];
                    }
                }
            }
            
            // حساب المخزون لكل فريق
            foreach ($targetTeams as $teamId) {
                $key = $transaction->product_id . '_' . $transaction->warehouse_id . '_' . $teamId;
                
                if (!isset($inventoryMap[$key])) {
                    $inventoryMap[$key] = [
                        'product_id' => $transaction->product_id,
                        'warehouse_id' => $transaction->warehouse_id,
                        'team_id' => $teamId,
                        'quantity' => 0
                    ];
                }
                
                // حساب الكمية: + للدخول، - للخروج
                $inventoryMap[$key]['quantity'] += $transaction->type === 'in' ? $transaction->quantity : -$transaction->quantity;
            }
        }
        
        $results = array_values($inventoryMap);

        $inventory = [];
        
        // تجميع المخزون حسب product_id و warehouse_id (للمستخدم)
        $userInventoryMap = [];
        
        foreach ($results as $result) {
            $product = \App\Models\Product::find($result['product_id']);
            $warehouse = \App\Models\Warehouse::find($result['warehouse_id']);

            if (!$product || !$warehouse) {
                continue;
            }

            // Filter by user teams if user has teams
            if (count($userTeams) > 0) {
                // Check if product belongs to user's teams
                $productTeams = $product->teams()->pluck('teams.id')->toArray();
                $productMatches = count(array_intersect($userTeams, $productTeams)) > 0;
                
                // Check if warehouse belongs to user's teams
                $warehouseTeams = $warehouse->teams()->pluck('teams.id')->toArray();
                $warehouseMatches = count(array_intersect($userTeams, $warehouseTeams)) > 0;
                
                // Only include if both product and warehouse match user's teams
                if (!$productMatches || !$warehouseMatches) {
                    continue;
                }
                
                // فقط إذا كان المخزون متعلق بفِرق المستخدم
                if (!in_array($result['team_id'], $userTeams)) {
                    continue;
                }
            }

            $key = $result['product_id'] . '_' . $result['warehouse_id'];
            
            // إذا كان المستخدم في عدة فِرق، نجمع المخزون فقط من أول فريق مشترك
            // نستخدم أول فريق مشترك بين فِرق المستخدم وفِرق المنتج
            if (!isset($userInventoryMap[$key])) {
                $relevantTeamId = null;
                if (count($userTeams) > 0) {
                    $productTeams = $product->teams()->pluck('teams.id')->toArray();
                    $intersect = array_intersect($userTeams, $productTeams);
                    if (!empty($intersect)) {
                        $relevantTeamId = reset($intersect);
                    }
                }
                
                $userInventoryMap[$key] = [
                    'productId' => $result['product_id'],
                    'warehouseId' => $result['warehouse_id'],
                    'quantity' => 0,
                    'product' => $product,
                    'warehouse' => $warehouse,
                    'relevantTeamId' => $relevantTeamId, // نستخدم هذا لتجميع المخزون من فريق واحد فقط
                ];
            }
            
            // إذا كان هناك فريق محدد، نجمع فقط من هذا الفريق
            if ($userInventoryMap[$key]['relevantTeamId'] && $result['team_id'] !== $userInventoryMap[$key]['relevantTeamId']) {
                continue;
            }
            
            $userInventoryMap[$key]['quantity'] += (float) $result['quantity'];
        }
        
        // تحويل إلى array وإزالة relevantTeamId (للاستخدام الداخلي فقط)
        foreach ($userInventoryMap as $key => $item) {
            $quantity = (float) $item['quantity'];
            
            // Only show items with stock unless filtering by warehouse
            if ($quantity <= 0 && !$warehouseId) {
                continue;
            }
            
            // إزالة relevantTeamId من الـ output
            unset($item['relevantTeamId']);
            $inventory[] = $item;
        }

        return response()->json($inventory);
    }
}