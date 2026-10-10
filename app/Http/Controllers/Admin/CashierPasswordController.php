<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetCashierPasswordRequest;
use App\Models\User;
use App\Services\CashierAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CashierPasswordController extends Controller
{
    public function edit(User $staff): View
    {
        Gate::authorize('resetPassword', $staff);

        return view('admin.staff.reset-password', compact('staff'));
    }

    public function update(ResetCashierPasswordRequest $request, User $staff, CashierAccountService $cashierAccounts): RedirectResponse
    {
        $cashierAccounts->resetPassword($staff, $request->validated('password'));

        return redirect()->route('admin.staff.show', $staff)
            ->with('success', 'Cashier password reset successfully. Existing sessions were signed out.');
    }
}
