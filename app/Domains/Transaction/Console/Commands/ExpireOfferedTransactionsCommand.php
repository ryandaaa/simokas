<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Console\Commands;

use App\Domains\Transaction\Actions\ExpireOfferedTransactions;
use Illuminate\Console\Command;

final class ExpireOfferedTransactionsCommand extends Command
{
    protected $signature = 'transaction:expire-offered';

    protected $description = 'Kedaluwarsakan transaksi offered yang melewati batas 48 jam (TRX-R9, TRX-T11)';

    public function handle(ExpireOfferedTransactions $action): int
    {
        $count = $action->handle();

        $this->info("Berhasil mengedaluwarsakan {$count} transaksi offered.");

        return self::SUCCESS;
    }
}
