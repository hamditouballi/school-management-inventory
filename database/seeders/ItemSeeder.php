<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Item;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['designation' => 'Notebooks (A4)', 'description' => 'Lined notebooks for students', 'quantity' => 500, 'price' => 2.50, 'unit' => 'piece', 'low_stock_threshold' => 100],
            ['designation' => 'Pens (Blue)', 'description' => 'Ball-point pens, blue ink', 'quantity' => 1000, 'price' => 0.50, 'unit' => 'piece', 'low_stock_threshold' => 200],
            ['designation' => 'Pens (Red)', 'description' => 'Ball-point pens, red ink', 'quantity' => 500, 'price' => 0.50, 'unit' => 'piece', 'low_stock_threshold' => 100],
            ['designation' => 'Pencils (HB)', 'description' => 'Graphite pencils for writing', 'quantity' => 800, 'price' => 0.30, 'unit' => 'piece', 'low_stock_threshold' => 150],
            ['designation' => 'Erasers', 'description' => 'White rubber erasers', 'quantity' => 300, 'price' => 0.25, 'unit' => 'piece', 'low_stock_threshold' => 80],
            ['designation' => 'Rulers (30cm)', 'description' => 'Plastic rulers with metric measurements', 'quantity' => 200, 'price' => 1.00, 'unit' => 'piece', 'low_stock_threshold' => 50],
            ['designation' => 'Whiteboard Markers', 'description' => 'Dry-erase markers, assorted colors', 'quantity' => 35, 'price' => 3.00, 'unit' => 'piece', 'low_stock_threshold' => 50],
            ['designation' => 'Chalk (Box)', 'description' => 'White chalk sticks, 100 per box', 'quantity' => 25, 'price' => 5.00, 'unit' => 'box', 'low_stock_threshold' => 30],
            ['designation' => 'A4 Paper (Ream)', 'description' => 'White copy paper, 500 sheets per ream', 'quantity' => 100, 'price' => 7.50, 'unit' => 'ream', 'low_stock_threshold' => 25],
            ['designation' => 'Staplers', 'description' => 'Desktop staplers, standard size', 'quantity' => 30, 'price' => 8.00, 'unit' => 'piece', 'low_stock_threshold' => 10],
            ['designation' => 'Staples (Box)', 'description' => 'Standard staples, 1000 per box', 'quantity' => 50, 'price' => 2.00, 'unit' => 'box', 'low_stock_threshold' => 15],
            ['designation' => 'Scissors', 'description' => 'Safety scissors for students', 'quantity' => 40, 'price' => 4.50, 'unit' => 'piece', 'low_stock_threshold' => 20],
            ['designation' => 'Glue Sticks', 'description' => 'Washable glue sticks, 21g', 'quantity' => 60, 'price' => 1.50, 'unit' => 'piece', 'low_stock_threshold' => 30],
            ['designation' => 'Colored Pencils (Set)', 'description' => '24-color pencil sets', 'quantity' => 25, 'price' => 12.00, 'unit' => 'set', 'low_stock_threshold' => 10],
            ['designation' => 'Drawing Paper (Pack)', 'description' => 'Art paper, 50 sheets per pack', 'quantity' => 40, 'price' => 6.00, 'unit' => 'pack', 'low_stock_threshold' => 15],
            ['designation' => 'Folders (Plastic)', 'description' => 'Document folders with pockets', 'quantity' => 200, 'price' => 1.20, 'unit' => 'piece', 'low_stock_threshold' => 50],
            ['designation' => 'Binders (3-Ring)', 'description' => '1-inch capacity binders', 'quantity' => 80, 'price' => 4.00, 'unit' => 'piece', 'low_stock_threshold' => 20],
            ['designation' => 'Index Cards', 'description' => 'Ruled index cards, 100 per pack', 'quantity' => 45, 'price' => 3.50, 'unit' => 'pack', 'low_stock_threshold' => 20],
            ['designation' => 'Highlighters', 'description' => 'Fluorescent highlighters, assorted', 'quantity' => 120, 'price' => 1.80, 'unit' => 'piece', 'low_stock_threshold' => 40],
            ['designation' => 'Correction Tape', 'description' => 'White correction tape', 'quantity' => 70, 'price' => 2.50, 'unit' => 'piece', 'low_stock_threshold' => 25],
            ['designation' => 'Calculator (Basic)', 'description' => 'Basic arithmetic calculators', 'quantity' => 30, 'price' => 8.50, 'unit' => 'piece', 'low_stock_threshold' => 15],
            ['designation' => 'Protractors', 'description' => '180-degree plastic protractors', 'quantity' => 60, 'price' => 1.50, 'unit' => 'piece', 'low_stock_threshold' => 20],
            ['designation' => 'Compasses', 'description' => 'Geometry compasses with pencil holder', 'quantity' => 45, 'price' => 3.00, 'unit' => 'piece', 'low_stock_threshold' => 15],
            ['designation' => 'Graph Paper (Pad)', 'description' => 'Quad-ruled graph paper, 50 sheets', 'quantity' => 35, 'price' => 4.00, 'unit' => 'pad', 'low_stock_threshold' => 15],
            ['designation' => 'Poster Boards', 'description' => 'White poster boards for projects', 'quantity' => 50, 'price' => 2.00, 'unit' => 'piece', 'low_stock_threshold' => 20],
            ['designation' => 'Tape (Scotch)', 'description' => 'Clear adhesive tape', 'quantity' => 80, 'price' => 1.80, 'unit' => 'piece', 'low_stock_threshold' => 30],
            ['designation' => 'Paper Clips (Box)', 'description' => 'Standard paper clips, 100 per box', 'quantity' => 60, 'price' => 1.00, 'unit' => 'box', 'low_stock_threshold' => 20],
            ['designation' => 'Rubber Bands (Box)', 'description' => 'Assorted rubber bands', 'quantity' => 40, 'price' => 1.50, 'unit' => 'box', 'low_stock_threshold' => 15],
            ['designation' => 'Manila Envelopes', 'description' => 'A4 size manila envelopes', 'quantity' => 100, 'price' => 0.80, 'unit' => 'piece', 'low_stock_threshold' => 30],
            ['designation' => 'Clipboards', 'description' => 'A4 plastic clipboards', 'quantity' => 25, 'price' => 3.50, 'unit' => 'piece', 'low_stock_threshold' => 10],
        ];

        foreach ($items as $item) {
            Item::create($item);
        }
    }
}
