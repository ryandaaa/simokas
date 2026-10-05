<div class="max-w-3xl mx-auto px-4 py-6 space-y-6">
    <div class="space-y-1">
        <h1 class="text-2xl sm:text-3xl font-bold text-text">
            {{ __('listing.edit.title') }}
        </h1>
        <p class="text-sm text-muted">
            {{ __('listing.edit.subtitle') }}
        </p>
    </div>

    @if (session()->has('success'))
        <x-ui.alert variant="success" :message="session('success')" dismissible />
    @endif

    @if ($successMessage)
        <x-ui.alert variant="success" :message="$successMessage" />
    @endif

    @if ($errorMessage)
        <x-ui.alert variant="danger" :message="$errorMessage" />
    @endif

    <x-ui.card>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-xs uppercase font-bold text-muted">Status Saat Ini</span>
                <x-ui.status-chip
                    :label="$listing->status->label()"
                    :variant="$listing->status->color()"
                    :icon="$listing->status->icon()"
                />
            </div>
            <div class="text-lg font-bold text-text">
                {{ $listing->title }}
            </div>
            <div class="text-sm text-primary font-semibold">
                <x-ui.money :amount="$listing->price" />
            </div>
            @if ($listing->is_inspected && $listing->last_inspected_at)
                <x-ui.badge-inspected :date="$listing->last_inspected_at" />
            @endif
        </div>
    </x-ui.card>

    @if ($listing->status->isRejected() && $listing->rejection_reason)
        <x-ui.card>
            <div class="space-y-4">
                <x-ui.alert
                    variant="danger"
                    :title="__('listing.mine.rejection_reason')"
                    :message="$listing->rejection_reason"
                />
                <x-ui.button
                    type="button"
                    variant="primary"
                    wire:click="resubmit"
                    wire:loading.attr="disabled"
                    wire:target="resubmit"
                >
                    <span wire:loading.remove wire:target="resubmit">{{ __('listing.mine.resubmit') }}</span>
                    <span wire:loading wire:target="resubmit">{{ __('listing.mine.processing') }}</span>
                </x-ui.button>
            </div>
        </x-ui.card>
    @endif

    @if ($sold)
        <x-ui.card>
            <x-ui.alert
                variant="info"
                :title="__('listing.status.sold')"
                :message="__('listing.edit.sold_notice')"
            />
        </x-ui.card>
    @else
        @if ($allLocked)
            <x-ui.alert
                variant="warning"
                :message="__('listing.edit.all_locked_notice')"
            />
        @endif

        @if ($editable)
        <x-ui.card :title="__('listing.edit.section_vehicle')">
            <div class="space-y-4">
                <x-ui.alert
                    variant="warning"
                    :title="__('listing.edit.core_warning_title')"
                    :message="__('listing.edit.core_warning')"
                />

                <p class="text-xs text-muted">
                    <span class="inline-flex items-center rounded-full bg-warning/10 text-warning px-2 py-0.5 text-xs font-bold">{{ __('listing.edit.core_badge') }}</span>
                    {{ __('listing.edit.core_warning_title') }}
                </p>

                @if ($coreLocked)
                    <x-ui.alert
                        variant="info"
                        :message="__('listing.edit.core_locked_reason')"
                    />
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-ui.select
                            id="vehicle_type"
                            name="vehicle_type"
                            wire:model="vehicle_type"
                            :label="__('listing.fields.vehicle_type')"
                            :options="[
                                'car' => __('listing.vehicle_types.car'),
                                'motorcycle' => __('listing.vehicle_types.motorcycle'),
                            ]"
                            :error="$errors->first('vehicle_type')"
                            :disabled="$coreLocked || ! $canEdit"
                            required
                        />
                        <span class="inline-flex items-center rounded-full bg-warning/10 text-warning px-2 py-0.5 text-xs font-bold mt-1">{{ __('listing.edit.core_badge') }}</span>
                    </div>
                    <div>
                        <x-ui.input
                            id="brand"
                            name="brand"
                            wire:model="brand"
                            list="brand-suggestions"
                            :label="__('listing.fields.brand')"
                            :error="$errors->first('brand')"
                            :disabled="$coreLocked || ! $canEdit"
                            required
                        />
                        <datalist id="brand-suggestions">
                            @foreach ($brandSuggestions as $suggestion)
                                <option value="{{ $suggestion }}"></option>
                            @endforeach
                        </datalist>
                        <span class="inline-flex items-center rounded-full bg-warning/10 text-warning px-2 py-0.5 text-xs font-bold mt-1">{{ __('listing.edit.core_badge') }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-ui.input
                            id="model"
                            name="model"
                            wire:model="model"
                            :label="__('listing.fields.model')"
                            :hint="__('listing.fields.model_hint')"
                            :error="$errors->first('model')"
                            :disabled="$coreLocked || ! $canEdit"
                            required
                        />
                        <span class="inline-flex items-center rounded-full bg-warning/10 text-warning px-2 py-0.5 text-xs font-bold mt-1">{{ __('listing.edit.core_badge') }}</span>
                    </div>
                    <div>
                        <x-ui.input
                            id="year"
                            name="year"
                            type="number"
                            wire:model="year"
                            :label="__('listing.fields.year')"
                            :error="$errors->first('year')"
                            :disabled="$coreLocked || ! $canEdit"
                            required
                        />
                        <span class="inline-flex items-center rounded-full bg-warning/10 text-warning px-2 py-0.5 text-xs font-bold mt-1">{{ __('listing.edit.core_badge') }}</span>
                    </div>
                </div>

                <div>
                    <x-ui.input
                        id="title"
                        name="title"
                        wire:model="title"
                        :label="__('listing.fields.title')"
                        :hint="__('listing.fields.title_hint')"
                        :error="$errors->first('title')"
                        :disabled="! $canEdit"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-ui.input
                            id="price"
                            name="price"
                            type="number"
                            wire:model="price"
                            :label="__('listing.fields.price')"
                            :hint="__('listing.fields.price_hint')"
                            :error="$errors->first('price')"
                            :disabled="! $canEdit"
                            required
                        />
                        <p class="text-xs text-muted mt-1">{{ __('listing.edit.price_offer_warning') }}</p>
                    </div>
                    <div>
                        <x-ui.input
                            id="odometer_km"
                            name="odometer_km"
                            type="number"
                            wire:model="odometer_km"
                            :label="__('listing.fields.odometer_km')"
                            :error="$errors->first('odometer_km')"
                            :disabled="$coreLocked || ! $canEdit"
                            required
                        />
                        <span class="inline-flex items-center rounded-full bg-warning/10 text-warning px-2 py-0.5 text-xs font-bold mt-1">{{ __('listing.edit.core_badge') }}</span>
                    </div>
                </div>

                <div>
                    <x-ui.checkbox
                        id="negotiable"
                        name="negotiable"
                        wire:model="negotiable"
                        :label="__('listing.fields.negotiable_yes')"
                        :disabled="! $canEdit"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-ui.select
                        id="transmission"
                        name="transmission"
                        wire:model="transmission"
                        :label="__('listing.fields.transmission')"
                        :options="[
                            'manual' => __('listing.transmissions.manual'),
                            'automatic' => __('listing.transmissions.automatic'),
                        ]"
                        :error="$errors->first('transmission')"
                        :disabled="! $canEdit"
                        required
                    />
                    <x-ui.select
                        id="fuel"
                        name="fuel"
                        wire:model="fuel"
                        :label="__('listing.fields.fuel')"
                        :options="[
                            'petrol' => __('listing.fuel_types.petrol'),
                            'diesel' => __('listing.fuel_types.diesel'),
                            'electric' => __('listing.fuel_types.electric'),
                            'hybrid' => __('listing.fuel_types.hybrid'),
                        ]"
                        :error="$errors->first('fuel')"
                        :disabled="! $canEdit"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-ui.input
                            id="color"
                            name="color"
                            wire:model="color"
                            :label="__('listing.fields.color')"
                            :error="$errors->first('color')"
                            :disabled="! $canEdit"
                            required
                        />
                    </div>
                    <div>
                        <x-ui.input
                            id="chassis_number"
                            name="chassis_number"
                            wire:model="chassis_number"
                            :label="__('listing.fields.chassis_number')"
                            :hint="__('listing.fields.chassis_number_hint')"
                            :error="$errors->first('chassis_number')"
                            :disabled="$coreLocked || ! $canEdit"
                        />
                        <span class="inline-flex items-center rounded-full bg-warning/10 text-warning px-2 py-0.5 text-xs font-bold mt-1">{{ __('listing.edit.core_badge') }}</span>
                    </div>
                </div>

                <div>
                    <x-ui.textarea
                        id="description"
                        name="description"
                        wire:model="description"
                        rows="4"
                        :label="__('listing.fields.description')"
                        :hint="__('listing.fields.description_hint')"
                        :error="$errors->first('description')"
                        :disabled="! $canEdit"
                    />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card :title="__('listing.edit.section_photos')">
            <div class="space-y-4">
                <p class="text-xs text-muted">{{ __('listing.fields.photos_hint') }}</p>
                @if ($errors->has('newPhotos') || $errors->has('newPhotos.*'))
                    <x-ui.alert variant="danger" :message="$errors->first('newPhotos') ?: $errors->first('newPhotos.*')" />
                @endif

                <div>
                    <h3 class="text-sm font-semibold text-text">{{ __('listing.edit.existing_photos') }}</h3>
                    <div class="grid grid-cols-3 gap-3 pt-2">
                        @foreach ($listing->photos as $photo)
                            @if (! in_array($photo->id, $removePhotoIds, true))
                                <div class="relative rounded-lg overflow-hidden border border-muted/20 bg-bg">
                                    <img
                                        src="{{ route('listings.photos.show', ['listing' => $listing->id, 'photo' => $photo->id]) }}"
                                        alt="{{ $listing->title }}"
                                        class="w-full h-24 object-cover"
                                        loading="lazy"
                                    />
                                    @if ($photo->is_cover || $coverPhotoId === $photo->id)
                                        <span class="absolute top-1 left-1 inline-flex items-center rounded-full bg-accent text-text px-2 py-0.5 text-xs font-bold">{{ __('listing.edit.is_cover') }}</span>
                                    @endif
                                    @if ($canEdit)
                                        <div class="flex gap-1 p-1">
                                            <x-ui.button
                                                type="button"
                                                variant="ghost"
                                                wire:click="setCoverPhoto('{{ $photo->id }}')"
                                                wire:loading.attr="disabled"
                                            >
                                                {{ __('listing.edit.set_as_cover') }}
                                            </x-ui.button>
                                            <x-ui.button
                                                type="button"
                                                variant="ghost"
                                                wire:click="removeExistingPhoto('{{ $photo->id }}')"
                                                wire:loading.attr="disabled"
                                            >
                                                {{ __('listing.edit.remove') }}
                                            </x-ui.button>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                @if ($canEdit)
                    <div>
                        <label for="newPhotos" class="block text-sm font-medium text-text mb-1.5">
                            {{ __('listing.edit.add_photos') }}
                        </label>
                        <input
                            id="newPhotos"
                            type="file"
                            multiple
                            accept="image/jpeg,image/png,image/webp"
                            wire:model="newPhotos"
                            class="w-full min-h-[44px] px-3 py-2.5 rounded-lg text-sm bg-surface text-text border border-muted/20"
                        />
                    </div>

                    @if (count($newPhotos) > 0)
                        <div class="grid grid-cols-3 gap-3">
                            @foreach ($newPhotos as $index => $upload)
                                <div class="relative rounded-lg overflow-hidden border border-muted/20 bg-bg p-1">
                                    @if (is_object($upload) && method_exists($upload, 'temporaryUrl'))
                                        <img src="{{ $upload->temporaryUrl() }}" alt="" class="w-full h-24 object-cover rounded" />
                                    @endif
                                    @if ($coverNewPhotoIndex === $index)
                                        <span class="absolute top-1 left-1 inline-flex items-center rounded-full bg-accent text-text px-2 py-0.5 text-xs font-bold">{{ __('listing.edit.is_cover') }}</span>
                                    @endif
                                    <div class="flex gap-1 p-1">
                                        <x-ui.button
                                            type="button"
                                            variant="ghost"
                                            wire:click="setCoverNewPhoto({{ $index }})"
                                            wire:loading.attr="disabled"
                                        >
                                            {{ __('listing.edit.set_as_cover') }}
                                        </x-ui.button>
                                        <x-ui.button
                                            type="button"
                                            variant="ghost"
                                            wire:click="removeNewPhoto({{ $index }})"
                                            wire:loading.attr="disabled"
                                        >
                                            {{ __('listing.edit.remove') }}
                                        </x-ui.button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </x-ui.card>

        <x-ui.card :title="__('listing.edit.section_location')">
            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-text">
                        {{ __('listing.fields.location_title') }}
                    </h3>
                    <p class="text-xs text-muted">
                        {{ __('listing.fields.location_hint') }}
                    </p>
                </div>

                @include('listing::partials.location-picker', ['lat' => $lat, 'lng' => $lng, 'mapCentre' => $mapCentre])

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-ui.input
                        id="lat"
                        name="lat"
                        type="text"
                        inputmode="decimal"
                        wire:model="lat"
                        :label="__('listing.fields.lat')"
                        :hint="__('listing.fields.lat_hint')"
                        :error="$errors->first('lat')"
                        :disabled="! $canEdit"
                        required
                    />
                    <x-ui.input
                        id="lng"
                        name="lng"
                        type="text"
                        inputmode="decimal"
                        wire:model="lng"
                        :label="__('listing.fields.lng')"
                        :hint="__('listing.fields.lng_hint')"
                        :error="$errors->first('lng')"
                        :disabled="! $canEdit"
                        required
                    />
                </div>

                <x-ui.textarea
                    id="address_full"
                    name="address_full"
                    wire:model="address_full"
                    rows="3"
                    :label="__('listing.fields.address_full')"
                    :hint="__('listing.fields.address_full_hint')"
                    :error="$errors->first('address_full')"
                    :disabled="! $canEdit"
                    required
                />

                <x-ui.input
                    id="area_name"
                    name="area_name"
                    wire:model="area_name"
                    :label="__('listing.fields.area_name')"
                    :hint="__('listing.fields.area_name_hint')"
                    :error="$errors->first('area_name')"
                    :disabled="! $canEdit"
                    required
                />

                <x-ui.input
                    id="inspection_place"
                    name="inspection_place"
                    wire:model="inspection_place"
                    :label="__('listing.fields.inspection_place')"
                    :hint="__('listing.fields.inspection_place_hint')"
                    :error="$errors->first('inspection_place')"
                    :disabled="! $canEdit"
                    required
                />
            </div>
        </x-ui.card>

        <x-ui.card :title="__('listing.edit.section_slots')">
            <div class="space-y-3">
                <p class="text-xs text-muted">{{ __('listing.fields.inspection_slots_hint') }}</p>
                @foreach ($inspection_slots as $index => $slot)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end rounded-lg border border-muted/20 p-3">
                        <x-ui.input
                            id="slot-start-{{ $index }}"
                            name="inspection_slots[{{ $index }}][slot_start]"
                            type="datetime-local"
                            wire:model="inspection_slots.{{ $index }}.slot_start"
                            :label="__('listing.fields.slot_time')"
                            :error="$errors->first('inspection_slots.' . $index . '.slot_start')"
                            :disabled="! $canEdit"
                            required
                        />
                        <div class="flex gap-2 items-end">
                            <div class="flex-1">
                                <x-ui.input
                                    id="slot-note-{{ $index }}"
                                    name="inspection_slots[{{ $index }}][note]"
                                    wire:model="inspection_slots.{{ $index }}.note"
                                    :label="__('listing.fields.slot_note')"
                                    :error="$errors->first('inspection_slots.' . $index . '.note')"
                                    :disabled="! $canEdit"
                                />
                            </div>
                            @if ($canEdit && count($inspection_slots) > 1)
                                <x-ui.button
                                    type="button"
                                    variant="ghost"
                                    wire:click="removeSlot({{ $index }})"
                                    wire:loading.attr="disabled"
                                >
                                    {{ __('listing.fields.remove_slot') }}
                                </x-ui.button>
                            @endif
                        </div>
                    </div>
                @endforeach
                @if ($canEdit)
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        wire:click="addSlot"
                        wire:loading.attr="disabled"
                    >
                        {{ __('listing.fields.add_slot') }}
                    </x-ui.button>
                @endif
            </div>
        </x-ui.card>
        @endif

        @if ($canEdit && $editable)
            <x-ui.card>
                <div class="flex flex-col sm:flex-row gap-3">
                    <x-ui.button
                        type="button"
                        variant="primary"
                        wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                    >
                        <span wire:loading.remove wire:target="save">{{ __('listing.edit.save') }}</span>
                        <span wire:loading wire:target="save">{{ __('listing.edit.saving') }}</span>
                    </x-ui.button>

                    @if ($listing->status->isActive())
                        <x-ui.button
                            type="button"
                            variant="danger"
                            wire:click="confirmDeactivate"
                            wire:loading.attr="disabled"
                        >
                            {{ __('listing.edit.deactivate') }}
                        </x-ui.button>
                    @endif
                </div>
            </x-ui.card>
        @endif

        @if ($reactivatable)
            <x-ui.card>
                <x-ui.button
                    type="button"
                    variant="primary"
                    wire:click="confirmReactivate"
                    wire:loading.attr="disabled"
                >
                    {{ __('listing.edit.reactivate') }}
                </x-ui.button>
            </x-ui.card>
        @endif

        @if (! $editable && ! $reactivatable && ! $allLocked)
            <x-ui.alert
                variant="info"
                :message="__('listing.errors.not_editable', ['status' => $listing->status->value])"
            />
        @endif
    @endif

    <div class="flex justify-between items-center pt-2">
        <x-ui.button
            :href="route('listings.mine')"
            variant="secondary"
            icon="panah-kiri"
        >
            {{ __('listing.edit.back_to_mine') }}
        </x-ui.button>
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
