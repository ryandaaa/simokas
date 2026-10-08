<?php

declare(strict_types=1);

namespace App\Domains\Notification\Listeners;

use App\Domains\Chat\Events\MessageSent;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Inspection\Events\JobCompleted;
use App\Domains\Inspection\Events\JobScheduled;
use App\Domains\Inspection\Events\JobTaken;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Events\InspectorApproved;
use App\Domains\Inspector\Events\InspectorRejected;
use App\Domains\Inspector\Events\InspectorSubmitted;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Events\ListingApproved;
use App\Domains\Listing\Events\ListingRejected;
use App\Domains\Listing\Events\ListingSubmitted;
use App\Domains\Listing\Models\Listing;
use App\Domains\Notification\Actions\StoreNotification;
use App\Domains\Notification\Enums\NotificationType;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Result\Events\ResultSubmitted;
use App\Domains\Transaction\Events\TransactionAccepted;
use App\Domains\Transaction\Events\TransactionCancelled;
use App\Domains\Transaction\Events\TransactionCompleted;
use App\Domains\Transaction\Events\TransactionExpired;
use App\Domains\Transaction\Events\TransactionOffered;
use App\Domains\Transaction\Events\TransactionPaid;
use App\Domains\Transaction\Events\TransactionRejected;
use App\Support\Clock;
use App\Support\Money;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Mendengar event domain lain dan menyimpan notifikasi dalam web (NTF-1).
 *
 * Daftar pemicu mengikuti tabel di docs/flows/notification.md. Domain lain
 * tidak memanggil Notification langsung, hanya memancarkan event (NTF-R2).
 *
 * NTF-E7: kegagalan listener tidak boleh menggagalkan aksi utama, sehingga
 * semua exception ditangkap dan dicatat, bukan dilempar ulang. Pengiriman
 * real-time (Pusher) ditambahkan di NTF-2.
 */
final class StoreNotificationOnEvent implements ShouldQueue
{
    public function __construct(
        private readonly StoreNotification $store,
    ) {}

    public function handle(object $event): void
    {
        try {
            match (true) {
                $event instanceof PaymentSucceeded => $this->onPaymentSucceeded($event),
                $event instanceof JobTaken => $this->onJobTaken($event),
                $event instanceof JobScheduled => $this->onJobScheduled($event),
                $event instanceof JobCancelled => $this->onJobCancelled($event),
                $event instanceof JobCompleted => $this->onJobCompleted($event),
                $event instanceof ResultSubmitted => $this->onResultSubmitted($event),
                $event instanceof ListingSubmitted => $this->onListingSubmitted($event),
                $event instanceof ListingApproved => $this->onListingApproved($event),
                $event instanceof ListingRejected => $this->onListingRejected($event),
                $event instanceof InspectorApproved => $this->onInspectorApproved($event),
                $event instanceof InspectorRejected => $this->onInspectorRejected($event),
                $event instanceof InspectorSubmitted => $this->onInspectorSubmitted($event),
                $event instanceof MessageSent => $this->onMessageSent($event),
                $event instanceof TransactionOffered => $this->onTransactionOffered($event),
                $event instanceof TransactionAccepted => $this->onTransactionAccepted($event),
                $event instanceof TransactionRejected => $this->onTransactionRejected($event),
                $event instanceof TransactionPaid => $this->onTransactionPaid($event),
                $event instanceof TransactionCompleted => $this->onTransactionCompleted($event),
                $event instanceof TransactionCancelled => $this->onTransactionCancelled($event),
                $event instanceof TransactionExpired => $this->onTransactionExpired($event),
                default => null,
            };
        } catch (\Throwable $exception) {
            Log::error('notification.listener_failed', [
                'event' => $event::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function onPaymentSucceeded(PaymentSucceeded $event): void
    {
        $this->store->handle(
            userId: $event->payerId,
            type: NotificationType::PaymentSucceeded,
            title: __('notification.payment_success.title'),
            body: __('notification.payment_success.body', ['nominal' => Money::format($event->amount)]),
            link: route('payments.show', ['payment' => $event->paymentId]),
            dedupeKey: "payment_succeeded:{$event->paymentId}:{$event->payerId}",
        );

        if ($event->purpose !== PaymentPurpose::Inspection) {
            return;
        }

        $job = InspectionJob::query()->find($event->refId);

        if (! $job instanceof InspectionJob) {
            return;
        }

        $listing = $job->listing;

        if (! $listing instanceof Listing) {
            return;
        }

        $inspectorIds = InspectorProfile::query()
            ->where('verification_status', VerificationStatus::Approved)
            ->whereJsonContains('vehicle_types', $listing->vehicle_type->value)
            ->whereHas('user', fn ($query) => $query
                ->where('role', UserRole::Inspector)
                ->where('status', UserStatus::Active))
            ->pluck('user_id');

        foreach ($inspectorIds as $inspectorId) {
            $this->store->handle(
                userId: (string) $inspectorId,
                type: NotificationType::JobAvailable,
                title: __('notification.job_available.title'),
                body: __('notification.job_available.body', ['judul' => $listing->title]),
                link: route('jobs.show', ['job' => $job->getKey()]),
                dedupeKey: "job_available:{$job->getKey()}:{$inspectorId}",
            );
        }
    }

    private function onJobTaken(JobTaken $event): void
    {
        $job = InspectionJob::query()->find($event->jobId);

        if (! $job instanceof InspectionJob) {
            return;
        }

        $listing = $job->listing;
        $inspectorName = $this->userName($event->inspectorId);
        $link = route('jobs.show', ['job' => $job->getKey()]);

        if ($listing instanceof Listing) {
            $this->store->handle(
                userId: $listing->seller_id,
                type: NotificationType::JobTaken,
                title: __('notification.job_taken.title'),
                body: __('notification.job_taken.body_seller', ['nama' => $inspectorName, 'judul' => $listing->title]),
                link: $link,
                dedupeKey: "job_taken:{$job->getKey()}:{$listing->seller_id}",
            );
        }

        $this->store->handle(
            userId: $job->requester_id,
            type: NotificationType::JobTaken,
            title: __('notification.job_taken.title'),
            body: __('notification.job_taken.body_requester', ['nama' => $inspectorName]),
            link: $link,
            dedupeKey: "job_taken:{$job->getKey()}:{$job->requester_id}",
        );
    }

    private function onJobScheduled(JobScheduled $event): void
    {
        $job = InspectionJob::query()->find($event->jobId);

        if (! $job instanceof InspectionJob) {
            return;
        }

        $listing = $job->listing;
        $link = route('jobs.show', ['job' => $job->getKey()]);
        $body = __('notification.job_scheduled.body', ['tanggal' => Clock::formatWib($event->scheduledAt)]);

        if ($listing instanceof Listing) {
            $this->store->handle(
                userId: $listing->seller_id,
                type: NotificationType::JobScheduled,
                title: __('notification.job_scheduled.title'),
                body: $body,
                link: $link,
                dedupeKey: "job_scheduled:{$job->getKey()}:{$listing->seller_id}",
            );
        }

        $this->store->handle(
            userId: $job->requester_id,
            type: NotificationType::JobScheduled,
            title: __('notification.job_scheduled.title'),
            body: $body,
            link: $link,
            dedupeKey: "job_scheduled:{$job->getKey()}:{$job->requester_id}",
        );
    }

    private function onJobCancelled(JobCancelled $event): void
    {
        $job = InspectionJob::query()->find($event->jobId);

        if (! $job instanceof InspectionJob) {
            return;
        }

        $this->store->handle(
            userId: $job->requester_id,
            type: NotificationType::JobCancelled,
            title: __('notification.job_cancelled.title'),
            body: __('notification.job_cancelled.body'),
            link: route('jobs.show', ['job' => $job->getKey()]),
            dedupeKey: "job_cancelled:{$job->getKey()}:{$job->requester_id}",
        );
    }

    private function onJobCompleted(JobCompleted $event): void
    {
        $job = InspectionJob::query()->find($event->jobId);

        if (! $job instanceof InspectionJob) {
            return;
        }

        $requesterId = $event->requesterId ?? $job->requester_id;

        $this->store->handle(
            userId: $requesterId,
            type: NotificationType::JobCompleted,
            title: __('notification.job_completed.title'),
            body: __('notification.job_completed.body'),
            link: route('dashboard'),
            dedupeKey: "job_completed:{$job->getKey()}:{$requesterId}",
        );

        $inspectorId = $event->inspectorId ?? $job->inspector_id;

        if ($inspectorId !== null) {
            $this->store->handle(
                userId: $inspectorId,
                type: NotificationType::FeeReleased,
                title: __('notification.fee_released.title'),
                body: __('notification.fee_released.body', ['nominal' => Money::format($job->fee_amount)]),
                link: route('inspector.earnings'),
                dedupeKey: "fee_released:{$job->getKey()}:{$inspectorId}",
            );
        }
    }

    private function onResultSubmitted(ResultSubmitted $event): void
    {
        $job = InspectionJob::query()->find($event->jobId);

        if (! $job instanceof InspectionJob) {
            return;
        }

        $listing = $job->listing;
        $link = route('jobs.show', ['job' => $job->getKey()]);

        if ($listing instanceof Listing) {
            $this->store->handle(
                userId: $listing->seller_id,
                type: NotificationType::ResultSubmitted,
                title: __('notification.result_submitted.title'),
                body: __('notification.result_submitted.body'),
                link: $link,
                dedupeKey: "result_submitted:{$job->getKey()}:{$listing->seller_id}",
            );
        }

        $this->store->handle(
            userId: $job->requester_id,
            type: NotificationType::ResultSubmitted,
            title: __('notification.result_submitted.title'),
            body: __('notification.result_submitted.body'),
            link: $link,
            dedupeKey: "result_submitted:{$job->getKey()}:{$job->requester_id}",
        );
    }

    private function onListingSubmitted(ListingSubmitted $event): void
    {
        $adminIds = User::query()
            ->where('role', UserRole::Admin)
            ->where('status', UserStatus::Active)
            ->pluck('id');

        foreach ($adminIds as $adminId) {
            $this->store->handle(
                userId: (string) $adminId,
                type: NotificationType::ListingSubmitted,
                title: __('notification.listing_submitted.title'),
                body: __('notification.listing_submitted.body'),
                link: route('admin.listings.index'),
                dedupeKey: "listing_submitted:{$event->listingId}:{$adminId}",
            );
        }
    }

    private function onListingApproved(ListingApproved $event): void
    {
        $this->store->handle(
            userId: $event->sellerId,
            type: NotificationType::ListingApproved,
            title: __('notification.listing_approved.title'),
            body: __('notification.listing_approved.body'),
            link: route('listings.mine'),
            dedupeKey: "listing_approved:{$event->listingId}:{$event->sellerId}",
        );
    }

    private function onListingRejected(ListingRejected $event): void
    {
        $this->store->handle(
            userId: $event->sellerId,
            type: NotificationType::ListingRejected,
            title: __('notification.listing_rejected.title'),
            body: __('notification.listing_rejected.body', ['alasan' => $event->reason]),
            link: route('listings.mine'),
            dedupeKey: "listing_rejected:{$event->listingId}:{$event->sellerId}",
        );
    }

    private function onInspectorApproved(InspectorApproved $event): void
    {
        $this->store->handle(
            userId: $event->userId,
            type: NotificationType::InspectorApproved,
            title: __('notification.inspector_approved.title'),
            body: __('notification.inspector_approved.body'),
            link: route('identity.account'),
            dedupeKey: "inspector_approved:{$event->inspectorProfileId}:{$event->userId}",
        );
    }

    private function onInspectorRejected(InspectorRejected $event): void
    {
        $this->store->handle(
            userId: $event->userId,
            type: NotificationType::InspectorRejected,
            title: __('notification.inspector_rejected.title'),
            body: __('notification.inspector_rejected.body'),
            link: route('identity.account'),
            dedupeKey: "inspector_rejected:{$event->inspectorProfileId}:{$event->userId}",
        );
    }

    private function onInspectorSubmitted(InspectorSubmitted $event): void
    {
        $adminIds = User::query()
            ->where('role', UserRole::Admin)
            ->where('status', UserStatus::Active)
            ->pluck('id');

        foreach ($adminIds as $adminId) {
            $this->store->handle(
                userId: (string) $adminId,
                type: NotificationType::InspectorSubmitted,
                title: __('notification.inspector_submitted.title'),
                body: __('notification.inspector_submitted.body', ['nama' => $this->userName($event->userId)]),
                link: route('admin.inspectors.index'),
                dedupeKey: "inspector_submitted:{$event->inspectorProfileId}:{$adminId}",
            );
        }
    }

    private function transactionLink(string $transactionId): string
    {
        return route('transactions.show', ['transaction' => $transactionId]);
    }

    private function transactionTitle(string $listingId): string
    {
        $listing = Listing::query()->find($listingId);

        return $listing instanceof Listing ? $listing->title : 'kendaraan';
    }

    private function onTransactionOffered(TransactionOffered $event): void
    {
        $this->store->handle(
            userId: $event->sellerId,
            type: NotificationType::TransactionOffered,
            title: __('notification.transaction_offered.title'),
            body: __('notification.transaction_offered.body', [
                'nama' => $this->userName($event->buyerId),
                'judul' => $this->transactionTitle($event->listingId),
            ]),
            link: $this->transactionLink($event->transactionId),
            dedupeKey: "transaction_offered:{$event->transactionId}:{$event->sellerId}",
        );
    }

    private function onTransactionAccepted(TransactionAccepted $event): void
    {
        $this->store->handle(
            userId: $event->buyerId,
            type: NotificationType::TransactionAccepted,
            title: __('notification.transaction_accepted.title'),
            body: __('notification.transaction_accepted.body', ['judul' => $this->transactionTitle($event->listingId)]),
            link: $this->transactionLink($event->transactionId),
            dedupeKey: "transaction_accepted:{$event->transactionId}:{$event->buyerId}",
        );
    }

    private function onTransactionRejected(TransactionRejected $event): void
    {
        $this->store->handle(
            userId: $event->buyerId,
            type: NotificationType::TransactionRejected,
            title: __('notification.transaction_rejected.title'),
            body: __('notification.transaction_rejected.body', ['judul' => $this->transactionTitle($event->listingId)]),
            link: $this->transactionLink($event->transactionId),
            dedupeKey: "transaction_rejected:{$event->transactionId}:{$event->buyerId}",
        );
    }

    private function onTransactionPaid(TransactionPaid $event): void
    {
        $this->store->handle(
            userId: $event->sellerId,
            type: NotificationType::TransactionPaid,
            title: __('notification.transaction_paid.title'),
            body: __('notification.transaction_paid.body', ['judul' => $this->transactionTitle($event->listingId)]),
            link: $this->transactionLink($event->transactionId),
            dedupeKey: "transaction_paid:{$event->transactionId}:{$event->sellerId}",
        );
    }

    private function onTransactionCompleted(TransactionCompleted $event): void
    {
        $title = $this->transactionTitle($event->listingId);
        $link = $this->transactionLink($event->transactionId);

        $this->store->handle(
            userId: $event->sellerId,
            type: NotificationType::TransactionCompleted,
            title: __('notification.transaction_completed.title'),
            body: __('notification.transaction_completed.body_seller', ['judul' => $title]),
            link: $link,
            dedupeKey: "transaction_completed:{$event->transactionId}:{$event->sellerId}",
        );

        $this->store->handle(
            userId: $event->buyerId,
            type: NotificationType::TransactionCompleted,
            title: __('notification.transaction_completed.title'),
            body: __('notification.transaction_completed.body_buyer', ['judul' => $title]),
            link: $link,
            dedupeKey: "transaction_completed:{$event->transactionId}:{$event->buyerId}",
        );
    }

    private function onTransactionCancelled(TransactionCancelled $event): void
    {
        $title = $this->transactionTitle($event->listingId);
        $link = $this->transactionLink($event->transactionId);

        foreach ([$event->buyerId, $event->sellerId] as $recipientId) {
            if ($recipientId === $event->cancelledBy) {
                continue;
            }

            $this->store->handle(
                userId: $recipientId,
                type: NotificationType::TransactionCancelled,
                title: __('notification.transaction_cancelled.title'),
                body: __('notification.transaction_cancelled.body', ['judul' => $title]),
                link: $link,
                dedupeKey: "transaction_cancelled:{$event->transactionId}:{$recipientId}",
            );
        }
    }

    private function onTransactionExpired(TransactionExpired $event): void
    {
        $title = $this->transactionTitle($event->listingId);
        $link = $this->transactionLink($event->transactionId);

        foreach ([$event->buyerId, $event->sellerId] as $recipientId) {
            $this->store->handle(
                userId: $recipientId,
                type: NotificationType::TransactionExpired,
                title: __('notification.transaction_expired.title'),
                body: __('notification.transaction_expired.body', ['judul' => $title]),
                link: $link,
                dedupeKey: "transaction_expired:{$event->transactionId}:{$recipientId}",
            );
        }
    }

    private function onMessageSent(MessageSent $event): void
    {
        $message = $event->message;

        /** @var Conversation|null $conversation */
        $conversation = $message->conversation()->first();

        if (! $conversation instanceof Conversation) {
            return;
        }

        $senderName = $this->userName($message->sender_id);
        $link = route('conversations.show', ['conversation' => $conversation->getKey()]);

        $recipientIds = $conversation->users()
            ->where('users.id', '!=', $message->sender_id)
            ->pluck('users.id');

        foreach ($recipientIds as $recipientId) {
            $this->store->handleChatMessage(
                userId: (string) $recipientId,
                conversationId: $conversation->getKey(),
                senderName: $senderName,
                link: $link,
            );
        }
    }

    private function userName(string $userId): string
    {
        $user = User::query()->find($userId);

        return $user instanceof User ? $user->name : __('notification.unknown_user');
    }
}
