<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProductStatusRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;

class ProductStatusController extends Controller
{
    public function __invoke(UpdateProductStatusRequest $request, Product $product, ProductService $products): RedirectResponse
    {
        $isActive = $request->boolean('is_active');
        $products->setActive($product, $isActive);

        return back()->with('success', $isActive ? 'Product activated.' : 'Product deactivated.');
    }
}
