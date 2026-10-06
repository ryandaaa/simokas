<?php

declare(strict_types=1);

use App\Domains\Notification\Livewire\NotificationIndex;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'not_blocked'])->group(function (): void {
    Route::get('/notifications', NotificationIndex::class)->name('notifications.index');
});
