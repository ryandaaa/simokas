<div class="ds-sans bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
    <div class="space-y-4">
        <div class="border-b border-ds-line/60 pb-3">
            <h3 class="text-lg font-extrabold text-ds-ink tracking-tight">
                {{ __('rating.title') }}
            </h3>
            @if ($transaction?->inspector)
                <p class="text-sm text-ds-muted mt-1">
                    Inspektor: <span class="font-bold text-ds-ink">{{ $transaction->inspector->name }}</span>
                </p>
            @endif
        </div>

        @if ($errorMessage)
            <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
                {{ $errorMessage }}
            </div>
        @endif

        @if ($successMessage)
            <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
                {{ $successMessage }}
            </div>
        @endif

        @if ($isSubmitted)
            <div class="bg-ds-canvas/60 rounded-2xl p-4 border border-ds-line/60 space-y-2">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-ds-muted">{{ __('rating.score') }}:</span>
                    <div class="flex items-center text-ds-yellow-deep" aria-label="{{ __('rating.display.out_of_five', ['score' => $existingScore]) }}">
                        @for ($i = 1; $i <= 5; $i++)
                            @if ($i <= ($existingScore ?? 5))
                                <i aria-hidden="true" class="fa-solid fa-star w-5 h-5"></i>
                            @else
                                <i aria-hidden="true" class="fa-regular fa-star w-5 h-5 text-ds-faint"></i>
                            @endif
                        @endfor
                        <span class="ml-2 font-extrabold text-ds-ink">{{ $existingScore }} / 5</span>
                    </div>
                </div>

                @if ($existingComment)
                    <div class="pt-2">
                        <span class="text-xs text-ds-muted block mb-1">{{ __('rating.comment') }}:</span>
                        <p class="text-sm text-ds-ink italic whitespace-pre-wrap">{{ $existingComment }}</p>
                    </div>
                @endif

                <p class="text-xs text-ds-muted pt-2 border-t border-ds-line/60">
                    {{ __('rating.cannot_modify') }}
                </p>
            </div>
        @elseif ($canRate)
            <form wire:submit.prevent="submit" class="space-y-4">
                {{-- Star rating selector --}}
                <div>
                    <span class="block text-sm font-bold text-ds-ink mb-2" id="rating-score-label">
                        {{ __('rating.score') }}
                    </span>
                    <div class="flex items-center gap-2" role="radiogroup" aria-labelledby="rating-score-label">
                        @for ($i = 1; $i <= 5; $i++)
                            <button
                                type="button"
                                wire:click="$set('score', {{ $i }})"
                                class="p-1 rounded-full focus:outline-none focus:ring-2 focus:ring-ds-blue transition"
                                title="{{ __('rating.display.out_of_five', ['score' => $i]) }}"
                                role="radio"
                                aria-checked="{{ $i === $score ? 'true' : 'false' }}"
                                aria-label="{{ __('rating.display.out_of_five', ['score' => $i]) }}"
                            >
                                <i aria-hidden="true" class="fa-{{ $i <= $score ? 'solid' : 'regular' }} fa-star w-8 h-8 {{ $i <= $score ? 'text-ds-yellow-deep' : 'text-ds-faint' }} hover:scale-110 transition-transform"></i>
                            </button>
                        @endfor
                        <span class="ml-2 font-extrabold text-ds-ink text-base">{{ $score }} / 5</span>
                    </div>
                    @error('score')
                        <p class="text-xs text-ds-danger mt-1" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Comment textarea --}}
                <div class="w-full">
                    <label for="rating-comment" class="block text-sm font-bold text-ds-ink mb-1">
                        {{ __('rating.comment') }}
                    </label>
                    <textarea
                        id="rating-comment"
                        wire:model="comment"
                        rows="3"
                        maxlength="500"
                        placeholder="{{ __('rating.comment_placeholder') }}"
                        class="w-full rounded-xl border border-ds-line bg-white p-3 text-ds-ink placeholder:text-ds-faint focus:border-ds-blue focus:outline-none focus:ring-2 focus:ring-ds-blue/20 text-sm"
                    ></textarea>
                    <div class="flex justify-between items-center text-xs text-ds-muted mt-1">
                        <span>{{ __('rating.comment_hint') }}</span>
                        <span>{{ __('rating.character_count', ['count' => strlen($comment)]) }}</span>
                    </div>
                    @error('comment')
                        <p class="text-xs text-ds-danger mt-1" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <div class="pt-2">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="submit">
                            {{ __('rating.submit') }}
                        </span>
                        <span wire:loading wire:target="submit">
                            {{ __('rating.submitting') }}
                        </span>
                    </button>
                </div>
            </form>
        @else
            <div class="p-4 rounded-2xl border border-ds-blue-soft bg-ds-blue-soft/40 text-xs font-semibold text-ds-ink">
                {{ __('rating.already_rated') }}
            </div>
        @endif
    </div>
</div>
