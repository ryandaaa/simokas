<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Console\Commands;

use App\Domains\Inspection\Actions\AutoCompleteResultSubmittedJobs;
use Illuminate\Console\Command;

final class AutoCompleteJobsCommand extends Command
{
    protected $signature = 'inspection:auto-complete-jobs';

    protected $description = 'Selesaikan otomatis job result_submitted yang melewati 3 hari tanpa konfirmasi (D-03, JOB-R9, JOB-T11)';

    public function handle(AutoCompleteResultSubmittedJobs $action): int
    {
        $count = $action->handle();

        $this->info("Berhasil menyelesaikan {$count} job result_submitted.");

        return self::SUCCESS;
    }
}
