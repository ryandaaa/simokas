<div class="ds-sans max-w-3xl mx-auto space-y-6 pb-12">
    <div class="flex items-center justify-between">
        <div class="space-y-2">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('notification.index.heading') }}</span>
            <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('notification.index.heading') }}</h1>
        </div>
        @if ($unreadCount > 0)
            <button type="button" wire:click="markAllAsRead" wire:loading.attr="disabled"
                class="shrink-0 inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs disabled:opacity-60">
                {{ __('notification.index.mark_all_read') }}
            </button>
        @endif
    </div>

    @if ($notifications->isEmpty())
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                <i aria-hidden="true" class="fa-solid fa-bell"></i>
            </div>
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('notification.index.empty_title') }}</h3>
            <p class="text-xs text-ds-muted mt-2">{{ __('notification.index.empty_description') }}</p>
        </div>
    @else
        <ul class="space-y-3" aria-live="polite">
            @foreach ($notifications as $notification)
                <li>
                    <button type="button"
                            wire:click="openNotification('{{ $notification->id }}')"
                            wire:loading.attr="disabled"
                            class="w-full text-left bg-white border rounded-2xl p-4 flex items-start gap-3 transition-all shadow-xs hover:shadow-md focus:outline-none focus:ring-2 focus:ring-ds-blue {{ $notification->isRead() ? 'border-ds-line/80' : 'border-ds-blue/40' }}">
                        <span class="mt-0.5 shrink-0">
                            @if ($notification->isRead())
                                <i aria-hidden="true" class="fa-regular fa-bell text-ds-faint text-lg"></i>
                            @else
                                <span class="block w-2.5 h-2.5 mt-1.5 rounded-full bg-ds-blue" aria-hidden="true"></span>
                            @endif
                        </span>
                        <span class="flex-1 min-w-0">
                            <span class="block font-extrabold text-ds-ink text-sm">{{ $notification->title }}</span>
                            <span class="block text-ds-muted text-sm mt-0.5">{{ $notification->body }}</span>
                            <span class="block text-ds-faint text-xs mt-1.5">
                                {{ $notification->created_at?->translatedFormat('d M Y H:i') }}
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
