<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProductService
{
    public function __construct(private readonly InventoryAlertService $alerts) {}

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes, ?UploadedFile $image = null): Product
    {
        $newImagePath = $this->storeImage($image);

        try {
            return DB::transaction(function () use ($attributes, $newImagePath): Product {
                if ($newImagePath !== null) {
                    $attributes['image_path'] = $newImagePath;
                }

                $product = Product::query()->create(Arr::only($attributes, [
                    'category_id',
                    'name',
                    'normalized_name',
                    'description',
                    'image_path',
                    'selling_price',
                    'low_stock_threshold',
                    'is_active',
                ]));

                $this->alerts->sync($product);

                return $product->refresh();
            });
        } catch (Throwable $exception) {
            $this->deleteNewImage($newImagePath);

            throw $exception;
        }
    }

    /** @param array<string, mixed> $attributes */
    public function update(Product $product, array $attributes, ?UploadedFile $image = null, bool $removeImage = false): Product
    {
        $newImagePath = $this->storeImage($image);

        try {
            [$updatedProduct, $obsoleteImagePath] = DB::transaction(function () use ($product, $attributes, $newImagePath, $removeImage): array {
                $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->getKey());
                $obsoleteImagePath = null;

                if ($newImagePath !== null || $removeImage) {
                    $obsoleteImagePath = $lockedProduct->image_path;
                    $attributes['image_path'] = $newImagePath;
                }

                $lockedProduct->update(Arr::only($attributes, [
                    'category_id',
                    'name',
                    'normalized_name',
                    'description',
                    'image_path',
                    'selling_price',
                    'low_stock_threshold',
                ]));

                $this->alerts->sync($lockedProduct);

                return [$lockedProduct->refresh(), $obsoleteImagePath];
            });
        } catch (Throwable $exception) {
            $this->deleteNewImage($newImagePath);

            throw $exception;
        }

        if ($obsoleteImagePath && $obsoleteImagePath !== $newImagePath) {
            try {
                Storage::disk($this->imageDisk())->delete($obsoleteImagePath);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $updatedProduct;
    }

    public function setActive(Product $product, bool $isActive): Product
    {
        return DB::transaction(function () use ($product, $isActive): Product {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->getKey());
            $lockedProduct->update(['is_active' => $isActive]);

            return $lockedProduct->refresh();
        });
    }

    public function imageUrl(Product $product): ?string
    {
        if (! $product->image_path || ! Storage::disk($this->imageDisk())->exists($product->image_path)) {
            return null;
        }

        return route('media.products.image', $product, absolute: false);
    }

    private function storeImage(?UploadedFile $image): ?string
    {
        if (! $image) {
            return null;
        }

        $storedPath = $image->store((string) config('products.image_directory'), $this->imageDisk());

        if (! is_string($storedPath)) {
            throw new RuntimeException('The product image could not be stored.');
        }

        return $storedPath;
    }

    private function deleteNewImage(?string $path): void
    {
        if ($path !== null) {
            Storage::disk($this->imageDisk())->delete($path);
        }
    }

    private function imageDisk(): string
    {
        return (string) config('products.image_disk');
    }
}
