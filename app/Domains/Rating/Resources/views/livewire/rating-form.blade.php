<x-ui.card class="p-6">
    <div class="space-y-4">
        <div class="border-b border-fog pb-3">
            <h3 class="text-lg font-bold text-obs tracking-tight">
                {{ __('rating.title') }}
            </h3>
            @if ($transaction?->inspector)
                <p class="text-sm text-charcoal mt-1">
                    Inspektor: <span class="font-medium text-forest">{{ $transaction->inspector->name }}</span>
                </p>
            @endif
        </div>

        @if ($errorMessage)
            <x-ui.alert variant="danger" :message="$errorMessage" />
        @endif

        @if ($successMessage)
            <x-ui.alert variant="success" :message="$successMessage" />
        @endif

        @if ($isSubmitted)
            <div class="bg-linen/40 rounded-xl p-4 border border-fog space-y-2">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-charcoal">{{ __('rating.score') }}:</span>
                    <div class="flex items-center text-warning" aria-label="{{ __('rating.display.out_of_five', ['score' => $existingScore]) }}">
                        @for ($i = 1; $i <= 5; $i++)
                            @if ($i <= ($existingScore ?? 5))
                                <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-fog fill-current" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endif
                        @endfor
                        <span class="ml-2 font-bold text-obs">{{ $existingScore }} / 5</span>
                    </div>
                </div>

                @if ($existingComment)
                    <div class="pt-2">
                        <span class="text-xs text-muted block mb-1">{{ __('rating.comment') }}:</span>
                        <p class="text-sm text-charcoal italic whitespace-pre-wrap">{{ $existingComment }}</p>
                    </div>
                @endif

                <p class="text-xs text-muted pt-2 border-t border-fog/60">
                    {{ __('rating.cannot_modify') }}
                </p>
            </div>
        @elseif ($canRate)
            <form wire:submit.prevent="submit" class="space-y-4">
                {{-- Star rating selector --}}
                <div>
                    <label class="block text-base font-medium text-charcoal mb-2">
                        {{ __('rating.score') }}
                    </label>
                    <div class="flex items-center gap-2">
                        @for ($i = 1; $i <= 5; $i++)
                            <button
                                type="button"
                                wire:click="$set('score', {{ $i }})"
                                class="p-1 rounded-full focus:outline-none focus:ring-2 focus:ring-forest transition"
                                title="{{ __('rating.display.out_of_five', ['score' => $i]) }}"
                            >
                                <svg
                                    class="w-8 h-8 {{ $i <= $score ? 'text-warning fill-current' : 'text-fog fill-current' }} hover:scale-110 transition-transform"
                                    viewBox="0 0 20 20"
                                >
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </button>
                        @endfor
                        <span class="ml-2 font-bold text-obs text-base">{{ $score }} / 5</span>
                    </div>
                    @error('score')
                        <p class="text-xs text-alarm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Comment textarea --}}
                <div>
                    <label for="rating-comment" class="block text-base font-medium text-charcoal mb-1">
                        {{ __('rating.comment') }}
                    </label>
                    <textarea
                        id="rating-comment"
                        wire:model="comment"
                        rows="3"
                        maxlength="500"
                        placeholder="{{ __('rating.comment_placeholder') }}"
                        class="w-full rounded-[10px] border border-pebble bg-paper p-3 text-charcoal placeholder-pebble focus:border-forest focus:outline-none text-base"
                    ></textarea>
                    <div class="flex justify-between items-center text-xs text-muted mt-1">
                        <span>{{ __('rating.comment_hint') }}</span>
                        <span>{{ __('rating.character_count', ['count' => strlen($comment)]) }}</span>
                    </div>
                    @error('comment')
                        <p class="text-xs text-alarm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <div class="pt-2">
                    <x-ui.button
                        type="submit"
                        variant="primary"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                        class="w-full sm:w-auto"
                    >
                        <span wire:loading.remove wire:target="submit">
                            {{ __('rating.submit') }}
                        </span>
                        <span wire:loading wire:target="submit">
                            {{ __('rating.submitting') }}
                        </span>
                    </x-ui.button>
                </div>
            </form>
        @else
            <x-ui.alert variant="info" :message="__('rating.already_rated')" />
        @endif
    </div>
</x-ui.card>
