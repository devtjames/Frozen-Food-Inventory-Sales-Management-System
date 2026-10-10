<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('stock_receipt_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('stock_adjustment_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->decimal('original_quantity', 12, 3);
            $table->decimal('remaining_quantity', 12, 3);
            $table->decimal('purchase_cost_per_kg', 12, 2);
            $table->decimal('total_purchase_cost', 24, 2);
            $table->timestamp('received_at')->index();
            $table->string('reference_number', 100)->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'remaining_quantity', 'received_at'], 'batches_fifo_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
