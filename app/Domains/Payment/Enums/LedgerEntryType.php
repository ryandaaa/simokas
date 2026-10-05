<?php

declare(strict_types=1);

namespace App\Domains\Payment\Enums;

enum LedgerEntryType: string
{
    case InspectionFee = 'inspection_fee';
    case AccessFee = 'access_fee';
    case SaleCommission = 'sale_commission';
    case PlatformFee = 'platform_fee';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::InspectionFee => __('payment.ledger.types.inspection_fee'),
            self::AccessFee => __('payment.ledger.types.access_fee'),
            self::SaleCommission => __('payment.ledger.types.sale_commission'),
            self::PlatformFee => __('payment.ledger.types.platform_fee'),
            self::Refund => __('payment.ledger.types.refund'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::InspectionFee => 'primary',
            self::AccessFee => 'primary',
            self::SaleCommission => 'success',
            self::PlatformFee => 'muted',
            self::Refund => 'warning',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::InspectionFee => 'kaca-pembesar',
            self::AccessFee => 'dokumen',
            self::SaleCommission => 'tag',
            self::PlatformFee => 'perisai-centang',
            self::Refund => 'dua-panah',
        };
    }
}
