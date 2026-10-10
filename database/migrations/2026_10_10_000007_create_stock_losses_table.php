<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_losses', function (Blueprint $table): void {
            $table->id();
            $table->uuid('idempotency_key')->unique();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('selected_stock_batch_id')->nullable()->constrained('stock_batches')->restrictOnDelete();
            $table->string('category', 30)->index();
            $table->decimal('quantity_lost', 12, 3);
            $table->decimal('financial_value', 24, 2);
            $table->string('reason', 255);
            $table->text('description')->nullable();
            $table->timestamp('lost_at')->index();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'lost_at']);
            $table->index(['category', 'lost_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_losses');
    }
};
