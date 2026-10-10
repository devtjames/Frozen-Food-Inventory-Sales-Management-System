<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\BusinessSettingsService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function businessLogo(BusinessSettingsService $settings): StreamedResponse
    {
        $profile = $settings->get();

        return $this->imageResponse((string) config('business.logo_disk'), $profile->logo_path);
    }

    public function productImage(Product $product): StreamedResponse
    {
        return $this->imageResponse((string) config('products.image_disk'), $product->image_path);
    }

    private function imageResponse(string $diskName, ?string $path): StreamedResponse
    {
        abort_if(blank($path), 404);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($diskName);
        abort_unless($disk->exists($path), 404);

        $mimeType = $disk->mimeType($path);
        abort_unless(in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        return $disk->response($path, null, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=3600',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
