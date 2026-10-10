<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListExpenseCategoriesRequest;
use App\Http\Requests\Admin\StoreExpenseCategoryRequest;
use App\Http\Requests\Admin\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use App\Services\ExpenseCategoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function index(ListExpenseCategoriesRequest $request): View
    {
        $search = $request->validated('search');
        $categories = ExpenseCategory::query()->withCount('expenses')
            ->when($search, fn (Builder $query): Builder => $query->where('normalized_name', 'like', '%'.Str::lower($search).'%'))
            ->orderBy('name')->paginate(10)->withQueryString();

        return view('admin.expense-categories.index', compact('categories', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', ExpenseCategory::class);

        return view('admin.expense-categories.create');
    }

    public function store(StoreExpenseCategoryRequest $request, ExpenseCategoryService $service): RedirectResponse
    {
        $service->create($request->validated());

        return redirect()->route('admin.expense-categories.index')->with('success', 'Expense category created successfully.');
    }

    public function edit(ExpenseCategory $expenseCategory): View
    {
        $this->authorize('update', $expenseCategory);

        return view('admin.expense-categories.edit', compact('expenseCategory'));
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory, ExpenseCategoryService $service): RedirectResponse
    {
        $service->update($expenseCategory, $request->validated());

        return redirect()->route('admin.expense-categories.index')->with('success', 'Expense category updated successfully.');
    }
}
