<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Electricity',
            'Generator fuel',
            'Transportation',
            'Staff salaries',
            'Shop rent',
            'Freezer maintenance',
            'Equipment repairs',
            'Miscellaneous expenses',
        ] as $name) {
            ExpenseCategory::query()->firstOrCreate(
                ['normalized_name' => Str::lower($name)],
                ['name' => $name],
            );
        }
    }
}
