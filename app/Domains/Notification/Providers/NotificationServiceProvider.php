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
        Event::listen(MessageSent::class, StoreNotificationOnEvent::class);

        Livewire::component('notification.bell', NotificationBell::class);
        Livewire::component('notification.index-page', NotificationIndex::class);
    }
}
