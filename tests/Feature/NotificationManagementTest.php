<?php

namespace Tests\Feature;

use App\Enums\AdminNotificationType;
use App\Enums\StockAlertState;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\AdminNotificationService;
use App\Services\InventoryAlertService;
use App\Services\InventoryService;
use App\Services\SalesService;
use App\Services\StockLossService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_transitions_create_one_low_and_one_out_of_stock_alert(): void
    {
        [$administrator, $cashier, $product] = $this->actors();
        $this->receive($product, $administrator, '20');

        $this->sell($product, $cashier, '12');

        $this->assertSame(StockAlertState::LowStock, $product->refresh()->stock_alert_state);
        $lowAlert = $administrator->notifications()->sole();
        $this->assertSame('low_stock', $lowAlert->data['type']);
        $this->assertSame(route('admin.products.show', $product), $lowAlert->data['related_url']);

        DB::transaction(fn () => app(InventoryAlertService::class)->sync($product));
        $this->assertSame(1, $administrator->notifications()->count());

        $this->sell($product, $cashier, '8');

        $this->assertSame(StockAlertState::OutOfStock, $product->refresh()->stock_alert_state);
        $this->assertSame(2, $administrator->notifications()->count());
        $this->assertTrue($administrator->notifications()->get()->contains(
            fn ($notification): bool => $notification->data['type'] === 'out_of_stock',
        ));
    }

    public function test_replenishment_resets_state_and_allows_a_later_low_stock_alert(): void
    {
        [$administrator, $cashier, $product] = $this->actors();
        $this->receive($product, $administrator, '20');
        $this->sell($product, $cashier, '12');

        $this->receive($product, $administrator, '10');
        $this->assertSame(StockAlertState::Normal, $product->refresh()->stock_alert_state);

        $this->sell($product, $cashier, '10');

        $this->assertSame(StockAlertState::LowStock, $product->refresh()->stock_alert_state);
        $this->assertSame(2, $administrator->notifications()->whereJsonContains('data->type', 'low_stock')->count());
    }

    public function test_out_of_stock_product_cannot_be_sold_again(): void
    {
        [$administrator, $cashier, $product] = $this->actors();
        $this->receive($product, $administrator, '2');
        $this->sell($product, $cashier, '2');

        $this->expectException(InsufficientStockException::class);
        $this->sell($product, $cashier, '0.500');
    }

    public function test_adjustments_and_losses_create_auditable_activity_notifications_without_retry_duplicates(): void
    {
        [$administrator, , $product] = $this->actors();
        $this->receive($product, $administrator, '20');
        $inventory = app(InventoryService::class);
        $adjustmentPayload = [
            'idempotency_key' => (string) Str::uuid(),
            'product_id' => $product->id,
            'type' => 'positive',
            'quantity' => '2.000',
            'cost_basis_per_kg' => '3000.00',
            'reason' => 'Verified count correction.',
            'adjusted_at' => '2026-10-10T09:00',
            'reference_number' => null,
        ];
        $inventory->adjustStock($adjustmentPayload, $administrator);
        $inventory->adjustStock($adjustmentPayload, $administrator);

        $losses = app(StockLossService::class);
        $lossPayload = [
            'idempotency_key' => (string) Str::uuid(),
            'product_id' => $product->id,
            'stock_batch_id' => null,
            'category' => 'damaged',
            'quantity_lost' => '1.000',
            'reason' => 'Packaging damaged during handling.',
            'description' => null,
            'lost_at' => '2026-10-10T10:00',
        ];
        $losses->record($lossPayload, $administrator);
        $losses->record($lossPayload, $administrator);

        $types = $administrator->notifications()->get()->pluck('data.type');
        $this->assertSame(1, $types->filter(fn (string $type): bool => $type === 'stock_adjustment')->count());
        $this->assertSame(1, $types->filter(fn (string $type): bool => $type === 'stock_loss')->count());
    }

    public function test_administrator_can_view_and_mark_notifications_read_while_cashier_is_forbidden(): void
    {
        [$administrator, $cashier] = $this->actors();
        app(AdminNotificationService::class)->send(AdminNotificationType::Activity, 'Inventory check completed', 'The scheduled stock review was recorded.', route('admin.inventory.index'));
        app(AdminNotificationService::class)->send(AdminNotificationType::StockAdjustment, 'Stock adjustment recorded', 'A verified correction was recorded.', route('admin.inventory.adjustments.index'));
        $notification = $administrator->unreadNotifications()->firstOrFail();

        $this->actingAs($administrator)->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Inventory check completed')
            ->assertSee('Unread')
            ->assertSee(route('admin.inventory.index'));
        $this->actingAs($administrator)->patch(route('admin.notifications.read', $notification->id))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
        $this->actingAs($administrator)->patch(route('admin.notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $administrator->unreadNotifications()->count());

        $this->actingAs($cashier)->get(route('admin.notifications.index'))->assertForbidden();
        $this->actingAs($cashier)->patch(route('admin.notifications.read', $notification->id))->assertForbidden();
    }

    public function test_dashboard_displays_live_stock_warnings_with_product_links(): void
    {
        [$administrator, , $product] = $this->actors();
        $this->receive($product, $administrator, '8.500');

        $this->actingAs($administrator)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Stock warnings')
            ->assertSee($product->name)
            ->assertSee('8.5 kg')
            ->assertSee(route('admin.products.show', $product));
    }

    public function test_inactive_administrators_and_cashiers_do_not_receive_administrative_alerts(): void
    {
        [$administrator, $cashier, $product] = $this->actors();
        $inactiveAdministrator = User::factory()->administrator()->inactive()->create();
        $this->receive($product, $administrator, '8');

        $this->assertSame(1, $administrator->notifications()->count());
        $this->assertSame(0, $inactiveAdministrator->notifications()->count());
        $this->assertSame(0, $cashier->notifications()->count());
    }

    /** @return array{User, User, Product} */
    private function actors(): array
    {
        $administrator = User::factory()->administrator()->create();
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->for(Category::factory())->create([
            'selling_price' => '4000.00',
            'low_stock_threshold' => '10.000',
        ]);

        return [$administrator, $cashier, $product];
    }

    private function receive(Product $product, User $administrator, string $quantity): void
    {
        app(InventoryService::class)->receiveStock([
            'idempotency_key' => (string) Str::uuid(),
            'product_id' => $product->id,
            'quantity_received' => $quantity,
            'purchase_cost_per_kg' => '2500.00',
            'received_at' => '2026-10-10T08:00',
            'supplier_name' => null,
            'reference_number' => null,
            'notes' => null,
        ], $administrator);
    }

    private function sell(Product $product, User $cashier, string $quantity): void
    {
        app(SalesService::class)->checkout([[
            'product_id' => $product->id,
            'selling_price_per_kg' => $product->selling_price,
            'selling_method' => 'kilograms',
            'input_value' => $quantity,
        ]], 'card', null, (string) Str::uuid(), $cashier);
    }
}
