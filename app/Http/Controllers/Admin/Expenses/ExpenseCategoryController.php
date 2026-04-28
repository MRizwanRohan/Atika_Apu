<?php

namespace App\Http\Controllers\Admin\Expenses;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        return inertia('Admin/Expenses/Category/Index', [
            'categories' => \App\Models\ExpenseCategory::all()
        ]);
    }

    public function store(Request $request)
    {
        \App\Models\ExpenseCategory::create($request->all());
        return redirect()->back()->with('success', 'Category created');
    }

    public function update(Request $request, $id)
    {
        $cat = \App\Models\ExpenseCategory::findOrFail($id);
        $cat->update($request->all());
        return redirect()->back()->with('success', 'Category updated');
    }

    public function destroy($id)
    {
        \App\Models\ExpenseCategory::destroy($id);
        return redirect()->back()->with('success', 'Category deleted');
    }
}
