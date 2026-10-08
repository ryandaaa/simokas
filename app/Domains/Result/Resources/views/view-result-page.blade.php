@php
use App\Domains\Result\Enums\InspectionRecommendation;
@endphp

<div class="ds-sans min-h-screen py-8">
    <div class="max-w-2xl mx-auto px-4 space-y-8 pb-12">
        @if ($hasAccess)
            {{-- Full result view --}}
            <div class="space-y-8">
                {{-- Header with recommendation --}}
                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div class="space-y-1">
                            <h1 class="text-2xl font-extrabold text-ds-ink tracking-tight">
                                {{ __('result.inspection_results') }}
                            </h1>
                            <p class="text-ds-muted text-sm">
                                {{ $result->submitted_at?->format(__('common.date_format')) }}
                            </p>
                        </div>
                        {{-- Recommendation badge --}}
                        @if ($result->recommendation)
                            <div class="flex-shrink-0">
                                @php
                                    $recPill = match ($result->recommendation) {
                                        InspectionRecommendation::Recommended => 'bg-ds-green-soft text-ds-green-ink',
                                        InspectionRecommendation::Caution => 'bg-ds-amber-soft text-ds-amber-ink',
                                        InspectionRecommendation::NotRecommended => 'bg-ds-red-soft text-ds-red-ink',
                                    };
                                @endphp
                                <span class="text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider {{ $recPill }}">
                                    {{ $result->recommendation->label() }}
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Inspector info --}}
                    <div class="border-t border-ds-line/60 pt-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-ds-muted mb-1">{{ __('result.inspector') }}</p>
                            <p class="text-lg font-extrabold text-ds-ink">{{ $result->inspector->name }}</p>
                        </div>
                        @if (isset($inspectorRating) && $inspectorRating['ratingCount'] > 0)
                            <div class="flex items-center gap-1.5 bg-ds-yellow-soft px-3 py-1.5 rounded-full">
                                <i aria-hidden="true" class="fa-solid fa-star text-ds-yellow-deep text-sm"></i>
                                <span class="font-extrabold text-sm text-ds-ink">{{ number_format($inspectorRating['ratingAvg'], 2) }}</span>
                                <span class="text-xs text-ds-muted">({{ $inspectorRating['ratingCount'] }})</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Score --}}
                @if ($result->total_score !== null)
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                        <h2 class="text-lg font-extrabold text-ds-ink mb-4">{{ __('result.score') }}</h2>
                        <div class="flex items-center gap-6">
                            <div class="text-5xl font-extrabold text-ds-blue">
                                {{ number_format($result->total_score, 1) }}
                            </div>
                            <div>
                                <p class="text-sm text-ds-muted">{{ __('result.out_of_100') }}</p>
                                <p class="text-base font-bold text-ds-ink mt-2">
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
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                        <h3 class="font-extrabold text-ds-ink mb-3">{{ __('result.summary') }}</h3>
                        <p class="text-ds-ink text-sm whitespace-pre-wrap leading-relaxed">{{ $result->summary_note }}</p>
                    </div>
                @endif

                {{-- Items by category --}}
                @forelse ($result->items->groupBy('template_item_id') as $groupedItems)
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                        <div class="space-y-4">
                            @foreach ($groupedItems as $item)
                                <div class="flex items-start justify-between gap-4 border-b border-ds-line/60 pb-4 last:border-b-0">
                                    <div>
                                        <p class="font-bold text-ds-ink text-sm">{{ $item->templateItem?->label ?? __('result.unnamed_item') }}</p>
                                        @if ($item->note)
                                            <p class="text-xs text-ds-muted mt-1">{{ $item->note }}</p>
                                        @endif
                                    </div>
                                    @php
                                        $itemPill = match ($item->rating->value) {
                                            'good' => 'bg-ds-green-soft text-ds-green-ink',
                                            'okay' => 'bg-ds-amber-soft text-ds-amber-ink',
                                            'bad' => 'bg-ds-red-soft text-ds-red-ink',
                                            default => 'bg-ds-soft text-ds-muted',
                                        };
                                    @endphp
                                    <span class="text-[10px] px-2 py-0.5 rounded-md font-bold shrink-0 {{ $itemPill }}">
                                        {{ $item->rating->label() }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center">
                        <p class="text-sm font-bold text-ds-ink">{{ __('result.no_items') }}</p>
                    </div>
                @endforelse

                {{-- Photos --}}
                @forelse ($result->photos as $photo)
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs overflow-hidden">
                        <img
                            src="{{ $photoUrls[$photo->id] ?? '' }}"
                            alt="{{ __('result.inspection_photo') }}"
                            class="w-full h-auto aspect-4/3 object-cover"
                            width="800"
                            height="600"
                            loading="lazy"
                        />
                        <div class="p-4">
                            <p class="text-xs text-ds-muted">
                                {{ $photo->captured_at?->format(__('common.datetime_format')) ?? __('result.photo_not_timestamped') }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center">
                        <p class="text-sm font-bold text-ds-ink">{{ __('result.no_photos') }}</p>
                    </div>
                @endforelse
            </div>
        @else
            {{-- Limited summary + access button --}}
            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div class="space-y-1">
                            <h1 class="text-2xl font-extrabold text-ds-ink tracking-tight">
                                {{ __('result.inspection_results') }}
                            </h1>
                            <p class="text-ds-muted text-sm">
                                {{ $result->submitted_at?->format(__('common.date_format')) }}
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-ds-green text-white shrink-0">
                            <i aria-hidden="true" class="fa-solid fa-shield-halved"></i>
                            <span>{{ __('ui.badges.inspected') }}</span>
                        </span>
                    </div>

                    {{-- Inspector info --}}
                    <div class="border-t border-ds-line/60 pt-4">
                        <p class="text-sm text-ds-muted mb-2">{{ __('result.inspector') }}</p>
                        <p class="text-lg font-extrabold text-ds-ink">{{ $result->inspector->name }}</p>
                    </div>

                    {{-- Recommendation if available --}}
                    @if ($result->recommendation)
                        <div class="border-t border-ds-line/60 pt-4 mt-4">
                            <p class="text-sm text-ds-muted mb-2">{{ __('result.recommendation') }}</p>
                            @php
                                $recPill = match ($result->recommendation) {
                                    InspectionRecommendation::Recommended => 'bg-ds-green-soft text-ds-green-ink',
                                    InspectionRecommendation::Caution => 'bg-ds-amber-soft text-ds-amber-ink',
                                    InspectionRecommendation::NotRecommended => 'bg-ds-red-soft text-ds-red-ink',
                                };
                            @endphp
                            <span class="text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider {{ $recPill }}">
                                {{ $result->recommendation->label() }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Access button --}}
                @if ($canRequestAccess)
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                        <p class="text-sm text-ds-ink mb-4">
                            {{ __('result.access_required', ['cost' => \App\Support\Money::format($accessFee)]) }}
                        </p>
                        <button
                            wire:click="$set('showAccessModal', true)"
                            wire:loading.attr="disabled"
                            class="w-full inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                        >
                            {{ __('result.open_results', ['cost' => \App\Support\Money::format($accessFee)]) }}
                        </button>
                    </div>

                    {{-- Confirmation modal --}}
                    <x-ui.modal
                        :show="$showAccessModal"
                        @close="$set('showAccessModal', false)"
                    >
                        <div class="space-y-4">
                            <h2 class="text-xl font-extrabold text-ds-ink">
                                {{ __('result.confirm_access_payment') }}
                            </h2>
                            <p class="text-sm text-ds-ink">
                                {{ __('result.access_payment_description', ['cost' => \App\Support\Money::format($accessFee)]) }}
                            </p>
                            <div class="bg-ds-canvas/60 rounded-2xl p-4 border border-ds-line/80">
                                <p class="text-sm text-ds-muted">{{ __('result.includes') }}</p>
                                <ul class="list-disc list-inside text-sm text-ds-ink mt-2 space-y-1">
                                    <li>{{ __('result.full_score_details') }}</li>
                                    <li>{{ __('result.complete_inspection_notes') }}</li>
                                    <li>{{ __('result.inspection_photos') }}</li>
                                </ul>
                            </div>
                            <div class="flex gap-3 pt-4">
                                <button
                                    @click="$set('showAccessModal', false)"
                                    class="flex-1 inline-flex items-center justify-center px-5 py-2.5 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs"
                                >
                                    {{ __('common.cancel') }}
                                </button>
                                <button
                                    wire:click="requestAccess"
                                    wire:loading.attr="disabled"
                                    class="flex-1 inline-flex items-center justify-center px-5 py-2.5 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-xs rounded-xl transition-all shadow-md disabled:opacity-60"
                                >
                                    {{ __('result.confirm_and_pay', ['cost' => \App\Support\Money::format($accessFee)]) }}
                                </button>
                            </div>
                        </div>
                    </x-ui.modal>
                @else
                    <div role="status" class="p-4 rounded-2xl border border-ds-blue-soft bg-ds-blue-soft/40 text-xs font-semibold text-ds-ink">
                        {{ __('result.access_not_available') }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
