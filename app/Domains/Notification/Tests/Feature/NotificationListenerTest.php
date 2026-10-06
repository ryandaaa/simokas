<?php

declare(strict_types=1);

namespace App\Domains\Notification\Tests\Feature;

use App\Domains\Chat\Events\MessageSent;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Inspection\Events\JobCompleted;
use App\Domains\Inspection\Events\JobScheduled;
use App\Domains\Inspection\Events\JobTaken;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Events\InspectorApproved;
use App\Domains\Inspector\Events\InspectorRejected;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Events\ListingApproved;
use App\Domains\Listing\Events\ListingRejected;
use App\Domains\Listing\Events\ListingSubmitted;
use App\Domains\Listing\Models\Listing;
use App\Domains\Notification\Enums\NotificationType;
use App\Domains\Notification\Models\Notification;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Result\Events\ResultSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeJobWithSellerAndRequester(): array
{
    $seller = User::factory()->user()->create();
    $requester = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->getKey(),
        'requester_id' => $requester->getKey(),
    ]);

    return [$job, $seller, $requester, $listing];
}

it('NTF-T01 notifies the payer when a payment succeeds', function (): void {
    $payer = User::factory()->user()->create();

    PaymentSucceeded::dispatch(
        paymentId: 'pay-1',
        purpose: PaymentPurpose::Access,
        refId: 'ref-1',
        amount: 50000,
        payerId: $payer->getKey(),
    );

    $notification = Notification::query()->firstOrFail();

    expect($notification->user_id)->toBe($payer->getKey())
        ->and($notification->type)->toBe(NotificationType::PaymentSucceeded)
        ->and($notification->link)->toContain('pay-1');
});

it('NTF-T01 notifies matching inspectors when an inspection payment succeeds', function (): void {
    [$job, $seller, $requester, $listing] = makeJobWithSellerAndRequester();

    $carInspector = User::factory()->inspector()->active()->create();
    InspectorProfile::factory()->approved()->for($carInspector, 'user')->create([
        'vehicle_types' => [VehicleType::Car->value],
    ]);

    $motorInspector = User::factory()->inspector()->active()->create();
    InspectorProfile::factory()->approved()->for($motorInspector, 'user')->create([
        'vehicle_types' => [VehicleType::Motorcycle->value],
    ]);

    $payer = User::factory()->user()->create();

    PaymentSucceeded::dispatch(
        paymentId: 'pay-2',
        purpose: PaymentPurpose::Inspection,
        refId: $job->getKey(),
        amount: 150000,
        payerId: $payer->getKey(),
    );

    expect(Notification::query()->where('type', NotificationType::JobAvailable)->count())->toBe(1);

    $forInspector = Notification::query()
        ->where('type', NotificationType::JobAvailable)
        ->firstOrFail();

    expect($forInspector->user_id)->toBe($carInspector->getKey())
        ->and($forInspector->link)->toContain($job->getKey());
});

it('NTF-T01 notifies the seller and the requester when a job is taken', function (): void {
    [$job, $seller, $requester] = makeJobWithSellerAndRequester();
    $inspector = User::factory()->inspector()->active()->create();

    JobTaken::dispatch(
        jobId: $job->getKey(),
        listingId: $job->listing_id,
        inspectorId: $inspector->getKey(),
    );

    $recipients = Notification::query()->where('type', NotificationType::JobTaken)
        ->pluck('user_id')->all();

    sort($recipients);
    $expected = [$seller->getKey(), $requester->getKey()];
    sort($expected);

    expect($recipients)->toBe($expected);
});

it('NTF-T01 notifies the seller and the requester when a job is scheduled', function (): void {
    [$job, $seller, $requester] = makeJobWithSellerAndRequester();

    JobScheduled::dispatch(
        jobId: $job->getKey(),
        listingId: $job->listing_id,
        inspectorId: User::factory()->inspector()->create()->getKey(),
        scheduledAt: now()->addDay()->toDateTimeString(),
    );

    $recipients = Notification::query()->where('type', NotificationType::JobScheduled)
        ->pluck('user_id')->all();

    sort($recipients);
    $expected = [$seller->getKey(), $requester->getKey()];
    sort($expected);

    expect($recipients)->toBe($expected);
});

it('NTF-T01 notifies the requester when a job is cancelled', function (): void {
    [$job, $seller, $requester] = makeJobWithSellerAndRequester();

    JobCancelled::dispatch(jobId: $job->getKey(), listingId: $job->listing_id);

    $notification = Notification::query()->firstOrFail();

    expect(Notification::query()->count())->toBe(1)
        ->and($notification->user_id)->toBe($requester->getKey())
        ->and($notification->type)->toBe(NotificationType::JobCancelled);
});

it('NTF-T01 notifies the requester and the inspector when a job is completed', function (): void {
    [$job, $seller, $requester] = makeJobWithSellerAndRequester();
    $inspector = User::factory()->inspector()->active()->create();
    $job->update(['inspector_id' => $inspector->getKey()]);

    JobCompleted::dispatch(
        jobId: $job->getKey(),
        listingId: $job->listing_id,
        inspectorId: $inspector->getKey(),
        requesterId: $requester->getKey(),
    );

    $types = Notification::query()->pluck('type', 'user_id')->all();

    expect($types[$requester->getKey()])->toBe(NotificationType::JobCompleted)
        ->and($types[$inspector->getKey()])->toBe(NotificationType::FeeReleased);
});

it('NTF-T01 notifies the seller and the requester when a result is submitted', function (): void {
    [$job, $seller, $requester] = makeJobWithSellerAndRequester();

    ResultSubmitted::dispatch(
        resultId: 'res-1',
        jobId: $job->getKey(),
        inspectorId: User::factory()->inspector()->create()->getKey(),
        listingId: $job->listing_id,
    );

    $recipients = Notification::query()->where('type', NotificationType::ResultSubmitted)
        ->pluck('user_id')->all();

    sort($recipients);
    $expected = [$seller->getKey(), $requester->getKey()];
    sort($expected);

    expect($recipients)->toBe($expected);
});

it('NTF-T01 notifies admins when a listing is submitted', function (): void {
    $adminOne = User::factory()->admin()->active()->create();
    $adminTwo = User::factory()->admin()->active()->create();
    User::factory()->user()->create();
    $listing = Listing::factory()->create();

    ListingSubmitted::dispatch(listingId: $listing->getKey(), sellerId: $listing->seller_id);

    $recipients = Notification::query()->where('type', NotificationType::ListingSubmitted)
        ->pluck('user_id')->all();

    sort($recipients);
    $expected = [$adminOne->getKey(), $adminTwo->getKey()];
    sort($expected);

    expect($recipients)->toBe($expected);
});

it('NTF-T01 notifies the seller when a listing is approved or rejected', function (): void {
    $seller = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();
    $approved = Listing::factory()->create(['seller_id' => $seller->id]);
    $rejected = Listing::factory()->create(['seller_id' => $seller->id]);

    ListingApproved::dispatch(listingId: $approved->getKey(), sellerId: $seller->getKey(), approvedBy: $admin->getKey());
    ListingRejected::dispatch(listingId: $rejected->getKey(), sellerId: $seller->getKey(), rejectedBy: $admin->getKey(), reason: 'Foto kurang jelas');

    expect(Notification::query()->where('type', NotificationType::ListingApproved)->count())->toBe(1)
        ->and(Notification::query()->where('type', NotificationType::ListingRejected)->count())->toBe(1)
        ->and(Notification::query()->where('type', NotificationType::ListingRejected)->firstOrFail()->body)->toContain('Foto kurang jelas');
});

it('NTF-T01 notifies the inspector when verification is approved or rejected', function (): void {
    $user = User::factory()->inspector()->create();
    $admin = User::factory()->admin()->create();

    InspectorApproved::dispatch(inspectorProfileId: 'prof-1', userId: $user->getKey(), verifiedBy: $admin->getKey());
    InspectorRejected::dispatch(inspectorProfileId: 'prof-1', userId: $user->getKey(), rejectedBy: $admin->getKey(), reason: 'Dokumen kurang');

    expect(Notification::query()->where('type', NotificationType::InspectorApproved)->count())->toBe(1)
        ->and(Notification::query()->where('type', NotificationType::InspectorRejected)->count())->toBe(1);
});

it('NTF-T01 notifies conversation members except the sender on a new message', function (): void {
    $sender = User::factory()->user()->create();
    $recipient = User::factory()->user()->create();

    /** @var Conversation $conversation */
    $conversation = Conversation::factory()->withMembers($sender, $recipient)->create();

    /** @var Message $message */
    $message = Message::factory()->for($conversation, 'conversation')->create(['sender_id' => $sender->getKey()]);

    MessageSent::dispatch($message);

    expect(Notification::query()->count())->toBe(1);

    $notification = Notification::query()->firstOrFail();

    expect($notification->user_id)->toBe($recipient->getKey())
        ->and($notification->type)->toBe(NotificationType::MessageReceived);
});

it('NTF-T02 does not create duplicate notifications when an event is dispatched twice', function (): void {
    [$job, $seller, $requester] = makeJobWithSellerAndRequester();
    $inspector = User::factory()->inspector()->active()->create();

    JobTaken::dispatch(jobId: $job->getKey(), listingId: $job->listing_id, inspectorId: $inspector->getKey());
    JobTaken::dispatch(jobId: $job->getKey(), listingId: $job->listing_id, inspectorId: $inspector->getKey());

    expect(Notification::query()->count())->toBe(2);
});

it('NTF-T07 a failing listener does not fail the main action', function (): void {
    [$job] = makeJobWithSellerAndRequester();

    DB::transaction(function () use ($job): void {
        $job->update(['scheduled_at' => now()]);

        // scheduledAt yang tidak valid membuat listener gagal di dalamnya;
        // kegagalan harus ditangkap listener, bukan menggagalkan aksi utama.
        JobScheduled::dispatch(
            jobId: $job->getKey(),
            listingId: $job->listing_id,
            inspectorId: User::factory()->inspector()->create()->getKey(),
            scheduledAt: 'bukan-tanggal-yang-valid',
        );
    });

    expect($job->refresh()->scheduled_at)->not->toBeNull()
        ->and(Notification::query()->count())->toBe(0);
});

it('NTF-T08 notifications never contain precise listing data', function (): void {
    [$job, $seller, $requester, $listing] = makeJobWithSellerAndRequester();
    $inspector = User::factory()->inspector()->active()->create();

    JobTaken::dispatch(jobId: $job->getKey(), listingId: $job->listing_id, inspectorId: $inspector->getKey());

    $forbidden = [
        $listing->chassis_number,
        $listing->address_full,
        (string) $listing->lat,
        (string) $listing->lng,
    ];

    foreach (Notification::query()->get() as $notification) {
        foreach ($forbidden as $value) {
            expect($notification->title.$notification->body)->not->toContain((string) $value);
        }
    }
});

it('NTF-T09 blocked users do not receive notifications', function (): void {
    $blockedSeller = User::factory()->user()->blocked()->create();
    $requester = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $blockedSeller->id]);
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->getKey(),
        'requester_id' => $requester->getKey(),
    ]);
    $inspector = User::factory()->inspector()->active()->create();

    JobTaken::dispatch(jobId: $job->getKey(), listingId: $job->listing_id, inspectorId: $inspector->getKey());

    expect(Notification::query()->count())->toBe(1)
        ->and(Notification::query()->firstOrFail()->user_id)->toBe($requester->getKey());
});

it('NTF-T10 consecutive chat messages are grouped into one notification', function (): void {
    $sender = User::factory()->user()->create();
    $recipient = User::factory()->user()->create();

    /** @var Conversation $conversation */
    $conversation = Conversation::factory()->withMembers($sender, $recipient)->create();

    /** @var Message $first */
    $first = Message::factory()->for($conversation, 'conversation')->create(['sender_id' => $sender->getKey()]);
    MessageSent::dispatch($first);

    /** @var Message $second */
    $second = Message::factory()->for($conversation, 'conversation')->create(['sender_id' => $sender->getKey()]);
    MessageSent::dispatch($second);

    expect(Notification::query()->count())->toBe(1);

    $notification = Notification::query()->firstOrFail();

    expect($notification->body)->toContain('2')
        ->and($notification->data['count'])->toBe(2);
});
