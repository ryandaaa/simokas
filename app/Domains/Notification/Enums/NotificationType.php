<?php

declare(strict_types=1);

namespace App\Domains\Notification\Enums;

enum NotificationType: string
{
    case JobAvailable = 'job_available';
    case PaymentSucceeded = 'payment_succeeded';
    case InspectorApproved = 'inspector_approved';
    case InspectorRejected = 'inspector_rejected';
    case ListingSubmitted = 'listing_submitted';
    case ListingApproved = 'listing_approved';
    case ListingRejected = 'listing_rejected';
    case JobTaken = 'job_taken';
    case JobScheduled = 'job_scheduled';
    case ResultSubmitted = 'result_submitted';
    case JobCancelled = 'job_cancelled';
    case JobCompleted = 'job_completed';
    case FeeReleased = 'fee_released';
    case MessageReceived = 'message_received';
    case InspectorSubmitted = 'inspector_submitted';
    case TransactionOffered = 'transaction_offered';
    case TransactionAccepted = 'transaction_accepted';
    case TransactionRejected = 'transaction_rejected';
    case TransactionPaid = 'transaction_paid';
    case TransactionCompleted = 'transaction_completed';
    case TransactionCancelled = 'transaction_cancelled';
    case TransactionExpired = 'transaction_expired';

    public function label(): string
    {
        return match ($this) {
            self::JobAvailable => __('notification.types.job_available'),
            self::PaymentSucceeded => __('notification.types.payment_succeeded'),
            self::InspectorApproved => __('notification.types.inspector_approved'),
            self::InspectorRejected => __('notification.types.inspector_rejected'),
            self::ListingSubmitted => __('notification.types.listing_submitted'),
            self::ListingApproved => __('notification.types.listing_approved'),
            self::ListingRejected => __('notification.types.listing_rejected'),
            self::JobTaken => __('notification.types.job_taken'),
            self::JobScheduled => __('notification.types.job_scheduled'),
            self::ResultSubmitted => __('notification.types.result_submitted'),
            self::JobCancelled => __('notification.types.job_cancelled'),
            self::JobCompleted => __('notification.types.job_completed'),
            self::FeeReleased => __('notification.types.fee_released'),
            self::MessageReceived => __('notification.types.message_received'),
            self::InspectorSubmitted => __('notification.types.inspector_submitted'),
            self::TransactionOffered => __('notification.types.transaction_offered'),
            self::TransactionAccepted => __('notification.types.transaction_accepted'),
            self::TransactionRejected => __('notification.types.transaction_rejected'),
            self::TransactionPaid => __('notification.types.transaction_paid'),
            self::TransactionCompleted => __('notification.types.transaction_completed'),
            self::TransactionCancelled => __('notification.types.transaction_cancelled'),
            self::TransactionExpired => __('notification.types.transaction_expired'),
        };
    }
}
