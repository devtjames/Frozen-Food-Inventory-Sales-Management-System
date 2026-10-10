<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('business_name')->default('Frozen Food Inventory');
            $table->string('logo_path')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('receipt_footer_message', 500)->nullable();
            $table->char('currency_code', 3)->default('NGN');
            $table->decimal('default_low_stock_threshold', 12, 3)->default(10);
            $table->boolean('show_logo_on_receipts')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_settings');
    }
};
