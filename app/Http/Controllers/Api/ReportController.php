<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    /**
     * Export inventory report as Excel/CSV for a specific warehouse
     */
    public function exportInventoryExcel(Request $request, $warehouseId = null)
    {
        $user = $request->user();
        $userTeams = $user->teams()->pluck('teams.id')->toArray();
        
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $data = $this->getInventoryData($userTeams, $warehouseId, $startDate, $endDate);
        
        $warehouse = $warehouseId ? Warehouse::find($warehouseId) : null;
        $warehouseName = $warehouse ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $warehouse->name) : 'all';
        $filename = "inventory_report_{$warehouseName}_" . now()->format('Y-m-d') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($data, $warehouse, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            $headers = ['#', 'Product Name', 'Product Code'];
            if (!$warehouse) {
                $headers[] = 'Warehouse Name';
            }
            $headers[] = 'Available Quantity';
            $headers[] = 'Unit';
            $headers[] = 'Teams';
            fputcsv($file, $headers);
            
            // Data rows
            $index = 1;
            foreach ($data as $item) {
                $unitTranslations = ['قطعة' => 'Piece', 'كيلو' => 'Kilogram', 'لتر' => 'Liter', 'متر' => 'Meter', 'صندوق' => 'Box', 'كيس' => 'Bag', 'علبة' => 'Can'];
                $teamTranslations = ['الإنترنت' => 'Internet', 'الكهرباء' => 'Electricity', 'المياه' => 'Water'];
                
                $translatedUnit = $unitTranslations[$item['product']->unit] ?? $item['product']->unit;
                $translatedTeams = $item['product']->teams->map(function($team) use ($teamTranslations) {
                    return $teamTranslations[$team->name] ?? $team->name;
                })->join(', ');
                
                $row = [
                    $index++,
                    $item['product']->name,
                    $item['product']->code,
                ];
                
                if (!$warehouse) {
                    $row[] = $item['warehouse']->name;
                }
                
                $row[] = number_format($item['quantity'], 2);
                $row[] = $translatedUnit;
                $row[] = $translatedTeams;
                
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export transactions report as Excel/CSV
     */
    public function exportTransactionsExcel(Request $request)
    {
        $user = $request->user();
        $userTeams = $user->teams()->pluck('teams.id')->toArray();
        
        $warehouseId = $request->query('warehouse_id');
        $type = $request->query('type'); // 'in', 'out', or null for all
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $data = $this->getTransactionsData($userTeams, $warehouseId, $type, $startDate, $endDate);
        
        $filename = "transactions_report_" . now()->format('Y-m-d') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $warehouse = $warehouseId ? Warehouse::find($warehouseId) : null;

        $callback = function() use ($data, $warehouse) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            $headers = ['#', 'Transaction Date', 'Type', 'Product Name', 'Product Code'];
            if (!$warehouse) {
                $headers[] = 'Warehouse Name';
            }
            $headers[] = 'Quantity';
            $headers[] = 'Serial Number';
            $headers[] = 'Created By';
            $headers[] = 'Notes';
            fputcsv($file, $headers);
            
            // Data rows
            $index = 1;
            foreach ($data as $transaction) {
                $row = [
                    $index++,
                    $transaction->created_at->format('Y-m-d H:i'),
                    $transaction->type === 'in' ? 'In' : 'Out',
                    $transaction->product->name,
                    $transaction->product->code,
                ];
                
                if (!$warehouse) {
                    $row[] = $transaction->warehouse->name;
                }
                
                $row[] = number_format($transaction->quantity, 2);
                $row[] = $transaction->serial_number ?? '-';
                $row[] = $transaction->createdBy->name ?? '-';
                $row[] = $transaction->notes ?? '-';
                
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Get inventory data based on user teams and warehouse filter
     * نفس منطق InventoryController لحساب المخزون حسب الفِرق
     */
    private function getInventoryData(array $userTeams, $warehouseId = null, $startDate = null, $endDate = null)
    {
        // حساب المخزون حسب الفِرق
        // لكل منتج-مخزن، نحسب المخزون لكل فريق على حدة
        $allTransactions = Transaction::query();
        
        if ($warehouseId) {
            $allTransactions->where('warehouse_id', $warehouseId);
        }
        
        // Filter by date range for inventory at specific date
        if ($startDate) {
            $allTransactions->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $allTransactions->whereDate('created_at', '<=', $endDate);
        }
        
        $transactions = $allTransactions->get();
        
        // تجميع المخزون حسب product_id, warehouse_id, team_id
        $inventoryMap = [];
        
        foreach ($transactions as $transaction) {
            $product = Product::find($transaction->product_id);
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
            $product = Product::find($result['product_id']);
            $warehouse = Warehouse::find($result['warehouse_id']);

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
                    'product' => $product,
                    'warehouse' => $warehouse,
                    'quantity' => 0,
                    'relevantTeamId' => $relevantTeamId, // نستخدم هذا لتجميع المخزون من فريق واحد فقط
                ];
            }
            
            // إذا كان هناك فريق محدد، نجمع فقط من هذا الفريق
            if ($userInventoryMap[$key]['relevantTeamId'] && $result['team_id'] !== $userInventoryMap[$key]['relevantTeamId']) {
                continue;
            }
            
            $userInventoryMap[$key]['quantity'] += (float) $result['quantity'];
        }
        
        // تحويل إلى array
        foreach ($userInventoryMap as $key => $item) {
            $quantity = (float) $item['quantity'];
            
            if ($quantity <= 0 && !$warehouseId) {
                continue;
            }
            
            // إزالة relevantTeamId من الـ output
            unset($item['relevantTeamId']);
            $inventory[] = $item;
        }

        return $inventory;
    }

    /**
     * Get transactions data based on filters
     */
    private function getTransactionsData(array $userTeams, $warehouseId = null, $type = null, $startDate = null, $endDate = null, $user = null)
    {
        $query = Transaction::with(['product', 'warehouse', 'createdBy', 'fromTeam', 'toTeam'])
            ->orderBy('created_at', 'desc');

        if (count($userTeams) > 0) {
            $query->where(function($q) use ($userTeams, $user) {
                // الحالة 1: الحركات العادية - فقط إذا كان المستخدم هو المنشئ أو يشارك فريق مع المنشئ
                // (فقط إذا لم تكن حركة نقل بين فِرق)
                $q->where(function($subQuery) use ($userTeams, $user) {
                    // الحركات العادية فقط (بدون نقل بين فِرق)
                    $subQuery->whereNull('from_team_id')->whereNull('to_team_id')
                        ->where(function($q0) use ($userTeams, $user) {
                            // إما المستخدم المحدد هو المنشئ (إذا كان موجود)
                            if ($user) {
                                $q0->where('created_by', $user->id);
                            }
                            // أو يشارك فريق مع المنشئ والمنتج والمخزن من الفِرق المشتركة
                            // (هذا يطبق دائماً للتحقق من أن المنشئ يشارك فريق مع المستخدم الحالي)
                            $q0->orWhere(function($q1) use ($userTeams) {
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

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->get();
    }

}
