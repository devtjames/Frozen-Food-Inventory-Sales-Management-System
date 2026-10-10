<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\InitialAdministratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(InitialAdministratorService $administrators): View|RedirectResponse
    {
        if (! $administrators->hasAdministrator()) {
            return redirect()->route('setup.create');
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request, InitialAdministratorService $administrators): RedirectResponse
    {
        if (! $administrators->hasAdministrator()) {
            return redirect()->route('setup.create');
        }

        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route($request->user()->dashboardRoute(), absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
