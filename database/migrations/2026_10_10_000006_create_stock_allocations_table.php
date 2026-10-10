<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_batch_id')->constrained()->restrictOnDelete();
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->decimal('quantity', 12, 3);
            $table->decimal('purchase_cost_per_kg', 12, 2);
            $table->decimal('total_cost', 24, 2);
            $table->timestamp('allocated_at');
            $table->timestamps();

            $table->unique(['stock_batch_id', 'source_type', 'source_id'], 'allocations_batch_source_unique');
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_allocations');
    }
};
