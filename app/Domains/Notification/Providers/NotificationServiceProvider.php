<?php

declare(strict_types=1);

namespace App\Domains\Notification\Providers;

use App\Domains\Chat\Events\MessageSent;
use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Inspection\Events\JobCompleted;
use App\Domains\Inspection\Events\JobScheduled;
use App\Domains\Inspection\Events\JobTaken;
use App\Domains\Inspector\Events\InspectorApproved;
use App\Domains\Inspector\Events\InspectorRejected;
use App\Domains\Inspector\Events\InspectorSubmitted;
use App\Domains\Listing\Events\ListingApproved;
use App\Domains\Listing\Events\ListingRejected;
use App\Domains\Listing\Events\ListingSubmitted;
use App\Domains\Notification\Listeners\StoreNotificationOnEvent;
use App\Domains\Notification\Livewire\NotificationBell;
use App\Domains\Notification\Livewire\NotificationIndex;
use App\Domains\Notification\Models\Notification;
use App\Domains\Notification\Policies\NotificationPolicy;
use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Result\Events\ResultSubmitted;
use App\Domains\Transaction\Events\TransactionAccepted;
use App\Domains\Transaction\Events\TransactionCancelled;
use App\Domains\Transaction\Events\TransactionCompleted;
use App\Domains\Transaction\Events\TransactionExpired;
use App\Domains\Transaction\Events\TransactionOffered;
use App\Domains\Transaction\Events\TransactionPaid;
use App\Domains\Transaction\Events\TransactionRejected;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'notification');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        Gate::policy(Notification::class, NotificationPolicy::class);

        Event::listen(PaymentSucceeded::class, StoreNotificationOnEvent::class);
        Event::listen(JobTaken::class, StoreNotificationOnEvent::class);
        Event::listen(JobScheduled::class, StoreNotificationOnEvent::class);
        Event::listen(JobCancelled::class, StoreNotificationOnEvent::class);
        Event::listen(JobCompleted::class, StoreNotificationOnEvent::class);
        Event::listen(ResultSubmitted::class, StoreNotificationOnEvent::class);
        Event::listen(ListingSubmitted::class, StoreNotificationOnEvent::class);
        Event::listen(ListingApproved::class, StoreNotificationOnEvent::class);
        Event::listen(ListingRejected::class, StoreNotificationOnEvent::class);
        Event::listen(InspectorApproved::class, StoreNotificationOnEvent::class);
        Event::listen(InspectorRejected::class, StoreNotificationOnEvent::class);
        Event::listen(InspectorSubmitted::class, StoreNotificationOnEvent::class);
        Event::listen(MessageSent::class, StoreNotificationOnEvent::class);
        Event::listen(TransactionOffered::class, StoreNotificationOnEvent::class);
        Event::listen(TransactionAccepted::class, StoreNotificationOnEvent::class);
        Event::listen(TransactionRejected::class, StoreNotificationOnEvent::class);
        Event::listen(TransactionPaid::class, StoreNotificationOnEvent::class);
        Event::listen(TransactionCompleted::class, StoreNotificationOnEvent::class);
        Event::listen(TransactionCancelled::class, StoreNotificationOnEvent::class);
        Event::listen(TransactionExpired::class, StoreNotificationOnEvent::class);

        Livewire::component('notification.bell', NotificationBell::class);
        Livewire::component('notification.index-page', NotificationIndex::class);
    }
}
