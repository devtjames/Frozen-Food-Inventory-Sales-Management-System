<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Accounts are created explicitly through administrative workflows.
        $this->call(CategorySeeder::class);
        $this->call(ExpenseCategorySeeder::class);
    }
}
