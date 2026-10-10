<?php

namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class BusinessSettingsService
{
    public function get(): BusinessSetting
    {
        $profileId = (int) config('business.profile_id');

        return BusinessSetting::unguarded(fn (): BusinessSetting => BusinessSetting::query()->firstOrCreate(
            ['id' => $profileId],
            [
                'business_name' => config('app.name'),
                'currency_code' => config('business.default_currency'),
                'default_low_stock_threshold' => config('business.default_low_stock_threshold'),
                'show_logo_on_receipts' => true,
            ],
        ));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(array $attributes, ?UploadedFile $logo = null, bool $removeLogo = false): BusinessSetting
    {
        $disk = (string) config('business.logo_disk');
        $newLogoPath = null;

        if ($logo) {
            $storedPath = $logo->store((string) config('business.logo_directory'), $disk);

            if (! is_string($storedPath)) {
                throw new RuntimeException('The business logo could not be stored.');
            }

            $newLogoPath = $storedPath;
        }

        try {
            [$settings, $obsoleteLogoPath] = DB::transaction(function () use ($attributes, $newLogoPath, $removeLogo): array {
                $settings = BusinessSetting::query()
                    ->lockForUpdate()
                    ->findOrFail(config('business.profile_id'));
                $obsoleteLogoPath = null;

                if ($newLogoPath !== null || $removeLogo) {
                    $obsoleteLogoPath = $settings->logo_path;
                    $attributes['logo_path'] = $newLogoPath;
                }

                $settings->update(Arr::only($attributes, $settings->getFillable()));

                return [$settings->fresh(), $obsoleteLogoPath];
            });
        } catch (Throwable $exception) {
            if ($newLogoPath !== null) {
                Storage::disk($disk)->delete($newLogoPath);
            }

            throw $exception;
        }

        if ($obsoleteLogoPath && $obsoleteLogoPath !== $newLogoPath) {
            try {
                Storage::disk($disk)->delete($obsoleteLogoPath);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $settings;
    }

    public function logoUrl(?BusinessSetting $settings = null): ?string
    {
        $settings ??= $this->get();

        if (! $settings->logo_path || ! Storage::disk((string) config('business.logo_disk'))->exists($settings->logo_path)) {
            return null;
        }

        return route('media.business-logo', absolute: false);
    }

    /**
     * Information safe for dashboards, reports, and receipts to consume.
     *
     * @return array<string, mixed>
     */
    public function profile(?BusinessSetting $settings = null): array
    {
        $settings ??= $this->get();

        return [
            'business_name' => $settings->business_name,
            'phone' => $settings->phone,
            'email' => $settings->email,
            'address' => $settings->address,
            'currency_code' => $settings->currency_code,
            'currency_symbol' => $this->currencySymbol($settings->currency_code),
            'default_low_stock_threshold' => $settings->default_low_stock_threshold,
            'logo_url' => $this->logoUrl($settings),
        ];
    }

    /** @return array<string, mixed> */
    public function receiptProfile(?BusinessSetting $settings = null): array
    {
        $settings ??= $this->get();

        return [
            ...$this->profile($settings),
            'receipt_footer_message' => $settings->receipt_footer_message,
            'show_logo' => $settings->show_logo_on_receipts,
        ];
    }

    public function currencySymbol(string $currencyCode): string
    {
        return match ($currencyCode) {
            'NGN' => '₦',
            default => $currencyCode.' ',
        };
    }
}
