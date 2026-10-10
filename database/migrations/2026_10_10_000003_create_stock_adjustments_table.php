<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('idempotency_key')->unique();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('type', 20)->index();
            $table->decimal('quantity', 12, 3);
            $table->decimal('cost_basis_per_kg', 12, 2)->nullable();
            $table->decimal('total_cost_impact', 24, 2)->default(0);
            $table->text('reason');
            $table->timestamp('adjusted_at')->index();
            $table->string('reference_number', 100)->nullable()->index();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'adjusted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
