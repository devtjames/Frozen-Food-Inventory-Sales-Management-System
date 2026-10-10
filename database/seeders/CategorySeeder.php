<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Fish', 'Chicken', 'Turkey', 'Seafood', 'Other Frozen Foods'] as $name) {
            Category::query()->firstOrCreate(
                ['normalized_name' => Str::lower($name)],
                ['name' => $name, 'is_active' => true],
            );
        }
    }
}
