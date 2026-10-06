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
        };
    }
}
