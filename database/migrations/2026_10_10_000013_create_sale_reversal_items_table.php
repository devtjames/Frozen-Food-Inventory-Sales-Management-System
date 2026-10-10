<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_reversal_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_reversal_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_item_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('refund_amount', 24, 2);
            $table->decimal('purchase_cost_total', 24, 2);
            $table->boolean('stock_restored');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_reversal_items');
    }
};
