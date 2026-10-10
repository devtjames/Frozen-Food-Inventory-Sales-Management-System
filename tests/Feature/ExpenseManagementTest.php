<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\ExpenseReportingService;
use App\Services\ExpenseService;
use Database\Seeders\ExpenseCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_categories_are_seeded_and_administrator_can_create_and_edit_categories(): void
    {
        $this->seed(ExpenseCategorySeeder::class);
        $administrator = User::factory()->administrator()->create();

        $this->assertDatabaseCount('expense_categories', 8);
        $this->assertDatabaseHas('expense_categories', ['name' => 'Electricity']);

        $this->actingAs($administrator)->post(route('admin.expense-categories.store'), ['name' => '  Water   supply '])
            ->assertRedirect(route('admin.expense-categories.index'));
        $category = ExpenseCategory::query()->where('normalized_name', 'water supply')->firstOrFail();
        $this->actingAs($administrator)->put(route('admin.expense-categories.update', $category), ['name' => 'Water and sanitation'])
            ->assertRedirect(route('admin.expense-categories.index'));

        $this->assertDatabaseHas('expense_categories', ['id' => $category->id, 'name' => 'Water and sanitation', 'normalized_name' => 'water and sanitation']);
    }

    public function test_duplicate_category_names_are_rejected_case_insensitively(): void
    {
        $administrator = User::factory()->administrator()->create();
        ExpenseCategory::query()->create(['name' => 'Electricity', 'normalized_name' => 'electricity']);

        $this->actingAs($administrator)->post(route('admin.expense-categories.store'), ['name' => ' ELECTRICITY '])
            ->assertSessionHasErrors('normalized_name');
        $this->assertDatabaseCount('expense_categories', 1);
    }

    public function test_expense_creation_is_decimal_safe_idempotent_and_records_the_administrator(): void
    {
        $administrator = User::factory()->administrator()->create();
        $category = $this->category('Electricity');
        $data = $this->expenseData($category, '15000.50');
        $this->actingAs($administrator)->post(route('admin.expenses.store'), $data)->assertRedirect();
        $this->actingAs($administrator)->post(route('admin.expenses.store'), $data)->assertRedirect();
        $expense = Expense::query()->firstOrFail();

        $this->assertSame('15000.50', $expense->amount);
        $this->assertSame($administrator->id, $expense->recorded_by);
        $this->assertDatabaseCount('expenses', 1);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_invalid_and_negative_expense_submissions_are_rejected(): void
    {
        $administrator = User::factory()->administrator()->create();
        $category = $this->category('Fuel');

        foreach (['-1', '0', '12.345', 'not-money'] as $amount) {
            $this->actingAs($administrator)->post(route('admin.expenses.store'), $this->expenseData($category, $amount))
                ->assertSessionHasErrors('amount');
        }

        $this->actingAs($administrator)->post(route('admin.expenses.store'), [
            'idempotency_key' => (string) Str::uuid(),
            'amount' => '100.00',
            'expense_date' => '2026-10-10',
        ])->assertSessionHasErrors(['expense_category_id', 'description']);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_listing_filters_expenses_and_total_covers_all_filtered_pages(): void
    {
        $administrator = User::factory()->administrator()->create();
        $electricity = $this->category('Electricity');
        $fuel = $this->category('Generator fuel');

        for ($index = 1; $index <= 11; $index++) {
            $this->createExpense($electricity, $administrator, '100.00', '2026-10-10', "Power payment {$index}");
        }
        $this->createExpense($fuel, $administrator, '500.00', '2026-09-01', 'Generator refill');

        $this->actingAs($administrator)->get(route('admin.expenses.index', [
            'search' => 'Power', 'category' => $electricity->id, 'date_from' => '2026-10-01', 'date_to' => '2026-10-31', 'page' => 2,
        ]))->assertOk()->assertSee('1,100.00')->assertSee('11 expense records')->assertSee('Power payment');

        $this->actingAs($administrator)->get(route('admin.expenses.index', ['category' => $fuel->id]))
            ->assertOk()->assertSee('Generator refill')->assertDontSee('Power payment 1');
    }

    public function test_expense_correction_preserves_changed_values_reason_and_retry_safety(): void
    {
        $administrator = User::factory()->administrator()->create();
        $electricity = $this->category('Electricity');
        $maintenance = $this->category('Freezer maintenance');
        $expense = $this->createExpense($electricity, $administrator, '15000.00', '2026-10-10', 'Monthly electricity payment');
        $key = (string) Str::uuid();
        $data = [
            'idempotency_key' => $key,
            'expense_category_id' => $maintenance->id,
            'description' => 'Freezer electrical repairs',
            'amount' => '18000.00',
            'expense_date' => '2026-10-10',
            'note' => 'Invoice corrected',
            'correction_reason' => 'Original invoice was classified and entered incorrectly.',
        ];
        $this->actingAs($administrator)->put(route('admin.expenses.update', $expense), $data)->assertRedirect(route('admin.expenses.show', $expense));
        $this->actingAs($administrator)->put(route('admin.expenses.update', $expense), $data)->assertRedirect(route('admin.expenses.show', $expense));
        $revision = $expense->refresh()->revisions()->firstOrFail();

        $this->assertSame('18000.00', $expense->amount);
        $this->assertSame($maintenance->id, $expense->expense_category_id);
        $this->assertSame(['category' => 'Electricity', 'description' => 'Monthly electricity payment', 'amount' => '15000.00', 'note' => null], $revision->previous_values);
        $this->assertSame(['category' => 'Freezer maintenance', 'description' => 'Freezer electrical repairs', 'amount' => '18000.00', 'note' => 'Invoice corrected'], $revision->updated_values);
        $this->assertSame($administrator->id, $revision->changed_by);
        $this->assertDatabaseCount('expense_revisions', 1);
        $this->actingAs($administrator)->get(route('admin.expenses.show', $expense))->assertOk()->assertSee('Original invoice was classified')->assertSee('15,000.00')->assertSee('18,000.00');
    }

    public function test_reporting_service_provides_range_daily_weekly_monthly_and_category_totals(): void
    {
        $administrator = User::factory()->administrator()->create();
        $electricity = $this->category('Electricity');
        $fuel = $this->category('Generator fuel');
        $this->createExpense($electricity, $administrator, '1000.10', '2026-09-30', 'September power');
        $this->createExpense($electricity, $administrator, '2000.20', '2026-10-10', 'October power');
        $this->createExpense($fuel, $administrator, '3000.30', '2026-10-12', 'October fuel');
        $reports = app(ExpenseReportingService::class);

        $this->assertSame('5000.50', $reports->totalForRange('2026-10-01', '2026-10-31'));
        $this->assertSame('2000.20', $reports->dailyTotals('2026-10-01', '2026-10-31')->get('2026-10-10'));
        $this->assertSame('2000.20', $reports->weeklyTotals('2026-10-01', '2026-10-31')->get('2026-10-05'));
        $this->assertSame('3000.30', $reports->weeklyTotals('2026-10-01', '2026-10-31')->get('2026-10-12'));
        $this->assertSame('5000.50', $reports->monthlyTotals('2026-09-01', '2026-10-31')->get('2026-10'));
        $this->assertSame('2000.20', $reports->categoryTotals('2026-10-01', '2026-10-31')->get($electricity->id)['total']);
        $this->assertSame('3000.30', $reports->categoryTotals('2026-10-01', '2026-10-31')->get($fuel->id)['total']);
    }

    public function test_cashiers_cannot_view_or_change_expenses_or_categories(): void
    {
        $administrator = User::factory()->administrator()->create();
        $cashier = User::factory()->cashier()->create();
        $category = $this->category('Electricity');
        $expense = $this->createExpense($category, $administrator, '100.00', '2026-10-10', 'Power');

        $this->actingAs($cashier)->get(route('admin.expenses.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('admin.expenses.show', $expense))->assertForbidden();
        $this->actingAs($cashier)->post(route('admin.expenses.store'), $this->expenseData($category, '500.00'))->assertForbidden();
        $this->actingAs($cashier)->put(route('admin.expenses.update', $expense), $this->expenseData($category, '500.00') + ['correction_reason' => 'Unauthorized edit attempt.'])->assertForbidden();
        $this->actingAs($cashier)->get(route('admin.expense-categories.index'))->assertForbidden();
    }

    private function category(string $name): ExpenseCategory
    {
        return ExpenseCategory::query()->create(['name' => $name, 'normalized_name' => Str::lower($name)]);
    }

    /** @return array<string, mixed> */
    private function expenseData(ExpenseCategory $category, string $amount): array
    {
        return [
            'idempotency_key' => (string) Str::uuid(),
            'expense_category_id' => $category->id,
            'description' => 'Monthly electricity payment',
            'amount' => $amount,
            'expense_date' => '2026-10-10',
            'note' => null,
        ];
    }

    private function createExpense(ExpenseCategory $category, User $administrator, string $amount, string $date, string $description): Expense
    {
        return app(ExpenseService::class)->create([
            'idempotency_key' => (string) Str::uuid(),
            'expense_category_id' => $category->id,
            'description' => $description,
            'amount' => $amount,
            'expense_date' => $date,
            'note' => null,
        ], $administrator);
    }
}
