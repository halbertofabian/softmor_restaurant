<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::where('branch_id', session('branch_id'))
            ->orderBy('name')
            ->get();
            
        return view('expense_categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        ExpenseCategory::create([
            'tenant_id' => auth()->user()->tenant_id ?? 'default', 
            'branch_id' => session('branch_id'),
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Categoría creada.');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        // Check tenants in future
        $expenseCategory->delete();
        return back()->with('success', 'Categoría eliminada.');
    }
}
