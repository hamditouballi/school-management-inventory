<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['designation' => 'Cahiers (A4)', 'description' => 'Cahiers lignés pour étudiants', 'quantity' => 500, 'unit' => 'pièce', 'low_stock_threshold' => 100],
            ['designation' => 'Stylos (Bleu)', 'description' => 'Stylos à bille, encre bleue', 'quantity' => 1000, 'unit' => 'pièce', 'low_stock_threshold' => 200],
            ['designation' => 'Stylos (Rouge)', 'description' => 'Stylos à bille, encre rouge', 'quantity' => 500, 'unit' => 'pièce', 'low_stock_threshold' => 100],
            ['designation' => 'Crayons (HB)', 'description' => 'Crayons à graphite pour écrire', 'quantity' => 800, 'unit' => 'pièce', 'low_stock_threshold' => 150],
            ['designation' => 'Gommes', 'description' => 'Gommes blanches en caoutchouc', 'quantity' => 300, 'unit' => 'pièce', 'low_stock_threshold' => 80],
            ['designation' => 'Règles (30cm)', 'description' => 'Règles en plastique avec mesures métriques', 'quantity' => 200, 'unit' => 'pièce', 'low_stock_threshold' => 50],
            ['designation' => 'Marqueurs pour Tableau Blanc', 'description' => 'Marqueurs effaçables, couleurs variées', 'quantity' => 35, 'unit' => 'pièce', 'low_stock_threshold' => 50],
            ['designation' => 'Craie (Boîte)', 'description' => 'Craies blanches, 100 par boîte', 'quantity' => 25, 'unit' => 'boîte', 'low_stock_threshold' => 30],
            ['designation' => 'Papier A4 (Ramette)', 'description' => 'Papier blanc, 500 feuilles par ramette', 'quantity' => 100, 'unit' => 'ramette', 'low_stock_threshold' => 25],
            ['designation' => 'Agrafeuses', 'description' => 'Agrafeuses de bureau, taille standard', 'quantity' => 30, 'unit' => 'pièce', 'low_stock_threshold' => 10],
            ['designation' => 'Agrafes (Boîte)', 'description' => 'Agrafes standard, 1000 par boîte', 'quantity' => 50, 'unit' => 'boîte', 'low_stock_threshold' => 15],
            ['designation' => 'Ciseaux', 'description' => 'Ciseaux de sécurité pour étudiants', 'quantity' => 40, 'unit' => 'pièce', 'low_stock_threshold' => 20],
            ['designation' => 'Bâtons de Colle', 'description' => 'Colles lavables, 21g', 'quantity' => 60, 'unit' => 'pièce', 'low_stock_threshold' => 30],
            ['designation' => 'Crayons de Couleur (Set)', 'description' => 'Sets de 24 crayons de couleur', 'quantity' => 25, 'unit' => 'set', 'low_stock_threshold' => 10],
            ['designation' => 'Papier à Dessin (Paquet)', 'description' => 'Papier d\'art, 50 feuilles par paquet', 'quantity' => 40, 'unit' => 'paquet', 'low_stock_threshold' => 15],
            ['designation' => 'Chemises (Plastique)', 'description' => 'Chemises de documents avec poches', 'quantity' => 200, 'unit' => 'pièce', 'low_stock_threshold' => 50],
            ['designation' => 'Classeurs (3 anneaux)', 'description' => 'Classeurs capacité 1 pouce', 'quantity' => 80, 'unit' => 'pièce', 'low_stock_threshold' => 20],
            ['designation' => 'Fiches Bristol', 'description' => 'Fiches lignées, 100 par paquet', 'quantity' => 45, 'unit' => 'paquet', 'low_stock_threshold' => 20],
            ['designation' => 'Surligneurs', 'description' => 'Surligneurs fluorescents, variés', 'quantity' => 120, 'unit' => 'pièce', 'low_stock_threshold' => 40],
            ['designation' => 'Ruban Correcteur', 'description' => 'Ruban correcteur blanc', 'quantity' => 70, 'unit' => 'pièce', 'low_stock_threshold' => 25],
            ['designation' => 'Calculatrices (Basique)', 'description' => 'Calculatrices arithmétiques basiques', 'quantity' => 30, 'unit' => 'pièce', 'low_stock_threshold' => 15],
            ['designation' => 'Rapporteurs', 'description' => 'Rapporteurs en plastique 180 degrés', 'quantity' => 60, 'unit' => 'pièce', 'low_stock_threshold' => 20],
            ['designation' => 'Compas', 'description' => 'Compas de géométrie avec porte-mine', 'quantity' => 45, 'unit' => 'pièce', 'low_stock_threshold' => 15],
            ['designation' => 'Papier Millimétré (Bloc)', 'description' => 'Papier quadrillé, 50 feuilles', 'quantity' => 35, 'unit' => 'bloc', 'low_stock_threshold' => 15],
            ['designation' => 'Panneaux d\'Affichage', 'description' => 'Panneaux blancs pour projets', 'quantity' => 50, 'unit' => 'pièce', 'low_stock_threshold' => 20],
            ['designation' => 'Ruban Adhésif', 'description' => 'Ruban adhésif transparent', 'quantity' => 80, 'unit' => 'pièce', 'low_stock_threshold' => 30],
            ['designation' => 'Trombones (Boîte)', 'description' => 'Trombones standard, 100 par boîte', 'quantity' => 60, 'unit' => 'boîte', 'low_stock_threshold' => 20],
            ['designation' => 'Élastiques (Boîte)', 'description' => 'Élastiques assortis', 'quantity' => 40, 'unit' => 'boîte', 'low_stock_threshold' => 15],
            ['designation' => 'Enveloppes Jaunes', 'description' => 'Enveloppes format A4', 'quantity' => 100, 'unit' => 'pièce', 'low_stock_threshold' => 30],
            ['designation' => 'Planches à Clipboard', 'description' => 'Planches en plastique A4', 'quantity' => 25, 'unit' => 'pièce', 'low_stock_threshold' => 10],
        ];

        foreach ($items as $item) {
            Item::create($item);
        }
    }
}
