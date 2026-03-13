<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'MARQUEURS', 'description' => 'Marqueurs et stylos'],
            ['name' => 'TONNER', 'description' => 'Tonner pour imprimantes'],
            ['name' => 'PAPETERIE', 'description' => 'Articles de papeterie'],
            ['name' => 'HYGIENE', 'description' => 'Produits d\'hygiène'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
