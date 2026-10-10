<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListExpensesRequest;
use App\Http\Requests\Admin\StoreExpenseRequest;
use App\Http\Requests\Admin\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\BusinessFormatter;
use App\Services\ExpenseHistoryService;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(ListExpensesRequest $request, ExpenseHistoryService $history, BusinessFormatter $formatter): View
    {
        $filters = $request->validated();
        $result = $history->filtered($filters);

        return view('admin.expenses.index', [
            ...$result,
            'filters' => $filters,
            'categories' => ExpenseCategory::query()->orderBy('name')->pluck('name', 'id')->all(),
            'formatter' => $formatter,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Expense::class);

        return view('admin.expenses.create', [
            'categories' => ExpenseCategory::query()->orderBy('name')->pluck('name', 'id')->all(),
            'idempotencyKey' => (string) Str::uuid(),
            'defaultDate' => Carbon::now(config('app.business_timezone'))->format('Y-m-d'),
        ]);
    }

    public function store(StoreExpenseRequest $request, ExpenseService $service): RedirectResponse
    {
        $expense = $service->create($request->validated(), $request->user());

        return redirect()->route('admin.expenses.show', $expense)->with('success', 'Expense recorded successfully.');
    }

    public function show(Expense $expense, BusinessFormatter $formatter): View
    {
        $this->authorize('view', $expense);
        $expense->load(['category', 'recorder', 'revisions.editor']);

        return view('admin.expenses.show', compact('expense', 'formatter'));
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('update', $expense);
        $expense->load('category');

        return view('admin.expenses.edit', [
            'expense' => $expense,
            'categories' => ExpenseCategory::query()->orderBy('name')->pluck('name', 'id')->all(),
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense, ExpenseService $service): RedirectResponse
    {
        $service->update($expense, $request->validated(), $request->user());

        return redirect()->route('admin.expenses.show', $expense)->with('success', 'Expense correction saved with an audit record.');
    }
}
