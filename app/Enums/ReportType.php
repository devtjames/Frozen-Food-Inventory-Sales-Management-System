<?php

namespace App\Enums;

enum ReportType: string
{
    case Sales = 'sales';
    case ProductSales = 'product-sales';
    case CategorySales = 'category-sales';
    case CashierSales = 'cashier-sales';
    case Inventory = 'inventory';
    case StockReceiving = 'stock-receiving';
    case StockLoss = 'stock-loss';
    case Expense = 'expense';
    case Profit = 'profit';

    public function label(): string
    {
        return match ($this) {
            self::Sales => 'Sales report',
            self::ProductSales => 'Product sales report',
            self::CategorySales => 'Category sales report',
            self::CashierSales => 'Cashier sales report',
            self::Inventory => 'Inventory report',
            self::StockReceiving => 'Stock receiving report',
            self::StockLoss => 'Stock loss report',
            self::Expense => 'Expense report',
            self::Profit => 'Profit report',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Sales => 'Transaction values and effective reversal status.',
            self::ProductSales => 'Valid sales quantities, revenue, FIFO cost, and gross profit by product.',
            self::CategorySales => 'Valid sales performance using category snapshots captured at checkout.',
            self::CashierSales => 'Valid transaction count and revenue by cashier.',
            self::Inventory => 'Current batch-derived quantities, valuation, receipts, sales, and losses.',
            self::StockReceiving => 'Delivery quantities and purchase costs recorded during the period.',
            self::StockLoss => 'Recorded stock losses and their effective recognition status.',
            self::Expense => 'Operating expenses recorded during the period.',
            self::Profit => 'Estimated revenue, FIFO COGS, operating expenses, inventory losses, and profit.',
        };
    }
}
