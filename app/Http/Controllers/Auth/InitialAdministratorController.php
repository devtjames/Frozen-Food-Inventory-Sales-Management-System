<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\InitialAdministratorRequest;
use App\Services\InitialAdministratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use LogicException;

class InitialAdministratorController extends Controller
{
    public function create(InitialAdministratorService $administrators): View|RedirectResponse
    {
        if ($administrators->hasAdministrator()) {
            return redirect()->route('login')->with('status', 'setup-complete');
        }

        return view('auth.setup');
    }

    public function store(
        InitialAdministratorRequest $request,
        InitialAdministratorService $administrators,
    ): RedirectResponse {
        try {
            $administrator = $administrators->create($request->validated());
        } catch (LogicException) {
            return redirect()->route('login')->with('status', 'setup-complete');
        }

        Auth::guard('web')->login($administrator);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('success', 'Owner administrator account created successfully.');
    }
}
