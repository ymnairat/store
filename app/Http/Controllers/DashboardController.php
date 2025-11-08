<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{

    public function index()
    {
        $stats = [
            'products' => Product::count(),
            'warehouses' => Warehouse::count(),
            'totalItems' => DB::table('transactions')
                ->selectRaw('SUM(CASE WHEN type = "in" THEN quantity ELSE -quantity END) as total')
                ->value('total') ?? 0,
            'transactions' => Transaction::count(),
        ];

        return view('dashboard', compact('stats'));
    }
}

