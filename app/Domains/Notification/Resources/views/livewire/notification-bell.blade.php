<div @auth wire:poll.15s @endauth>
    @auth
    <a href="{{ route('notifications.index') }}"
       class="relative inline-flex items-center justify-center w-11 h-11 rounded-lg text-muted hover:text-text hover:bg-muted/10 focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
       aria-label="{{ __('notification.bell.label') }}{{ $unreadCount > 0 ? ': '.__('notification.bell.unread', ['count' => $unreadCount]) : '' }}">
        <x-ui.icon name="lonceng" class="w-6 h-6" />
        @if ($unreadCount > 0)
            <span class="absolute top-1 right-1 inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-danger text-surface text-xs font-bold"
                  aria-hidden="true">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </a>
    @endauth
</div>
