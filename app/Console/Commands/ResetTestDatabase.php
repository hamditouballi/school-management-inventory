<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetTestDatabase extends Command
{
    protected $signature = 'db:reset-test {--seed : Also run seeders}';

    protected $description = 'Reset database by truncating all tables and optionally seeding';

    public function handle(): int
    {
        $this->info('Truncating all tables...');

        $tables = [
            'invoice_items',
            'bon_de_livraison_items',
            'propositions',
            'proposition_groups',
            'supplier_items',
            'purchase_order_items',
            'request_items',
            'bon_de_livraisons',
            'invoices',
            'purchase_orders',
            'requests',
            'items',
            'suppliers',
            'categories',
            'departments',
            'bon_de_sorties',
            'notifications',
            'personal_access_tokens',
            'users',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            try {
                DB::table($table)->truncate();
                $this->line("  - Truncated: {$table}");
            } catch (\Exception $e) {
                $this->warn("  - Skipped: {$table} ({$e->getMessage()})");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info('All tables truncated successfully.');

        if ($this->option('seed')) {
            $this->info('Running seeders...');
            $this->call('db:seed');
        }

        return Command::SUCCESS;
    }
}
