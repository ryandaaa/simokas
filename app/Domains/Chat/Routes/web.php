<?php

declare(strict_types=1);

use App\Domains\Chat\Http\Controllers\StartConversationController;
use App\Domains\Chat\Livewire\ConversationDetail;
use App\Domains\Chat\Livewire\ConversationList;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'not_blocked'])->group(function (): void {
        Route::get('/conversations', ConversationList::class)->name('conversations.index');
        Route::get('/conversations/{conversation}', ConversationDetail::class)->name('conversations.show');
        Route::post('/conversations/start', StartConversationController::class)->name('conversations.start');
    });
});
