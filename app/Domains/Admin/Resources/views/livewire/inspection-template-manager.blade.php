@php
    $currentUser = auth()->user();
@endphp

<div class="min-h-screen bg-color-bg p-4 md:p-6">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-color-text mb-2">
                {{ __('admin.templates.title') }}
            </h1>
            <p class="text-color-muted">
                {{ __('admin.templates.subtitle') }}
            </p>
        </div>

        @if ($successMessage)
            <x-ui.alert variant="success" class="mb-6">
                {{ $successMessage }}
            </x-ui.alert>
        @endif

        @if ($errorMessage)
            <x-ui.alert variant="danger" class="mb-6">
                {{ $errorMessage }}
            </x-ui.alert>
        @endif

        <!-- Main Content Grid -->
        <div class="grid md:grid-cols-4 gap-6">
            <!-- Template Selection Sidebar -->
            <div class="md:col-span-1">
                <div class="space-y-4">
                    @forelse ($templates as $vehicleType => $templateList)
                        <div class="bg-color-surface rounded-xl p-4 border border-color-muted/20">
                            <h3 class="font-semibold text-color-text mb-3">
                                {{ $vehicleType }}
                            </h3>
                            <div class="space-y-2">
                                @foreach ($templateList as $template)
                                    <button
                                        wire:click="selectTemplate('{{ $template->id }}')"
                                        class="w-full text-left p-3 rounded-lg transition-colors
                                            {{ $selectedTemplate?->id === $template->id
                                                ? 'bg-color-primary text-white'
                                                : 'bg-color-muted/5 text-color-text hover:bg-color-muted/10'
                                            }}"
                                    >
                                        <div class="font-medium">{{ $template->name }}</div>
                                        <div class="text-sm {{ $selectedTemplate?->id === $template->id ? 'text-white opacity-90' : 'text-color-muted' }}">
                                            {{ __('admin.templates.version', ['version' => $template->version]) }}
                                            @if (!$template->is_active)
                                                <span class="text-xs opacity-75">({{ __('admin.templates.inactive') }})</span>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <x-ui.empty-state
                            icon="document"
                            title="{{ __('admin.templates.no_templates') }}"
                            description="{{ __('admin.templates.no_templates_description') }}"
                        />
                    @endforelse
                </div>
            </div>

            <!-- Items Editor -->
            <div class="md:col-span-3">
                @if ($selectedTemplate)
                    <div class="bg-color-surface rounded-xl p-6 border border-color-muted/20">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-color-text">
                                {{ $selectedTemplate->name }}
                            </h2>
                            <span class="text-sm font-medium px-2.5 py-0.5 rounded bg-color-primary/10 text-color-primary">
                                {{ __('admin.templates.version', ['version' => $selectedTemplate->version]) }}
                            </span>
                        </div>

                        <form wire:submit="saveItems" class="space-y-6">
                            @forelse ($items as $index => $item)
                                <div class="border border-color-muted/20 rounded-lg p-4 space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Label -->
                                        <div>
                                            <x-ui.input
                                                type="text"
                                                wire:model="items.{{ $index }}.label"
                                                label="{{ __('admin.templates.label') }}"
                                                required
                                            />
                                            @error("items.{$index}.label")
                                                <p class="text-sm text-color-danger mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- Weight -->
                                        <div>
                                            <x-ui.input
                                                type="number"
                                                wire:model.number="items.{{ $index }}.weight"
                                                label="{{ __('admin.templates.weight') }}"
                                                min="1"
                                                required
                                            />
                                            @error("items.{$index}.weight")
                                                <p class="text-sm text-color-danger mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Sort Order -->
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div>
                                            <x-ui.input
                                                type="number"
                                                wire:model.number="items.{{ $index }}.sort_order"
                                                label="{{ __('admin.templates.sort_order') }}"
                                                min="0"
                                                required
                                            />
                                            @error("items.{$index}.sort_order")
                                                <p class="text-sm text-color-danger mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- Checkboxes -->
                                        <div class="flex items-end">
                                            <x-ui.checkbox
                                                wire:model="items.{{ $index }}.is_required"
                                                label="{{ __('admin.templates.is_required') }}"
                                            />
                                        </div>

                                        <div class="flex items-end">
                                            <x-ui.checkbox
                                                wire:model="items.{{ $index }}.requires_photo"
                                                label="{{ __('admin.templates.requires_photo') }}"
                                            />
                                        </div>

                                        <div class="flex items-end">
                                            <x-ui.checkbox
                                                wire:model="items.{{ $index }}.is_critical"
                                                label="{{ __('admin.templates.is_critical') }}"
                                            />
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <x-ui.empty-state
                                    icon="exclamation"
                                    title="{{ __('admin.templates.no_items') }}"
                                    description="{{ __('admin.templates.no_items_description') }}"
                                />
                            @endforelse

                            @if ($items)
                                <div class="flex gap-3 pt-4">
                                    <x-ui.button type="submit" variant="primary">
                                        {{ __('admin.templates.save') }}
                                    </x-ui.button>
                                    <button
                                        type="button"
                                        wire:click="cancelEdit"
                                        class="px-4 py-2 text-color-text hover:bg-color-muted/10 rounded-lg transition-colors"
                                    >
                                        {{ __('admin.templates.cancel') }}
                                    </button>
                                </div>
                            @endif
                        </form>
                    </div>
                @else
                    <div class="bg-color-surface rounded-xl p-6 border border-color-muted/20 h-full flex items-center justify-center">
                        <x-ui.empty-state
                            icon="clipboard"
                            title="{{ __('admin.templates.empty_title') }}"
                            description="{{ __('admin.templates.empty_description') }}"
                        />
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
