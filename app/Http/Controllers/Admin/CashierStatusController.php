<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCashierStatusRequest;
use App\Models\User;
use App\Services\CashierAccountService;
use Illuminate\Http\RedirectResponse;

class CashierStatusController extends Controller
{
    public function __invoke(UpdateCashierStatusRequest $request, User $staff, CashierAccountService $cashierAccounts): RedirectResponse
    {
        $isActive = $request->boolean('is_active');
        $cashierAccounts->setActive($staff, $isActive);

        return back()->with('success', $isActive ? 'Cashier account activated.' : 'Cashier account deactivated.');
    }
}
