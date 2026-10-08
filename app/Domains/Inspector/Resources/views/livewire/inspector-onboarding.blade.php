<div class="ds-sans max-w-4xl mx-auto py-6 space-y-8 pb-12">
    <div class="space-y-2">
        <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('inspector.onboarding.profile_section') }}</h1>
        <p class="text-sm text-ds-muted">{{ __('inspector.onboarding.subtitle') }}</p>
    </div>

    {{-- Header Banner / Status --}}
    @if ($profile && $profile->isRejected())
        <div class="space-y-3">
            <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40">
                <p class="text-xs font-extrabold text-ds-ink">{{ __('inspector.onboarding.rejected_alert_title') }}</p>
                <p class="mt-1 text-xs text-ds-muted">{{ __('inspector.onboarding.rejected_alert_message', ['reason' => $profile->rejection_reason ?? '-']) }}</p>
            </div>
            <div class="flex justify-end">
                <button
                    type="button"
                    wire:click="resubmit"
                    wire:loading.attr="disabled"
                    wire:target="resubmit"
                    class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="resubmit">
                        {{ __('inspector.onboarding.resubmit_button') }}
                    </span>
                    <span wire:loading wire:target="resubmit">
                        {{ __('inspector.onboarding.resubmitting') }}
                    </span>
                </button>
            </div>
        </div>
    @elseif ($profile && $profile->isApproved())
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40">
            <p class="text-xs font-extrabold text-ds-ink">{{ __('inspector.onboarding.approved_alert_title') }}</p>
            <p class="mt-1 text-xs text-ds-muted">{{ __('inspector.onboarding.approved_alert_message') }}</p>
        </div>
    @elseif ($isComplete)
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40">
            <p class="text-xs font-extrabold text-ds-ink">{{ __('inspector.onboarding.queue_ready_title') }}</p>
            <p class="mt-1 text-xs text-ds-muted">{{ __('inspector.onboarding.queue_ready_message') }}</p>
        </div>
    @else
        <div role="alert" class="p-4 rounded-2xl border border-ds-amber-soft bg-ds-amber-soft/40">
            <p class="text-xs font-extrabold text-ds-ink">{{ __('inspector.onboarding.queue_missing_title') }}</p>
            <p class="mt-1 text-xs text-ds-muted">{{ __('inspector.onboarding.queue_missing_message') }}</p>
            <ul class="list-disc list-inside mt-2 text-xs text-ds-muted space-y-1">
                @foreach ($missingTypes as $type)
                    <li>{{ $type->label() }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Notification Messages --}}
    @if (session()->has('profile_success'))
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ session('profile_success') }}
        </div>
    @endif
    @if (session()->has('document_success'))
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ session('document_success') }}
        </div>
    @endif

    {{-- Section 1: Profil Keahlian --}}
    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
        <h2 class="text-lg font-extrabold text-ds-ink">{{ __('inspector.onboarding.profile_section') }}</h2>
        <p class="text-xs text-ds-muted mt-1 mb-5">{{ __('inspector.onboarding.subtitle') }}</p>
        <form wire:submit="saveProfile" class="space-y-6">
            <div>
                <span class="block text-xs font-bold text-ds-ink mb-1" id="vehicle-types-label">
                    {{ __('inspector.onboarding.vehicle_types_label') }} <span class="text-ds-danger" aria-hidden="true">*</span>
                </span>
                <p class="text-xs text-ds-faint mb-3">{{ __('inspector.onboarding.vehicle_types_hint') }}</p>
                <div class="flex flex-wrap gap-6" role="group" aria-labelledby="vehicle-types-label">
                    <label for="type_car" class="inline-flex items-center gap-2 text-sm text-ds-ink cursor-pointer min-h-[44px]">
                        <input type="checkbox" id="type_car" value="car" wire:model="vehicle_types"
                            class="w-4 h-4 rounded border-ds-line text-ds-blue focus:ring-ds-blue/30">
                        {{ __('inspector.vehicle_types.car') }}
                    </label>
                    <label for="type_motorcycle" class="inline-flex items-center gap-2 text-sm text-ds-ink cursor-pointer min-h-[44px]">
                        <input type="checkbox" id="type_motorcycle" value="motorcycle" wire:model="vehicle_types"
                            class="w-4 h-4 rounded border-ds-line text-ds-blue focus:ring-ds-blue/30">
                        {{ __('inspector.vehicle_types.motorcycle') }}
                    </label>
                </div>
                @error('vehicle_types')
                    <p class="mt-1 text-xs text-ds-danger" role="alert">{{ $message }}</p>
                @enderror
                @error('vehicle_types.*')
                    <p class="mt-1 text-xs text-ds-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full">
                <label for="experience_years" class="block text-xs font-bold text-ds-ink mb-1.5">
                    {{ __('inspector.onboarding.experience_label') }}
                    <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                    <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                </label>
                <input type="number" id="experience_years" min="0" max="50" wire:model="experience_years" required
                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                <p class="text-xs text-ds-faint mt-1.5">{{ __('inspector.onboarding.experience_hint') }}</p>
                @error('experience_years')
                    <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="saveProfile"
                    class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="saveProfile">
                        {{ __('inspector.onboarding.save_profile') }}
                    </span>
                    <span wire:loading wire:target="saveProfile">
                        {{ __('ui.actions.loading') }}
                    </span>
                </button>
            </div>
        </form>
    </div>

    {{-- Section 2: Unggah Dokumen Verifikasi --}}
    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
        <h2 class="text-lg font-extrabold text-ds-ink">{{ __('inspector.onboarding.documents_section') }}</h2>
        <p class="text-xs text-ds-muted mt-1 mb-5">{{ __('inspector.onboarding.documents_hint') }}</p>
        <div class="space-y-8 divide-y divide-ds-line/60">

            {{-- 1. KTP --}}
            <div class="pt-6 first:pt-0 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-ds-ink">
                            {{ __('inspector.documents.types.ktp') }} <span class="text-ds-danger" aria-hidden="true">*</span>
                        </h3>
                        <p class="text-xs text-ds-muted">{{ __('inspector.documents.file_requirements_ktp') }}</p>
                    </div>
                    @if ($ktpDocument)
                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-ds-green-soft text-ds-green-ink">{{ __('inspector.status.approved') }}</span>
                    @else
                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-ds-amber-soft text-ds-amber-ink">{{ __('inspector.status.pending') }}</span>
                    @endif
                </div>

                @if ($ktpDocument)
                    <div class="flex items-center justify-between p-4 bg-ds-canvas/60 rounded-2xl border border-ds-line/80">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-ds-blue-soft text-ds-blue-ink flex items-center justify-center">
                                <i aria-hidden="true" class="fa-solid fa-file-lines"></i>
                            </span>
                            <div>
                                <p class="text-sm font-bold text-ds-ink">
                                    {{ $ktpDocument->metadata['original_filename'] ?? 'KTP.pdf' }}
                                </p>
                                <p class="text-xs text-ds-muted">
                                    {{ __('inspector.documents.uploaded_at') }} {{ $ktpDocument->created_at?->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ $ktpDocument->getTemporarySignedUrl() }}"
                                target="_blank"
                                rel="noopener noreferrer nofollow"
                                class="inline-flex items-center gap-1.5 px-4 py-2 min-h-[44px] bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs">
                                <i aria-hidden="true" class="fa-solid fa-eye text-xs"></i>
                                {{ __('inspector.documents.view') }}
                            </a>
                            <button type="button" wire:click="confirmDeleteDocument('{{ $ktpDocument->id }}')"
                                class="inline-flex items-center gap-1.5 px-4 py-2 min-h-[44px] bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 font-bold text-xs rounded-xl transition-all shadow-xs">
                                <i aria-hidden="true" class="fa-solid fa-trash text-xs"></i>
                                {{ __('inspector.documents.delete') }}
                            </button>
                        </div>
                    </div>
                @else
                    <form wire:submit="uploadKtp" class="space-y-3">
                        <input id="ktp_file" type="file" wire:model="ktp_file" aria-label="{{ __('inspector.documents.types.ktp') }}"
                            class="w-full min-h-[44px] px-3 py-2.5 rounded-xl text-sm bg-white text-ds-ink border border-ds-line file:mr-3 file:px-4 file:py-2 file:rounded-xl file:border-0 file:bg-ds-blue file:text-white file:text-xs file:font-bold" />
                        @error('ktp_file')
                            <p class="text-xs text-ds-danger" role="alert">{{ $message }}</p>
                        @enderror
                        <div class="flex justify-end">
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="uploadKtp, ktp_file"
                                class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                            >
                                <span wire:loading.remove wire:target="uploadKtp, ktp_file">
                                    {{ __('inspector.documents.upload_button') }}
                                </span>
                                <span wire:loading wire:target="uploadKtp, ktp_file">
                                    {{ __('ui.actions.loading') }}
                                </span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- 2. Sertifikat Pendukung (Maksimal 3) --}}
            <div class="pt-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-ds-ink">
                            {{ __('inspector.documents.types.certificate') }} <span class="text-ds-danger" aria-hidden="true">*</span>
                        </h3>
                        <p class="text-xs text-ds-muted">
                            {{ __('inspector.documents.file_requirements_certificate') }} —
                            <span class="font-bold text-ds-ink">
                                {{ __('inspector.documents.certificate_count', ['count' => $certificateDocuments->count()]) }}
                            </span>
                        </p>
                    </div>
                    @if ($certificateDocuments->count() > 0)
                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-ds-green-soft text-ds-green-ink">{{ __('inspector.status.approved') }}</span>
                    @else
                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-ds-amber-soft text-ds-amber-ink">{{ __('inspector.status.pending') }}</span>
                    @endif
                </div>

                @if ($certificateDocuments->count() > 0)
                    <div class="space-y-2">
                        @foreach ($certificateDocuments as $cert)
                            <div class="flex items-center justify-between p-4 bg-ds-canvas/60 rounded-2xl border border-ds-line/80">
                                <div class="flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-xl bg-ds-blue-soft text-ds-blue-ink flex items-center justify-center">
                                        <i aria-hidden="true" class="fa-solid fa-file-lines"></i>
                                    </span>
                                    <div>
                                        <p class="text-sm font-bold text-ds-ink">
                                            {{ $cert->metadata['original_filename'] ?? 'Sertifikat.pdf' }}
                                        </p>
                                        <p class="text-xs text-ds-muted">
                                            {{ __('inspector.documents.uploaded_at') }} {{ $cert->created_at?->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ $cert->getTemporarySignedUrl() }}"
                                        target="_blank"
                                        rel="noopener noreferrer nofollow"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 min-h-[44px] bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs">
                                        <i aria-hidden="true" class="fa-solid fa-eye text-xs"></i>
                                        {{ __('inspector.documents.view') }}
                                    </a>
                                    <button type="button" wire:click="confirmDeleteDocument('{{ $cert->id }}')"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 min-h-[44px] bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 font-bold text-xs rounded-xl transition-all shadow-xs">
                                        <i aria-hidden="true" class="fa-solid fa-trash text-xs"></i>
                                        {{ __('inspector.documents.delete') }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-ds-muted italic">{{ __('inspector.documents.empty_certificates') }}</p>
                @endif

                @if ($certificateDocuments->count() < 3)
                    <form wire:submit="uploadCertificate" class="space-y-3 pt-2">
                        <input id="certificate_file" type="file" wire:model="certificate_file" aria-label="{{ __('inspector.documents.types.certificate') }}"
                            class="w-full min-h-[44px] px-3 py-2.5 rounded-xl text-sm bg-white text-ds-ink border border-ds-line file:mr-3 file:px-4 file:py-2 file:rounded-xl file:border-0 file:bg-ds-blue file:text-white file:text-xs file:font-bold" />
                        @error('certificate_file')
                            <p class="text-xs text-ds-danger" role="alert">{{ $message }}</p>
                        @enderror
                        <div class="flex justify-end">
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="uploadCertificate, certificate_file"
                                class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                            >
                                <span wire:loading.remove wire:target="uploadCertificate, certificate_file">
                                    {{ __('inspector.documents.upload_button') }}
                                </span>
                                <span wire:loading wire:target="uploadCertificate, certificate_file">
                                    {{ __('ui.actions.loading') }}
                                </span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- 3. CV --}}
            <div class="pt-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-ds-ink">
                            {{ __('inspector.documents.types.cv') }} <span class="text-ds-danger" aria-hidden="true">*</span>
                        </h3>
                        <p class="text-xs text-ds-muted">{{ __('inspector.documents.file_requirements_cv') }}</p>
                    </div>
                    @if ($cvDocument)
                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-ds-green-soft text-ds-green-ink">{{ __('inspector.status.approved') }}</span>
                    @else
                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-ds-amber-soft text-ds-amber-ink">{{ __('inspector.status.pending') }}</span>
                    @endif
                </div>

                @if ($cvDocument)
                    <div class="flex items-center justify-between p-4 bg-ds-canvas/60 rounded-2xl border border-ds-line/80">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-ds-blue-soft text-ds-blue-ink flex items-center justify-center">
                                <i aria-hidden="true" class="fa-solid fa-file-lines"></i>
                            </span>
                            <div>
                                <p class="text-sm font-bold text-ds-ink">
                                    {{ $cvDocument->metadata['original_filename'] ?? 'CV.pdf' }}
                                </p>
                                <p class="text-xs text-ds-muted">
                                    {{ __('inspector.documents.uploaded_at') }} {{ $cvDocument->created_at?->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ $cvDocument->getTemporarySignedUrl() }}"
                                target="_blank"
                                rel="noopener noreferrer nofollow"
                                class="inline-flex items-center gap-1.5 px-4 py-2 min-h-[44px] bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs">
                                <i aria-hidden="true" class="fa-solid fa-eye text-xs"></i>
                                {{ __('inspector.documents.view') }}
                            </a>
                            <button type="button" wire:click="confirmDeleteDocument('{{ $cvDocument->id }}')"
                                class="inline-flex items-center gap-1.5 px-4 py-2 min-h-[44px] bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 font-bold text-xs rounded-xl transition-all shadow-xs">
                                <i aria-hidden="true" class="fa-solid fa-trash text-xs"></i>
                                {{ __('inspector.documents.delete') }}
                            </button>
                        </div>
                    </div>
                @else
                    <form wire:submit="uploadCv" class="space-y-3">
                        <input id="cv_file" type="file" wire:model="cv_file" aria-label="{{ __('inspector.documents.types.cv') }}"
                            class="w-full min-h-[44px] px-3 py-2.5 rounded-xl text-sm bg-white text-ds-ink border border-ds-line file:mr-3 file:px-4 file:py-2 file:rounded-xl file:border-0 file:bg-ds-blue file:text-white file:text-xs file:font-bold" />
                        @error('cv_file')
                            <p class="text-xs text-ds-danger" role="alert">{{ $message }}</p>
                        @enderror
                        <div class="flex justify-end">
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="uploadCv, cv_file"
                                class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                            >
                                <span wire:loading.remove wire:target="uploadCv, cv_file">
                                    {{ __('inspector.documents.upload_button') }}
                                </span>
                                <span wire:loading wire:target="uploadCv, cv_file">
                                    {{ __('ui.actions.loading') }}
                                </span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- 4. Portofolio --}}
            <div class="pt-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-ds-ink">
                            {{ __('inspector.documents.types.portfolio') }} <span class="text-ds-danger" aria-hidden="true">*</span>
                        </h3>
                        <p class="text-xs text-ds-muted">{{ __('inspector.documents.file_requirements_portfolio') }}</p>
                    </div>
                    @if ($portfolioDocument)
                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-ds-green-soft text-ds-green-ink">{{ __('inspector.status.approved') }}</span>
                    @else
                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-ds-amber-soft text-ds-amber-ink">{{ __('inspector.status.pending') }}</span>
                    @endif
                </div>

                @if ($portfolioDocument)
                    <div class="flex items-center justify-between p-4 bg-ds-canvas/60 rounded-2xl border border-ds-line/80">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-ds-blue-soft text-ds-blue-ink flex items-center justify-center">
                                <i aria-hidden="true" class="fa-solid fa-file-lines"></i>
                            </span>
                            <div>
                                @if ($portfolioDocument->isUrl())
                                    <p class="text-sm font-bold text-ds-ink">
                                        {{ $portfolioDocument->metadata['url'] ?? '' }}
                                    </p>
                                    <p class="text-xs text-ds-muted">{{ __('inspector.documents.portfolio_mode_url') }}</p>
                                @else
                                    <p class="text-sm font-bold text-ds-ink">
                                        {{ $portfolioDocument->metadata['original_filename'] ?? 'Portofolio.pdf' }}
                                    </p>
                                    <p class="text-xs text-ds-muted">
                                        {{ __('inspector.documents.uploaded_at') }} {{ $portfolioDocument->created_at?->diffForHumans() }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($portfolioDocument->isUrl())
                                @if ($portfolioDocument->isSafeUrl())
                                    <a href="{{ $portfolioDocument->getSafeUrl() }}"
                                        target="_blank"
                                        rel="noopener noreferrer nofollow"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 min-h-[44px] bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs">
                                        <i aria-hidden="true" class="fa-solid fa-link text-xs"></i>
                                        {{ __('inspector.documents.open_url') }}
                                    </a>
                                @else
                                    <span class="text-xs text-ds-muted font-mono truncate max-w-xs block">
                                        {{ $portfolioDocument->metadata['url'] ?? '' }}
                                    </span>
                                @endif
                            @else
                                <a href="{{ $portfolioDocument->getTemporarySignedUrl() }}"
                                    target="_blank"
                                    rel="noopener noreferrer nofollow"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 min-h-[44px] bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs">
                                    <i aria-hidden="true" class="fa-solid fa-eye text-xs"></i>
                                    {{ __('inspector.documents.view') }}
                                </a>
                            @endif
                            <button type="button" wire:click="confirmDeleteDocument('{{ $portfolioDocument->id }}')"
                                class="inline-flex items-center gap-1.5 px-4 py-2 min-h-[44px] bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 font-bold text-xs rounded-xl transition-all shadow-xs">
                                <i aria-hidden="true" class="fa-solid fa-trash text-xs"></i>
                                {{ __('inspector.documents.delete') }}
                            </button>
                        </div>
                    </div>
                @else
                    <div class="space-y-4">
                        <div class="flex gap-4">
                            <label class="inline-flex items-center gap-2 text-sm text-ds-ink cursor-pointer min-h-[44px]">
                                <input
                                    type="radio"
                                    wire:model.live="portfolio_mode"
                                    value="file"
                                    class="text-ds-blue focus:ring-ds-blue/30"
                                />
                                {{ __('inspector.documents.portfolio_mode_file') }}
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-ds-ink cursor-pointer min-h-[44px]">
                                <input
                                    type="radio"
                                    wire:model.live="portfolio_mode"
                                    value="url"
                                    class="text-ds-blue focus:ring-ds-blue/30"
                                />
                                {{ __('inspector.documents.portfolio_mode_url') }}
                            </label>
                        </div>

                        <form wire:submit="uploadPortfolio" class="space-y-3">
                            @if ($portfolio_mode === 'file')
                                <input id="portfolio_file" type="file" wire:model="portfolio_file" aria-label="{{ __('inspector.documents.types.portfolio') }}"
                                    class="w-full min-h-[44px] px-3 py-2.5 rounded-xl text-sm bg-white text-ds-ink border border-ds-line file:mr-3 file:px-4 file:py-2 file:rounded-xl file:border-0 file:bg-ds-blue file:text-white file:text-xs file:font-bold" />
                                @error('portfolio_file')
                                    <p class="text-xs text-ds-danger" role="alert">{{ $message }}</p>
                                @enderror
                            @else
                                <input id="portfolio_url" type="url" wire:model="portfolio_url" placeholder="{{ __('inspector.documents.portfolio_url_placeholder') }}"
                                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors" />
                                @error('portfolio_url')
                                    <p class="text-xs text-ds-danger" role="alert">{{ $message }}</p>
                                @enderror
                            @endif

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    wire:target="uploadPortfolio, portfolio_file"
                                    class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                                >
                                    <span wire:loading.remove wire:target="uploadPortfolio, portfolio_file">
                                        {{ $portfolio_mode === 'file' ? __('inspector.documents.upload_button') : __('inspector.documents.save_link_button') }}
                                    </span>
                                    <span wire:loading wire:target="uploadPortfolio, portfolio_file">
                                        {{ __('ui.actions.loading') }}
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Modal Konfirmasi Hapus Dokumen --}}
    @if ($showDeleteModal)
        <x-ui.modal
            name="confirm-delete-modal"
            :show="true"
            :title="__('inspector.documents.confirm_delete_title')"
            :description="__('inspector.documents.confirm_delete_description')"
        >
            <x-slot:footer>
                <x-ui.button
                    wire:click="cancelDeleteDocument"
                    variant="secondary"
                >
                    {{ __('inspector.documents.cancel_button') }}
                </x-ui.button>
                <x-ui.button
                    wire:click="deleteDocument"
                    variant="danger"
                    wire:loading.attr="disabled"
                >
                    {{ __('inspector.documents.confirm_delete_button') }}
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
