<div class="max-w-4xl mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[var(--color-text)]">
                {{ __('chat.title') }}
            </h1>
            <p class="text-sm text-[var(--color-muted)] mt-1">
                {{ __('chat.conversations') }}
            </p>
        </div>

        <div class="w-full sm:w-72">
            <x-ui.input
                wire:model.live.debounce.300ms="search"
                type="search"
                :placeholder="__('chat.search_conversations')"
                icon="search"
            />
        </div>
    </div>

    {{-- Loading indicator --}}
    <div wire:loading.delay class="w-full py-4 text-center">
        <x-ui.skeleton variant="card" />
    </div>

    {{-- Conversations list --}}
    <div wire:loading.remove>
        @if ($conversations->isEmpty())
            <x-ui.empty-state
                icon="document-text"
                :title="__('chat.empty_conversations')"
                :description="__('chat.empty_conversations_desc')"
            />
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
                        class="block bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-[var(--color-primary)] transition shadow-sm hover:shadow"
                    >
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-[var(--color-primary)] font-bold shrink-0">
                                @if ($other)
                                    {{ mb_strtoupper(mb_substr($other->name, 0, 1)) }}
                                @else
                                    <x-ui.icon name="user" class="w-6 h-6" />
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="text-base font-semibold text-[var(--color-text)] truncate">
                                        {{ $other?->name ?? __('chat.participant') }}
                                    </h3>
                                    @if ($latest)
                                        <span class="text-xs text-[var(--color-muted)] shrink-0">
                                            <x-ui.datetime :value="$latest->created_at" format="relative" />
                                        </span>
                                    @endif
                                </div>

                                <div class="text-xs font-medium text-[var(--color-muted)] flex items-center gap-2 mt-0.5">
                                    <span class="truncate">{{ $context['title'] }}</span>
                                    @if ($context['price'] !== null)
                                        <span>&bull;</span>
                                        <x-ui.money :amount="$context['price']" />
                                    @endif
                                </div>

                                <div class="flex items-center justify-between gap-2 mt-2">
                                    <p class="text-sm text-[var(--color-muted)] truncate">
                                        @if ($latest)
                                            @if ($latest->sender_id === $currentUser->id)
                                                <span class="font-medium text-[var(--color-text)]">{{ __('chat.you') }}:</span>
                                            @endif
                                            {{ $latest->body }}
                                        @else
                                            <span class="italic text-slate-400">{{ __('chat.empty_messages') }}</span>
                                        @endif
                                    </p>

                                    @if ($unread > 0)
                                        <span class="shrink-0 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-[var(--color-primary)] rounded-full">
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
