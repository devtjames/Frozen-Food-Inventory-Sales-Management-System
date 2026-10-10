<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->uuid('checkout_key')->unique();
            $table->string('transaction_number', 50)->unique();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('sold_at')->index();
            $table->decimal('total_amount', 24, 2);
            $table->decimal('total_rounding_adjustment', 24, 2)->default(0);
            $table->string('payment_method', 30)->index();
            $table->string('payment_status', 20)->index();
            $table->string('transaction_status', 20)->index();
            $table->decimal('amount_tendered', 24, 2)->nullable();
            $table->decimal('change_due', 24, 2)->nullable();
            $table->timestamps();

            $table->index(['cashier_id', 'sold_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
