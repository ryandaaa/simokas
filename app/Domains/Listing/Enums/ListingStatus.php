<?php

declare(strict_types=1);

namespace App\Domains\Listing\Enums;

enum ListingStatus: string
{
    case PendingReview = 'pending_review';
    case Active = 'active';
    case Rejected = 'rejected';
    case InInspection = 'in_inspection';
    case InTransaction = 'in_transaction';
    case Sold = 'sold';
    case Inactive = 'inactive';

    /**
     * Check if a transition from current status to target status is valid.
     * Follows docs/flows/listing.md state machine & decisions/0002-flow-decisions.md (D-05).
     */
    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::PendingReview => in_array($to, [self::Active, self::Rejected], true),
            self::Rejected => $to === self::PendingReview,
            self::Active => in_array($to, [self::PendingReview, self::InInspection, self::InTransaction, self::Inactive], true),
            self::InInspection => $to === self::Active,
            self::InTransaction => in_array($to, [self::Active, self::Sold], true),
            self::Inactive => $to === self::Active,
            self::Sold => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => __('listing.status.pending_review'),
            self::Active => __('listing.status.active'),
            self::Rejected => __('listing.status.rejected'),
            self::InInspection => __('listing.status.in_inspection'),
            self::InTransaction => __('listing.status.in_transaction'),
            self::Sold => __('listing.status.sold'),
            self::Inactive => __('listing.status.inactive'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingReview => 'warning',
            self::Active => 'success',
            self::Rejected => 'danger',
            self::InInspection, self::InTransaction => 'primary',
            self::Sold, self::Inactive => 'muted',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PendingReview => 'jam',
            self::Active => 'centang',
            self::Rejected => 'silang',
            self::InInspection => 'kaca-pembesar',
            self::InTransaction => 'dua-panah',
            self::Sold => 'tag',
            self::Inactive => 'jeda',
        };
    }

    public function isPendingReview(): bool
    {
        return $this === self::PendingReview;
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isRejected(): bool
    {
        return $this === self::Rejected;
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this, [self::Active, self::InInspection, self::InTransaction], true);
    }
}
