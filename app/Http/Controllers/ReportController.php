<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $warehouses = Warehouse::all();
        return view('reports.index', compact('warehouses'));
    }

    public function exportInventoryExcel(Request $request, $warehouseId = null)
    {
        // Similar to Api\ReportController logic
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
        
        $inventory = $query->get();
        
        // Generate CSV
        $filename = 'inventory_report_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($inventory) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['اسم المنتج', 'الكود', 'المخزن', 'الكمية', 'الوحدة'], ',');
            
            foreach ($inventory as $item) {
                $product = \App\Models\Product::find($item->product_id);
                $warehouse = \App\Models\Warehouse::find($item->warehouse_id);
                
                fputcsv($file, [
                    $product->name ?? 'غير معروف',
                    $product->code ?? '',
                    $warehouse->name ?? 'غير معروف',
                    $item->quantity,
                    $product->unit ?? 'قطعة'
                ], ',');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    public function exportTransactionsExcel(Request $request)
    {
        $query = Transaction::with(['product', 'warehouse'])
            ->orderBy('created_at', 'desc');
        
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        $transactions = $query->get();
        
        $filename = 'transactions_report_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['التاريخ', 'النوع', 'اسم المنتج', 'الكود', 'المخزن', 'الكمية', 'السيريال', 'الملاحظات'], ',');
            
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->created_at->format('Y-m-d H:i'),
                    $transaction->type === 'in' ? 'دخول' : 'خروج',
                    $transaction->product->name ?? 'غير معروف',
                    $transaction->product->code ?? '',
                    $transaction->warehouse->name ?? 'غير معروف',
                    $transaction->quantity,
                    $transaction->serial_number ?? '',
                    $transaction->notes ?? ''
                ], ',');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}

