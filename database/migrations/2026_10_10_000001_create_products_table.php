<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('normalized_name', 150);
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->decimal('selling_price', 12, 2);
            $table->decimal('low_stock_threshold', 12, 3);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['category_id', 'normalized_name']);
            $table->index(['category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
