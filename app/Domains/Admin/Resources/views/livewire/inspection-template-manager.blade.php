@php
    $currentUser = auth()->user();
@endphp

<div class="ds-sans min-h-screen p-4 md:p-6">
    <div class="max-w-6xl mx-auto space-y-8 pb-12">
        <!-- Header -->
        <div class="space-y-2">
            <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">
                {{ __('admin.templates.title') }}
            </h1>
            <p class="text-sm text-ds-muted">
                {{ __('admin.templates.subtitle') }}
            </p>
        </div>

        @if ($successMessage)
            <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
                {{ $successMessage }}
            </div>
        @endif

        @if ($errorMessage)
            <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
                {{ $errorMessage }}
            </div>
        @endif

        <!-- Main Content Grid -->
        <div class="grid md:grid-cols-4 gap-6">
            <!-- Template Selection Sidebar -->
            <div class="md:col-span-1">
                <div class="space-y-4">
                    @forelse ($templates as $vehicleType => $templateList)
                        <div class="bg-white rounded-2xl p-4 border border-ds-line/80 shadow-xs">
                            <h3 class="font-extrabold text-ds-ink mb-3">
                                {{ $vehicleType }}
                            </h3>
                            <div class="space-y-2">
                                @foreach ($templateList as $template)
                                    <button
                                        wire:click="selectTemplate('{{ $template->id }}')"
                                        aria-pressed="{{ $selectedTemplate?->id === $template->id ? 'true' : 'false' }}"
                                        class="w-full text-left p-3 rounded-xl transition-colors min-h-[44px]
                                            {{ $selectedTemplate?->id === $template->id
                                                ? 'bg-ds-blue text-white shadow-md'
                                                : 'bg-ds-canvas/60 text-ds-ink hover:bg-ds-blue-soft/40'
                                            }}"
                                    >
                                        <div class="font-bold text-sm">{{ $template->name }}</div>
                                        <div class="text-xs mt-0.5 {{ $selectedTemplate?->id === $template->id ? 'text-white opacity-90' : 'text-ds-muted' }}">
                                            {{ __('admin.templates.version', ['version' => $template->version]) }}
                                            @if (!$template->is_active)
                                                <span class="opacity-75">({{ __('admin.templates.inactive') }})</span>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center">
                            <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                                <i aria-hidden="true" class="fa-solid fa-file-lines"></i>
                            </div>
                            <h3 class="text-base font-extrabold text-ds-ink">{{ __('admin.templates.no_templates') }}</h3>
                            <p class="text-xs text-ds-muted mt-2">{{ __('admin.templates.no_templates_description') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Items Editor -->
            <div class="md:col-span-3">
                @if ($selectedTemplate)
                    <div class="bg-white rounded-2xl p-6 border border-ds-line/80 shadow-xs">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-extrabold text-ds-ink">
                                {{ $selectedTemplate->name }}
                            </h2>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-ds-blue-soft text-ds-blue-ink">
                                {{ __('admin.templates.version', ['version' => $selectedTemplate->version]) }}
                            </span>
                        </div>

                        <form wire:submit="saveItems" class="space-y-6">
                            @forelse ($items as $index => $item)
                                <div class="border border-ds-line/80 rounded-2xl p-4 space-y-4 bg-ds-canvas/40">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Label -->
                                        <div class="w-full">
                                            <label for="item-label-{{ $index }}" class="block text-xs font-bold text-ds-ink mb-1.5">
                                                {{ __('admin.templates.label') }}
                                                <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                                                <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                                            </label>
                                            <input type="text" id="item-label-{{ $index }}" wire:model="items.{{ $index }}.label" required
                                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                                            @error("items.{$index}.label")
                                                <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- Weight -->
                                        <div class="w-full">
                                            <label for="item-weight-{{ $index }}" class="block text-xs font-bold text-ds-ink mb-1.5">
                                                {{ __('admin.templates.weight') }}
                                                <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                                                <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                                            </label>
                                            <input type="number" id="item-weight-{{ $index }}" wire:model.number="items.{{ $index }}.weight" min="1" required
                                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                                            @error("items.{$index}.weight")
                                                <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Sort Order -->
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div class="w-full">
                                            <label for="item-sort-{{ $index }}" class="block text-xs font-bold text-ds-ink mb-1.5">
                                                {{ __('admin.templates.sort_order') }}
                                                <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                                                <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                                            </label>
                                            <input type="number" id="item-sort-{{ $index }}" wire:model.number="items.{{ $index }}.sort_order" min="0" required
                                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                                            @error("items.{$index}.sort_order")
                                                <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- Checkboxes -->
                                        <div class="flex items-end pb-2">
                                            <label class="inline-flex items-center gap-2 text-sm text-ds-ink cursor-pointer">
                                                <input type="checkbox" wire:model="items.{{ $index }}.is_required"
                                                    class="w-4 h-4 rounded border-ds-line text-ds-blue focus:ring-ds-blue/30">
                                                {{ __('admin.templates.is_required') }}
                                            </label>
                                        </div>

                                        <div class="flex items-end pb-2">
                                            <label class="inline-flex items-center gap-2 text-sm text-ds-ink cursor-pointer">
                                                <input type="checkbox" wire:model="items.{{ $index }}.requires_photo"
                                                    class="w-4 h-4 rounded border-ds-line text-ds-blue focus:ring-ds-blue/30">
                                                {{ __('admin.templates.requires_photo') }}
                                            </label>
                                        </div>

                                        <div class="flex items-end pb-2">
                                            <label class="inline-flex items-center gap-2 text-sm text-ds-ink cursor-pointer">
                                                <input type="checkbox" wire:model="items.{{ $index }}.is_critical"
                                                    class="w-4 h-4 rounded border-ds-line text-ds-blue focus:ring-ds-blue/30">
                                                {{ __('admin.templates.is_critical') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="bg-white rounded-2xl border border-ds-line/80 p-8 text-center">
                                    <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-amber-soft text-ds-amber-ink flex items-center justify-center text-xl mb-4">
                                        <i aria-hidden="true" class="fa-solid fa-triangle-exclamation"></i>
                                    </div>
                                    <h3 class="text-base font-extrabold text-ds-ink">{{ __('admin.templates.no_items') }}</h3>
                                    <p class="text-xs text-ds-muted mt-2">{{ __('admin.templates.no_items_description') }}</p>
                                </div>
                            @endforelse

                            @if ($items)
                                <div class="flex gap-3 pt-4">
                                    <button type="submit"
                                        class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                                        {{ __('admin.templates.save') }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="cancelEdit"
                                        class="min-h-[44px] inline-flex items-center px-4 py-2 text-ds-muted hover:text-ds-ink text-sm font-bold transition-colors"
                                    >
                                        {{ __('admin.templates.cancel') }}
                                    </button>
                                </div>
                            @endif
                        </form>
                    </div>
                @else
                    <div class="bg-white rounded-2xl p-6 border border-ds-line/80 shadow-xs h-full flex items-center justify-center">
                        <div class="text-center py-8">
                            <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                                <i aria-hidden="true" class="fa-solid fa-clipboard-list"></i>
                            </div>
                            <h3 class="text-base font-extrabold text-ds-ink">{{ __('admin.templates.empty_title') }}</h3>
                            <p class="text-xs text-ds-muted mt-2">{{ __('admin.templates.empty_description') }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
