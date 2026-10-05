<?php

declare(strict_types=1);

namespace App\Domains\Chat\Livewire;

use App\Domains\Chat\Models\Conversation;
use App\Domains\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

final class ConversationList extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $query = Conversation::query()
            ->whereHas('members', fn (Builder $q) => $q->where('user_id', $user->id))
            ->with(['members.user', 'users', 'latestMessage.sender'])
            ->withCount(['messages as latest_message_at' => function (Builder $q) {
                $q->selectRaw('max(created_at)');
            }])
            ->orderByDesc('latest_message_at')
            ->orderByDesc('updated_at');

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->whereHas('users', function (Builder $q) use ($term, $user) {
                $q->where('users.id', '!=', $user->id)
                    ->where('users.name', 'like', $term);
            });
        }

        $conversations = $query->paginate(15);

        return view('chat::livewire.conversation-list', [
            'conversations' => $conversations,
            'currentUser' => $user,
        ])->layout('components.layouts.app', ['title' => __('chat.title')]);
    }
}
