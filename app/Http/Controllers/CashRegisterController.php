<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends Controller
{
    public function index()
    {
        return view('cash_registers.index');
    }

    public function datatable()
    {
        $registers = CashRegister::where('branch_id', session('branch_id'))
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $registers->map(function ($register) {
            $statusBadge = $register->status == 'open'
                ? '<span class="badge bg-label-success">Abierta</span>'
                : '<span class="badge bg-label-secondary">Cerrada</span>';

            $openedAt = '<div class="d-flex flex-column">'
                . '<span class="fw-medium">' . $register->opened_at->format('d/m/Y') . '</span>'
                . '<small class="text-muted">' . $register->opened_at->format('g:i A') . '</small>'
                . '</div>';

            $closedAt = $register->closed_at
                ? '<div class="d-flex flex-column"><span>' . $register->closed_at->format('d/m/Y') . '</span><small class="text-muted">' . $register->closed_at->format('g:i A') . '</small></div>'
                : '<span class="text-muted fst-italic">-</span>';

            $userInitial = strtoupper(substr($register->user->name ?? 'U', 0, 1));
            $userName = e($register->user->name ?? 'Usuario');
            $userCell = '<div class="d-flex align-items-center">'
                . '<div class="avatar avatar-xs me-2"><span class="avatar-initial rounded-circle bg-label-primary">' . $userInitial . '</span></div>'
                . '<span class="text-truncate" style="max-width: 150px;">' . $userName . '</span>'
                . '</div>';

            $openingAmount = '$' . number_format($register->opening_amount, 2);
            $closingAmount = $register->closing_amount !== null
                ? '<span class="fw-medium text-success">$' . number_format($register->closing_amount, 2) . '</span>'
                : '-';

            $actions = '<div class="dropdown">'
                . '<button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ti tabler-dots-vertical"></i></button>'
                . '<div class="dropdown-menu">'
                . '<a class="dropdown-item" href="' . route('cash-registers.show', $register) . '"><i class="ti tabler-eye me-1"></i> Ver Detalle</a>';

            if ($register->status == 'closed') {
                $actions .= '<a class="dropdown-item" href="' . route('cash-registers.print', $register) . '" target="_blank"><i class="ti tabler-printer me-1"></i> Imprimir Ticket</a>';
                $actions .= '<a class="dropdown-item" href="' . route('cash-registers.report', $register) . '" target="_blank"><i class="ti tabler-file-analytics me-1"></i> Reporte PDF</a>';
            }

            if ($register->status == 'open') {
                $actions .= '<a class="dropdown-item text-primary" href="' . route('cash-registers.show', $register) . '"><i class="ti tabler-lock-open me-1"></i> Hacer Corte</a>';
            }

            $actions .= '</div></div>';

            return [
                'folio' => '<span class="fw-medium">#' . $register->id . '</span>',
                'status' => $statusBadge,
                'opened_at' => $openedAt,
                'closed_at' => $closedAt,
                'user' => $userCell,
                'opening_amount' => $openingAmount,
                'closing_amount' => $closingAmount,
                'actions' => $actions,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function create()
    {
        // Check if there is already an open register for this user/branch
        $activeRegister = CashRegister::where('branch_id', session('branch_id'))
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();

        if ($activeRegister) {
            return redirect()->route('cash-registers.show', $activeRegister);
        }

        return view('cash_registers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'opening_amount' => 'required|numeric|min:0',
        ]);

        CashRegister::create([
            'tenant_id' => auth()->user()->tenant_id ?? 'default', // fallback if not using tenant scope properly yet
            'branch_id' => session('branch_id'),
            'user_id' => auth()->id(),
            'opening_amount' => $request->opening_amount,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        return redirect()->route('orders.index')->with('success', 'Caja abierta correctamente.');
    }

    public function show(CashRegister $cashRegister)
    {
        $cashRegister->load(['movements', 'user', 'payments']);
        
        // Calculate totals dynamically for display
        // In a real app we might optimize this query
        // Expected = Opening + Sales(Payments?) + In - Out - Expenses
        // For now we need to link Payments to CashRegister. 
        // Plan: Add column 'cash_register_id' to payments, OR query payments between opened_at and now?
        // Querying by time is risky if shifts overlap. Best to add cash_register_id to payments.
        // For this iteration, let's query payments by user and time range if column doesn't exist yet, 
        // BUT we should add the column. 
        // Let's assume for now we list movements.
        
        $expenseCategories = \App\Models\ExpenseCategory::where('branch_id', session('branch_id'))
            ->get();
        
        // Payment methods breakdown
        $paymentsByMethod = [
            'cash' => $cashRegister->payments->where('method', 'cash')->sum('amount'),
            'card' => $cashRegister->payments->where('method', 'card')->sum('amount'),
            'transfer' => $cashRegister->payments->where('method', 'transfer')->sum('amount'),
            'deposit' => $cashRegister->payments->where('method', 'deposit')->sum('amount'),
        ];
        
        $totalSales = array_sum($paymentsByMethod);
        
        return view('cash_registers.show', compact('cashRegister', 'expenseCategories', 'paymentsByMethod', 'totalSales'));
    }

    public function edit(CashRegister $cashRegister)
    {
        $cashRegister->load(['movements', 'payments']);

        $sales = $cashRegister->payments->where('method', 'cash')->sum('amount'); // Only Cash Sales
        $in = $cashRegister->movements->where('type', 'in')->sum('amount');
        $out = $cashRegister->movements->where('type', 'out')->sum('amount');
        $expenses = $cashRegister->movements->where('type', 'expense')->sum('amount');
        
        $expected = $cashRegister->opening_amount + $sales + $in - $out - $expenses;

        // Payment methods breakdown
        $paymentsByMethod = [
            'cash' => $sales,
            'card' => $cashRegister->payments->where('method', 'card')->sum('amount'),
            'transfer' => $cashRegister->payments->where('method', 'transfer')->sum('amount'),
            'deposit' => $cashRegister->payments->where('method', 'deposit')->sum('amount'),
        ];

        return view('cash_registers.close', compact('cashRegister', 'sales', 'in', 'out', 'expenses', 'expected', 'paymentsByMethod'));
    }

    public function update(Request $request, CashRegister $cashRegister)
    {
        // Handle Close
        if ($request->has('close_register')) {
            $request->validate([
                'closing_amount' => 'required|numeric|min:0',
            ]);

            // Calculate system expected amount (Logic to be refined)
            $expected = $cashRegister->opening_amount; 
            // + Payments where method=cash (we need to filter payments)
            // + Movements IN
            // - Movements OUT
            // - Movements EXPENSE
            
            $cashRegister->update([
                'closing_amount' => $request->closing_amount,
                'declared_card' => $request->declared_card ?? 0,
                'declared_transfer' => $request->declared_transfer ?? 0,
                'declared_deposit' => $request->declared_deposit ?? 0,
                'calculated_amount' => $expected, // Placeholder
                'status' => 'closed',
                'closed_at' => now(),
                'notes' => $request->notes,
            ]);

            return redirect()->route('cash-registers.index')->with('success', 'Corte de caja realizado.');
        }

        return back();
    }

    public function storeMovement(Request $request, CashRegister $cashRegister)
    {
        $request->validate([
            'type' => 'required|in:in,out,expense',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
        ]);

        $cashRegister->movements()->create([
            'type' => $request->type,
            'amount' => $request->amount,
            'description' => $request->description,
            'expense_category_id' => $request->expense_category_id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Movimiento registrado correctamente.');
    }
    
    public function addMovement(Request $request, CashRegister $cashRegister) 
    {
        $request->validate([
            'type' => 'required|in:in,out,expense',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
        ]);

        // Sanitize input: If type is not expense, expense_category_id should be null
        $categoryId = $request->type === 'expense' ? $request->expense_category_id : null;

        CashMovement::create([
            'cash_register_id' => $cashRegister->id,
            'type' => $request->type,
            'amount' => $request->amount,
            'description' => $request->description,
            'expense_category_id' => $categoryId,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Movimiento registrado.');
    }

    public function print(CashRegister $cashRegister)
    {
        $cashRegister->load(['movements.user', 'movements.expenseCategory', 'payments.order', 'user']);
        
        // Cash calculations
        $sales = $cashRegister->payments->where('method', 'cash')->sum('amount');
        $in = $cashRegister->movements->where('type', 'in')->sum('amount');
        $out = $cashRegister->movements->where('type', 'out')->sum('amount');
        $expenses = $cashRegister->movements->where('type', 'expense')->sum('amount');
        $expected = $cashRegister->opening_amount + $sales + $in - $out - $expenses;
        
        // Payment methods breakdown
        $paymentsByMethod = [
            'cash' => $cashRegister->payments->where('method', 'cash')->sum('amount'),
            'card' => $cashRegister->payments->where('method', 'card')->sum('amount'),
            'transfer' => $cashRegister->payments->where('method', 'transfer')->sum('amount'),
            'deposit' => $cashRegister->payments->where('method', 'deposit')->sum('amount'),
        ];
        
        $totalSales = array_sum($paymentsByMethod);
        
        $settings = \App\Models\Setting::where('branch_id', session('branch_id'))
            ->pluck('value', 'key')->toArray();

        return view('cash_registers.print', compact('cashRegister', 'sales', 'in', 'out', 'expenses', 'expected', 'settings', 'paymentsByMethod', 'totalSales'));
    }

    public function report(CashRegister $cashRegister)
    {
        $cashRegister->load(['movements.user', 'movements.expenseCategory', 'payments.order', 'user']);
        
        // Cash calculations
        $sales = $cashRegister->payments->where('method', 'cash')->sum('amount');
        $in = $cashRegister->movements->where('type', 'in')->sum('amount');
        $out = $cashRegister->movements->where('type', 'out')->sum('amount');
        $expenses = $cashRegister->movements->where('type', 'expense')->sum('amount');
        $expected = $cashRegister->opening_amount + $sales + $in - $out - $expenses;
        
        // Payment methods breakdown
        $paymentsByMethod = [
            'cash' => $cashRegister->payments->where('method', 'cash')->sum('amount'),
            'card' => $cashRegister->payments->where('method', 'card')->sum('amount'),
            'transfer' => $cashRegister->payments->where('method', 'transfer')->sum('amount'),
            'deposit' => $cashRegister->payments->where('method', 'deposit')->sum('amount'),
        ];
        
        $totalSales = array_sum($paymentsByMethod);
        
        $settings = \App\Models\Setting::where('branch_id', session('branch_id'))
            ->pluck('value', 'key')->toArray();

        return view('cash_registers.report', compact('cashRegister', 'sales', 'in', 'out', 'expenses', 'expected', 'settings', 'paymentsByMethod', 'totalSales'));
    }
}
