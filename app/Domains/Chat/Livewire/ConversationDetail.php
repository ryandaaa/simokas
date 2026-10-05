<?php

declare(strict_types=1);

namespace App\Domains\Chat\Livewire;

use App\Domains\Chat\Actions\MarkConversationAsRead;
use App\Domains\Chat\Actions\SendMessage;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Identity\Models\User;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

final class ConversationDetail extends Component
{
    public string $conversationId = '';

    public string $newMessage = '';

    public string $clientKey = '';

    public bool $sendFailed = false;

    public ?string $errorMessage = null;

    public function mount(Conversation $conversation, MarkConversationAsRead $markAsRead): void
    {
        $this->conversationId = $conversation->id;

        $this->authorize('view', $conversation);

        /** @var User $user */
        $user = Auth::user();
        $markAsRead->handle($user, $conversation);

        $this->clientKey = (string) Str::uuid();
    }

    public function pollNewMessages(MarkConversationAsRead $markAsRead): void
    {
        /** @var Conversation|null $conversation */
        $conversation = Conversation::query()->find($this->conversationId);

        if (! $conversation) {
            return;
        }

        $this->authorize('view', $conversation);

        /** @var User $user */
        $user = Auth::user();
        $markAsRead->handle($user, $conversation);
    }

    public function send(SendMessage $sendMessage): void
    {
        $this->errorMessage = null;
        $this->sendFailed = false;

        $this->validate(
            [
                'newMessage' => ['required', 'string', 'max:1000'],
            ],
            [
                'newMessage.required' => __('chat.errors.message_empty'),
                'newMessage.max' => __('chat.errors.message_too_long'),
            ]
        );

        /** @var Conversation $conversation */
        $conversation = Conversation::query()->findOrFail($this->conversationId);

        $this->authorize('sendMessage', $conversation);

        /** @var User $user */
        $user = Auth::user();

        try {
            $sendMessage->handle(
                sender: $user,
                conversation: $conversation,
                body: $this->newMessage,
                clientKey: $this->clientKey !== '' ? $this->clientKey : null,
            );

            $this->newMessage = '';
            $this->clientKey = (string) Str::uuid();
            $this->sendFailed = false;
        } catch (DomainException $e) {
            $this->sendFailed = true;
            $this->errorMessage = $e->getMessage();
        }
    }

    public function retry(SendMessage $sendMessage): void
    {
        if ($this->sendFailed) {
            $this->send($sendMessage);
        }
    }

    public function render(): View
    {
        /** @var Conversation $conversation */
        $conversation = Conversation::query()
            ->with(['messages.sender', 'members.user', 'users'])
            ->findOrFail($this->conversationId);

        /** @var User $user */
        $user = Auth::user();
        $otherMember = $conversation->getOtherMember($user);
        $context = $conversation->contextSummary();

        return view('chat::livewire.conversation-detail', [
            'conversation' => $conversation,
            'messages' => $conversation->messages()->orderBy('created_at', 'asc')->get(),
            'currentUser' => $user,
            'otherMember' => $otherMember,
            'context' => $context,
        ])->layout('components.layouts.app', [
            'title' => $otherMember ? 'Chat: '.$otherMember->name : __('chat.title'),
        ]);
    }
}
