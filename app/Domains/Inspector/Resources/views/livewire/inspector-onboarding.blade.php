<div class="max-w-4xl mx-auto py-6 space-y-6">
    {{-- Header Banner / Status --}}
    @if ($profile && $profile->isRejected())
        <x-ui.alert
            variant="danger"
            :title="__('inspector.onboarding.rejected_alert_title')"
            :message="__('inspector.onboarding.rejected_alert_message', ['reason' => $profile->rejection_reason ?? '-'])"
        />
    @elseif ($profile && $profile->isApproved())
        <x-ui.alert
            variant="success"
            :title="__('inspector.onboarding.approved_alert_title')"
            :message="__('inspector.onboarding.approved_alert_message')"
        />
    @elseif ($isComplete)
        <x-ui.alert
            variant="success"
            :title="__('inspector.onboarding.queue_ready_title')"
            :message="__('inspector.onboarding.queue_ready_message')"
        />
    @else
        <x-ui.alert
            variant="warning"
            :title="__('inspector.onboarding.queue_missing_title')"
            :message="__('inspector.onboarding.queue_missing_message')"
        >
            <ul class="list-disc list-inside mt-2 text-sm space-y-1">
                @foreach ($missingTypes as $type)
                    <li>{{ $type->label() }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    {{-- Notification Messages --}}
    @if (session()->has('profile_success'))
        <x-ui.alert variant="success" :message="session('profile_success')" />
    @endif
    @if (session()->has('document_success'))
        <x-ui.alert variant="success" :message="session('document_success')" />
    @endif

    {{-- Section 1: Profil Keahlian --}}
    <x-ui.card
        :title="__('inspector.onboarding.profile_section')"
        :subtitle="__('inspector.onboarding.subtitle')"
    >
        <form wire:submit="saveProfile" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-text mb-2">
                    {{ __('inspector.onboarding.vehicle_types_label') }} <span class="text-danger">*</span>
                </label>
                <p class="text-xs text-muted mb-3">{{ __('inspector.onboarding.vehicle_types_hint') }}</p>
                <div class="flex flex-wrap gap-6">
                    <x-ui.checkbox
                        id="type_car"
                        value="car"
                        wire:model="vehicle_types"
                        :label="__('inspector.vehicle_types.car')"
                    />
                    <x-ui.checkbox
                        id="type_motorcycle"
                        value="motorcycle"
                        wire:model="vehicle_types"
                        :label="__('inspector.vehicle_types.motorcycle')"
                    />
                </div>
                @error('vehicle_types')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
                @error('vehicle_types.*')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-ui.input
                    id="experience_years"
                    type="number"
                    min="0"
                    max="50"
                    wire:model="experience_years"
                    :label="__('inspector.onboarding.experience_label')"
                    :hint="__('inspector.onboarding.experience_hint')"
                    :error="$errors->first('experience_years')"
                    required
                />
            </div>

            <div class="flex justify-end">
                <x-ui.button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                    wire:target="saveProfile"
                >
                    <span wire:loading.remove wire:target="saveProfile">
                        {{ __('inspector.onboarding.save_profile') }}
                    </span>
                    <span wire:loading wire:target="saveProfile">
                        {{ __('ui.actions.loading') }}
                    </span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Section 2: Unggah Dokumen Verifikasi --}}
    <x-ui.card
        :title="__('inspector.onboarding.documents_section')"
        :subtitle="__('inspector.onboarding.documents_hint')"
    >
        <div class="space-y-8 divide-y divide-muted/10">

            {{-- 1. KTP --}}
            <div class="pt-6 first:pt-0 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-text">
                            {{ __('inspector.documents.types.ktp') }} <span class="text-danger">*</span>
                        </h3>
                        <p class="text-xs text-muted">{{ __('inspector.documents.file_requirements_ktp') }}</p>
                    </div>
                    @if ($ktpDocument)
                        <x-ui.status-chip
                            :label="__('inspector.status.approved')"
                            variant="success"
                            icon="centang"
                        />
                    @else
                        <x-ui.status-chip
                            :label="__('inspector.status.pending')"
                            variant="warning"
                            icon="jam"
                        />
                    @endif
                </div>

                @if ($ktpDocument)
                    <div class="flex items-center justify-between p-4 bg-bg rounded-xl border border-muted/20">
                        <div class="flex items-center gap-3">
                            <x-ui.icon name="dokumen" class="w-6 h-6 text-primary" />
                            <div>
                                <p class="text-sm font-medium text-text">
                                    {{ $ktpDocument->metadata['original_filename'] ?? 'KTP.pdf' }}
                                </p>
                                <p class="text-xs text-muted">
                                    {{ __('inspector.documents.uploaded_at') }} {{ $ktpDocument->created_at?->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-ui.button
                                :href="$ktpDocument->getTemporarySignedUrl()"
                                target="_blank"
                                rel="noopener noreferrer nofollow"
                                variant="secondary"
                                icon="mata"
                            >
                                {{ __('inspector.documents.view') }}
                            </x-ui.button>
                            <x-ui.button
                                wire:click="confirmDeleteDocument('{{ $ktpDocument->id }}')"
                                variant="danger"
                                icon="sampah"
                            >
                                {{ __('inspector.documents.delete') }}
                            </x-ui.button>
                        </div>
                    </div>
                @else
                    <form wire:submit="uploadKtp" class="space-y-3">
                        <x-ui.input
                            id="ktp_file"
                            type="file"
                            wire:model="ktp_file"
                            :error="$errors->first('ktp_file')"
                        />
                        <div class="flex justify-end">
                            <x-ui.button
                                type="submit"
                                variant="primary"
                                wire:loading.attr="disabled"
                                wire:target="uploadKtp, ktp_file"
                            >
                                <span wire:loading.remove wire:target="uploadKtp, ktp_file">
                                    {{ __('inspector.documents.upload_button') }}
                                </span>
                                <span wire:loading wire:target="uploadKtp, ktp_file">
                                    {{ __('ui.actions.loading') }}
                                </span>
                            </x-ui.button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- 2. Sertifikat Pendukung (Maksimal 3) --}}
            <div class="pt-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-text">
                            {{ __('inspector.documents.types.certificate') }} <span class="text-danger">*</span>
                        </h3>
                        <p class="text-xs text-muted">
                            {{ __('inspector.documents.file_requirements_certificate') }} —
                            <span class="font-medium text-text">
                                {{ __('inspector.documents.certificate_count', ['count' => $certificateDocuments->count()]) }}
                            </span>
                        </p>
                    </div>
                    @if ($certificateDocuments->count() > 0)
                        <x-ui.status-chip
                            :label="__('inspector.status.approved')"
                            variant="success"
                            icon="centang"
                        />
                    @else
                        <x-ui.status-chip
                            :label="__('inspector.status.pending')"
                            variant="warning"
                            icon="jam"
                        />
                    @endif
                </div>

                @if ($certificateDocuments->count() > 0)
                    <div class="space-y-2">
                        @foreach ($certificateDocuments as $cert)
                            <div class="flex items-center justify-between p-4 bg-bg rounded-xl border border-muted/20">
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="dokumen" class="w-6 h-6 text-primary" />
                                    <div>
                                        <p class="text-sm font-medium text-text">
                                            {{ $cert->metadata['original_filename'] ?? 'Sertifikat.pdf' }}
                                        </p>
                                        <p class="text-xs text-muted">
                                            {{ __('inspector.documents.uploaded_at') }} {{ $cert->created_at?->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-ui.button
                                        :href="$cert->getTemporarySignedUrl()"
                                        target="_blank"
                                        rel="noopener noreferrer nofollow"
                                        variant="secondary"
                                        icon="mata"
                                    >
                                        {{ __('inspector.documents.view') }}
                                    </x-ui.button>
                                    <x-ui.button
                                        wire:click="confirmDeleteDocument('{{ $cert->id }}')"
                                        variant="danger"
                                        icon="sampah"
                                    >
                                        {{ __('inspector.documents.delete') }}
                                    </x-ui.button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-muted italic">{{ __('inspector.documents.empty_certificates') }}</p>
                @endif

                @if ($certificateDocuments->count() < 3)
                    <form wire:submit="uploadCertificate" class="space-y-3 pt-2">
                        <x-ui.input
                            id="certificate_file"
                            type="file"
                            wire:model="certificate_file"
                            :error="$errors->first('certificate_file')"
                        />
                        <div class="flex justify-end">
                            <x-ui.button
                                type="submit"
                                variant="primary"
                                wire:loading.attr="disabled"
                                wire:target="uploadCertificate, certificate_file"
                            >
                                <span wire:loading.remove wire:target="uploadCertificate, certificate_file">
                                    {{ __('inspector.documents.upload_button') }}
                                </span>
                                <span wire:loading wire:target="uploadCertificate, certificate_file">
                                    {{ __('ui.actions.loading') }}
                                </span>
                            </x-ui.button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- 3. CV --}}
            <div class="pt-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-text">
                            {{ __('inspector.documents.types.cv') }} <span class="text-danger">*</span>
                        </h3>
                        <p class="text-xs text-muted">{{ __('inspector.documents.file_requirements_cv') }}</p>
                    </div>
                    @if ($cvDocument)
                        <x-ui.status-chip
                            :label="__('inspector.status.approved')"
                            variant="success"
                            icon="centang"
                        />
                    @else
                        <x-ui.status-chip
                            :label="__('inspector.status.pending')"
                            variant="warning"
                            icon="jam"
                        />
                    @endif
                </div>

                @if ($cvDocument)
                    <div class="flex items-center justify-between p-4 bg-bg rounded-xl border border-muted/20">
                        <div class="flex items-center gap-3">
                            <x-ui.icon name="dokumen" class="w-6 h-6 text-primary" />
                            <div>
                                <p class="text-sm font-medium text-text">
                                    {{ $cvDocument->metadata['original_filename'] ?? 'CV.pdf' }}
                                </p>
                                <p class="text-xs text-muted">
                                    {{ __('inspector.documents.uploaded_at') }} {{ $cvDocument->created_at?->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-ui.button
                                :href="$cvDocument->getTemporarySignedUrl()"
                                target="_blank"
                                rel="noopener noreferrer nofollow"
                                variant="secondary"
                                icon="mata"
                            >
                                {{ __('inspector.documents.view') }}
                            </x-ui.button>
                            <x-ui.button
                                wire:click="confirmDeleteDocument('{{ $cvDocument->id }}')"
                                variant="danger"
                                icon="sampah"
                            >
                                {{ __('inspector.documents.delete') }}
                            </x-ui.button>
                        </div>
                    </div>
                @else
                    <form wire:submit="uploadCv" class="space-y-3">
                        <x-ui.input
                            id="cv_file"
                            type="file"
                            wire:model="cv_file"
                            :error="$errors->first('cv_file')"
                        />
                        <div class="flex justify-end">
                            <x-ui.button
                                type="submit"
                                variant="primary"
                                wire:loading.attr="disabled"
                                wire:target="uploadCv, cv_file"
                            >
                                <span wire:loading.remove wire:target="uploadCv, cv_file">
                                    {{ __('inspector.documents.upload_button') }}
                                </span>
                                <span wire:loading wire:target="uploadCv, cv_file">
                                    {{ __('ui.actions.loading') }}
                                </span>
                            </x-ui.button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- 4. Portofolio --}}
            <div class="pt-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-text">
                            {{ __('inspector.documents.types.portfolio') }} <span class="text-danger">*</span>
                        </h3>
                        <p class="text-xs text-muted">{{ __('inspector.documents.file_requirements_portfolio') }}</p>
                    </div>
                    @if ($portfolioDocument)
                        <x-ui.status-chip
                            :label="__('inspector.status.approved')"
                            variant="success"
                            icon="centang"
                        />
                    @else
                        <x-ui.status-chip
                            :label="__('inspector.status.pending')"
                            variant="warning"
                            icon="jam"
                        />
                    @endif
                </div>

                @if ($portfolioDocument)
                    <div class="flex items-center justify-between p-4 bg-bg rounded-xl border border-muted/20">
                        <div class="flex items-center gap-3">
                            <x-ui.icon name="dokumen" class="w-6 h-6 text-primary" />
                            <div>
                                @if ($portfolioDocument->isUrl())
                                    <p class="text-sm font-medium text-text">
                                        {{ $portfolioDocument->metadata['url'] ?? '' }}
                                    </p>
                                    <p class="text-xs text-muted">{{ __('inspector.documents.portfolio_mode_url') }}</p>
                                @else
                                    <p class="text-sm font-medium text-text">
                                        {{ $portfolioDocument->metadata['original_filename'] ?? 'Portofolio.pdf' }}
                                    </p>
                                    <p class="text-xs text-muted">
                                        {{ __('inspector.documents.uploaded_at') }} {{ $portfolioDocument->created_at?->diffForHumans() }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($portfolioDocument->isUrl())
                                @if ($portfolioDocument->isSafeUrl())
                                    <x-ui.button
                                        :href="$portfolioDocument->getSafeUrl()"
                                        target="_blank"
                                        rel="noopener noreferrer nofollow"
                                        variant="secondary"
                                        icon="tautan"
                                    >
                                        {{ __('inspector.documents.open_url') }}
                                    </x-ui.button>
                                @else
                                    <span class="text-xs text-muted font-mono truncate max-w-xs block">
                                        {{ $portfolioDocument->metadata['url'] ?? '' }}
                                    </span>
                                @endif
                            @else
                                <x-ui.button
                                    :href="$portfolioDocument->getTemporarySignedUrl()"
                                    target="_blank"
                                    rel="noopener noreferrer nofollow"
                                    variant="secondary"
                                    icon="mata"
                                >
                                    {{ __('inspector.documents.view') }}
                                </x-ui.button>
                            @endif
                            <x-ui.button
                                wire:click="confirmDeleteDocument('{{ $portfolioDocument->id }}')"
                                variant="danger"
                                icon="sampah"
                            >
                                {{ __('inspector.documents.delete') }}
                            </x-ui.button>
                        </div>
                    </div>
                @else
                    <div class="space-y-4">
                        <div class="flex gap-4">
                            <label class="inline-flex items-center gap-2 text-sm text-text cursor-pointer">
                                <input
                                    type="radio"
                                    wire:model.live="portfolio_mode"
                                    value="file"
                                    class="text-primary focus:ring-primary"
                                />
                                {{ __('inspector.documents.portfolio_mode_file') }}
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-text cursor-pointer">
                                <input
                                    type="radio"
                                    wire:model.live="portfolio_mode"
                                    value="url"
                                    class="text-primary focus:ring-primary"
                                />
                                {{ __('inspector.documents.portfolio_mode_url') }}
                            </label>
                        </div>

                        <form wire:submit="uploadPortfolio" class="space-y-3">
                            @if ($portfolio_mode === 'file')
                                <x-ui.input
                                    id="portfolio_file"
                                    type="file"
                                    wire:model="portfolio_file"
                                    :error="$errors->first('portfolio_file')"
                                />
                            @else
                                <x-ui.input
                                    id="portfolio_url"
                                    type="url"
                                    wire:model="portfolio_url"
                                    :placeholder="__('inspector.documents.portfolio_url_placeholder')"
                                    :error="$errors->first('portfolio_url')"
                                />
                            @endif

                            <div class="flex justify-end">
                                <x-ui.button
                                    type="submit"
                                    variant="primary"
                                    wire:loading.attr="disabled"
                                    wire:target="uploadPortfolio, portfolio_file"
                                >
                                    <span wire:loading.remove wire:target="uploadPortfolio, portfolio_file">
                                        {{ $portfolio_mode === 'file' ? __('inspector.documents.upload_button') : __('inspector.documents.save_link_button') }}
                                    </span>
                                    <span wire:loading wire:target="uploadPortfolio, portfolio_file">
                                        {{ __('ui.actions.loading') }}
                                    </span>
                                </x-ui.button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

        </div>
    </x-ui.card>

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
