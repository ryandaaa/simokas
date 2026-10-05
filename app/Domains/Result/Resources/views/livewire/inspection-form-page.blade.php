<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <x-ui.button :href="route('jobs.show', $job->id)" variant="ghost" icon="chevron-left" class="mb-2 -ml-2">
                {{ __('inspection.form.back_to_job') }}
            </x-ui.button>
            <h1 class="text-2xl font-bold text-text">{{ __('inspection.form.title') }}</h1>
            <p class="text-sm text-muted mt-0.5">{{ __('inspection.form.subtitle') }}</p>
        </div>
        @if ($result)
            <div class="flex items-center gap-3">
                <x-ui.status-chip
                    :label="$result->status->label()"
                    :variant="$result->status->color()"
                    :icon="$result->status->icon()"
                />
                @if ($lastSavedAt)
                    <span class="text-xs text-muted">
                        {{ __('inspection.form.last_saved_at', ['time' => $lastSavedAt]) }}
                    </span>
                @endif
            </div>
        @endif
    </div>

    {{-- Alerts --}}
    @if ($actionSuccess)
        <x-ui.alert variant="success" :message="$actionSuccess" dismissible />
    @endif

    @if ($actionError)
        <x-ui.alert variant="danger" :message="$actionError" dismissible />
    @endif

    @if ($result === null)
        <x-ui.card>
            <x-ui.empty-state
                icon="exclamation-triangle"
                :title="__('inspection.form.unauthorized')"
            />
        </x-ui.card>
    @else
        {{-- Progress Indicator --}}
        @if (count($categories) > 1)
            <x-ui.card>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-text">
                        {{ __('inspection.form.category_progress', [
                            'current' => $currentCategoryIndex + 1,
                            'total' => $totalCategories
                        ]) }}
                    </span>
                    <span class="text-sm font-semibold text-primary">
                        {{ $categories[$currentCategoryIndex] ?? '' }}
                    </span>
                </div>
                <div class="grid grid-flow-col auto-cols-fr gap-1.5 mt-3">
                    @foreach ($categories as $idx => $catName)
                        <div
                            @class([
                                'h-2 rounded-full transition-colors',
                                'bg-primary' => $idx === $currentCategoryIndex,
                                'bg-success' => $idx < $currentCategoryIndex,
                                'bg-muted/20' => $idx > $currentCategoryIndex,
                            ])
                            title="{{ $catName }}"
                        ></div>
                    @endforeach
                </div>
            </x-ui.card>
        @endif

        {{-- Category Form --}}
        <x-ui.card :title="$categories[$currentCategoryIndex] ?? __('inspection.form.title')">
            <div class="space-y-6">
                @foreach ($currentItems as $templateItem)
                    <div
                        class="p-4 rounded-lg border border-muted/20 bg-bg space-y-3"
                        wire:key="item-{{ $templateItem->id }}"
                    >
                        {{-- Item Header --}}
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="flex-1">
                                <p class="font-medium text-text">{{ $templateItem->label }}</p>
                                <div class="flex flex-wrap gap-1.5 mt-1">
                                    @if ($templateItem->is_required)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-warning/15 text-warning">
                                            {{ __('inspection.form.badge_required') }}
                                        </span>
                                    @endif
                                    @if ($templateItem->is_critical)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-danger/15 text-danger">
                                            {{ __('inspection.form.badge_critical') }}
                                        </span>
                                    @endif
                                    @if ($templateItem->requires_photo)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-primary/10 text-primary">
                                            {{ __('inspection.form.badge_requires_photo') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Rating Selection --}}
                        <div>
                            <p class="text-xs text-muted mb-2">{{ __('inspection.form.rating_label') }}</p>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ($ratings as $rating)
                                    <button
                                        type="button"
                                        wire:click="$set('items.{{ $templateItem->id }}.rating', '{{ $rating->value }}')"
                                        @class([
                                            'flex flex-col items-center justify-center p-3 rounded-lg border-2 transition-all min-h-[56px] text-sm font-medium',
                                            'border-success bg-success/10 text-success' => ($items[$templateItem->id]['rating'] ?? '') === $rating->value && $rating->value === 'good',
                                            'border-warning bg-warning/10 text-warning' => ($items[$templateItem->id]['rating'] ?? '') === $rating->value && $rating->value === 'fair',
                                            'border-danger bg-danger/10 text-danger' => ($items[$templateItem->id]['rating'] ?? '') === $rating->value && $rating->value === 'poor',
                                            'border-muted/30 text-muted hover:border-muted/60' => ($items[$templateItem->id]['rating'] ?? '') !== $rating->value,
                                        ])
                                        aria-label="{{ $rating->label() }}"
                                        aria-pressed="{{ ($items[$templateItem->id]['rating'] ?? '') === $rating->value ? 'true' : 'false' }}"
                                    >
                                        {{ $rating->label() }}
                                    </button>
                                @endforeach
                            </div>
                            @error('items.'.$templateItem->id.'.rating')
                                <p class="text-xs text-danger mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Note --}}
                        <x-ui.textarea
                            :id="'note-'.$templateItem->id"
                            :name="'items['.$templateItem->id.'][note]'"
                            :label="__('inspection.form.note_label')"
                            :placeholder="__('inspection.form.note_placeholder')"
                            wire:model.lazy="items.{{ $templateItem->id }}.note"
                            :error="$errors->first('items.'.$templateItem->id.'.note')"
                            rows="2"
                        />

                        {{-- Photo Capture for items requiring photo --}}
                        @if ($templateItem->requires_photo)
                            <div class="pt-3 border-t border-muted/20 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-semibold text-text uppercase tracking-wider">
                                        {{ __('result.photos.title') }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    @if (! isset($photos[$templateItem->id]))
                                        <span class="text-xs text-muted">
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
                                    <p class="text-xs text-danger mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        {{-- Summary Note (visible on last category) --}}
        @if ($currentCategoryIndex === $totalCategories - 1)
            <x-ui.card>
                <x-ui.textarea
                    id="summary-note"
                    name="summaryNote"
                    :label="__('inspection.form.summary_note_label')"
                    :placeholder="__('inspection.form.summary_note_placeholder')"
                    wire:model.lazy="summaryNote"
                    :error="$errors->first('summaryNote')"
                    rows="4"
                />
            </x-ui.card>
        @endif

        {{-- Navigation Buttons --}}
        <div class="flex flex-col sm:flex-row gap-3 justify-between">
            {{-- Previous --}}
            <div>
                @if ($currentCategoryIndex > 0)
                    <x-ui.button
                        variant="secondary"
                        icon="chevron-left"
                        wire:click="previousCategory"
                        wire:loading.attr="disabled"
                    >
                        {{ __('inspection.form.previous_category') }}
                    </x-ui.button>
                @endif
            </div>

            {{-- Save Draft + Next --}}
            <div class="flex gap-3">
                <x-ui.button
                    variant="ghost"
                    wire:click="saveDraft"
                    wire:loading.attr="disabled"
                    wire:target="saveDraft"
                    :loading="$draftSaving"
                >
                    <span wire:loading.remove wire:target="saveDraft">{{ __('inspection.form.save_draft') }}</span>
                    <span wire:loading wire:target="saveDraft">{{ __('inspection.form.saving_draft') }}</span>
                </x-ui.button>

                @if ($currentCategoryIndex < $totalCategories - 1)
                    <x-ui.button
                        variant="primary"
                        iconRight="chevron-right"
                        wire:click="nextCategory"
                        wire:loading.attr="disabled"
                        wire:target="nextCategory"
                    >
                        {{ __('inspection.form.next_category') }}
                    </x-ui.button>
                @else
                    <x-ui.button
                        variant="primary"
                        wire:click="saveDraft"
                        wire:loading.attr="disabled"
                        wire:target="saveDraft"
                    >
                        {{ __('inspection.form.save_draft') }}
                    </x-ui.button>
                @endif
            </div>
        </div>

        {{-- All categories completed notice --}}
        @if ($currentCategoryIndex === $totalCategories - 1 && $totalCategories > 0)
            <x-ui.alert variant="success" :message="__('inspection.form.all_categories_completed')" />
        @endif
    @endif
</div>
