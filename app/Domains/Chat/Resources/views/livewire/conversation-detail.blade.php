<div
    wire:poll.3000ms="pollNewMessages"
    x-data="{
        realtime: false,
        init() {
            if (!window.Echo) return;
            window.Echo.private('conversation.{{ $conversation->id }}')
                .listen('.message.sent', () => { this.$wire.pollNewMessages(); });
            window.addEventListener('simokas:realtime', (event) => { this.realtime = event.detail.connected; });
            this.realtime = true;
        }
    }"
    class="ds-sans max-w-4xl mx-auto px-4 py-4 flex flex-col h-[calc(100vh-6rem)] sm:h-[calc(100vh-7rem)]"
>
    {{-- Header --}}
    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-4 mb-3 shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('conversations.index') }}"
                class="min-h-[44px] min-w-[44px] p-2 inline-flex items-center justify-center rounded-full text-ds-muted hover:text-ds-blue hover:bg-ds-canvas"
                title="{{ __('chat.back_to_conversations') }}">
                <span class="sr-only">{{ __('chat.back_to_conversations') }}</span>
                <i aria-hidden="true" class="fa-solid fa-chevron-left"></i>
            </a>

            <div class="w-10 h-10 rounded-2xl bg-ds-blue-soft text-ds-blue-ink flex items-center justify-center font-extrabold shrink-0">
                @if ($otherMember)
                    {{ mb_strtoupper(mb_substr($otherMember->name, 0, 1)) }}
                @else
                    <i aria-hidden="true" class="fa-solid fa-user"></i>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <h1 class="text-base font-extrabold text-ds-ink truncate">
                    {{ $otherMember?->name ?? __('chat.participant') }}
                </h1>
                <div class="text-xs text-ds-muted flex items-center gap-2 mt-0.5">
                    <span class="truncate">{{ $context['title'] }}</span>
                    @if ($context['price'] !== null)
                        <span>&bull;</span>
                        <span class="font-extrabold text-ds-ink">{{ \App\Support\Money::format($context['price']) }}</span>
                    @endif
                    <span>&bull;</span>
                    <span class="inline-flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full" :class="realtime ? 'bg-ds-green' : 'bg-ds-faint'"></span>
                        <span x-text="realtime ? '{{ __('chat.realtime_on') }}' : '{{ __('chat.polling_fallback') }}'"></span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Error Alert --}}
    @if ($sendFailed)
        <div class="mb-3 shrink-0">
            <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 flex items-start gap-3">
                <i aria-hidden="true" class="fa-solid fa-circle-exclamation text-ds-danger mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-xs font-extrabold text-ds-ink">{{ __('chat.failed_to_send') }}</p>
                    @if ($errorMessage)
                        <p class="mt-1 text-xs text-ds-muted">{{ $errorMessage }}</p>
                    @endif
                    <button type="button" wire:click="retry" class="mt-2 text-xs font-bold text-ds-blue hover:text-ds-blue-ink">
                        {{ __('chat.retry') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Messages Area --}}
    <div
        id="chat-messages-container"
        class="flex-1 overflow-y-auto space-y-3 p-4 bg-ds-canvas/60 rounded-2xl border border-ds-line/60"
    >
        @if ($messages->isEmpty())
            <div class="py-12 text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-white text-ds-muted border border-ds-line/80 flex items-center justify-center text-xl mb-4">
                    <i aria-hidden="true" class="fa-solid fa-comments"></i>
                </div>
                <h3 class="text-base font-extrabold text-ds-ink">{{ __('chat.empty_messages') }}</h3>
                <p class="text-xs text-ds-muted mt-1">{{ __('chat.empty_messages_desc') }}</p>
            </div>
        @else
            @foreach ($messages as $message)
                @php
                    $isMine = $message->sender_id === $currentUser->id;
                @endphp
                <div class="flex flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
                    <div
                        class="max-w-[85%] sm:max-w-md px-4 py-2.5 shadow-xs text-sm break-words {{
                            $isMine
                                ? 'bg-ds-blue text-white rounded-2xl rounded-tr-sm'
                                : 'bg-white text-ds-ink border border-ds-line/80 rounded-2xl rounded-tl-sm'
                        }}"
                    >
                        {{-- Message content is escaped by Blade (CHT-E5, CHT-T05) --}}
                        <p class="whitespace-pre-wrap leading-relaxed">{{ $message->body }}</p>
                    </div>

                    <div class="mt-1 px-1 flex items-center gap-1 text-[11px] text-ds-faint">
                        <span>{{ $message->created_at?->translatedFormat('H:i') }}</span>
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
        <form wire:submit="send" class="bg-white rounded-2xl border border-ds-line/80 p-3 shadow-xs">
            <div class="flex flex-col gap-2">
                <div class="relative">
                    <textarea
                        wire:model="newMessage"
                        rows="2"
                        maxlength="1000"
                        class="w-full text-sm text-ds-ink bg-ds-canvas/60 border border-ds-line/80 rounded-xl p-2.5 focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue resize-none placeholder:text-ds-faint"
                        placeholder="{{ __('chat.type_message_placeholder') }}"
                        required
                    ></textarea>
                </div>

                @error('newMessage')
                    <p class="text-xs text-ds-danger" role="alert">{{ $message }}</p>
                @enderror

                <div class="flex items-center justify-between gap-3">
                    <div class="text-xs text-ds-faint">
                        <span x-text="$wire.newMessage ? $wire.newMessage.length : 0">0</span>/1000
                    </div>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="send"
                        class="inline-flex items-center justify-center gap-2 px-5 min-h-[44px] bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="send">
                            {{ __('chat.send') }}
                        </span>
                        <span wire:loading wire:target="send" class="inline-flex items-center gap-2">
                            <i aria-hidden="true" class="fa-solid fa-circle-notch fa-spin"></i>
                            {{ __('chat.sending') }}
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
