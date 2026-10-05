<?php

declare(strict_types=1);

namespace App\Domains\Chat\Providers;

use App\Domains\Chat\Listeners\CreateJobConversation;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Policies\ConversationPolicy;
use App\Domains\Inspection\Events\JobTaken;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class ChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'chat');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        Gate::policy(Conversation::class, ConversationPolicy::class);
        Broadcast::channel('conversation.{conversationId}', [ConversationPolicy::class, 'viewChannel']);

        Event::listen(JobTaken::class, CreateJobConversation::class);
    }
}
