<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('stock_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('movement_type', 40)->index();
            $table->decimal('quantity_delta', 12, 3);
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->timestamp('occurred_at')->index();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id', 'movement_type'], 'movements_source_type_unique');
            $table->index(['product_id', 'occurred_at']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
