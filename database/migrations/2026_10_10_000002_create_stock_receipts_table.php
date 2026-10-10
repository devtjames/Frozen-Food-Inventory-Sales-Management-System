<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_receipts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('idempotency_key')->unique();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_received', 12, 3);
            $table->decimal('purchase_cost_per_kg', 12, 2);
            $table->decimal('total_purchase_cost', 24, 2);
            $table->string('supplier_name')->nullable();
            $table->timestamp('received_at')->index();
            $table->string('reference_number', 100)->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_receipts');
    }
};
