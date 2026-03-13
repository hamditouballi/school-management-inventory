<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            CategorySeeder::class,
            UserSeeder::class,
            ItemSeeder::class,
            // ArticlesSeeder::class,
            RequestSeeder::class,
            PurchaseOrderSeeder::class,
            InvoiceSeeder::class,
        ]);
    }
}
