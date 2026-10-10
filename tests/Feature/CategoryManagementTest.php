<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_categories_are_seeded_as_editable_active_records(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertSame(
            ['Chicken', 'Fish', 'Other Frozen Foods', 'Seafood', 'Turkey'],
            Category::query()->orderBy('name')->pluck('name')->all(),
        );
        $this->assertSame(5, Category::query()->active()->count());

        $administrator = User::factory()->administrator()->create();
        $fish = Category::query()->where('normalized_name', 'fish')->firstOrFail();

        $this->actingAs($administrator)->put("/admin/categories/{$fish->id}", [
            'name' => 'Fin Fish',
            'description' => 'Whole and portioned frozen fish.',
            'is_active' => '1',
        ])->assertRedirect(route('admin.categories.index', absolute: false));

        $this->assertDatabaseHas('categories', [
            'id' => $fish->id,
            'name' => 'Fin Fish',
            'normalized_name' => 'fin fish',
        ]);
    }

    public function test_administrator_can_view_search_and_paginate_categories(): void
    {
        $administrator = User::factory()->administrator()->create();
        $target = Category::factory()->create([
            'name' => 'Premium Shellfish',
            'normalized_name' => 'premium shellfish',
            'description' => 'Lobster and prawn selections.',
        ]);
        Category::factory()->count(10)->create();

        $this->actingAs($administrator)->get('/admin/categories')
            ->assertOk()
            ->assertSee('Product categories')
            ->assertSee('Next')
            ->assertSee('md:hidden', false)
            ->assertSee('hidden md:block', false)
            ->assertSee('open-confirmation', false);

        $this->actingAs($administrator)->get('/admin/categories?search=shellfish')
            ->assertOk()
            ->assertSee($target->name)
            ->assertSee($target->description)
            ->assertDontSee('No categories match your search');
    }

    public function test_administrator_can_create_and_persist_a_category(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->post('/admin/categories', [
            'name' => '  Frozen   Vegetables ',
            'description' => '  Mixed vegetables for freezer storage.  ',
            'is_active' => '1',
        ])->assertRedirect(route('admin.categories.index', absolute: false))
            ->assertSessionHas('success');

        $category = Category::query()->sole();
        $this->assertSame('Frozen Vegetables', $category->name);
        $this->assertSame('frozen vegetables', $category->normalized_name);
        $this->assertSame('Mixed vegetables for freezer storage.', $category->description);
        $this->assertTrue($category->is_active);
    }

    public function test_duplicate_category_names_are_rejected_case_insensitively(): void
    {
        $administrator = User::factory()->administrator()->create();
        Category::factory()->create([
            'name' => 'Fish',
            'normalized_name' => 'fish',
        ]);

        $this->actingAs($administrator)->post('/admin/categories', [
            'name' => '  FISH  ',
            'description' => null,
            'is_active' => '1',
        ])->assertSessionHasErrors('name');

        $this->assertSame(1, Category::query()->count());
    }

    public function test_administrator_can_edit_category_information_without_bypassing_status_confirmation(): void
    {
        $administrator = User::factory()->administrator()->create();
        $category = Category::factory()->create();

        $this->actingAs($administrator)->put("/admin/categories/{$category->id}", [
            'name' => 'Crustaceans',
            'description' => 'Frozen crabs, lobster, and prawns.',
            'is_active' => '0',
        ])->assertRedirect(route('admin.categories.index', absolute: false));

        $category->refresh();
        $this->assertSame('Crustaceans', $category->name);
        $this->assertSame('Frozen crabs, lobster, and prawns.', $category->description);
        $this->assertTrue($category->is_active);
        $this->assertTrue($category->isAvailableForNewSales());
    }

    public function test_administrator_can_deactivate_and_reactivate_without_deleting_the_category(): void
    {
        $administrator = User::factory()->administrator()->create();
        $category = Category::factory()->create();

        $this->actingAs($administrator)->patch("/admin/categories/{$category->id}/status", [
            'is_active' => '0',
        ])->assertRedirect();

        $this->assertFalse($category->refresh()->is_active);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertSame(0, Category::query()->active()->count());

        $this->actingAs($administrator)->patch("/admin/categories/{$category->id}/status", [
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertTrue($category->refresh()->is_active);
        $this->assertTrue($category->isAvailableForNewSales());
    }

    public function test_guest_and_cashier_cannot_manage_categories(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $payload = [
            'name' => 'Unauthorized Category',
            'description' => 'Must not be stored.',
            'is_active' => '1',
        ];

        $this->get('/admin/categories')->assertRedirect('/login');
        $this->actingAs($cashier)->get('/admin/categories')->assertForbidden();
        $this->actingAs($cashier)->get('/admin/categories/create')->assertForbidden();
        $this->actingAs($cashier)->post('/admin/categories', $payload)->assertForbidden();
        $this->actingAs($cashier)->put("/admin/categories/{$category->id}", $payload)->assertForbidden();
        $this->actingAs($cashier)->patch("/admin/categories/{$category->id}/status", ['is_active' => '0'])->assertForbidden();

        $this->assertDatabaseMissing('categories', ['normalized_name' => 'unauthorized category']);
        $this->assertTrue($category->refresh()->is_active);
    }

    public function test_category_deletion_route_is_not_exposed(): void
    {
        $administrator = User::factory()->administrator()->create();
        $category = Category::factory()->create();

        $this->actingAs($administrator)->delete("/admin/categories/{$category->id}")->assertStatus(405);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
