<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_register_a_product_with_category_and_decimal_pricing(): void
    {
        $administrator = User::factory()->administrator()->create();
        $category = Category::factory()->create(['name' => 'Fish', 'normalized_name' => 'fish']);

        $this->actingAs($administrator)->get('/admin/products/create')
            ->assertOk()
            ->assertSee('Search categories')
            ->assertSee('name="selling_price"', false)
            ->assertDontSee('name="available_stock"', false)
            ->assertDontSee('name="purchase_cost"', false);

        $response = $this->actingAs($administrator)->post('/admin/products', $this->validPayload($category, [
            'name' => '  Titus   Fish ',
            'selling_price' => '4500.50',
            'low_stock_threshold' => '7.250',
            'available_stock' => '999.000',
            'purchase_cost' => '100.00',
        ]));

        $product = Product::query()->sole();
        $response->assertRedirect(route('admin.products.show', $product, absolute: false));
        $this->assertSame($category->id, $product->category_id);
        $this->assertTrue($product->category->is($category));
        $this->assertSame('Titus Fish', $product->name);
        $this->assertSame('titus fish', $product->normalized_name);
        $this->assertSame('4500.50', $product->selling_price);
        $this->assertSame('7.250', $product->low_stock_threshold);
        $this->assertFalse(Schema::hasColumn('products', 'available_stock'));
        $this->assertFalse(Schema::hasColumn('products', 'purchase_cost'));

        $this->actingAs($administrator)->get("/admin/products/{$product->id}")
            ->assertOk()
            ->assertSee('₦4,500.50/kg')
            ->assertSee('0 kg');
    }

    public function test_selling_price_must_be_positive_and_use_at_most_two_decimal_places(): void
    {
        $administrator = User::factory()->administrator()->create();
        $category = Category::factory()->create();

        foreach (['0', '-1', '4500.999'] as $invalidPrice) {
            $this->actingAs($administrator)->post('/admin/products', $this->validPayload($category, [
                'selling_price' => $invalidPrice,
            ]))->assertSessionHasErrors('selling_price');
        }

        $this->assertDatabaseCount('products', 0);
    }

    public function test_duplicate_product_names_are_rejected_only_within_the_same_category(): void
    {
        $administrator = User::factory()->administrator()->create();
        $fish = Category::factory()->create(['name' => 'Fish', 'normalized_name' => 'fish']);
        $seafood = Category::factory()->create(['name' => 'Seafood', 'normalized_name' => 'seafood']);
        Product::factory()->for($fish)->create(['name' => 'Titus Fish', 'normalized_name' => 'titus fish']);

        $this->actingAs($administrator)->post('/admin/products', $this->validPayload($fish, [
            'name' => '  TITUS   FISH ',
        ]))->assertSessionHasErrors('name');

        $this->actingAs($administrator)->post('/admin/products', $this->validPayload($seafood, [
            'name' => 'Titus Fish',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(2, Product::query()->count());
    }

    public function test_administrator_can_update_product_and_price_without_changing_stock_or_bypassing_status(): void
    {
        $administrator = User::factory()->administrator()->create();
        $fish = Category::factory()->create();
        $chicken = Category::factory()->create();
        $product = Product::factory()->for($fish)->create([
            'selling_price' => '4000.00',
            'is_active' => true,
        ]);

        $this->actingAs($administrator)->put("/admin/products/{$product->id}", $this->validPayload($chicken, [
            'name' => 'Chicken Wings',
            'description' => 'Premium frozen wings.',
            'selling_price' => '4750.25',
            'low_stock_threshold' => '12.500',
            'is_active' => '0',
            'available_stock' => '250.000',
        ]))->assertRedirect(route('admin.products.show', $product, absolute: false));

        $product->refresh();
        $this->assertSame($chicken->id, $product->category_id);
        $this->assertSame('Chicken Wings', $product->name);
        $this->assertSame('4750.25', $product->selling_price);
        $this->assertSame('12.500', $product->low_stock_threshold);
        $this->assertTrue($product->is_active);
    }

    public function test_product_listing_supports_search_category_status_pagination_and_responsive_views(): void
    {
        $administrator = User::factory()->administrator()->create();
        $fish = Category::factory()->create(['name' => 'Fish', 'normalized_name' => 'fish']);
        $chicken = Category::factory()->create(['name' => 'Chicken', 'normalized_name' => 'chicken']);
        $target = Product::factory()->for($fish)->create([
            'name' => 'Premium Croaker',
            'normalized_name' => 'premium croaker',
            'description' => 'Large whole croaker.',
            'is_active' => true,
        ]);
        Product::factory()->for($chicken)->count(10)->create();

        $this->actingAs($administrator)->get('/admin/products')
            ->assertOk()
            ->assertSee('Next')
            ->assertSee('md:hidden', false)
            ->assertSee('hidden md:block', false)
            ->assertSee('Search categories')
            ->assertSee('Available stock is calculated from remaining batch quantities.')
            ->assertSee('open-confirmation', false);

        $this->actingAs($administrator)->get("/admin/products?search=croaker&category={$fish->id}&status=active")
            ->assertOk()
            ->assertSee($target->name)
            ->assertSee($target->description)
            ->assertDontSee('No products match your filters');
    }

    public function test_product_and_category_status_control_new_sale_availability_without_deletion(): void
    {
        $administrator = User::factory()->administrator()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();

        $this->assertTrue($product->load('category')->isAvailableForNewSales());
        $this->assertSame(1, Product::query()->availableForSale()->count());

        $category->update(['is_active' => false]);
        $this->assertFalse($product->unsetRelation('category')->isAvailableForNewSales());
        $this->assertSame(0, Product::query()->availableForSale()->count());

        $category->update(['is_active' => true]);
        $this->actingAs($administrator)->patch("/admin/products/{$product->id}/status", [
            'is_active' => '0',
        ])->assertRedirect();

        $this->assertFalse($product->refresh()->is_active);
        $this->assertFalse($product->isAvailableForNewSales());
        $this->assertDatabaseHas('products', ['id' => $product->id]);

        $this->actingAs($administrator)->patch("/admin/products/{$product->id}/status", [
            'is_active' => '1',
        ])->assertRedirect();
        $this->assertTrue($product->refresh()->is_active);
    }

    public function test_product_image_can_be_uploaded_replaced_and_removed_safely(): void
    {
        Storage::fake('uploads');
        $administrator = User::factory()->administrator()->create();
        $category = Category::factory()->create();

        $this->actingAs($administrator)->post('/admin/products', $this->validPayload($category, [
            'image' => $this->pngUpload('titus.png', 500, 400),
        ]))->assertSessionHasNoErrors();

        $product = Product::query()->sole();
        $firstImage = $product->image_path;
        $this->assertNotNull($firstImage);
        Storage::disk('uploads')->assertExists($firstImage);
        $this->actingAs($administrator)->get(route('media.products.image', $product))->assertOk()
            ->assertHeader('x-content-type-options', 'nosniff')
            ->assertHeader('content-type', 'image/png');

        $this->actingAs($administrator)->put("/admin/products/{$product->id}", $this->validPayload($category, [
            'image' => $this->pngUpload('replacement.png', 600, 500),
            'remove_image' => '0',
        ]))->assertSessionHasNoErrors();

        $product->refresh();
        $replacementImage = $product->image_path;
        $this->assertNotSame($firstImage, $replacementImage);
        Storage::disk('uploads')->assertMissing($firstImage);
        Storage::disk('uploads')->assertExists($replacementImage);

        $this->actingAs($administrator)->put("/admin/products/{$product->id}", $this->validPayload($category, [
            'remove_image' => '1',
        ]))->assertSessionHasNoErrors();

        $this->assertNull($product->refresh()->image_path);
        Storage::disk('uploads')->assertMissing($replacementImage);
    }

    public function test_invalid_product_image_is_rejected(): void
    {
        Storage::fake('uploads');
        $administrator = User::factory()->administrator()->create();
        $category = Category::factory()->create();

        $this->actingAs($administrator)->post('/admin/products', $this->validPayload($category, [
            'image' => UploadedFile::fake()->create('product.pdf', 100, 'application/pdf'),
        ]))->assertSessionHasErrors('image');

        $this->actingAs($administrator)->post('/admin/products', $this->validPayload($category, [
            'image' => UploadedFile::fake()->createWithContent('product.php', '<?php phpinfo();'),
        ]))->assertSessionHasErrors('image');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_cashier_and_guest_cannot_manage_products(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();

        $this->get('/admin/products')->assertRedirect('/login');
        $this->actingAs($cashier)->get('/admin/products')->assertForbidden();
        $this->actingAs($cashier)->get('/admin/products/create')->assertForbidden();
        $this->actingAs($cashier)->get("/admin/products/{$product->id}")->assertForbidden();
        $this->actingAs($cashier)->post('/admin/products', $this->validPayload($category))->assertForbidden();
        $this->actingAs($cashier)->put("/admin/products/{$product->id}", $this->validPayload($category))->assertForbidden();
        $this->actingAs($cashier)->patch("/admin/products/{$product->id}/status", ['is_active' => '0'])->assertForbidden();

        $this->assertTrue($product->refresh()->is_active);
    }

    public function test_product_deletion_is_not_exposed_and_category_listing_uses_real_product_counts(): void
    {
        $administrator = User::factory()->administrator()->create();
        $category = Category::factory()->create(['name' => 'Turkey', 'normalized_name' => 'turkey']);
        $product = Product::factory()->for($category)->create();

        $this->actingAs($administrator)->delete("/admin/products/{$product->id}")->assertStatus(405);
        $this->assertDatabaseHas('products', ['id' => $product->id]);

        $this->actingAs($administrator)->get('/admin/categories')
            ->assertOk()
            ->assertSee('Products')
            ->assertSee('Turkey');

        $this->assertSame(1, $category->products()->count());
    }

    /** @return array<string, mixed> */
    private function validPayload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Titus Fish',
            'category_id' => $category->id,
            'description' => 'Frozen product description.',
            'selling_price' => '4500.00',
            'low_stock_threshold' => '10.000',
            'is_active' => '1',
            'remove_image' => '0',
        ], $overrides);
    }

    private function pngUpload(string $name, int $width, int $height): UploadedFile
    {
        $scanline = "\0".str_repeat("\0\0\0", $width);
        $pixels = str_repeat($scanline, $height);
        $header = pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);
        $content = "\x89PNG\r\n\x1a\n"
            .$this->pngChunk('IHDR', $header)
            .$this->pngChunk('IDAT', gzcompress($pixels))
            .$this->pngChunk('IEND', '');

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }
}
