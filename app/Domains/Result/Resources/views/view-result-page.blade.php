@php
use App\Domains\Result\Enums\InspectionRecommendation;
@endphp

<div class="min-h-screen bg-color-bg py-8">
    <div class="max-w-2xl mx-auto px-4">
        @if ($hasAccess)
            {{-- Full result view --}}
            <div class="space-y-8">
                {{-- Header with recommendation --}}
                <div class="bg-color-surface rounded-xl p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div>
                            <h1 class="text-2xl font-bold text-color-text">
                                {{ __('result.inspection_results') }}
                            </h1>
                            <p class="text-color-muted text-sm">
                                {{ $result->submitted_at?->format(__('common.date_format')) }}
                            </p>
                        </div>
                        {{-- Recommendation badge --}}
                        @if ($result->recommendation)
                            <div class="flex-shrink-0">
                                <x-ui.status-chip
                                    :label="$result->recommendation->label()"
                                    :color="match ($result->recommendation) {
                                        InspectionRecommendation::Recommended => 'success',
                                        InspectionRecommendation::Caution => 'warning',
                                        InspectionRecommendation::NotRecommended => 'danger',
                                    }"
                                />
                            </div>
                        @endif
                    </div>

                    {{-- Inspector info --}}
                    <div class="border-t border-color-border pt-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-color-muted mb-1">{{ __('result.inspector') }}</p>
                            <p class="text-lg font-semibold text-color-text">{{ $result->inspector->name }}</p>
                        </div>
                        @if (isset($inspectorRating) && $inspectorRating['ratingCount'] > 0)
                            <div class="flex items-center gap-1.5 text-warning bg-linen/50 px-3 py-1.5 rounded-full border border-fog">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span class="font-bold text-sm text-obs">{{ number_format($inspectorRating['ratingAvg'], 2) }}</span>
                                <span class="text-xs text-muted">({{ $inspectorRating['ratingCount'] }})</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Score --}}
                @if ($result->total_score !== null)
                    <div class="bg-color-surface rounded-xl p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-color-text mb-4">{{ __('result.score') }}</h2>
                        <div class="flex items-center gap-6">
                            <div class="text-5xl font-bold text-color-primary">
                                {{ number_format($result->total_score, 1) }}
                            </div>
                            <div>
                                <p class="text-sm text-color-muted">{{ __('result.out_of_100') }}</p>
                                <p class="text-base font-semibold text-color-text mt-2">
                                    @if ($result->recommendation)
                                        {{ $result->recommendation->label() }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Summary note --}}
                @if ($result->summary_note)
                    <div class="bg-color-surface rounded-xl p-6 shadow-sm">
                        <h3 class="font-semibold text-color-text mb-3">{{ __('result.summary') }}</h3>
                        <p class="text-color-text whitespace-pre-wrap">{{ $result->summary_note }}</p>
                    </div>
                @endif

                {{-- Items by category --}}
                @forelse ($result->items->groupBy('template_item_id') as $groupedItems)
                    <div class="bg-color-surface rounded-xl p-6 shadow-sm">
                        <div class="space-y-4">
                            @foreach ($groupedItems as $item)
                                <div class="flex items-start justify-between gap-4 border-b border-color-border pb-4 last:border-b-0">
                                    <div>
                                        <p class="font-semibold text-color-text">{{ $item->templateItem?->label ?? __('result.unnamed_item') }}</p>
                                        @if ($item->note)
                                            <p class="text-sm text-color-muted mt-1">{{ $item->note }}</p>
                                        @endif
                                    </div>
                                    <x-ui.status-chip
                                        :label="$item->rating->label()"
                                        :color="match ($item->rating->value) {
                                            'good' => 'success',
                                            'okay' => 'warning',
                                            'bad' => 'danger',
                                        }"
                                    />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state
                        :icon="null"
                        :title="__('result.no_items')"
                    />
                @endforelse

                {{-- Photos --}}
                @forelse ($result->photos as $photo)
                    <div class="bg-color-surface rounded-xl overflow-hidden shadow-sm">
                        <img
                            src="{{ $photoUrls[$photo->id] ?? '' }}"
                            alt="{{ __('result.inspection_photo') }}"
                            class="w-full h-auto aspect-[4/3] object-cover"
                            width="800"
                            height="600"
                            loading="lazy"
                        />
                        <div class="p-4">
                            <p class="text-xs text-color-muted">
                                {{ $photo->captured_at?->format(__('common.datetime_format')) ?? __('result.photo_not_timestamped') }}
                            </p>
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state
                        :icon="null"
                        :title="__('result.no_photos')"
                    />
                @endforelse
            </div>
        @else
            {{-- Limited summary + access button --}}
            <div class="space-y-6">
                <div class="bg-color-surface rounded-xl p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div>
                            <h1 class="text-2xl font-bold text-color-text">
                                {{ __('result.inspection_results') }}
                            </h1>
                            <p class="text-color-muted text-sm">
                                {{ $result->submitted_at?->format(__('common.date_format')) }}
                            </p>
                        </div>
                        <x-ui.badge-inspected :date="$result->submitted_at" />
                    </div>

                    {{-- Inspector info --}}
                    <div class="border-t border-color-border pt-4">
                        <p class="text-sm text-color-muted mb-2">{{ __('result.inspector') }}</p>
                        <p class="text-lg font-semibold text-color-text">{{ $result->inspector->name }}</p>
                    </div>

                    {{-- Recommendation if available --}}
                    @if ($result->recommendation)
                        <div class="border-t border-color-border pt-4 mt-4">
                            <p class="text-sm text-color-muted mb-2">{{ __('result.recommendation') }}</p>
                            <x-ui.status-chip
                                :label="$result->recommendation->label()"
                                :color="match ($result->recommendation) {
                                    InspectionRecommendation::Recommended => 'success',
                                    InspectionRecommendation::Caution => 'warning',
                                    InspectionRecommendation::NotRecommended => 'danger',
                                }"
                            />
                        </div>
                    @endif
                </div>

                {{-- Access button --}}
                @if ($canRequestAccess)
                    <div class="bg-color-bg rounded-xl p-6 border border-color-border">
                        <p class="text-color-text mb-4">
                            {{ __('result.access_required', ['cost' => \App\Support\Money::format($accessFee)]) }}
                        </p>
                        <x-ui.button
                            wire:click="$set('showAccessModal', true)"
                            wire:loading.attr="disabled"
                            class="w-full"
                        >
                            {{ __('result.open_results', ['cost' => \App\Support\Money::format($accessFee)]) }}
                        </x-ui.button>
                    </div>

                    {{-- Confirmation modal --}}
                    <x-ui.modal
                        :show="$showAccessModal"
                        @close="$set('showAccessModal', false)"
                    >
                        <div class="space-y-4">
                            <h2 class="text-xl font-bold text-color-text">
                                {{ __('result.confirm_access_payment') }}
                            </h2>
                            <p class="text-color-text">
                                {{ __('result.access_payment_description', ['cost' => \App\Support\Money::format($accessFee)]) }}
                            </p>
                            <div class="bg-color-bg rounded-lg p-4">
                                <p class="text-sm text-color-muted">{{ __('result.includes') }}</p>
                                <ul class="list-disc list-inside text-sm text-color-text mt-2 space-y-1">
                                    <li>{{ __('result.full_score_details') }}</li>
                                    <li>{{ __('result.complete_inspection_notes') }}</li>
                                    <li>{{ __('result.inspection_photos') }}</li>
                                </ul>
                            </div>
                            <div class="flex gap-3 pt-4">
                                <x-ui.button
                                    variant="secondary"
                                    @click="$set('showAccessModal', false)"
                                    class="flex-1"
                                >
                                    {{ __('common.cancel') }}
                                </x-ui.button>
                                <x-ui.button
                                    wire:click="requestAccess"
                                    wire:loading.attr="disabled"
                                    class="flex-1"
                                >
                                    {{ __('result.confirm_and_pay', ['cost' => \App\Support\Money::format($accessFee)]) }}
                                </x-ui.button>
                            </div>
                        </div>
                    </x-ui.modal>
                @else
                    <x-ui.alert variant="info">
                        {{ __('result.access_not_available') }}
                    </x-ui.alert>
                @endif
            </div>
        @endif
    </div>
</div>
