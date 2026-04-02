<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\Supplier;
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
        ]);

        $categoryIds = Category::pluck('id')->toArray();

        $items = [];
        for ($i = 1; $i <= 3; $i++) {
            $items[] = Item::create([
                'designation' => "Item $i",
                'category_id' => fake()->randomElement($categoryIds),
                'quantity' => 0,
                'unit' => 'pcs',
                'low_stock_threshold' => 5,
            ]);
        }

        $suppliers = [];
        foreach (['Supplier A', 'Supplier B', 'Supplier C'] as $name) {
            $suppliers[] = Supplier::create([
                'name' => $name,
                'contact_info' => fake()->phoneNumber(),
            ]);
        }

        foreach ($items as $item) {
            $basePrice = fake()->randomFloat(2, 10, 100);
            foreach ($suppliers as $supplier) {
                $variance = fake()->randomFloat(2, -0.10, 0.10);
                $unitPrice = round($basePrice * (1 + $variance), 2);
                $supplier->items()->attach($item->id, ['unit_price' => $unitPrice]);
            }
        }
    }
}
