<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Console\Commands;

use App\Domains\Inspection\Actions\CancelUnclaimedPaidJobs;
use Illuminate\Console\Command;

final class CancelUnclaimedJobsCommand extends Command
{
    protected $signature = 'inspection:cancel-unclaimed-jobs';

    protected $description = 'Batalkan job paid yang tidak diambil dalam 7 hari dan lakukan refund 100% (D-01, JOB-R8, JOB-T10)';

    public function handle(CancelUnclaimedPaidJobs $action): int
    {
        $count = $action->handle();

        $this->info("Berhasil membatalkan {$count} job paid yang tidak diambil.");

        return self::SUCCESS;
    }
}
