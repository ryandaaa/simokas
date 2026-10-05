<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Actions\CreateInspectorProfile;
use App\Domains\Inspector\Actions\RemoveInspectorDocument;
use App\Domains\Inspector\Actions\ResubmitInspector;
use App\Domains\Inspector\Actions\UploadInspectorDocument;
use App\Domains\Inspector\Enums\DocumentType;
use App\Domains\Inspector\Models\InspectorDocument;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Inspector\Rules\SafePortfolioUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

final class InspectorOnboarding extends Component
{
    use WithFileUploads;

    /**
     * @var array<int, string>
     */
    public array $vehicle_types = [];

    public int $experience_years = 0;

    /** @var mixed */
    public $ktp_file;

    /** @var mixed */
    public $certificate_file;

    /** @var mixed */
    public $cv_file;

    /** @var mixed */
    public $portfolio_file;

    public string $portfolio_url = '';

    public string $portfolio_mode = 'file';

    public ?string $documentToDeleteId = null;

    public bool $showDeleteModal = false;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var InspectorProfile|null $profile */
        $profile = InspectorProfile::query()->where('user_id', $user->id)->first();

        if ($profile !== null) {
            $this->vehicle_types = $profile->vehicle_types;
            $this->experience_years = $profile->experience_years;
        }
    }

    public function saveProfile(CreateInspectorProfile $action): void
    {
        $this->validate([
            'vehicle_types' => ['required', 'array', 'min:1'],
            'vehicle_types.*' => ['required', 'string', 'in:car,motorcycle'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $action->handle($user, [
            'vehicle_types' => $this->vehicle_types,
            'experience_years' => $this->experience_years,
        ]);

        session()->flash('profile_success', __('inspector.onboarding.profile_saved'));
    }

    public function uploadKtp(UploadInspectorDocument $action): void
    {
        $this->validate([
            'ktp_file' => ['required', 'file', 'max:2048', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $action->handle($user, DocumentType::Ktp, $this->ktp_file);

        $this->reset('ktp_file');
        session()->flash('document_success', __('inspector.documents.uploaded'));
    }

    public function uploadCertificate(UploadInspectorDocument $action): void
    {
        $this->validate([
            'certificate_file' => ['required', 'file', 'max:2048', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $action->handle($user, DocumentType::Certificate, $this->certificate_file);

        $this->reset('certificate_file');
        session()->flash('document_success', __('inspector.documents.uploaded'));
    }

    public function uploadCv(UploadInspectorDocument $action): void
    {
        $this->validate([
            'cv_file' => ['required', 'file', 'max:2048', 'mimes:pdf'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $action->handle($user, DocumentType::Cv, $this->cv_file);

        $this->reset('cv_file');
        session()->flash('document_success', __('inspector.documents.uploaded'));
    }

    public function uploadPortfolio(UploadInspectorDocument $action): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($this->portfolio_mode === 'url') {
            $this->validate([
                'portfolio_url' => ['required', 'string', new SafePortfolioUrl],
            ]);

            $action->handle($user, DocumentType::Portfolio, $this->portfolio_url);
            $this->reset('portfolio_url');
        } else {
            $this->validate([
                'portfolio_file' => ['required', 'file', 'max:2048', 'mimes:pdf,jpg,jpeg,png'],
            ]);

            $action->handle($user, DocumentType::Portfolio, $this->portfolio_file);
            $this->reset('portfolio_file');
        }

        session()->flash('document_success', __('inspector.documents.uploaded'));
    }

    public function confirmDeleteDocument(string $documentId): void
    {
        $this->documentToDeleteId = $documentId;
        $this->showDeleteModal = true;
    }

    public function cancelDeleteDocument(): void
    {
        $this->documentToDeleteId = null;
        $this->showDeleteModal = false;
    }

    public function deleteDocument(RemoveInspectorDocument $action): void
    {
        if ($this->documentToDeleteId === null) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        /** @var InspectorDocument $document */
        $document = InspectorDocument::query()
            ->where('id', $this->documentToDeleteId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $action->handle($user, $document);

        $this->documentToDeleteId = null;
        $this->showDeleteModal = false;
        session()->flash('document_success', __('inspector.documents.deleted'));
    }

    public function resubmit(ResubmitInspector $action): void
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var InspectorProfile $profile */
        $profile = InspectorProfile::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $action->handle($user, $profile);

        session()->flash('profile_success', __('inspector.onboarding.resubmitted_success'));
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var InspectorProfile|null $profile */
        $profile = InspectorProfile::query()->where('user_id', $user->id)->first();

        /** @var Collection<int, InspectorDocument> $documents */
        $documents = InspectorDocument::query()->where('user_id', $user->id)->get();

        $ktpDocument = $documents->firstWhere('type', DocumentType::Ktp);
        $certificateDocuments = $documents->where('type', DocumentType::Certificate);
        $cvDocument = $documents->firstWhere('type', DocumentType::Cv);
        $portfolioDocument = $documents->firstWhere('type', DocumentType::Portfolio);

        $missingTypes = $profile !== null ? $profile->missingDocumentTypes() : [
            DocumentType::Ktp,
            DocumentType::Certificate,
            DocumentType::Cv,
            DocumentType::Portfolio,
        ];

        return view('inspector::livewire.inspector-onboarding', [
            'user' => $user,
            'profile' => $profile,
            'ktpDocument' => $ktpDocument,
            'certificateDocuments' => $certificateDocuments,
            'cvDocument' => $cvDocument,
            'portfolioDocument' => $portfolioDocument,
            'missingTypes' => $missingTypes,
            'isComplete' => $profile !== null && $profile->hasCompleteDocuments(),
        ])->layout('components.layouts.app', ['title' => __('inspector.onboarding.title')]);
    }
}
