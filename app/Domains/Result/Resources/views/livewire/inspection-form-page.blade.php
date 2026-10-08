<div class="ds-sans space-y-8 pb-12">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-2">
            <a href="{{ route('jobs.show', $job->id) }}" class="inline-flex items-center gap-1 text-xs font-bold text-ds-blue hover:text-ds-blue-ink">
                <i aria-hidden="true" class="fa-solid fa-chevron-left"></i>
                {{ __('inspection.form.back_to_job') }}
            </a>
            <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('inspection.form.title') }}</h1>
            <p class="text-sm text-ds-muted">{{ __('inspection.form.subtitle') }}</p>
        </div>
        @if ($result)
            <div class="flex items-center gap-3">
                @php
                    $resultPill = match ($result->status->color()) {
                        'success' => 'bg-ds-green-soft text-ds-green-ink',
                        'warning' => 'bg-ds-amber-soft text-ds-amber-ink',
                        'danger' => 'bg-ds-red-soft text-ds-red-ink',
                        'primary' => 'bg-ds-blue-soft text-ds-blue-ink',
                        default => 'bg-ds-soft text-ds-muted',
                    };
                @endphp
                <span class="text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider {{ $resultPill }}">
                    {{ $result->status->label() }}
                </span>
                @if ($lastSavedAt)
                    <span class="text-xs text-ds-muted">
                        {{ __('inspection.form.last_saved_at', ['time' => $lastSavedAt]) }}
                    </span>
                @endif
            </div>
        @endif
    </div>

    {{-- Alerts --}}
    @if ($actionSuccess)
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ $actionSuccess }}
        </div>
    @endif

    @if ($actionError)
        <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
            {{ $actionError }}
        </div>
    @endif

    @if ($result === null)
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-amber-soft text-ds-amber-ink flex items-center justify-center text-xl mb-4">
                <i aria-hidden="true" class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('inspection.form.unauthorized') }}</h3>
        </div>
    @else
        {{-- Progress Indicator --}}
        @if (count($categories) > 1)
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-ds-ink">
                        {{ __('inspection.form.category_progress', [
                            'current' => $currentCategoryIndex + 1,
                            'total' => $totalCategories
                        ]) }}
                    </span>
                    <span class="text-sm font-extrabold text-ds-blue">
                        {{ $categories[$currentCategoryIndex] ?? '' }}
                    </span>
                </div>
                <div class="grid grid-flow-col auto-cols-fr gap-1.5 mt-3" role="progressbar" aria-label="{{ __('inspection.form.category_progress', ['current' => $currentCategoryIndex + 1, 'total' => $totalCategories]) }}" aria-valuenow="{{ $currentCategoryIndex + 1 }}" aria-valuemin="1" aria-valuemax="{{ $totalCategories }}">
                    @foreach ($categories as $idx => $catName)
                        <div
                            @class([
                                'h-2 rounded-full transition-colors',
                                'bg-ds-blue' => $idx === $currentCategoryIndex,
                                'bg-ds-green' => $idx < $currentCategoryIndex,
                                'bg-ds-soft' => $idx > $currentCategoryIndex,
                            ])
                            title="{{ $catName }}"
                        ></div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Category Form --}}
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h2 class="text-lg font-extrabold text-ds-ink">{{ $categories[$currentCategoryIndex] ?? __('inspection.form.title') }}</h2>
            <div class="space-y-6 mt-4">
                @foreach ($currentItems as $templateItem)
                    <div
                        class="p-4 rounded-2xl border border-ds-line/80 bg-ds-canvas/60 space-y-3"
                        wire:key="item-{{ $templateItem->id }}"
                    >
                        {{-- Item Header --}}
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="flex-1">
                                <p class="font-bold text-ds-ink text-sm">{{ $templateItem->label }}</p>
                                <div class="flex flex-wrap gap-1.5 mt-1">
                                    @if ($templateItem->is_required)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-ds-amber-soft text-ds-amber-ink">
                                            {{ __('inspection.form.badge_required') }}
                                        </span>
                                    @endif
                                    @if ($templateItem->is_critical)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-ds-red-soft text-ds-red-ink">
                                            {{ __('inspection.form.badge_critical') }}
                                        </span>
                                    @endif
                                    @if ($templateItem->requires_photo)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-ds-blue-soft text-ds-blue-ink">
                                            {{ __('inspection.form.badge_requires_photo') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Rating Selection --}}
                        <div>
                            <p class="text-xs text-ds-muted mb-2">{{ __('inspection.form.rating_label') }}</p>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ($ratings as $rating)
                                    <button
                                        type="button"
                                        wire:click="$set('items.{{ $templateItem->id }}.rating', '{{ $rating->value }}')"
                                        @class([
                                            'flex flex-col items-center justify-center p-3 rounded-xl border-2 transition-all min-h-[56px] text-sm font-bold',
                                            'border-ds-green bg-ds-green-soft/50 text-ds-green-ink' => ($items[$templateItem->id]['rating'] ?? '') === $rating->value && $rating->value === 'good',
                                            'border-ds-amber-ink bg-ds-amber-soft/50 text-ds-amber-ink' => ($items[$templateItem->id]['rating'] ?? '') === $rating->value && $rating->value === 'fair',
                                            'border-ds-danger bg-ds-red-soft/50 text-ds-danger' => ($items[$templateItem->id]['rating'] ?? '') === $rating->value && $rating->value === 'poor',
                                            'border-ds-line/80 text-ds-muted hover:border-ds-blue' => ($items[$templateItem->id]['rating'] ?? '') !== $rating->value,
                                        ])
                                        aria-label="{{ $rating->label() }}"
                                        aria-pressed="{{ ($items[$templateItem->id]['rating'] ?? '') === $rating->value ? 'true' : 'false' }}"
                                    >
                                        {{ $rating->label() }}
                                    </button>
                                @endforeach
                            </div>
                            @error('items.'.$templateItem->id.'.rating')
                                <p class="text-xs text-ds-danger mt-1" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Note --}}
                        <div class="w-full">
                            <label for="{{ 'note-'.$templateItem->id }}" class="block text-xs font-bold text-ds-ink mb-1.5">
                                {{ __('inspection.form.note_label') }}
                            </label>
                            <textarea id="{{ 'note-'.$templateItem->id }}" name="{{ 'items['.$templateItem->id.'][note]' }}" rows="2"
                                placeholder="{{ __('inspection.form.note_placeholder') }}" wire:model.lazy="items.{{ $templateItem->id }}.note"
                                class="w-full px-4 py-3 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"></textarea>
                            @error('items.'.$templateItem->id.'.note')
                                <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Photo Capture for items requiring photo --}}
                        @if ($templateItem->requires_photo)
                            <div class="pt-3 border-t border-ds-line/60 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="block text-xs font-extrabold text-ds-ink uppercase tracking-wider">
                                        {{ __('result.photos.title') }}
                                        <span class="text-ds-danger" aria-hidden="true">*</span>
                                    </span>
                                    @if (! isset($photos[$templateItem->id]))
                                        <span class="text-xs text-ds-muted">
                                            {{ __('result.photos.required_notice') }}
                                        </span>
                                    @endif
                                </div>

                                @if (isset($photos[$templateItem->id]))
                                    <x-ui.camera-capture
                                        :item-id="$templateItem->id"
                                        :job-id="$job->id"
                                        :photo-url="$photos[$templateItem->id]['url']"
                                        :timestamp="$photos[$templateItem->id]['stamped_at'] ?? $photos[$templateItem->id]['captured_at']"
                                        wire:retake="retakePhoto('{{ $templateItem->id }}')"
                                    />
                                @else
                                    <x-ui.camera-capture
                                        :item-id="$templateItem->id"
                                        :job-id="$job->id"
                                        wire:model="tempPhotos.{{ $templateItem->id }}"
                                        wire:use="applyPhoto('{{ $templateItem->id }}')"
                                    />
                                @endif

                                @error('tempPhotos.'.$templateItem->id)
                                    <p class="text-xs text-ds-danger mt-1" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Summary Note (visible on last category) --}}
        @if ($currentCategoryIndex === $totalCategories - 1)
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <div class="w-full">
                    <label for="summary-note" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('inspection.form.summary_note_label') }}
                    </label>
                    <textarea id="summary-note" name="summaryNote" rows="4" wire:model.lazy="summaryNote"
                        placeholder="{{ __('inspection.form.summary_note_placeholder') }}"
                        class="w-full px-4 py-3 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"></textarea>
                    @error('summaryNote')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        {{-- Navigation Buttons --}}
        <div class="flex flex-col sm:flex-row gap-3 justify-between">
            {{-- Previous --}}
            <div>
                @if ($currentCategoryIndex > 0)
                    <button type="button" wire:click="previousCategory" wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs disabled:opacity-60">
                        <i aria-hidden="true" class="fa-solid fa-chevron-left text-xs"></i>
                        {{ __('inspection.form.previous_category') }}
                    </button>
                @endif
            </div>

            {{-- Save Draft + Next --}}
            <div class="flex gap-3">
                <button type="button" wire:click="saveDraft" wire:loading.attr="disabled" wire:target="saveDraft"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-bold text-ds-muted hover:text-ds-ink transition-colors disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveDraft">{{ __('inspection.form.save_draft') }}</span>
                    <span wire:loading wire:target="saveDraft">{{ __('inspection.form.saving_draft') }}</span>
                </button>

                @if ($currentCategoryIndex < $totalCategories - 1)
                    <button type="button" wire:click="nextCategory" wire:loading.attr="disabled" wire:target="nextCategory"
                        class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60">
                        {{ __('inspection.form.next_category') }}
                        <i aria-hidden="true" class="fa-solid fa-chevron-right text-xs"></i>
                    </button>
                @else
                    <button type="button" wire:click="showSubmitConfirmation" wire:loading.attr="disabled" wire:target="showSubmitConfirmation"
                        class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-green hover:brightness-95 text-white font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60">
                        <i aria-hidden="true" class="fa-solid fa-check text-xs"></i>
                        {{ __('inspection.form.submit_result') }}
                    </button>
                @endif
            </div>
        </div>

        {{-- All categories completed notice --}}
        @if ($currentCategoryIndex === $totalCategories - 1 && $totalCategories > 0)
            <div class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
                {{ __('inspection.form.all_categories_completed') }}
            </div>
        @endif

        {{-- Submit Confirmation Modal --}}
        @if ($showSubmitConfirmation)
            <x-ui.modal
                :show="true"
                name="submit-result-modal"
            >
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-ds-ink">{{ __('inspection.form.submit_confirmation_title') }}</h3>
                        <p class="text-sm text-ds-muted mt-2">{{ __('inspection.form.submit_confirmation_message') }}</p>
                    </div>

                    <div class="bg-ds-amber-soft/40 border border-ds-amber-soft rounded-2xl p-3">
                        <p class="text-xs font-bold text-ds-amber-ink">{{ __('inspection.form.submit_warning') }}</p>
                    </div>

                    <x-slot:footer>
                        <x-ui.button
                            type="button"
                            variant="secondary"
                            wire:click="cancelSubmit"
                            :disabled="$isSubmitting"
                        >
                            {{ __('ui.actions.cancel') }}
                        </x-ui.button>
                        <x-ui.button
                            type="button"
                            variant="success"
                            wire:click="submitResult"
                            wire:loading.attr="disabled"
                            wire:target="submitResult"
                            :loading="$isSubmitting"
                            icon="check"
                        >
                            <span wire:loading.remove wire:target="submitResult">{{ __('inspection.form.submit_result') }}</span>
                            <span wire:loading wire:target="submitResult">{{ __('inspection.form.submitting') }}</span>
                        </x-ui.button>
                    </x-slot:footer>
                </div>
            </x-ui.modal>
        @endif
    @endif
</div>
