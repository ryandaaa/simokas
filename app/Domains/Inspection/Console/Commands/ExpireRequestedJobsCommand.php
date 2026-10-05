<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Console\Commands;

use App\Domains\Inspection\Actions\ExpireRequestedJobs;
use Illuminate\Console\Command;

final class ExpireRequestedJobsCommand extends Command
{
    protected $signature = 'inspection:expire-requested-jobs';

    protected $description = 'Kedaluwarsakan job requested yang melewati batas waktu 30 menit (JOB-R7, JOB-T09)';

    public function handle(ExpireRequestedJobs $action): int
    {
        $count = $action->handle();

        $this->info("Berhasil mengedaluwarsakan {$count} job requested.");

        return self::SUCCESS;
    }
}
