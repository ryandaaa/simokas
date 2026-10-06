<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Console\Commands;

use App\Domains\Transaction\Actions\MarkStalePaidHeldTransactions;
use Illuminate\Console\Command;

final class MarkStalePaidHeldTransactionsCommand extends Command
{
    protected $signature = 'transaction:mark-stale-paid-held';

    protected $description = 'Tandai transaksi paid_held yang melewati 7 hari sebagai perlu tindakan admin (TRX-E10, TRX-T12)';

    public function handle(MarkStalePaidHeldTransactions $action): int
    {
        $count = $action->handle();

        $this->info("Berhasil menandai {$count} transaksi paid_held sebagai perlu tindakan.");

        return self::SUCCESS;
    }
}
