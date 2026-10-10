<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListProductsRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\BusinessFormatter;
use App\Services\BusinessSettingsService;
use App\Services\InventoryService;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly BusinessFormatter $formatter,
        private readonly BusinessSettingsService $businessSettings,
        private readonly InventoryService $inventory,
    ) {}

    public function index(ListProductsRequest $request): View
    {
        $filters = $request->validated();
        $search = $filters['search'] ?? null;
        $categoryId = $filters['category'] ?? null;
        $status = $filters['status'] ?? null;
        $normalizedSearch = $search ? Str::lower($search) : null;

        $products = Product::query()
            ->with('category')
            ->when($search, function (Builder $query) use ($search, $normalizedSearch): void {
                $query->where(function (Builder $query) use ($search, $normalizedSearch): void {
                    $query->where('normalized_name', 'like', "%{$normalizedSearch}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($categoryId, fn (Builder $query, int|string $categoryId) => $query->where('category_id', $categoryId))
            ->when($status === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        $this->inventory->attachBalances($products->getCollection());

        return view('admin.products.index', [
            'products' => $products,
            'categories' => $this->categoryOptions(),
            'filters' => $filters,
            'formatter' => $this->formatter,
            'productService' => $this->products,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.create', [
            'categories' => $this->categoryOptions(),
            'defaultLowStockThreshold' => $this->businessSettings->get()->default_low_stock_threshold,
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->validated(), $request->file('image'));

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);
        $product->load('category');

        return view('admin.products.show', [
            'product' => $product,
            'formatter' => $this->formatter,
            'imageUrl' => $this->products->imageUrl($product),
            'availableQuantity' => $this->inventory->availableQuantity($product),
        ]);
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);
        $product->load('category');

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $this->categoryOptions(),
            'imageUrl' => $this->products->imageUrl($product),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product = $this->products->update(
            $product,
            $request->validated(),
            $request->file('image'),
            $request->boolean('remove_image'),
        );

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    /** @return Collection<int|string, string> */
    private function categoryOptions(): Collection
    {
        return Category::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Category $category): array => [
                $category->id => $category->name.($category->is_active ? '' : ' (Inactive)'),
            ]);
    }
}
