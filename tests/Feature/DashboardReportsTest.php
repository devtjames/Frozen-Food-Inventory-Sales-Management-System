<?php

namespace Tests\Feature;

use App\Enums\ReportType;
use App\Enums\SaleReversalStockAction;
use App\Models\Category;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\User;
use App\Services\ExpenseService;
use App\Services\FinancialReportService;
use App\Services\InventoryReportService;
use App\Services\InventoryService;
use App\Services\ReportDataService;
use App\Services\ReportingPeriodFactory;
use App\Services\SaleReversalService;
use App\Services\SalesService;
use App\Services\StockLossService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-10 12:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_financial_summary_uses_fifo_costs_expenses_and_separate_inventory_losses(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4500.00');
        $this->receive($product, $administrator, '2', '3000', '2026-10-08T09:00');
        $this->receive($product, $administrator, '5', '3500', '2026-10-09T09:00');
        $this->sell($product, $cashier, '2.500');
        $this->expense($administrator, '500.00', '2026-10-10');
        $this->loss($product, $administrator, '0.500', '2026-10-10T11:00');
        $period = app(ReportingPeriodFactory::class)->make(['period' => 'today']);
        $summary = app(FinancialReportService::class)->summary($period);

        $this->assertSame('11250.00', $summary['revenue']);
        $this->assertSame(1, $summary['transactions']);
        $this->assertSame('7750.00', $summary['cogs']);
        $this->assertSame('3500.00', $summary['gross_profit']);
        $this->assertSame('500.00', $summary['expenses']);
        $this->assertSame('1750.00', $summary['inventory_losses']);
        $this->assertSame('1250.00', $summary['net_profit']);
    }

    public function test_stock_valuation_uses_each_remaining_batch_purchase_cost(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4500.00');
        $this->receive($product, $administrator, '2', '3000', '2026-10-08T09:00');
        $this->receive($product, $administrator, '5', '3500', '2026-10-09T09:00');
        $this->sell($product, $cashier, '2.500');
        $this->loss($product, $administrator, '0.500', '2026-10-10T11:00');
        $summary = app(InventoryReportService::class)->summary();
        $row = app(InventoryReportService::class)->row(app(InventoryReportService::class)->products()->first());

        $this->assertSame('4.000', $summary['available_quantity']);
        $this->assertSame('14000.00', $summary['stock_value']);
        $this->assertSame('7.000', $row['received_quantity']);
        $this->assertSame('2.500', $row['sold_quantity']);
        $this->assertSame('0.500', $row['damaged_quantity']);
    }

    public function test_reversals_remove_revenue_and_cogs_while_non_restocked_cost_becomes_inventory_loss(): void
    {
        [$administrator, $cashier, $product] = $this->actors('5000.00');
        $this->receive($product, $administrator, '5', '3000');
        $restoredSale = $this->sell($product, $cashier, '1');
        app(SaleReversalService::class)->reverse($restoredSale, $this->reversalData(SaleReversalStockAction::Restore), $administrator);
        $lostSale = $this->sell($product, $cashier, '1');
        app(SaleReversalService::class)->reverse($lostSale, $this->reversalData(SaleReversalStockAction::DoNotRestore), $administrator);
        $period = app(ReportingPeriodFactory::class)->make(['period' => 'today']);
        $summary = app(FinancialReportService::class)->summary($period);

        $this->assertSame('0.00', $summary['revenue']);
        $this->assertSame('0.00', $summary['cogs']);
        $this->assertSame('3000.00', $summary['inventory_losses']);
        $this->assertSame('-3000.00', $summary['net_profit']);
        $this->assertSame('4.000', app(InventoryReportService::class)->summary()['available_quantity']);
    }

    public function test_business_timezone_controls_today_and_yesterday_boundaries(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4000.00');
        $this->receive($product, $administrator, '5', '2500');
        Carbon::setTestNow(Carbon::parse('2026-10-09 23:30:00', 'UTC'));
        $this->sell($product, $cashier, '1');
        Carbon::setTestNow(Carbon::parse('2026-10-10 12:00:00', 'UTC'));
        $factory = app(ReportingPeriodFactory::class);

        $this->assertSame('4000.00', app(FinancialReportService::class)->summary($factory->make(['period' => 'today']))['revenue']);
        $this->assertSame('0.00', app(FinancialReportService::class)->summary($factory->make(['period' => 'yesterday']))['revenue']);
    }

    public function test_admin_dashboard_and_all_detailed_reports_render_real_and_empty_periods(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4500.00');
        $this->receive($product, $administrator, '3', '2500');
        $this->sell($product, $cashier, '1');

        $this->actingAs($administrator)->get(route('admin.dashboard'))->assertOk()->assertSee("Today's revenue")->assertSee('4,500.00')->assertSee('Daily sales')->assertSee('Sales by cashier');

        foreach (ReportType::cases() as $type) {
            $this->actingAs($administrator)->get(route('admin.reports.show', $type->value))->assertOk()->assertSee($type->label());
        }

        $this->actingAs($administrator)->get(route('admin.reports.show', ['report' => 'sales', 'period' => 'custom', 'date_from' => '2020-01-01', 'date_to' => '2020-01-02']))
            ->assertOk()->assertSee('No report data')->assertSee('₦0.00');
    }

    public function test_product_category_and_cashier_reports_use_valid_sale_snapshots(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4500.00');
        $originalCategory = $product->category->name;
        $this->receive($product, $administrator, '5', '2500');
        $sale = $this->sell($product, $cashier, '2');
        $newCategory = Category::factory()->create(['name' => 'Renamed Group']);
        $product->update(['name' => 'Renamed Product', 'category_id' => $newCategory->id]);
        $period = app(ReportingPeriodFactory::class)->make(['period' => 'today']);
        $reports = app(ReportDataService::class);

        $this->assertSame($originalCategory, $sale->items->first()->category_name);
        $this->assertSame($sale->items->first()->product_name, $reports->generate(ReportType::ProductSales, $period, [])->rows->first()['product']);
        $this->assertSame($originalCategory, $reports->generate(ReportType::CategorySales, $period, [])->rows->first()['category']);
        $this->assertSame($cashier->name, $reports->generate(ReportType::CashierSales, $period, [])->rows->first()['cashier']);
        $this->assertSame('9000.00', $reports->generate(ReportType::ProductSales, $period, [])->rows->first()['revenue']);
    }

    public function test_csv_exports_have_clear_headers_exact_values_and_admin_authorization(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4500.00');
        $this->receive($product, $administrator, '5', '2500');
        $sale = $this->sell($product, $cashier, '1.125');
        $response = $this->actingAs($administrator)->get(route('admin.reports.export', ['report' => 'product-sales', 'period' => 'today']));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Quantity sold', $csv);
        $this->assertStringContainsString('1.125', $csv);
        $this->assertStringContainsString($sale->items->first()->product_name, $csv);
        $this->actingAs($cashier)->get(route('admin.reports.export', ['report' => 'profit']))->assertForbidden();
        auth()->logout();
        $this->get(route('admin.reports.export', ['report' => 'sales']))->assertRedirect('/login');
    }

    public function test_cashier_dashboard_contains_only_personal_non_sensitive_metrics(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4500.00');
        $other = User::factory()->cashier()->create();
        $this->receive($product, $administrator, '5', '2500');
        $owned = $this->sell($product, $cashier, '1');
        $otherSale = $this->sell($product, $other, '1');

        $this->actingAs($cashier)->get(route('cashier.dashboard'))->assertOk()
            ->assertSee("Today's personal sales")->assertSee('4,500.00')->assertSee($owned->transaction_number)
            ->assertDontSee($otherSale->transaction_number)->assertDontSee('FIFO COGS')->assertDontSee('Estimated net profit');
        $this->actingAs($cashier)->get(route('admin.reports.show', 'profit'))->assertForbidden();
    }

    public function test_custom_report_filters_limit_expenses_and_stock_losses(): void
    {
        [$administrator, $cashier, $product] = $this->actors();
        $this->receive($product, $administrator, '5', '2500');
        $expenseCategory = $this->expense($administrator, '700.00', '2026-10-10')->category;
        $this->expense($administrator, '900.00', '2026-09-01');
        $this->loss($product, $administrator, '0.250', '2026-10-10T11:00');

        $this->actingAs($administrator)->get(route('admin.reports.show', ['report' => 'expense', 'period' => 'custom', 'date_from' => '2026-10-10', 'date_to' => '2026-10-10', 'expense_category' => $expenseCategory->id]))
            ->assertOk()->assertSee('700.00')->assertDontSee('900.00');
        $this->actingAs($administrator)->get(route('admin.reports.show', ['report' => 'stock-loss', 'period' => 'today', 'loss_category' => 'damaged']))
            ->assertOk()->assertSee('Damaged')->assertSee('0.25 kg');
    }

    /** @return array{User, User, Product} */
    private function actors(string $sellingPrice = '4500.00'): array
    {
        $administrator = User::factory()->administrator()->create();
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->for(Category::factory())->create(['selling_price' => $sellingPrice]);

        return [$administrator, $cashier, $product];
    }

    private function receive(Product $product, User $administrator, string $quantity, string $cost, string $receivedAt = '2026-10-09T10:00')
    {
        return app(InventoryService::class)->receiveStock([
            'idempotency_key' => (string) Str::uuid(), 'product_id' => $product->id, 'quantity_received' => $quantity,
            'purchase_cost_per_kg' => $cost, 'received_at' => $receivedAt, 'supplier_name' => null, 'reference_number' => null, 'notes' => null,
        ], $administrator);
    }

    private function sell(Product $product, User $cashier, string $quantity)
    {
        return app(SalesService::class)->checkout([[
            'product_id' => $product->id, 'selling_price_per_kg' => $product->selling_price, 'selling_method' => 'kilograms', 'input_value' => $quantity,
        ]], 'card', null, (string) Str::uuid(), $cashier);
    }

    private function expense(User $administrator, string $amount, string $date)
    {
        $category = ExpenseCategory::query()->firstOrCreate(['normalized_name' => 'electricity'], ['name' => 'Electricity']);

        return app(ExpenseService::class)->create([
            'idempotency_key' => (string) Str::uuid(), 'expense_category_id' => $category->id, 'description' => 'Power expense',
            'amount' => $amount, 'expense_date' => $date, 'note' => null,
        ], $administrator);
    }

    private function loss(Product $product, User $administrator, string $quantity, string $date)
    {
        return app(StockLossService::class)->record([
            'idempotency_key' => (string) Str::uuid(), 'product_id' => $product->id, 'stock_batch_id' => null,
            'category' => 'damaged', 'quantity_lost' => $quantity, 'reason' => 'Damaged during handling.', 'description' => null, 'lost_at' => $date,
        ], $administrator);
    }

    /** @return array<string, string> */
    private function reversalData(SaleReversalStockAction $action): array
    {
        return [
            'idempotency_key' => (string) Str::uuid(), 'stock_action' => $action->value, 'refund_method' => 'cash',
            'reason' => 'Transaction reversal for reporting test.', 'reversed_at' => '2026-10-10T12:00',
        ];
    }
}
