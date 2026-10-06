<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Console\Commands;

use App\Domains\Transaction\Actions\ExpireAcceptedTransactions;
use Illuminate\Console\Command;

final class ExpireAcceptedTransactionsCommand extends Command
{
    protected $signature = 'transaction:expire-accepted';

    protected $description = 'Kedaluwarsakan transaksi accepted yang melewati batas 24 jam pembayaran (TRX-R9, TRX-T11)';

    public function handle(ExpireAcceptedTransactions $action): int
    {
        $count = $action->handle();

        $this->info("Berhasil mengedaluwarsakan {$count} transaksi accepted.");

        return self::SUCCESS;
    }
}
