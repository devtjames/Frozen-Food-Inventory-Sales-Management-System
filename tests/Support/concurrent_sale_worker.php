<?php

use App\Models\Product;
use App\Models\User;
use App\Services\SalesService;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[$script, $productId, $sellerId, $quantity, $checkoutKey, $startAtMilliseconds] = $argv;
$delayMicroseconds = (((int) $startAtMilliseconds * 1000) - (int) round(microtime(true) * 1_000_000));

if ($delayMicroseconds > 0) {
    usleep($delayMicroseconds);
}

try {
    $product = Product::query()->findOrFail((int) $productId);
    $seller = User::query()->findOrFail((int) $sellerId);
    $sale = app(SalesService::class)->checkout([[
        'product_id' => $product->id,
        'selling_price_per_kg' => $product->selling_price,
        'selling_method' => 'kilograms',
        'input_value' => $quantity,
    ]], 'card', null, $checkoutKey, $seller);

    fwrite(STDOUT, json_encode(['status' => 'completed', 'sale_id' => $sale->id], JSON_THROW_ON_ERROR).PHP_EOL);
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDOUT, json_encode(['status' => 'rejected', 'type' => $exception::class], JSON_THROW_ON_ERROR).PHP_EOL);
    exit(2);
}
