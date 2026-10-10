<?php

namespace App\Providers;

use App\Services\BusinessSettingsService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->symbols());

        view()->composer(['components.layouts.app', 'components.layouts.guest'], function (View $view): void {
            $settings = app(BusinessSettingsService::class)->get();

            $view->with([
                'businessSettings' => $settings,
                'businessLogoUrl' => app(BusinessSettingsService::class)->logoUrl($settings),
                'unreadNotificationCount' => auth()->check() && auth()->user()->isAdministrator()
                    ? auth()->user()->unreadNotifications()->count()
                    : 0,
            ]);
        });
    }
}
