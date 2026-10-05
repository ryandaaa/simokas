<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Console\Commands;

use App\Domains\Inspection\Actions\MarkJobsNeedingAttention;
use Illuminate\Console\Command;

final class MarkNeedsAttentionCommand extends Command
{
    protected $signature = 'inspection:mark-needs-attention';

    protected $description = 'Tandai job yang memerlukan tindakan admin (JOB-R10, JOB-E4, JOB-T12)';

    public function handle(MarkJobsNeedingAttention $action): int
    {
        $count = $action->handle();

        $this->info("Berhasil menandai {$count} job yang memerlukan tindakan.");

        return self::SUCCESS;
    }
}
