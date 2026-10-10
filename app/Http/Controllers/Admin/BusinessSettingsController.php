<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBusinessSettingsRequest;
use App\Services\BusinessSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BusinessSettingsController extends Controller
{
    public function __construct(private readonly BusinessSettingsService $businessSettings) {}

    public function edit(): View
    {
        $settings = $this->businessSettings->get();
        $this->authorize('view', $settings);

        return view('admin.business-settings.edit', [
            'settings' => $settings,
            'logoUrl' => $this->businessSettings->logoUrl($settings),
            'currencies' => ['NGN' => 'Nigerian Naira (₦)'],
        ]);
    }

    public function update(UpdateBusinessSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->businessSettings->update(
            $validated,
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );

        return redirect()->route('admin.business-settings.edit')
            ->with('success', 'Business settings updated successfully.');
    }
}
