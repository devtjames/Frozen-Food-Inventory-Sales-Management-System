<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, list<string>> */
    private array $columns = [
        'stock_receipts' => ['received_at'],
        'stock_adjustments' => ['adjusted_at'],
        'stock_batches' => ['received_at'],
        'stock_movements' => ['occurred_at'],
        'stock_allocations' => ['allocated_at'],
        'stock_losses' => ['lost_at'],
        'stock_loss_reversals' => ['reversed_at'],
        'sales' => ['sold_at'],
        'payments' => ['paid_at'],
        'sale_reversals' => ['reversed_at'],
        'refunds' => ['refunded_at'],
        'expense_revisions' => ['changed_at'],
    ];

    public function up(): void
    {
        foreach ($this->columns as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                foreach ($columns as $column) {
                    $table->dateTime($column)->change();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->columns as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                foreach ($columns as $column) {
                    $table->timestamp($column)->change();
                }
            });
        }
    }
};
