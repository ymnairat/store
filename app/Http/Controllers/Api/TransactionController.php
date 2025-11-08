<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $userTeams = $user->teams()->pluck('teams.id')->toArray();
        
        $query = Transaction::with(['product', 'warehouse', 'createdBy', 'fromTeam', 'toTeam'])
            ->orderBy('created_at', 'desc');
        
        // If user has teams, filter transactions by user teams
        if (count($userTeams) > 0) {
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
        
        $transactions = $query->get();
        return response()->json($transactions);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $userTeams = $user->teams()->pluck('teams.id')->toArray();
        
        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'warehouse_id' => 'required|uuid|exists:warehouses,id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|numeric|min:0.01',
            'serial_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'from_team_id' => 'nullable|uuid|exists:teams,id', // من فريق (للنقل)
            'to_team_id' => 'nullable|uuid|exists:teams,id', // إلى فريق (للنقل)
            'is_return' => 'nullable|boolean', // للإرجاع
        ]);

        // Check if product and warehouse exist
        $product = Product::with('teams')->findOrFail($validated['product_id']);
        $warehouse = \App\Models\Warehouse::with('teams')->findOrFail($validated['warehouse_id']);
        
        // Check access if user has teams
        // استثناء: إذا كان هناك نقل بين فِرق أو إرجاع، السماح بالوصول للمنتج حتى لو لم يكن من فريق المستخدم
        $isTransfer = isset($validated['from_team_id']) && $validated['from_team_id'] && isset($validated['to_team_id']) && $validated['to_team_id'] && $validated['type'] === 'out';
        $isReturn = isset($validated['is_return']) && $validated['is_return'] && $validated['type'] === 'out';
        
        if (count($userTeams) > 0) {
            $productTeams = $product->teams()->pluck('teams.id')->toArray();
            $warehouseTeams = $warehouse->teams()->pluck('teams.id')->toArray();
            
            $productAccess = count(array_intersect($userTeams, $productTeams)) > 0;
            $warehouseAccess = count(array_intersect($userTeams, $warehouseTeams)) > 0;
            
            // إذا كان نقل لفريق آخر أو إرجاع، السماح بالوصول للمنتج حتى لو لم يكن من فريق المستخدم
            if (!$productAccess && !$isTransfer && !$isReturn) {
                return response()->json(['error' => 'غير مصرح لك بالوصول إلى هذا المنتج'], 403);
            }
            
            if (!$warehouseAccess) {
                return response()->json(['error' => 'غير مصرح لك بالوصول إلى هذا المخزن'], 403);
            }
        }

        // For 'out' transactions, check stock
        if ($validated['type'] === 'out') {
            $currentStock = $this->getCurrentStock($validated['product_id'], $validated['warehouse_id']);
            if ($currentStock < $validated['quantity']) {
                return response()->json([
                    'error' => 'الكمية المتوفرة غير كافية'
                ], 400);
            }
        }

        $validated['created_by'] = $user->id;
        
        // إذا كان هناك إرجاع منتج
        if ($isReturn) {
            // البحث عن آخر نقل لهذا المنتج من فريق المستخدم
            $userTeams = $user->teams()->pluck('teams.id')->toArray();
            $lastTransfer = Transaction::where('product_id', $validated['product_id'])
                ->where('warehouse_id', $validated['warehouse_id'])
                ->where('type', 'in')
                ->whereNotNull('from_team_id')
                ->whereNotNull('to_team_id')
                ->whereIn('to_team_id', $userTeams) // المنتج تم نقله لفريق المستخدم
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$lastTransfer || !$lastTransfer->from_team_id) {
                return response()->json([
                    'error' => 'لم يتم العثور على نقل سابق لهذا المنتج يمكن إرجاعه'
                ], 400);
            }
            
            // إنشاء transaction خروج من فريق المستخدم (إرجاع)
            $returnOutTransaction = Transaction::create([
                'product_id' => $validated['product_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'type' => 'out',
                'quantity' => $validated['quantity'],
                'serial_number' => $validated['serial_number'] ?? null,
                'notes' => ($validated['notes'] ?? '') . ' [إرجاع منتج]',
                'created_by' => $user->id,
                'from_team_id' => $lastTransfer->to_team_id, // الفريق الحالي
                'to_team_id' => $lastTransfer->from_team_id, // الفريق الأصلي
            ]);
            
            // إنشاء transaction دخول للفريق الأصلي
            $returnInTransaction = Transaction::create([
                'product_id' => $validated['product_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'type' => 'in',
                'quantity' => $validated['quantity'],
                'serial_number' => $validated['serial_number'] ?? null,
                'notes' => ($validated['notes'] ?? '') . ' [تم إرجاع المنتج]',
                'created_by' => $user->id,
                'from_team_id' => $lastTransfer->to_team_id, // من الفريق الذي أرجعه
                'to_team_id' => $lastTransfer->from_team_id, // للفريق الأصلي
                'transfer_transaction_id' => $returnOutTransaction->id,
            ]);
            
            // تحديث transaction الخروج برابط transaction الدخول
            $returnOutTransaction->update(['transfer_transaction_id' => $returnInTransaction->id]);
            
            return response()->json([
                'out_transaction' => $returnOutTransaction->load(['product', 'warehouse', 'createdBy', 'fromTeam', 'toTeam']),
                'in_transaction' => $returnInTransaction->load(['product', 'warehouse', 'createdBy', 'fromTeam', 'toTeam']),
                'message' => 'تم إرجاع المنتج بنجاح'
            ], 201);
        }
        
        // إذا كان هناك نقل بين فِرق (from_team_id و to_team_id موجودان)
        if ($validated['type'] === 'out' && isset($validated['from_team_id']) && $validated['from_team_id'] && isset($validated['to_team_id']) && $validated['to_team_id']) {
            $fromTeamId = $validated['from_team_id'];
            $toTeamId = $validated['to_team_id'];
            
            // التحقق من أن الفريقين مختلفين
            if ($fromTeamId === $toTeamId) {
                return response()->json([
                    'error' => 'لا يمكن النقل من وإلى نفس الفريق'
                ], 400);
            }
            
            // التحقق من أن المنتج موجود في الفريق المصدر
            $productTeams = $product->teams()->pluck('teams.id')->toArray();
            if (!in_array($fromTeamId, $productTeams)) {
                return response()->json([
                    'error' => 'المنتج لا ينتمي إلى الفريق المصدر المحدد'
                ], 400);
            }
            
            // التحقق من أن هناك مخزون كافٍ في الفريق المصدر
            // (يتم التحقق من المخزون العام مسبقاً)
            
            // الحصول على أسماء الفِرق للرسائل
            $fromTeam = \App\Models\Team::find($fromTeamId);
            $toTeam = \App\Models\Team::find($toTeamId);
            
            // إنشاء transaction خروج من الفريق المصدر
            $outTransaction = Transaction::create([
                'product_id' => $validated['product_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'type' => 'out',
                'quantity' => $validated['quantity'],
                'serial_number' => $validated['serial_number'] ?? null,
                'notes' => ($validated['notes'] ?? '') . ' [نقل من ' . ($fromTeam->name ?? 'فريق') . ' إلى ' . ($toTeam->name ?? 'فريق') . ']',
                'created_by' => $user->id,
                'from_team_id' => $fromTeamId,
                'to_team_id' => $toTeamId,
            ]);
            
            // إنشاء transaction دخول للفريق الوجهة
            $inTransaction = Transaction::create([
                'product_id' => $validated['product_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'type' => 'in',
                'quantity' => $validated['quantity'],
                'serial_number' => $validated['serial_number'] ?? null,
                'notes' => ($validated['notes'] ?? '') . ' [نقل من ' . ($fromTeam->name ?? 'فريق') . ' - تم استلام المنتج]',
                'created_by' => $user->id,
                'from_team_id' => $fromTeamId,
                'to_team_id' => $toTeamId,
                'transfer_transaction_id' => $outTransaction->id, // ربط مع transaction الخروج
            ]);
            
            // تحديث transaction الخروج برابط transaction الدخول
            $outTransaction->update(['transfer_transaction_id' => $inTransaction->id]);
            
            // إضافة المنتج للفريق الوجهة (إذا لم يكن موجوداً)
            $product->teams()->syncWithoutDetaching([$toTeamId => ['created_by' => $user->id]]);
            
            return response()->json([
                'out_transaction' => $outTransaction->load(['product', 'warehouse', 'createdBy', 'fromTeam', 'toTeam']),
                'in_transaction' => $inTransaction->load(['product', 'warehouse', 'createdBy', 'fromTeam', 'toTeam']),
                'message' => 'تم نقل المادة من ' . ($fromTeam->name ?? 'الفريق المصدر') . ' إلى ' . ($toTeam->name ?? 'الفريق الوجهة') . ' بنجاح'
            ], 201);
        }
        
        // Transaction عادية (بدون نقل بين فِرق)
        $transaction = Transaction::create($validated);
        return response()->json($transaction->load(['product', 'warehouse', 'createdBy', 'fromTeam', 'toTeam']), 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $userTeams = $user->teams()->pluck('teams.id')->toArray();
        
        $transaction = Transaction::with(['product', 'warehouse'])->findOrFail($id);
        
        // التحقق من الصلاحيات
        // يمكن للمستخدم حذف الحركة إذا:
        // 1. هو منشئ الحركة
        // 2. أو الحركة متعلقة بفِرق المستخدم
        // 3. أو المستخدم لديه صلاحية حذف الحركات
        $canDelete = false;
        
        if ($transaction->created_by === $user->id) {
            $canDelete = true;
        } elseif (count($userTeams) > 0) {
            // التحقق من أن الحركة متعلقة بفِرق المستخدم
            $productTeams = $transaction->product->teams()->pluck('teams.id')->toArray();
            $warehouseTeams = $transaction->warehouse->teams()->pluck('teams.id')->toArray();
            
            $productMatches = count(array_intersect($userTeams, $productTeams)) > 0;
            $warehouseMatches = count(array_intersect($userTeams, $warehouseTeams)) > 0;
            
            // التحقق من حركات النقل
            if ($transaction->from_team_id && in_array($transaction->from_team_id, $userTeams)) {
                $canDelete = true;
            } elseif ($transaction->to_team_id && in_array($transaction->to_team_id, $userTeams)) {
                $canDelete = true;
            } elseif ($productMatches && $warehouseMatches && !$transaction->from_team_id && !$transaction->to_team_id) {
                // حركة عادية متعلقة بفِرق المستخدم
                $canDelete = true;
            }
        } else {
            // المستخدم بدون فِرق (مدير عام)
            $canDelete = true;
        }
        
        if (!$canDelete) {
            return response()->json([
                'error' => 'غير مصرح لك بحذف هذه الحركة'
            ], 403);
        }
        
        // إذا كانت حركة نقل، نحذف الحركة المرتبطة أيضاً
        if ($transaction->transfer_transaction_id) {
            $linkedTransaction = Transaction::find($transaction->transfer_transaction_id);
            if ($linkedTransaction) {
                // إزالة الرابط من الحركة المرتبطة
                $linkedTransaction->update(['transfer_transaction_id' => null]);
                // حذف الحركة المرتبطة
                $linkedTransaction->delete();
            }
        }
        
        // البحث عن حركات أخرى مرتبطة بهذه الحركة
        $relatedTransactions = Transaction::where('transfer_transaction_id', $transaction->id)->get();
        foreach ($relatedTransactions as $related) {
            $related->update(['transfer_transaction_id' => null]);
            $related->delete();
        }
        
        // حذف الحركة
        $transaction->delete();
        
        return response()->json([
            'message' => 'تم حذف الحركة بنجاح'
        ], 200);
    }

    private function getCurrentStock(string $productId, string $warehouseId): float
    {
        $stock = Transaction::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->selectRaw('SUM(CASE WHEN type = "in" THEN quantity ELSE -quantity END) as stock')
            ->value('stock');

        return $stock ?? 0;
    }
}
