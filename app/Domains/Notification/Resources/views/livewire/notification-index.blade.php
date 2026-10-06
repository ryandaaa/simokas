<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-text">{{ __('notification.index.heading') }}</h1>
        @if ($unreadCount > 0)
            <x-ui.button variant="secondary" wire:click="markAllAsRead" wire:loading.attr="disabled">
                {{ __('notification.index.mark_all_read') }}
            </x-ui.button>
        @endif
    </div>

    @if ($notifications->isEmpty())
        <x-ui.empty-state
            icon="lonceng"
            title="{{ __('notification.index.empty_title') }}"
            description="{{ __('notification.index.empty_description') }}" />
    @else
        <ul class="space-y-3" aria-live="polite">
            @foreach ($notifications as $notification)
                <li>
                    <button type="button"
                            wire:click="openNotification('{{ $notification->id }}')"
                            wire:loading.attr="disabled"
                            class="w-full text-left bg-surface border rounded-xl p-4 flex items-start gap-3 transition-colors hover:border-primary/40 focus:outline-none focus:ring-2 focus:ring-accent {{ $notification->isRead() ? 'border-muted/20' : 'border-primary/30' }}">
                        <span class="mt-0.5 shrink-0">
                            @if ($notification->isRead())
                                <x-ui.icon name="lonceng" class="w-5 h-5 text-muted" />
                            @else
                                <span class="block w-2.5 h-2.5 mt-1.5 rounded-full bg-accent" aria-hidden="true"></span>
                            @endif
                        </span>
                        <span class="flex-1 min-w-0">
                            <span class="block font-semibold text-text text-sm">{{ $notification->title }}</span>
                            <span class="block text-muted text-sm mt-0.5">{{ $notification->body }}</span>
                            <span class="block text-muted text-xs mt-1.5">
                                <x-ui.datetime :value="$notification->created_at" />
                            </span>
                        </span>
                    </button>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
