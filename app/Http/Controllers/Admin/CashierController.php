<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListStaffRequest;
use App\Http\Requests\Admin\StoreCashierRequest;
use App\Http\Requests\Admin\UpdateCashierRequest;
use App\Models\User;
use App\Services\CashierAccountService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CashierController extends Controller
{
    public function __construct(private readonly CashierAccountService $cashierAccounts) {}

    public function index(ListStaffRequest $request): View
    {
        $search = $request->validated('search');
        $staff = User::query()
            ->where('role', UserRole::Cashier)
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.staff.index', compact('staff', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.staff.create');
    }

    public function store(StoreCashierRequest $request): RedirectResponse
    {
        $cashier = $this->cashierAccounts->create($request->validated());

        return redirect()->route('admin.staff.show', $cashier)
            ->with('success', 'Cashier account created successfully.');
    }

    public function show(User $staff): View
    {
        $this->authorize('view', $staff);

        return view('admin.staff.show', compact('staff'));
    }

    public function edit(User $staff): View
    {
        $this->authorize('update', $staff);

        return view('admin.staff.edit', compact('staff'));
    }

    public function update(UpdateCashierRequest $request, User $staff): RedirectResponse
    {
        $cashier = $this->cashierAccounts->updateDetails($staff, $request->validated());

        return redirect()->route('admin.staff.show', $cashier)
            ->with('success', 'Cashier details updated successfully.');
    }
}
