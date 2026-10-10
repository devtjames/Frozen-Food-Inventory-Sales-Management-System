<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCategoryStatusRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;

class CategoryStatusController extends Controller
{
    public function __invoke(UpdateCategoryStatusRequest $request, Category $category, CategoryService $categories): RedirectResponse
    {
        $isActive = $request->boolean('is_active');
        $categories->setActive($category, $isActive);

        return back()->with('success', $isActive ? 'Category activated.' : 'Category deactivated.');
    }
}
