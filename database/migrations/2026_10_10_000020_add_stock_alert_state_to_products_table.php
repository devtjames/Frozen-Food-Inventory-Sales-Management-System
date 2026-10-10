<?php

use App\Enums\StockAlertState;
use App\Services\DecimalMath;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('stock_alert_state', 24)->default(StockAlertState::Normal->value)->index()->after('low_stock_threshold');
        });

        $math = app(DecimalMath::class);

        DB::table('products')->orderBy('id')->each(function (object $product) use ($math): void {
            $available = DB::table('stock_batches')
                ->where('product_id', $product->id)
                ->where('remaining_quantity', '>', 0)
                ->pluck('remaining_quantity')
                ->reduce(
                    fn (string $total, mixed $quantity): string => $math->add($total, (string) $quantity, 3),
                    '0.000',
                );

            $state = $math->compare($available, '0.000', 3) <= 0
                ? StockAlertState::OutOfStock
                : ($math->compare($available, (string) $product->low_stock_threshold, 3) <= 0
                    ? StockAlertState::LowStock
                    : StockAlertState::Normal);

            DB::table('products')->where('id', $product->id)->update(['stock_alert_state' => $state->value]);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['stock_alert_state']);
            $table->dropColumn('stock_alert_state');
        });
    }
};
