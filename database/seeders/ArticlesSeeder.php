<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ArticlesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $file = fopen(database_path('seeders/articles.csv'), 'r');

        // Skip header row
        fgetcsv($file);

        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            // Convert encoding to UTF-8
            $data = array_map(function ($value) {
                return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
            }, $data);

            if (empty($data[2])) continue;

            Item::create([
                'image_path' => 'items/' . $data[0] . ".jpg",
                'designation' => $data[2],
                'quantity' => (int) $data[3],
                'unit' => $data[4],
                'price' => (float) $data[5],
                'description' => $data[6] ?? null,
            ]);
        }
        fclose($file);
    }
}
