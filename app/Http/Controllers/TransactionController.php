<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:transactions.view')->only(['index']);
        $this->middleware('permission:transactions.create')->only(['store']);
        $this->middleware('permission:transactions.delete')->only(['destroy']);
    }

    public function index()
    {
        $query = Transaction::with(['product', 'warehouse', 'warehouseFrom', 'warehouseTo', 'fromTeam', 'toTeam', 'createdBy']);

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
                    $query->whereRaw('1 = 0');
                }
            }
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();
        $products = Product::with('teams')->get();
        $warehouses = Warehouse::all();
        $teams = Team::all();

        return view('transactions.index', compact('transactions', 'products', 'warehouses', 'teams'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $userTeams = $user->teams()->pluck('teams.id')->toArray();

        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'warehouse_id' => 'required|uuid|exists:warehouses,id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|numeric|min:0.01',
            'serial_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'from_team_id' => 'nullable|uuid|exists:teams,id',
            'to_team_id' => 'nullable|uuid|exists:teams,id',
            'warehouse_from_id' => 'nullable|uuid|exists:warehouses,id',
            'warehouse_to_id' => 'nullable|uuid|exists:warehouses,id',
            //'is_return' => 'nullable|boolean',
        ]);

        $validated['created_by'] = $user->id;

        // Handle team transfer
        if (isset($validated['from_team_id']) && isset($validated['to_team_id'])) {
            // Use existing API logic
            return $this->handleTeamTransfer($validated, $user, $userTeams);
        }

        // Handle warehouse transfer
        if (isset($validated['warehouse_from_id']) && isset($validated['warehouse_to_id'])) {
            return $this->handleWarehouseTransfer($validated, $user, $userTeams);
        }

        // Regular transaction
        $transaction = Transaction::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تمت إضافة الحركة بنجاح',
                'transaction' => $transaction->load(['product', 'warehouse', 'createdBy'])
            ]);
        }

        return redirect()->route('transactions.index')->with('success', 'تمت إضافة الحركة بنجاح');
    }

    private function handleTeamTransfer($validated, $user, $userTeams)
    {
        // Copy logic from Api\TransactionController
        $fromTeamId = $validated['from_team_id'];
        $toTeamId = $validated['to_team_id'];

        if ($fromTeamId === $toTeamId) {
            return response()->json(['error' => 'لا يمكن النقل من وإلى نفس الفريق'], 400);
        }

        // Check permissions and stock
        $currentStock = $this->getCurrentStock($validated['product_id'], $validated['warehouse_id'], $fromTeamId);
        if ($currentStock < $validated['quantity']) {
            return response()->json(['error' => 'الكمية المتوفرة غير كافية'], 400);
        }

        // Create out and in transactions
        $outTransaction = Transaction::create([
            'product_id' => $validated['product_id'],
            'warehouse_id' => $validated['warehouse_id'],
            'type' => 'out',
            'quantity' => $validated['quantity'],
            'serial_number' => $validated['serial_number'] ?? null,
            'notes' => ($validated['notes'] ?? '') . ' [نقل من فريق إلى فريق]',
            'created_by' => $user->id,
            'from_team_id' => $fromTeamId,
            'to_team_id' => $toTeamId,
        ]);

        $inTransaction = Transaction::create([
            'product_id' => $validated['product_id'],
            'warehouse_id' => $validated['warehouse_id'],
            'type' => 'in',
            'quantity' => $validated['quantity'],
            'serial_number' => $validated['serial_number'] ?? null,
            'notes' => ($validated['notes'] ?? '') . ' [نقل من فريق - تم استلام المنتج]',
            'created_by' => $user->id,
            'from_team_id' => $fromTeamId,
            'to_team_id' => $toTeamId,
            'transfer_transaction_id' => $outTransaction->id,
        ]);

        $outTransaction->update(['transfer_transaction_id' => $inTransaction->id]);

        return response()->json([
            'success' => true,
            'message' => 'تم نقل المنتج بنجاح',
            'out_transaction' => $outTransaction->load(['product', 'warehouse', 'fromTeam', 'toTeam']),
            'in_transaction' => $inTransaction->load(['product', 'warehouse', 'fromTeam', 'toTeam']),
        ]);
    }

    private function handleWarehouseTransfer($validated, $user, $userTeams)
    {
        // Similar logic for warehouse transfer
        $warehouseFromId = $validated['warehouse_from_id'];
        $warehouseToId = $validated['warehouse_to_id'];

        if ($warehouseFromId === $warehouseToId) {
            return response()->json(['error' => 'لا يمكن النقل من وإلى نفس المخزن'], 400);
        }

        $currentStock = $this->getCurrentStock($validated['product_id'], $warehouseFromId);
        if ($currentStock < $validated['quantity']) {
            return response()->json(['error' => 'الكمية المتوفرة في المخزن المصدر غير كافية'], 400);
        }

        $outTransaction = Transaction::create([
            'product_id' => $validated['product_id'],
            'warehouse_id' => $warehouseFromId,
            'warehouse_from_id' => $warehouseFromId,
            'warehouse_to_id' => $warehouseToId,
            'type' => 'out',
            'quantity' => $validated['quantity'],
            'serial_number' => $validated['serial_number'] ?? null,
            'notes' => ($validated['notes'] ?? '') . ' [نقل من مخزن إلى مخزن]',
            'created_by' => $user->id,
        ]);

        $inTransaction = Transaction::create([
            'product_id' => $validated['product_id'],
            'warehouse_id' => $warehouseToId,
            'warehouse_from_id' => $warehouseFromId,
            'warehouse_to_id' => $warehouseToId,
            'type' => 'in',
            'quantity' => $validated['quantity'],
            'serial_number' => $validated['serial_number'] ?? null,
            'notes' => ($validated['notes'] ?? '') . ' [نقل من مخزن - تم استلام المنتج]',
            'created_by' => $user->id,
            'transfer_transaction_id' => $outTransaction->id,
        ]);

        $outTransaction->update(['transfer_transaction_id' => $inTransaction->id]);

        return response()->json([
            'success' => true,
            'message' => 'تم نقل المنتج من مخزن إلى مخزن بنجاح',
            'out_transaction' => $outTransaction->load(['product', 'warehouse', 'warehouseFrom', 'warehouseTo']),
            'in_transaction' => $inTransaction->load(['product', 'warehouse', 'warehouseFrom', 'warehouseTo']),
        ]);
    }

    private function getCurrentStock($productId, $warehouseId, $teamId = null)
    {
        $query = Transaction::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId);

        if ($teamId) {
            // For team transfers, check stock for specific team
            $query->where(function($q) use ($teamId) {
                $q->whereNull('from_team_id')
                  ->orWhere('to_team_id', $teamId);
            });
        }

        $stock = $query->selectRaw('SUM(CASE WHEN type = "in" THEN quantity ELSE -quantity END) as stock')
            ->value('stock');

        return $stock ?? 0;
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الحركة بنجاح'
            ]);
        }

        return redirect()->route('transactions.index')->with('success', 'تم حذف الحركة بنجاح');
    }
}

