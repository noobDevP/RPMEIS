<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckDatabaseCommand extends Command
{
    protected $signature = 'db:check-legacy {--import-hint : Show how to import the schema SQL file}';

    protected $description = 'Verify MySQL connection and legacy table presence';

    public function handle(): int
    {
        if ($this->option('import-hint')) {
            $schema = database_path('schema/u183679184_visayas_qr.sql');
            $this->line('Import the legacy database (with MySQL running):');
            $this->line("  mysql -u root -p -e \"CREATE DATABASE IF NOT EXISTS u183679184_visayas_qr;\"");
            $this->line("  mysql -u root -p u183679184_visayas_qr < \"{$schema}\"");

            return self::SUCCESS;
        }

        try {
            DB::connection()->getPdo();
            $this->info('Database connection: OK');
        } catch (Throwable $e) {
            $this->error('Database connection failed: '.$e->getMessage());
            $this->line('Start MySQL, set DB_* in .env, then run: php artisan db:check-legacy --import-hint');

            return self::FAILURE;
        }

        $tables = ['patient_data', 'user_table', 'admin_credential', 'place', 'announcements', 'logs', 'logs_doctors'];
        $missing = [];

        foreach ($tables as $table) {
            try {
                DB::table($table)->limit(1)->count();
                $this->line("  [ok] {$table}");
            } catch (Throwable) {
                $missing[] = $table;
                $this->line("  [missing] {$table}");
            }
        }

        if ($missing !== []) {
            $this->warn('Some legacy tables are missing. Run: php artisan db:check-legacy --import-hint');

            return self::FAILURE;
        }

        $this->info('All legacy tables present.');

        return self::SUCCESS;
    }
}
