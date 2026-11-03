<?php

namespace App\Console\Commands;

use App\Models\Patient;
use Illuminate\Console\Command;
use Carbon\Carbon;

class PruneArchivedPatients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patients:prune-archived {--months=60 : The number of months to keep inactive patients before deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete archived (inactive) patients that are older than the specified threshold.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $months = (int) $this->option('months');
        $threshold = Carbon::now()->subMonths($months);
        
        $this->info("Looking for inactive patients archived before {$threshold->toDateString()}...");

        // We assume inactive patients haven't been updated since they were archived
        // so we check updated_at.
        $patientsQuery = Patient::query()
            ->where('status', 'inactive')
            ->where('dateOfExamination', '<', $threshold->toDateString());
            
        $count = $patientsQuery->count();
        
        if ($count === 0) {
            $this->info('No old archived patients found to prune.');
            return 0;
        }
        
        if ($this->confirm("Are you sure you want to permanently delete {$count} old archived patients?")) {
            $patientsQuery->delete();
            $this->info("Successfully deleted {$count} archived patients.");
        } else {
            $this->info('Operation cancelled.');
        }
        
        return 0;
    }
}
