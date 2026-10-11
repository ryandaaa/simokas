@assets
    @vite('resources/js/listing-map.js')
@endassets

<div class="ds-sans max-w-3xl mx-auto px-4 py-6 space-y-8 pb-12" x-data="{ newPhotoPreviewUrls: [], captureNewPhotoPreviews(event) { this.clearNewPhotoPreviews(); this.newPhotoPreviewUrls = Array.from(event.target.files || []).map((file) => URL.createObjectURL(file)); }, clearNewPhotoPreviews() { this.newPhotoPreviewUrls.forEach((url) => URL.revokeObjectURL(url)); this.newPhotoPreviewUrls = []; }, removeNewPhotoPreview(index) { const [url] = this.newPhotoPreviewUrls.splice(index, 1); if (url) URL.revokeObjectURL(url); }, destroy() { this.clearNewPhotoPreviews(); } }">
    <div class="space-y-2">
        <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">
            {{ __('listing.edit.title') }}
        </h1>
        <p class="text-sm text-ds-muted">
            {{ __('listing.edit.subtitle') }}
        </p>
    </div>

    @if (session()->has('success'))
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ session('success') }}
        </div>
    @endif

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

    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-xs uppercase font-extrabold tracking-wider text-ds-muted">{{ __("listing.edit.status_now") }}</span>
                @php
                    $statusPill = match ($listing->status->color()) {
                        'success' => 'bg-ds-green-soft text-ds-green-ink',
                        'warning' => 'bg-ds-amber-soft text-ds-amber-ink',
                        'danger' => 'bg-ds-red-soft text-ds-red-ink',
                        'primary' => 'bg-ds-blue-soft text-ds-blue-ink',
                        default => 'bg-ds-soft text-ds-muted',
                    };
                @endphp
                <span class="text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider {{ $statusPill }}">
                    {{ $listing->status->label() }}
                </span>
            </div>
            <div class="text-lg font-extrabold text-ds-ink">
                {{ $listing->title }}
            </div>
            <div class="text-xl font-extrabold text-ds-ink">
                {{ \App\Support\Money::format($listing->price) }}
            </div>
            @if ($listing->is_inspected && $listing->last_inspected_at)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-ds-green text-white">
                    <i aria-hidden="true" class="fa-solid fa-shield-halved"></i>
                    <span>{{ __('ui.badges.inspected') }}</span>
                    <span class="font-normal opacity-90 border-l border-white/30 pl-1.5 ml-0.5">
                        {{ $listing->last_inspected_at instanceof \Carbon\CarbonInterface ? $listing->last_inspected_at->translatedFormat('d M Y') : $listing->last_inspected_at }}
                    </span>
                </span>
            @endif
        </div>
    </div>

    @if ($listing->status->isRejected() && $listing->rejection_reason)
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <div class="space-y-4">
                <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40">
                    <p class="text-xs font-extrabold text-ds-ink">{{ __('listing.mine.rejection_reason') }}</p>
                    <p class="mt-1 text-xs text-ds-muted">{{ $listing->rejection_reason }}</p>
                </div>
                <button
                    type="button"
                    wire:click="resubmit"
                    wire:loading.attr="disabled"
                    wire:target="resubmit"
                    class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="resubmit">{{ __('listing.mine.resubmit') }}</span>
                    <span wire:loading wire:target="resubmit">{{ __('listing.mine.processing') }}</span>
                </button>
            </div>
        </div>
    @endif

    @if ($sold)
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <div role="status" class="p-4 rounded-2xl border border-ds-blue-soft bg-ds-blue-soft/40">
                <p class="text-xs font-extrabold text-ds-ink">{{ __('listing.status.sold') }}</p>
                <p class="mt-1 text-xs text-ds-muted">{{ __('listing.edit.sold_notice') }}</p>
            </div>
        </div>
    @else
        @if ($allLocked)
            <div role="alert" class="p-4 rounded-2xl border border-ds-amber-soft bg-ds-amber-soft/40 text-xs font-semibold text-ds-ink">
                {{ __('listing.edit.all_locked_notice') }}
            </div>
        @endif

        @if ($editable)
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h2 class="text-lg font-extrabold text-ds-ink">{{ __('listing.edit.section_vehicle') }}</h2>
            <div class="space-y-4 mt-4">
                <div role="alert" class="p-4 rounded-2xl border border-ds-amber-soft bg-ds-amber-soft/40">
                    <p class="text-xs font-extrabold text-ds-ink">{{ __('listing.edit.core_warning_title') }}</p>
                    <p class="mt-1 text-xs text-ds-muted">{{ __('listing.edit.core_warning') }}</p>
                </div>

                <p class="text-xs text-ds-muted">
                    <span class="inline-flex items-center rounded-full bg-ds-amber-soft text-ds-amber-ink px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">{{ __('listing.edit.core_badge') }}</span>
                    {{ __('listing.edit.core_warning_title') }}
                </p>

                @if ($coreLocked)
                    <div role="status" class="p-4 rounded-2xl border border-ds-blue-soft bg-ds-blue-soft/40 text-xs font-semibold text-ds-ink">
                        {{ __('listing.edit.core_locked_reason') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="w-full">
                        <label for="vehicle_type" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.vehicle_type') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                        </label>
                        <select id="vehicle_type" name="vehicle_type" wire:model="vehicle_type" @disabled($coreLocked || ! $canEdit) required
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                            <option value="car">{{ __('listing.vehicle_types.car') }}</option>
                            <option value="motorcycle">{{ __('listing.vehicle_types.motorcycle') }}</option>
                        </select>
                        <span class="inline-flex items-center rounded-full bg-ds-amber-soft text-ds-amber-ink px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider mt-1">{{ __('listing.edit.core_badge') }}</span>
                        @error('vehicle_type')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="w-full">
                        <label for="brand" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.brand') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                        </label>
                        <input type="text" id="brand" name="brand" wire:model="brand" list="brand-suggestions" @disabled($coreLocked || ! $canEdit) required
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                        <datalist id="brand-suggestions">
                            @foreach ($brandSuggestions as $suggestion)
                                <option value="{{ $suggestion }}"></option>
                            @endforeach
                        </datalist>
                        <span class="inline-flex items-center rounded-full bg-ds-amber-soft text-ds-amber-ink px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider mt-1">{{ __('listing.edit.core_badge') }}</span>
                        @error('brand')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="w-full">
                        <label for="model" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.model') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                        </label>
                        <input type="text" id="model" name="model" wire:model="model" @disabled($coreLocked || ! $canEdit) required
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                        <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.model_hint') }}</p>
                        <span class="inline-flex items-center rounded-full bg-ds-amber-soft text-ds-amber-ink px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider mt-1">{{ __('listing.edit.core_badge') }}</span>
                        @error('model')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="w-full">
                        <label for="year" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.year') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                        </label>
                        <input type="number" id="year" name="year" wire:model="year" @disabled($coreLocked || ! $canEdit) required
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                        <span class="inline-flex items-center rounded-full bg-ds-amber-soft text-ds-amber-ink px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider mt-1">{{ __('listing.edit.core_badge') }}</span>
                        @error('year')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="w-full">
                    <label for="title" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('listing.fields.title') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <input type="text" id="title" name="title" wire:model="title" @disabled(! $canEdit) required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                    <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.title_hint') }}</p>
                    @error('title')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="w-full">
                        <label for="price" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.price') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                        </label>
                        <input type="number" id="price" name="price" wire:model="price" @disabled(! $canEdit) required
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                        <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.price_hint') }}</p>
                        <p class="text-xs text-ds-muted mt-1">{{ __('listing.edit.price_offer_warning') }}</p>
                        @error('price')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="w-full">
                        <label for="odometer_km" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.odometer_km') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                        </label>
                        <input type="number" id="odometer_km" name="odometer_km" wire:model="odometer_km" @disabled($coreLocked || ! $canEdit) required
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                        <span class="inline-flex items-center rounded-full bg-ds-amber-soft text-ds-amber-ink px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider mt-1">{{ __('listing.edit.core_badge') }}</span>
                        @error('odometer_km')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="w-full">
                    <label for="negotiable" class="inline-flex items-center gap-2 text-sm text-ds-muted cursor-pointer min-h-[44px]">
                        <input type="checkbox" id="negotiable" name="negotiable" wire:model="negotiable" @disabled(! $canEdit)
                            class="w-4 h-4 rounded border-ds-line text-ds-blue focus:ring-ds-blue/30 disabled:opacity-60">
                        {{ __('listing.fields.negotiable_yes') }}
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="w-full">
                        <label for="transmission" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.transmission') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                        </label>
                        <select id="transmission" name="transmission" wire:model="transmission" @disabled(! $canEdit) required
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                            <option value="manual">{{ __('listing.transmissions.manual') }}</option>
                            <option value="automatic">{{ __('listing.transmissions.automatic') }}</option>
                        </select>
                        @error('transmission')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="w-full">
                        <label for="fuel" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.fuel') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                        </label>
                        <select id="fuel" name="fuel" wire:model="fuel" @disabled(! $canEdit) required
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                            <option value="petrol">{{ __('listing.fuel_types.petrol') }}</option>
                            <option value="diesel">{{ __('listing.fuel_types.diesel') }}</option>
                            <option value="electric">{{ __('listing.fuel_types.electric') }}</option>
                            <option value="hybrid">{{ __('listing.fuel_types.hybrid') }}</option>
                        </select>
                        @error('fuel')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="w-full">
                        <label for="color" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.color') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                        </label>
                        <input type="text" id="color" name="color" wire:model="color" @disabled(! $canEdit) required
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                        @error('color')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="w-full">
                        <label for="chassis_number" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.chassis_number') }}
                        </label>
                        <input type="text" id="chassis_number" name="chassis_number" wire:model="chassis_number" @disabled($coreLocked || ! $canEdit)
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                        <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.chassis_number_hint') }}</p>
                        <span class="inline-flex items-center rounded-full bg-ds-amber-soft text-ds-amber-ink px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider mt-1">{{ __('listing.edit.core_badge') }}</span>
                        @error('chassis_number')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="w-full">
                    <label for="description" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('listing.fields.description') }}
                    </label>
                    <textarea id="description" name="description" wire:model="description" rows="4" @disabled(! $canEdit)
                        class="w-full px-4 py-3 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60"></textarea>
                    <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.description_hint') }}</p>
                    @error('description')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h2 class="text-lg font-extrabold text-ds-ink">{{ __('listing.edit.section_photos') }}</h2>
            <div class="space-y-4 mt-4">
                <p class="text-xs text-ds-muted">{{ __('listing.fields.photos_hint') }}</p>
                @if ($errors->has('newPhotos') || $errors->has('newPhotos.*'))
                    <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
                        {{ $errors->first('newPhotos') ?: $errors->first('newPhotos.*') }}
                    </div>
                @endif

                <div>
                    <h3 class="text-sm font-extrabold text-ds-ink">{{ __('listing.edit.existing_photos') }}</h3>
                    <div class="grid grid-cols-3 gap-3 pt-2">
                        @foreach ($listing->photos as $photo)
                            @if (! in_array($photo->id, $removePhotoIds, true))
                                <div class="relative rounded-2xl overflow-hidden border border-ds-line/80 bg-white shadow-xs">
                                    <img
                                        src="{{ route('listings.photos.show', ['listing' => $listing->id, 'photo' => $photo->id]) }}"
                                        alt="{{ $listing->title }}"
                                        class="w-full h-24 object-cover"
                                        loading="lazy"
                                    />
                                    @if ($photo->is_cover || $coverPhotoId === $photo->id)
                                        <span class="absolute top-1 left-1 inline-flex items-center rounded-full bg-ds-yellow text-ds-ink px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">{{ __('listing.edit.is_cover') }}</span>
                                    @endif
                                    @if ($canEdit)
                                        <div class="flex gap-1 p-1">
                                            <button
                                                type="button"
                                                wire:click="setCoverPhoto('{{ $photo->id }}')"
                                                wire:loading.attr="disabled"
                                                class="text-xs font-bold text-ds-blue hover:text-ds-blue-ink px-2 py-1"
                                            >
                                                {{ __('listing.edit.set_as_cover') }}
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="removeExistingPhoto('{{ $photo->id }}')"
                                                wire:loading.attr="disabled"
                                                class="text-xs font-bold text-ds-danger hover:underline px-2 py-1"
                                            >
                                                {{ __('listing.edit.remove') }}
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                @if ($canEdit)
                    <div class="w-full">
                        <label for="newPhotos" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.edit.add_photos') }}
                        </label>
                        <input
                            id="newPhotos"
                            type="file"
                            multiple
                            accept="image/jpeg,image/png,image/webp"
                            wire:model="newPhotos"
                            @change="captureNewPhotoPreviews($event)"
                            class="w-full min-h-[44px] px-3 py-2.5 rounded-xl text-sm bg-white text-ds-ink border border-ds-line file:mr-3 file:px-4 file:py-2 file:rounded-xl file:border-0 file:bg-ds-blue file:text-white file:text-xs file:font-bold"
                        />
                    </div>

                    @if (count($newPhotos) > 0)
                        <div class="grid grid-cols-3 gap-3">
                            @foreach ($newPhotos as $index => $upload)
                                <div class="relative rounded-2xl overflow-hidden border border-ds-line/80 bg-white shadow-xs p-1">
                                    @if (is_object($upload) && method_exists($upload, 'temporaryUrl'))
                                        <img x-bind:src="newPhotoPreviewUrls[{{ $index }}]" alt="" class="w-full h-24 object-cover rounded-xl" />
                                    @endif
                                    @if ($coverNewPhotoIndex === $index)
                                        <span class="absolute top-1 left-1 inline-flex items-center rounded-full bg-ds-yellow text-ds-ink px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">{{ __('listing.edit.is_cover') }}</span>
                                    @endif
                                    <div class="flex gap-1 p-1">
                                        <button
                                            type="button"
                                            wire:click="setCoverNewPhoto({{ $index }})"
                                            wire:loading.attr="disabled"
                                            class="text-xs font-bold text-ds-blue hover:text-ds-blue-ink px-2 py-1"
                                        >
                                            {{ __('listing.edit.set_as_cover') }}
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="removeNewPhoto({{ $index }})"
                                            @click="removeNewPhotoPreview({{ $index }})"
                                            wire:loading.attr="disabled"
                                            class="text-xs font-bold text-ds-danger hover:underline px-2 py-1"
                                        >
                                            {{ __('listing.edit.remove') }}
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h2 class="text-lg font-extrabold text-ds-ink">{{ __('listing.edit.section_location') }}</h2>
            <div class="space-y-4 mt-4">
                <div>
                    <h3 class="text-sm font-extrabold text-ds-ink">
                        {{ __('listing.fields.location_title') }}
                    </h3>
                    <p class="text-xs text-ds-muted">
                        {{ __('listing.fields.location_hint') }}
                    </p>
                </div>

                @include('listing::partials.location-picker', ['lat' => $lat, 'lng' => $lng, 'mapCentre' => $mapCentre])

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="w-full">
                        <label for="lat" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.lat') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                        </label>
                        <input type="text" id="lat" name="lat" inputmode="decimal" wire:model="lat" @disabled(! $canEdit) required
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                        <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.lat_hint') }}</p>
                        @error('lat')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="w-full">
                        <label for="lng" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.lng') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                        </label>
                        <input type="text" id="lng" name="lng" inputmode="decimal" wire:model="lng" @disabled(! $canEdit) required
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                        <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.lng_hint') }}</p>
                        @error('lng')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="w-full">
                    <label for="address_full" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('listing.fields.address_full') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <textarea id="address_full" name="address_full" wire:model="address_full" rows="3" @disabled(! $canEdit) required
                        class="w-full px-4 py-3 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60"></textarea>
                    <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.address_full_hint') }}</p>
                    @error('address_full')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="area_name" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('listing.fields.area_name') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <input type="text" id="area_name" name="area_name" wire:model="area_name" @disabled(! $canEdit) required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                    <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.area_name_hint') }}</p>
                    @error('area_name')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="inspection_place" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('listing.fields.inspection_place') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <input type="text" id="inspection_place" name="inspection_place" wire:model="inspection_place" @disabled(! $canEdit) required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                    <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.inspection_place_hint') }}</p>
                    @error('inspection_place')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h2 class="text-lg font-extrabold text-ds-ink">{{ __('listing.edit.section_slots') }}</h2>
            <div class="space-y-3 mt-4">
                <p class="text-xs text-ds-muted">{{ __('listing.fields.inspection_slots_hint') }}</p>
                @foreach ($inspection_slots as $index => $slot)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end rounded-2xl border border-ds-line/80 bg-ds-canvas/60 p-3">
                        <div class="w-full">
                            <label for="slot-start-{{ $index }}" class="block text-xs font-bold text-ds-ink mb-1.5">
                                {{ __('listing.fields.slot_time') }}
                                <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                                <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                            </label>
                            <input type="datetime-local" id="slot-start-{{ $index }}" name="inspection_slots[{{ $index }}][slot_start]" wire:model="inspection_slots.{{ $index }}.slot_start" @disabled(! $canEdit) required
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                            @error('inspection_slots.' . $index . '.slot_start')
                                <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex gap-2 items-end">
                            <div class="flex-1 w-full">
                                <label for="slot-note-{{ $index }}" class="block text-xs font-bold text-ds-ink mb-1.5">
                                    {{ __('listing.fields.slot_note') }}
                                </label>
                                <input type="text" id="slot-note-{{ $index }}" name="inspection_slots[{{ $index }}][note]" wire:model="inspection_slots.{{ $index }}.note" @disabled(! $canEdit)
                                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-60">
                                @error('inspection_slots.' . $index . '.note')
                                    <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                            @if ($canEdit && count($inspection_slots) > 1)
                                <button type="button" wire:click="removeSlot({{ $index }})" wire:loading.attr="disabled"
                                    class="text-xs font-bold text-ds-danger hover:underline px-2 py-1 min-h-[44px]">
                                    {{ __('listing.fields.remove_slot') }}
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
                @if ($canEdit)
                    <button type="button" wire:click="addSlot" wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs">
                        <i aria-hidden="true" class="fa-solid fa-plus text-xs"></i>
                        {{ __('listing.fields.add_slot') }}
                    </button>
                @endif
            </div>
        </div>
        @endif

        @if ($canEdit && $editable)
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <div class="flex flex-col sm:flex-row gap-3">
                    <button
                        type="button"
                        wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="save">{{ __('listing.edit.save') }}</span>
                        <span wire:loading wire:target="save">{{ __('listing.edit.saving') }}</span>
                    </button>

                    @if ($listing->status->isActive())
                        <button
                            type="button"
                            wire:click="confirmDeactivate"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 font-bold text-sm rounded-xl transition-all shadow-xs disabled:opacity-60"
                        >
                            {{ __('listing.edit.deactivate') }}
                        </button>
                    @endif
                </div>
            </div>
        @endif

        @if ($reactivatable)
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <button
                    type="button"
                    wire:click="confirmReactivate"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                >
                    {{ __('listing.edit.reactivate') }}
                </button>
            </div>
        @endif

        @if (! $editable && ! $reactivatable && ! $allLocked)
            <div role="status" class="p-4 rounded-2xl border border-ds-blue-soft bg-ds-blue-soft/40 text-xs font-semibold text-ds-ink">
                {{ __('listing.errors.not_editable', ['status' => $listing->status->value]) }}
            </div>
        @endif
    @endif

    <div class="flex justify-between items-center pt-2">
        <a href="{{ route('listings.mine') }}"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs">
            <i aria-hidden="true" class="fa-solid fa-arrow-left text-xs"></i>
            {{ __('listing.edit.back_to_mine') }}
        </a>
    </div>

    @if ($confirmingSave)
        <x-ui.modal
            name="modal-confirm-core-save"
            :show="true"
            :title="__('listing.edit.confirm_core_title')"
            :description="__('listing.edit.confirm_core_description')"
        >
            <div class="p-2 text-sm text-text">
                <p>{{ __('listing.edit.confirm_core_description') }}</p>
            </div>

            <x-slot:footer>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    wire:click="cancelSave"
                >
                    {{ __('listing.edit.cancel') }}
                </x-ui.button>
                <x-ui.button
                    type="button"
                    variant="primary"
                    wire:click="confirmSave"
                    wire:loading.attr="disabled"
                    wire:target="confirmSave"
                >
                    <span wire:loading.remove wire:target="confirmSave">{{ __('listing.edit.confirm_core_yes') }}</span>
                    <span wire:loading wire:target="confirmSave">{{ __('listing.edit.saving') }}</span>
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    @if ($confirmingDeactivate)
        <x-ui.modal
            name="modal-deactivate-listing"
            :show="true"
            :title="__('listing.edit.confirm_deactivate_title')"
            :description="__('listing.edit.confirm_deactivate_description')"
        >
            <div class="p-2 text-sm text-text">
                <p>{{ __('listing.edit.confirm_deactivate_description') }}</p>
            </div>

            <x-slot:footer>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    wire:click="cancelDeactivate"
                >
                    {{ __('listing.edit.cancel') }}
                </x-ui.button>
                <x-ui.button
                    type="button"
                    variant="danger"
                    wire:click="deactivate"
                    wire:loading.attr="disabled"
                    wire:target="deactivate"
                >
                    <span wire:loading.remove wire:target="deactivate">{{ __('listing.edit.confirm_deactivate_yes') }}</span>
                    <span wire:loading wire:target="deactivate">{{ __('listing.edit.deactivating') }}</span>
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    @if ($confirmingReactivate)
        <x-ui.modal
            name="modal-reactivate-listing"
            :show="true"
            :title="__('listing.edit.confirm_reactivate_title')"
            :description="__('listing.edit.confirm_reactivate_description')"
        >
            <div class="p-2 text-sm text-text">
                <p>{{ __('listing.edit.confirm_reactivate_description') }}</p>
            </div>

            <x-slot:footer>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    wire:click="cancelReactivate"
                >
                    {{ __('listing.edit.cancel') }}
                </x-ui.button>
                <x-ui.button
                    type="button"
                    variant="primary"
                    wire:click="reactivate"
                    wire:loading.attr="disabled"
                    wire:target="reactivate"
                >
                    <span wire:loading.remove wire:target="reactivate">{{ __('listing.edit.confirm_reactivate_yes') }}</span>
                    <span wire:loading wire:target="reactivate">{{ __('listing.edit.reactivating') }}</span>
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
