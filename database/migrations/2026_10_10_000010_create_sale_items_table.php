<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->decimal('selling_price_per_kg', 12, 2);
            $table->string('selling_method', 20);
            $table->decimal('requested_amount', 24, 2)->nullable();
            $table->decimal('requested_quantity', 12, 3)->nullable();
            $table->decimal('quantity', 12, 3);
            $table->decimal('item_amount', 24, 2);
            $table->decimal('calculated_value', 24, 2);
            $table->decimal('rounding_adjustment', 24, 2)->default(0);
            $table->decimal('purchase_cost_total', 24, 2)->default(0);
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
