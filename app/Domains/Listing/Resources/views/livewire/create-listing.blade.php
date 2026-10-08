<div class="ds-sans max-w-3xl mx-auto px-4 py-6 space-y-8 pb-12">
    {{-- Header --}}
    <div class="space-y-2">
        <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('listing.create.title') }}</span>
        <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">
            {{ __('listing.create.title') }}
        </h1>
        <p class="text-sm text-ds-muted">
            {{ __('listing.create.subtitle') }}
        </p>
    </div>

    {{-- Step Indicator --}}
    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-5 sm:p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold {{ $step === 1 ? 'bg-ds-blue text-white' : ($step > 1 ? 'bg-ds-green text-white' : 'bg-ds-soft text-ds-muted') }}">
                    @if ($step > 1)
                        <i aria-hidden="true" class="fa-solid fa-check"></i>
                    @else
                        1
                    @endif
                </span>
                <span class="text-sm font-bold {{ $step === 1 ? 'text-ds-blue' : 'text-ds-muted' }} hidden sm:inline">
                    {{ __('listing.steps.vehicle') }}
                </span>
            </div>

            <div class="flex-1 h-0.5 mx-3 bg-ds-line/60">
                <div class="h-full bg-ds-blue transition-all duration-300 {{ $step === 1 ? 'w-0' : ($step === 2 ? 'w-1/2' : 'w-full') }}"></div>
            </div>

            <div class="flex items-center gap-2">
                <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold {{ $step === 2 ? 'bg-ds-blue text-white' : ($step > 2 ? 'bg-ds-green text-white' : 'bg-ds-soft text-ds-muted') }}">
                    @if ($step > 2)
                        <i aria-hidden="true" class="fa-solid fa-check"></i>
                    @else
                        2
                    @endif
                </span>
                <span class="text-sm font-bold {{ $step === 2 ? 'text-ds-blue' : 'text-ds-muted' }} hidden sm:inline">
                    {{ __('listing.steps.photos') }}
                </span>
            </div>

            <div class="flex-1 h-0.5 mx-3 bg-ds-line/60">
                <div class="h-full bg-ds-blue transition-all duration-300 {{ $step === 3 ? 'w-full' : 'w-0' }}"></div>
            </div>

            <div class="flex items-center gap-2">
                <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold {{ $step === 3 ? 'bg-ds-blue text-white' : 'bg-ds-soft text-ds-muted' }}">
                    3
                </span>
                <span class="text-sm font-bold {{ $step === 3 ? 'text-ds-blue' : 'text-ds-muted' }} hidden sm:inline">
                    {{ __('listing.steps.inspection') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Error Banner --}}
    @if ($errors->any())
        <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40">
            <p class="text-xs font-extrabold text-ds-ink">Periksa Kembali Isian Anda</p>
            <ul class="list-disc list-inside text-xs text-ds-muted space-y-1 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form Body --}}
    <form wire:submit.prevent="submit" class="space-y-6">
        {{-- STEP 1: DATA KENDARAAN --}}
        @if ($step === 1)
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <h2 class="text-lg font-extrabold text-ds-ink">{{ __('listing.steps.vehicle') }}</h2>
                <p class="text-xs text-ds-muted mt-1 mb-5">Lengkapi spesifikasi umum kendaraan Anda.</p>
                <div class="space-y-4">
                    {{-- Jenis Kendaraan --}}
                    <div class="w-full">
                        <label for="vehicle_type" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.vehicle_type') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">(wajib diisi)</span>
                        </label>
                        <select id="vehicle_type" name="vehicle_type" wire:model.live="vehicle_type" required
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            <option value="car">{{ __('listing.vehicle_types.car') }}</option>
                            <option value="motorcycle">{{ __('listing.vehicle_types.motorcycle') }}</option>
                        </select>
                        @error('vehicle_type')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Judul Iklan --}}
                    <div class="w-full">
                        <label for="title" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.title') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">(wajib diisi)</span>
                        </label>
                        <input type="text" id="title" name="title" wire:model="title" required
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                        <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.title_hint') }}</p>
                        @error('title')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Merek & Model --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="w-full">
                            <label for="brand" class="block text-xs font-bold text-ds-ink mb-1.5">
                                {{ __('listing.fields.brand') }}
                                <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                                <span class="sr-only">(wajib diisi)</span>
                            </label>
                            <input type="text" id="brand" name="brand" list="brand-suggestions" wire:model="brand" required
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            <datalist id="brand-suggestions">
                                @foreach ($brandSuggestions as $suggestedBrand)
                                    <option value="{{ $suggestedBrand }}"></option>
                                @endforeach
                            </datalist>
                            <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.brand_placeholder') }}</p>
                            @error('brand')
                                <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full">
                            <label for="model" class="block text-xs font-bold text-ds-ink mb-1.5">
                                {{ __('listing.fields.model') }}
                                <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                                <span class="sr-only">(wajib diisi)</span>
                            </label>
                            <input type="text" id="model" name="model" wire:model="model" required
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.model_hint') }}</p>
                            @error('model')
                                <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Tahun & Kilometer --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="w-full">
                            <label for="year" class="block text-xs font-bold text-ds-ink mb-1.5">
                                {{ __('listing.fields.year') }}
                                <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                                <span class="sr-only">(wajib diisi)</span>
                            </label>
                            <input type="number" id="year" name="year" inputmode="numeric" wire:model="year" required
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            <p class="text-xs text-ds-faint mt-1.5">1990 - {{ date('Y') }}</p>
                            @error('year')
                                <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full">
                            <label for="odometer_km" class="block text-xs font-bold text-ds-ink mb-1.5">
                                {{ __('listing.fields.odometer_km') }}
                                <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                                <span class="sr-only">(wajib diisi)</span>
                            </label>
                            <input type="number" id="odometer_km" name="odometer_km" inputmode="numeric" wire:model="odometer_km" required placeholder="Contoh: 45000"
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            @error('odometer_km')
                                <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Harga & Nego --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                        <div class="w-full">
                            <label for="price" class="block text-xs font-bold text-ds-ink mb-1.5">
                                {{ __('listing.fields.price') }}
                                <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                                <span class="sr-only">(wajib diisi)</span>
                            </label>
                            <input type="number" id="price" name="price" inputmode="numeric" wire:model="price" required
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.price_hint') }}</p>
                            @error('price')
                                <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="p-2">
                            <label for="negotiable" class="inline-flex items-center gap-2 text-sm text-ds-muted cursor-pointer">
                                <input type="checkbox" id="negotiable" name="negotiable" wire:model="negotiable"
                                    class="w-4 h-4 rounded border-ds-line text-ds-blue focus:ring-ds-blue/30">
                                {{ __('listing.fields.negotiable_yes') }}
                            </label>
                        </div>
                    </div>

                    {{-- Transmisi & Bahan Bakar --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="w-full">
                            <label for="transmission" class="block text-xs font-bold text-ds-ink mb-1.5">
                                {{ __('listing.fields.transmission') }}
                                <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                                <span class="sr-only">(wajib diisi)</span>
                            </label>
                            <select id="transmission" name="transmission" wire:model="transmission" required
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
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
                                <span class="sr-only">(wajib diisi)</span>
                            </label>
                            <select id="fuel" name="fuel" wire:model="fuel" required
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
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

                    {{-- Warna --}}
                    <div class="w-full">
                        <label for="color" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.color') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">(wajib diisi)</span>
                        </label>
                        <input type="text" id="color" name="color" wire:model="color" required placeholder="Contoh: Hitam Metalik, Putih"
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                        @error('color')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nomor Rangka (Sensitif / Terenkripsi) --}}
                    <div class="w-full">
                        <label for="chassis_number" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.chassis_number') }}
                        </label>
                        <input type="text" id="chassis_number" name="chassis_number" wire:model="chassis_number"
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                        <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.chassis_number_hint') }}</p>
                        @error('chassis_number')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Deskripsi --}}
                    <div class="w-full">
                        <label for="description" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.description') }}
                        </label>
                        <textarea id="description" name="description" wire:model="description" rows="4"
                            class="w-full px-4 py-3 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"></textarea>
                        <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.description_hint') }}</p>
                        @error('description')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" wire:click="nextStep"
                        class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                        {{ __('listing.create.btn_next') }}
                        <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </div>
            </div>
        @endif

        {{-- STEP 2: FOTO KENDARAAN --}}
        @if ($step === 2)
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <h2 class="text-lg font-extrabold text-ds-ink">{{ __('listing.steps.photos') }}</h2>
                <p class="text-xs text-ds-muted mt-1 mb-5">{{ __('listing.fields.photos_hint') }}</p>
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
                    <div class="border-2 border-dashed border-ds-line rounded-2xl p-6 text-center space-y-3 bg-ds-canvas/60">
                        <div class="flex justify-center text-ds-faint">
                            <i aria-hidden="true" class="fa-solid fa-camera text-3xl"></i>
                        </div>
                        <div>
                            <label for="photo-input" class="cursor-pointer inline-flex items-center gap-2 px-5 py-2.5 bg-ds-blue hover:bg-ds-blue-ink text-white text-sm font-bold rounded-xl transition-all shadow-md">
                                <i aria-hidden="true" class="fa-solid fa-upload text-xs"></i>
                                <span>{{ __('listing.fields.photo_choose') }}</span>
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
                        <p class="text-xs text-ds-muted">
                            {{ __('listing.fields.photo_requirements') }}
                        </p>

                        {{-- Compression & Upload Loading Indicator --}}
                        <div x-show="compressing" x-cloak class="pt-2">
                            <div class="inline-flex items-center gap-2 text-sm text-ds-blue font-semibold">
                                <i aria-hidden="true" class="fa-solid fa-circle-notch fa-spin"></i>
                                <span>{{ __('listing.fields.photo_compressing') }}</span>
                            </div>
                        </div>

                        <div wire:loading wire:target="photos" class="pt-2">
                            <div class="inline-flex items-center gap-2 text-sm text-ds-blue font-semibold">
                                <i aria-hidden="true" class="fa-solid fa-circle-notch fa-spin"></i>
                                <span>{{ __('listing.fields.photo_processing') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Photo Previews & Cover Selection --}}
                    @if (!empty($photos))
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-ds-ink">
                                    {{ __('listing.fields.photo_selected', ['count' => count($photos)]) }}
                                </span>
                                <span class="text-xs text-ds-muted">
                                    {{ __('listing.fields.photo_cover_hint') }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                @foreach ($photos as $index => $photo)
                                    <div class="relative group rounded-2xl overflow-hidden border {{ $cover_index === $index ? 'border-ds-yellow ring-2 ring-ds-yellow' : 'border-ds-line/80' }} bg-white shadow-xs">
                                        <div class="aspect-4/3 overflow-hidden bg-ds-soft">
                                            @if (is_object($photo) && method_exists($photo, 'temporaryUrl'))
                                                <img src="{{ $photo->temporaryUrl() }}" alt="Pratinjau foto {{ $index + 1 }}" class="w-full h-full object-cover" />
                                            @endif
                                        </div>

                                        {{-- Cover Badge --}}
                                        @if ($cover_index === $index)
                                            <div class="absolute top-2 left-2 px-2 py-0.5 bg-ds-yellow text-ds-ink text-[10px] font-bold rounded-full shadow uppercase tracking-wider">
                                                {{ __('listing.fields.cover_photo') }}
                                            </div>
                                        @endif

                                        {{-- Actions --}}
                                        <div class="p-2 flex items-center justify-between gap-1 bg-white">
                                            <button
                                                type="button"
                                                wire:click="setCover({{ $index }})"
                                                class="text-xs px-2 py-1 rounded-lg {{ $cover_index === $index ? 'bg-ds-yellow-soft text-ds-ink font-bold' : 'text-ds-muted hover:text-ds-ink' }}"
                                            >
                                                {{ $cover_index === $index ? __('listing.fields.cover_photo') : __('listing.fields.set_as_cover') }}
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="removePhoto({{ $index }})"
                                                class="text-xs text-ds-danger hover:underline p-1 inline-flex items-center gap-1"
                                                title="Hapus foto"
                                            >
                                                <i aria-hidden="true" class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-between items-center">
                    <button type="button" wire:click="previousStep"
                        class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-sm rounded-xl transition-all shadow-xs">
                        <i aria-hidden="true" class="fa-solid fa-arrow-left text-xs"></i>
                        {{ __('listing.create.btn_prev') }}
                    </button>

                    <button type="button" wire:click="nextStep" @disabled(count($photos) < 3 || count($photos) > 10)
                        class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60">
                        {{ __('listing.create.btn_next') }}
                        <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </div>
            </div>
        @endif

        {{-- STEP 3: INSPEKSI --}}
        @if ($step === 3)
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <h2 class="text-lg font-extrabold text-ds-ink">{{ __('listing.steps.inspection') }}</h2>
                <p class="text-xs text-ds-muted mt-1 mb-5">Atur kesiapan waktu dan tempat bertemu dengan teknisi inspeksi SIMOKAS.</p>
                <div class="space-y-6">
                    {{-- Lokasi: peta + alamat (PRD 16.1) --}}
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-extrabold text-ds-ink">
                                {{ __('listing.fields.location_title') }}
                            </h3>
                            <p class="text-xs text-ds-muted">
                                {{ __('listing.fields.location_hint') }}
                            </p>
                        </div>

                        @include('listing::partials.location-picker')

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="w-full">
                                <label for="lat" class="block text-xs font-bold text-ds-ink mb-1.5">
                                    {{ __('listing.fields.lat') }}
                                    <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                                    <span class="sr-only">(wajib diisi)</span>
                                </label>
                                <input type="text" id="lat" name="lat" inputmode="decimal" wire:model="lat" required
                                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                                <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.lat_hint') }}</p>
                                @error('lat')
                                    <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="w-full">
                                <label for="lng" class="block text-xs font-bold text-ds-ink mb-1.5">
                                    {{ __('listing.fields.lng') }}
                                    <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                                    <span class="sr-only">(wajib diisi)</span>
                                </label>
                                <input type="text" id="lng" name="lng" inputmode="decimal" wire:model="lng" required
                                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
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
                                <span class="sr-only">(wajib diisi)</span>
                            </label>
                            <textarea id="address_full" name="address_full" wire:model="address_full" rows="3" required
                                class="w-full px-4 py-3 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"></textarea>
                            <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.address_full_hint') }}</p>
                            @error('address_full')
                                <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full">
                            <label for="area_name" class="block text-xs font-bold text-ds-ink mb-1.5">
                                {{ __('listing.fields.area_name') }}
                                <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                                <span class="sr-only">(wajib diisi)</span>
                            </label>
                            <input type="text" id="area_name" name="area_name" wire:model="area_name" required
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.area_name_hint') }} {{ __('listing.fields.area_name_public_note') }}</p>
                            @error('area_name')
                                <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Tempat Inspeksi --}}
                    <div class="w-full">
                        <label for="inspection_place" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('listing.fields.inspection_place') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">(wajib diisi)</span>
                        </label>
                        <input type="text" id="inspection_place" name="inspection_place" wire:model="inspection_place" required
                            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                        <p class="text-xs text-ds-faint mt-1.5">{{ __('listing.fields.inspection_place_hint') }}</p>
                        @error('inspection_place')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Opsi Waktu Inspeksi --}}
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-extrabold text-ds-ink">
                                    {{ __('listing.fields.inspection_slots') }}
                                </h3>
                                <p class="text-xs text-ds-muted">
                                    {{ __('listing.fields.inspection_slots_hint') }}
                                </p>
                            </div>

                            <button type="button" wire:click="addSlot"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs">
                                <i aria-hidden="true" class="fa-solid fa-plus text-xs"></i>
                                {{ __('listing.fields.add_slot') }}
                            </button>
                        </div>

                        <div class="space-y-3">
                            @foreach ($inspection_slots as $index => $slot)
                                <div class="p-4 rounded-2xl border border-ds-line/80 bg-ds-canvas/60 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-extrabold text-ds-blue uppercase tracking-wide">
                                            Opsi Waktu #{{ $index + 1 }}
                                        </span>

                                        @if (count($inspection_slots) > 1)
                                            <button
                                                type="button"
                                                wire:click="removeSlot({{ $index }})"
                                                class="text-xs text-ds-danger hover:underline inline-flex items-center gap-1"
                                            >
                                                <i aria-hidden="true" class="fa-solid fa-xmark"></i>
                                                <span>{{ __('listing.fields.remove_slot') }}</span>
                                            </button>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div class="w-full">
                                            <label for="slot_{{ $index }}_start" class="block text-xs font-bold text-ds-ink mb-1.5">
                                                {{ __('listing.fields.slot_time') }}
                                                <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                                                <span class="sr-only">(wajib diisi)</span>
                                            </label>
                                            <input type="datetime-local" id="slot_{{ $index }}_start" name="inspection_slots.{{ $index }}.slot_start" wire:model="inspection_slots.{{ $index }}.slot_start" required
                                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                                            @error('inspection_slots.'.$index.'.slot_start')
                                                <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <label for="slot_{{ $index }}_note" class="block text-xs font-bold text-ds-ink mb-1.5">
                                                {{ __('listing.fields.slot_note') }}
                                            </label>
                                            <input type="text" id="slot_{{ $index }}_note" name="inspection_slots.{{ $index }}.note" wire:model="inspection_slots.{{ $index }}.note" placeholder="Contoh: Di atas jam kerja"
                                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                                            @error('inspection_slots.'.$index.'.note')
                                                <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    </div>
                </div>

                <div class="mt-6 flex justify-between items-center">
                    <button type="button" wire:click="previousStep"
                        class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-sm rounded-xl transition-all shadow-xs">
                        <i aria-hidden="true" class="fa-solid fa-arrow-left text-xs"></i>
                        {{ __('listing.create.btn_prev') }}
                    </button>

                    <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="submit">
                            <i aria-hidden="true" class="fa-solid fa-check text-xs"></i>
                            {{ __('listing.create.btn_submit') }}
                        </span>
                        <span wire:loading wire:target="submit">
                            {{ __('listing.create.submitting') }}
                        </span>
                    </button>
                </div>
            </div>
        @endif
    </form>
</div>
