<div class="ds-sans max-w-4xl mx-auto px-4 py-6 space-y-6 pb-12">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="space-y-2">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('chat.title') }}</span>
            <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">
                {{ __('chat.title') }}
            </h1>
            <p class="text-sm text-ds-muted">
                {{ __('chat.conversations') }}
            </p>
        </div>

        <div class="w-full sm:w-72">
            <div class="relative">
                <i aria-hidden="true" class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-ds-faint text-sm"></i>
                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="{{ __('chat.search_conversations') }}"
                    aria-label="{{ __('chat.search_conversations') }}"
                    class="w-full h-11 pl-11 pr-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"
                />
            </div>
        </div>
    </div>

    {{-- Loading indicator --}}
    <div wire:loading.delay class="w-full py-4">
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6 flex items-center justify-center gap-3 text-ds-muted">
            <i aria-hidden="true" class="fa-solid fa-circle-notch fa-spin"></i>
            <span class="text-sm font-medium">{{ __('ui.actions.loading') }}</span>
        </div>
    </div>

    {{-- Conversations list --}}
    <div wire:loading.remove>
        @if ($conversations->isEmpty())
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                    <i aria-hidden="true" class="fa-solid fa-comments"></i>
                </div>
                <h3 class="text-lg font-extrabold text-ds-ink">{{ __('chat.empty_conversations') }}</h3>
                <p class="text-xs text-ds-muted mt-2">{{ __('chat.empty_conversations_desc') }}</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($conversations as $conversation)
                    @php
                        $other = $conversation->getOtherMember($currentUser);
                        $unread = $conversation->unreadCountFor($currentUser);
                        $context = $conversation->contextSummary();
                        $latest = $conversation->latestMessage;
                    @endphp
                    <a
                        href="{{ route('conversations.show', $conversation) }}"
                        class="block bg-white rounded-2xl border border-ds-line/80 p-4 sm:p-5 shadow-xs hover:shadow-md hover:border-ds-blue/40 transition-all"
                    >
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-ds-blue-soft text-ds-blue-ink flex items-center justify-center font-extrabold shrink-0">
                                @if ($other)
                                    {{ mb_strtoupper(mb_substr($other->name, 0, 1)) }}
                                @else
                                    <i aria-hidden="true" class="fa-solid fa-user text-lg"></i>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="text-base font-extrabold text-ds-ink truncate">
                                        {{ $other?->name ?? __('chat.participant') }}
                                    </h3>
                                    @if ($latest)
                                        <span class="text-xs text-ds-faint shrink-0">
                                            {{ $latest->created_at?->translatedFormat('d M Y H:i') }}
                                        </span>
                                    @endif
                                </div>

                                <div class="text-xs font-semibold text-ds-muted flex items-center gap-2 mt-0.5">
                                    <span class="truncate">{{ $context['title'] }}</span>
                                    @if ($context['price'] !== null)
                                        <span>&bull;</span>
                                        <span class="font-extrabold text-ds-ink">{{ \App\Support\Money::format($context['price']) }}</span>
                                    @endif
                                </div>

                                <div class="flex items-center justify-between gap-2 mt-2">
                                    <p class="text-sm text-ds-muted truncate">
                                        @if ($latest)
                                            @if ($latest->sender_id === $currentUser->id)
                                                <span class="font-bold text-ds-ink">{{ __('chat.you') }}:</span>
                                            @endif
                                            {{ $latest->body }}
                                        @else
                                            <span class="italic text-ds-faint">{{ __('chat.empty_messages') }}</span>
                                        @endif
                                    </p>

                                    @if ($unread > 0)
                                        <span class="shrink-0 inline-flex items-center justify-center min-w-5 h-5 px-1.5 text-[11px] font-extrabold text-white bg-ds-blue rounded-full">
                                            {{ $unread }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">
                <x-ui.pagination :paginator="$conversations" />
            </div>
        @endif
    </div>
</div>
