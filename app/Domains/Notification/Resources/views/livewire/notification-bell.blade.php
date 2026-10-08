<div @auth wire:poll.15s @endauth
    x-data="{
        init() {
            if (!window.Echo) return;
            window.Echo.private('user.{{ auth()->id() }}')
                .listen('.notification.created', () => { this.$wire.$refresh(); });
        }
    }">
    @auth
    <a href="{{ route('notifications.index') }}"
       class="relative inline-flex items-center justify-center w-11 h-11 rounded-full text-ds-muted hover:text-ds-blue hover:bg-ds-canvas transition-colors"
       aria-label="{{ __('notification.bell.label') }}{{ $unreadCount > 0 ? ': '.__('notification.bell.unread', ['count' => $unreadCount]) : '' }}">
        <i aria-hidden="true" class="fa-regular fa-bell text-lg"></i>
        @if ($unreadCount > 0)
            <span class="absolute top-1 right-1 inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-ds-danger text-white text-xs font-bold"
                  aria-hidden="true">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </a>
    @endauth
</div>
