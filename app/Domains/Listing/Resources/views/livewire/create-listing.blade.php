<div class="max-w-3xl mx-auto px-4 py-6 space-y-6">
    {{-- Header --}}
    <div class="space-y-1">
        <h1 class="text-2xl sm:text-3xl font-bold text-text">
            {{ __('listing.create.title') }}
        </h1>
        <p class="text-sm text-muted">
            {{ __('listing.create.subtitle') }}
        </p>
    </div>

    {{-- Step Indicator --}}
    <x-ui.card>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold {{ $step === 1 ? 'bg-primary text-white' : ($step > 1 ? 'bg-success text-white' : 'bg-muted/20 text-muted') }}">
                    @if ($step > 1)
                        <x-ui.icon name="centang" class="w-4 h-4" />
                    @else
                        1
                    @endif
                </span>
                <span class="text-sm font-medium {{ $step === 1 ? 'text-primary font-bold' : 'text-muted' }} hidden sm:inline">
                    {{ __('listing.steps.vehicle') }}
                </span>
            </div>

            <div class="flex-1 h-0.5 mx-3 bg-muted/20">
                <div class="h-full bg-primary transition-all duration-300 {{ $step === 1 ? 'w-0' : ($step === 2 ? 'w-1/2' : 'w-full') }}"></div>
            </div>

            <div class="flex items-center gap-2">
                <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold {{ $step === 2 ? 'bg-primary text-white' : ($step > 2 ? 'bg-success text-white' : 'bg-muted/20 text-muted') }}">
                    @if ($step > 2)
                        <x-ui.icon name="centang" class="w-4 h-4" />
                    @else
                        2
                    @endif
                </span>
                <span class="text-sm font-medium {{ $step === 2 ? 'text-primary font-bold' : 'text-muted' }} hidden sm:inline">
                    {{ __('listing.steps.photos') }}
                </span>
            </div>

            <div class="flex-1 h-0.5 mx-3 bg-muted/20">
                <div class="h-full bg-primary transition-all duration-300 {{ $step === 3 ? 'w-full' : 'w-0' }}"></div>
            </div>

            <div class="flex items-center gap-2">
                <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold {{ $step === 3 ? 'bg-primary text-white' : 'bg-muted/20 text-muted' }}">
                    3
                </span>
                <span class="text-sm font-medium {{ $step === 3 ? 'text-primary font-bold' : 'text-muted' }} hidden sm:inline">
                    {{ __('listing.steps.inspection') }}
                </span>
            </div>
        </div>
    </x-ui.card>

    {{-- Error Banner --}}
    @if ($errors->any())
        <x-ui.alert variant="danger" title="Periksa Kembali Isian Anda">
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    {{-- Form Body --}}
    <form wire:submit.prevent="submit" class="space-y-6">
        {{-- STEP 1: DATA KENDARAAN --}}
        @if ($step === 1)
            <x-ui.card :title="__('listing.steps.vehicle')" subtitle="Lengkapi spesifikasi umum kendaraan Anda.">
                <div class="space-y-4">
                    {{-- Jenis Kendaraan --}}
                    <div>
                        <x-ui.select
                            id="vehicle_type"
                            name="vehicle_type"
                            wire:model.live="vehicle_type"
                            :label="__('listing.fields.vehicle_type')"
                            :options="[
                                'car' => __('listing.vehicle_types.car'),
                                'motorcycle' => __('listing.vehicle_types.motorcycle'),
                            ]"
                            required
                        />
                    </div>

                    {{-- Judul Iklan --}}
                    <div>
                        <x-ui.input
                            id="title"
                            name="title"
                            wire:model="title"
                            :label="__('listing.fields.title')"
                            :hint="__('listing.fields.title_hint')"
                            :error="$errors->first('title')"
                            required
                        />
                    </div>

                    {{-- Merek & Model --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-ui.input
                                id="brand"
                                name="brand"
                                list="brand-suggestions"
                                wire:model="brand"
                                :label="__('listing.fields.brand')"
                                :hint="__('listing.fields.brand_placeholder')"
                                :error="$errors->first('brand')"
                                required
                            />
                            <datalist id="brand-suggestions">
                                @foreach ($brandSuggestions as $suggestedBrand)
                                    <option value="{{ $suggestedBrand }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <div>
                            <x-ui.input
                                id="model"
                                name="model"
                                wire:model="model"
                                :label="__('listing.fields.model')"
                                :hint="__('listing.fields.model_hint')"
                                :error="$errors->first('model')"
                                required
                            />
                        </div>
                    </div>

                    {{-- Tahun & Kilometer --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-ui.input
                                id="year"
                                name="year"
                                type="number"
                                inputmode="numeric"
                                wire:model="year"
                                :label="__('listing.fields.year')"
                                hint="1990 - {{ date('Y') }}"
                                :error="$errors->first('year')"
                                required
                            />
                        </div>

                        <div>
                            <x-ui.input
                                id="odometer_km"
                                name="odometer_km"
                                type="number"
                                inputmode="numeric"
                                wire:model="odometer_km"
                                :label="__('listing.fields.odometer_km')"
                                hint="Contoh: 45000"
                                :error="$errors->first('odometer_km')"
                                required
                            />
                        </div>
                    </div>

                    {{-- Harga & Nego --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                        <div>
                            <x-ui.input
                                id="price"
                                name="price"
                                type="number"
                                inputmode="numeric"
                                wire:model="price"
                                :label="__('listing.fields.price')"
                                :hint="__('listing.fields.price_hint')"
                                :error="$errors->first('price')"
                                required
                            />
                        </div>

                        <div class="p-2">
                            <x-ui.checkbox
                                id="negotiable"
                                name="negotiable"
                                wire:model="negotiable"
                                :label="__('listing.fields.negotiable_yes')"
                            />
                        </div>
                    </div>

                    {{-- Transmisi & Bahan Bakar --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-ui.select
                                id="transmission"
                                name="transmission"
                                wire:model="transmission"
                                :label="__('listing.fields.transmission')"
                                :options="[
                                    'manual' => __('listing.transmissions.manual'),
                                    'automatic' => __('listing.transmissions.automatic'),
                                ]"
                                required
                            />
                        </div>

                        <div>
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
                                required
                            />
                        </div>
                    </div>

                    {{-- Warna & Area --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-ui.input
                                id="color"
                                name="color"
                                wire:model="color"
                                :label="__('listing.fields.color')"
                                hint="Contoh: Hitam Metalik, Putih"
                                :error="$errors->first('color')"
                                required
                            />
                        </div>

                        <div>
                            <x-ui.input
                                id="area_name"
                                name="area_name"
                                wire:model="area_name"
                                :label="__('listing.fields.area_name')"
                                :hint="__('listing.fields.area_name_hint')"
                                :error="$errors->first('area_name')"
                            />
                        </div>
                    </div>

                    {{-- Nomor Rangka (Sensitif / Terenkripsi) --}}
                    <div>
                        <x-ui.input
                            id="chassis_number"
                            name="chassis_number"
                            wire:model="chassis_number"
                            :label="__('listing.fields.chassis_number')"
                            :hint="__('listing.fields.chassis_number_hint')"
                            :error="$errors->first('chassis_number')"
                        />
                    </div>

                    {{-- Deskripsi --}}
                    <div>
                        <x-ui.textarea
                            id="description"
                            name="description"
                            wire:model="description"
                            rows="4"
                            :label="__('listing.fields.description')"
                            :hint="__('listing.fields.description_hint')"
                            :error="$errors->first('description')"
                        />
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <x-ui.button
                        type="button"
                        wire:click="nextStep"
                        variant="primary"
                        iconRight="panah-kanan"
                    >
                        {{ __('listing.create.btn_next') }}
                    </x-ui.button>
                </div>
            </x-ui.card>
        @endif

        {{-- STEP 2: FOTO KENDARAAN --}}
        @if ($step === 2)
            <x-ui.card :title="__('listing.steps.photos')" :subtitle="__('listing.fields.photos_hint')">
                <div class="space-y-6" x-data="{
                    compressing: false,
                    async handleFiles(event) {
                        const input = event.target;
                        if (!input.files || input.files.length === 0) return;
                        
                        this.compressing = true;
                        try {
                            const compressedList = [];
                            for (let i = 0; i < input.files.length; i++) {
                                const file = input.files[i];
                                if (window.compressImageFile) {
                                    const compressed = await window.compressImageFile(file, 2 * 1024 * 1024);
                                    compressedList.push(compressed);
                                } else {
                                    compressedList.push(file);
                                }
                            }
                            
                            // Upload through Livewire upload multiple
                            @this.uploadMultiple('photos', compressedList, () => {
                                this.compressing = false;
                            }, () => {
                                this.compressing = false;
                            });
                        } catch (err) {
                            this.compressing = false;
                            alert('Gagal memproses kompresi gambar: ' + err.message);
                        }
                    }
                }">
                    {{-- Upload Area --}}
                    <div class="border-2 border-dashed border-muted/30 rounded-xl p-6 text-center space-y-3 bg-bg">
                        <div class="flex justify-center text-muted">
                            <x-ui.icon name="kamera" class="w-10 h-10" />
                        </div>
                        <div>
                            <label for="photo-input" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition">
                                <x-ui.icon name="panah-atas" class="w-4 h-4" />
                                <span>Pilih Foto dari Galeri / Kamera</span>
                            </label>
                            <input
                                id="photo-input"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                multiple
                                class="sr-only"
                                @change="handleFiles($event)"
                            />
                        </div>
                        <p class="text-xs text-muted">
                            Pilih minimal 3 dan maksimal 10 foto (JPG, PNG, WebP). Kompresi otomatis berjalan di browser.
                        </p>

                        {{-- Compression & Upload Loading Indicator --}}
                        <div x-show="compressing" x-cloak class="pt-2">
                            <div class="inline-flex items-center gap-2 text-sm text-primary font-medium">
                                <span class="animate-spin inline-block w-4 h-4 border-2 border-primary border-t-transparent rounded-full"></span>
                                <span>Mengompresi dan mengunggah foto...</span>
                            </div>
                        </div>

                        <div wire:loading wire:target="photos" class="pt-2">
                            <div class="inline-flex items-center gap-2 text-sm text-primary font-medium">
                                <span class="animate-spin inline-block w-4 h-4 border-2 border-primary border-t-transparent rounded-full"></span>
                                <span>Memproses foto ke server...</span>
                            </div>
                        </div>
                    </div>

                    {{-- Photo Previews & Cover Selection --}}
                    @if (!empty($photos))
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-text">
                                    Foto Terpilih ({{ count($photos) }} dari maks 10):
                                </span>
                                <span class="text-xs text-muted">
                                    Klik tombol "Sampul" untuk menetapkan foto utama.
                                </span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                @foreach ($photos as $index => $photo)
                                    <div class="relative group rounded-xl overflow-hidden border {{ $cover_index === $index ? 'border-accent ring-2 ring-accent' : 'border-muted/20' }} bg-surface">
                                        <div class="aspect-4/3 overflow-hidden bg-muted/10">
                                            @if (is_object($photo) && method_exists($photo, 'temporaryUrl'))
                                                <img src="{{ $photo->temporaryUrl() }}" alt="Pratinjau foto {{ $index + 1 }}" class="w-full h-full object-cover" />
                                            @endif
                                        </div>

                                        {{-- Cover Badge --}}
                                        @if ($cover_index === $index)
                                            <div class="absolute top-2 left-2 px-2 py-0.5 bg-accent text-white text-xs font-bold rounded shadow">
                                                Sampul
                                            </div>
                                        @endif

                                        {{-- Actions --}}
                                        <div class="p-2 flex items-center justify-between gap-1 bg-surface">
                                            <button
                                                type="button"
                                                wire:click="setCover({{ $index }})"
                                                class="text-xs px-2 py-1 rounded {{ $cover_index === $index ? 'bg-accent/10 text-accent font-semibold' : 'text-muted hover:text-text' }}"
                                            >
                                                {{ $cover_index === $index ? 'Foto Sampul' : 'Jadikan Sampul' }}
                                            </button>

                                            <button
                                                type="button"
                                                wire:click="removePhoto({{ $index }})"
                                                class="text-xs text-danger hover:underline p-1"
                                                title="Hapus foto"
                                            >
                                                <x-ui.icon name="silang" class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-between items-center">
                    <x-ui.button
                        type="button"
                        wire:click="previousStep"
                        variant="secondary"
                        icon="panah-kiri"
                    >
                        {{ __('listing.create.btn_prev') }}
                    </x-ui.button>

                    <x-ui.button
                        type="button"
                        wire:click="nextStep"
                        variant="primary"
                        iconRight="panah-kanan"
                        :disabled="count($photos) < 3 || count($photos) > 10"
                    >
                        {{ __('listing.create.btn_next') }}
                    </x-ui.button>
                </div>
            </x-ui.card>
        @endif

        {{-- STEP 3: INSPEKSI --}}
        @if ($step === 3)
            <x-ui.card :title="__('listing.steps.inspection')" subtitle="Atur kesiapan waktu dan tempat bertemu dengan teknisi inspeksi SIMOKAS.">
                <div class="space-y-6">
                    {{-- Tempat Inspeksi --}}
                    <div>
                        <x-ui.input
                            id="inspection_place"
                            name="inspection_place"
                            wire:model="inspection_place"
                            :label="__('listing.fields.inspection_place')"
                            :hint="__('listing.fields.inspection_place_hint')"
                            :error="$errors->first('inspection_place')"
                            required
                        />
                    </div>

                    {{-- Opsi Waktu Inspeksi --}}
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-text">
                                    {{ __('listing.fields.inspection_slots') }}
                                </h3>
                                <p class="text-xs text-muted">
                                    {{ __('listing.fields.inspection_slots_hint') }}
                                </p>
                            </div>

                            <x-ui.button
                                type="button"
                                wire:click="addSlot"
                                variant="secondary"
                                icon="plus"
                            >
                                {{ __('listing.fields.add_slot') }}
                            </x-ui.button>
                        </div>

                        <div class="space-y-3">
                            @foreach ($inspection_slots as $index => $slot)
                                <div class="p-4 rounded-xl border border-muted/20 bg-bg space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-primary uppercase tracking-wide">
                                            Opsi Waktu #{{ $index + 1 }}
                                        </span>

                                        @if (count($inspection_slots) > 1)
                                            <button
                                                type="button"
                                                wire:click="removeSlot({{ $index }})"
                                                class="text-xs text-danger hover:underline inline-flex items-center gap-1"
                                            >
                                                <x-ui.icon name="silang" class="w-3.5 h-3.5" />
                                                <span>{{ __('listing.fields.remove_slot') }}</span>
                                            </button>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <x-ui.input
                                                id="slot_{{ $index }}_start"
                                                name="inspection_slots.{{ $index }}.slot_start"
                                                type="datetime-local"
                                                wire:model="inspection_slots.{{ $index }}.slot_start"
                                                :label="__('listing.fields.slot_time')"
                                                :error="$errors->first('inspection_slots.'.$index.'.slot_start')"
                                                required
                                            />
                                        </div>

                                        <div>
                                            <x-ui.input
                                                id="slot_{{ $index }}_note"
                                                name="inspection_slots.{{ $index }}.note"
                                                wire:model="inspection_slots.{{ $index }}.note"
                                                :label="__('listing.fields.slot_note')"
                                                hint="Contoh: Di atas jam kerja"
                                                :error="$errors->first('inspection_slots.'.$index.'.note')"
                                            />
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    </div>
                </div>

                <div class="mt-6 flex justify-between items-center">
                    <x-ui.button
                        type="button"
                        wire:click="previousStep"
                        variant="secondary"
                        icon="panah-kiri"
                    >
                        {{ __('listing.create.btn_prev') }}
                    </x-ui.button>

                    <x-ui.button
                        type="submit"
                        variant="primary"
                        wire:loading.attr="disabled"
                        icon="centang"
                    >
                        <span wire:loading.remove wire:target="submit">
                            {{ __('listing.create.btn_submit') }}
                        </span>
                        <span wire:loading wire:target="submit">
                            {{ __('listing.create.submitting') }}
                        </span>
                    </x-ui.button>
                </div>
            </x-ui.card>
        @endif
    </form>
</div>
