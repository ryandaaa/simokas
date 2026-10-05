<?php

declare(strict_types=1);

use App\Domains\Admin\Providers\AdminServiceProvider;
use App\Domains\Catalog\Providers\CatalogServiceProvider;
use App\Domains\Chat\Providers\ChatServiceProvider;
use App\Domains\Dashboard\Providers\DashboardServiceProvider;
use App\Domains\Identity\Providers\IdentityServiceProvider;
use App\Domains\Inspection\Providers\InspectionServiceProvider;
use App\Domains\Inspector\Providers\InspectorServiceProvider;
use App\Domains\Listing\Providers\ListingServiceProvider;
use App\Domains\Notification\Providers\NotificationServiceProvider;
use App\Domains\Payment\Providers\PaymentServiceProvider;
use App\Domains\Rating\Providers\RatingServiceProvider;
use App\Domains\Result\Providers\ResultServiceProvider;
use App\Domains\Setting\Providers\SettingServiceProvider;
use App\Domains\Transaction\Providers\TransactionServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    InspectorServiceProvider::class,
    ListingServiceProvider::class,
    CatalogServiceProvider::class,
    InspectionServiceProvider::class,
    ResultServiceProvider::class,
    PaymentServiceProvider::class,
    TransactionServiceProvider::class,
    ChatServiceProvider::class,
    RatingServiceProvider::class,
    NotificationServiceProvider::class,
    SettingServiceProvider::class,
    AdminServiceProvider::class,
    DashboardServiceProvider::class,
];
