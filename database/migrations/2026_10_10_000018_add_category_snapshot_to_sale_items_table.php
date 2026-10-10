<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->after('product_id')->constrained()->restrictOnDelete();
            $table->string('category_name')->nullable()->after('product_name');
            $table->index(['category_id', 'created_at']);
        });

        DB::table('sale_items')->orderBy('id')->chunkById(200, function ($items): void {
            foreach ($items as $item) {
                $product = DB::table('products')->where('id', $item->product_id)->first(['category_id']);
                $category = $product ? DB::table('categories')->where('id', $product->category_id)->first(['id', 'name']) : null;

                if ($category) {
                    DB::table('sale_items')->where('id', $item->id)->update([
                        'category_id' => $category->id,
                        'category_name' => $category->name,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->dropIndex(['category_id', 'created_at']);
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn('category_name');
        });
    }
};
