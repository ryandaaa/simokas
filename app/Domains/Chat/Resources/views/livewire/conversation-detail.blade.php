<div
    wire:poll.3000ms="pollNewMessages"
    class="max-w-4xl mx-auto px-4 py-4 flex flex-col h-[calc(100vh-6rem)] sm:h-[calc(100vh-7rem)]"
>
    {{-- Header --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 mb-3 shrink-0 shadow-sm">
        <div class="flex items-center gap-3">
            <x-ui.button
                :href="route('conversations.index')"
                variant="ghost"
                icon="chevron-left"
                class="min-h-[44px] min-w-[44px] p-2"
                :title="__('chat.back_to_conversations')"
            >
                <span class="sr-only">{{ __('chat.back_to_conversations') }}</span>
            </x-ui.button>

            <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-[var(--color-primary)] font-bold shrink-0">
                @if ($otherMember)
                    {{ mb_strtoupper(mb_substr($otherMember->name, 0, 1)) }}
                @else
                    <x-ui.icon name="user" class="w-5 h-5" />
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <h1 class="text-base font-semibold text-[var(--color-text)] truncate">
                    {{ $otherMember?->name ?? __('chat.participant') }}
                </h1>
                <div class="text-xs text-[var(--color-muted)] flex items-center gap-2 mt-0.5">
                    <span class="truncate">{{ $context['title'] }}</span>
                    @if ($context['price'] !== null)
                        <span>&bull;</span>
                        <x-ui.money :amount="$context['price']" />
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Error Alert --}}
    @if ($sendFailed)
        <div class="mb-3 shrink-0">
            <x-ui.alert
                variant="danger"
                :title="__('chat.failed_to_send')"
                :message="$errorMessage"
                :retry-label="__('chat.retry')"
                retry-action="retry"
            />
        </div>
    @endif

    {{-- Messages Area --}}
    <div
        id="chat-messages-container"
        class="flex-1 overflow-y-auto space-y-3 p-4 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800"
    >
        @if ($messages->isEmpty())
            <div class="py-12">
                <x-ui.empty-state
                    icon="document-text"
                    :title="__('chat.empty_messages')"
                    :description="__('chat.empty_messages_desc')"
                />
            </div>
        @else
            @foreach ($messages as $message)
                @php
                    $isMine = $message->sender_id === $currentUser->id;
                @endphp
                <div class="flex flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
                    <div
                        class="max-w-[85%] sm:max-w-md px-4 py-2.5 shadow-sm text-sm break-words {{
                            $isMine
                                ? 'bg-[var(--color-primary)] text-white rounded-2xl rounded-tr-sm'
                                : 'bg-white dark:bg-slate-800 text-[var(--color-text)] border border-slate-200 dark:border-slate-700 rounded-2xl rounded-tl-sm'
                        }}"
                    >
                        {{-- Message content is escaped by Blade (CHT-E5, CHT-T05) --}}
                        <p class="whitespace-pre-wrap leading-relaxed">{{ $message->body }}</p>
                    </div>

                    <div class="mt-1 px-1 flex items-center gap-1 text-[11px] text-[var(--color-muted)]">
                        <x-ui.datetime :value="$message->created_at" format="time" />
                        @if ($isMine)
                            <span>&bull;</span>
                            <span>{{ __('chat.you') }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Input Area --}}
    <div class="mt-3 shrink-0">
        <form wire:submit="send" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 shadow-sm">
            <div class="flex flex-col gap-2">
                <div class="relative">
                    <textarea
                        wire:model="newMessage"
                        rows="2"
                        maxlength="1000"
                        class="w-full text-sm text-[var(--color-text)] bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent resize-none"
                        placeholder="{{ __('chat.type_message_placeholder') }}"
                        required
                    ></textarea>
                </div>

                @error('newMessage')
                    <p class="text-xs text-[var(--color-danger)]" role="alert">{{ $message }}</p>
                @enderror

                <div class="flex items-center justify-between gap-3">
                    <div class="text-xs text-[var(--color-muted)]">
                        <span x-text="$wire.newMessage ? $wire.newMessage.length : 0">0</span>/1000
                    </div>

                    <x-ui.button
                        type="submit"
                        variant="primary"
                        class="min-h-[44px] px-5"
                        wire:loading.attr="disabled"
                        wire:target="send"
                    >
                        <span wire:loading.remove wire:target="send">
                            {{ __('chat.send') }}
                        </span>
                        <span wire:loading wire:target="send" class="inline-flex items-center gap-2">
                            <x-ui.icon name="panah-putar" class="w-4 h-4 animate-spin" />
                            {{ __('chat.sending') }}
                        </span>
                    </x-ui.button>
                </div>
            </div>
        </form>
    </div>
</div>
