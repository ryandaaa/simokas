<?php

declare(strict_types=1);

namespace App\Domains\Result\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Actions\DeleteResultPhoto;
use App\Domains\Result\Actions\GetOrCreateDraftResult;
use App\Domains\Result\Actions\SaveDraftResult;
use App\Domains\Result\Actions\SubmitInspectionResult;
use App\Domains\Result\Actions\UploadResultPhoto;
use App\Domains\Result\DTOs\UploadPhotoData;
use App\Domains\Result\Enums\ItemRating;
use App\Domains\Result\Exceptions\MinPhotosNotMetException;
use App\Domains\Result\Exceptions\RequiredItemsMissingException;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\ResultPhoto;
use App\Domains\Result\Models\TemplateItem;
use App\Support\Clock;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

final class InspectionFormPage extends Component
{
    use WithFileUploads;

    public InspectionJob $job;

    public ?InspectionResult $result = null;

    /** @var array<string, array{rating: string, note: string}> */
    public array $items = [];

    public string $summaryNote = '';

    public int $currentCategoryIndex = 0;

    public ?string $actionError = null;

    public ?string $actionSuccess = null;

    public bool $draftSaving = false;

    public ?string $lastSavedAt = null;

    /** @var array<string> */
    public array $categories = [];

    /** @var array<string, mixed> */
    public array $tempPhotos = [];

    /** @var array<string, array{id: string, url: string, captured_at: string, stamped_at: ?string}> */
    public array $photos = [];

    public bool $showSubmitConfirmation = false;

    public int $submitModalKey = 0;

    public bool $isSubmitting = false;

    /**
     * Pesan sukses/gagal hanya milik respons aksi yang menetapkannya.
     * Tanpa ini, banner basi (mis. "Foto berhasil diunggah" dari item lain)
     * ikut tampil pada render berikutnya dan menipu pengguna.
     */
    public function hydrate(): void
    {
        $this->actionError = null;
        $this->actionSuccess = null;
    }

    public function mount(InspectionJob $job, GetOrCreateDraftResult $getOrCreateDraft): void
    {
        // Authorization: only the assigned inspector on an in_progress job (RES-R2)
        Gate::authorize('fill-inspection', $job);

        $this->job = $job;

        try {
            $this->result = $getOrCreateDraft->handle(Auth::user(), $job);
        } catch (DomainException $e) {
            $this->actionError = $e->getMessage();

            return;
        }

        $this->loadFromResult();
    }

    private function loadFromResult(): void
    {
        if ($this->result === null) {
            return;
        }

        $this->summaryNote = $this->result->summary_note ?? '';
        $this->lastSavedAt = $this->result->updated_at
            ? Clock::toWib($this->result->updated_at)->format('H:i')
            : null;

        // Build categories list from template items
        /** @var Collection<int, TemplateItem> $templateItems */
        $templateItems = $this->result->template->items;
        $this->categories = $templateItems
            ->pluck('category')
            ->unique()
            ->values()
            ->toArray();

        // Initialize items state from existing draft items
        foreach ($templateItems as $templateItem) {
            $existingItem = $this->result->items
                ->firstWhere('template_item_id', $templateItem->id);

            $this->items[$templateItem->id] = [
                'rating' => ($existingItem !== null && $existingItem->rating !== null) ? $existingItem->rating->value : '',
                'note' => ($existingItem !== null && $existingItem->note !== null) ? $existingItem->note : '',
            ];
        }

        // Initialize photos state from existing draft photos
        $this->result->load('photos');
        foreach ($this->result->photos as $photo) {
            $resultItem = $this->result->items->firstWhere('id', $photo->result_item_id);
            if ($resultItem !== null) {
                $this->photos[$resultItem->template_item_id] = [
                    'id' => $photo->id,
                    'url' => $photo->temporaryUrl(60),
                    'captured_at' => Clock::formatWib($photo->captured_at),
                    'stamped_at' => $photo->stamped_at ? Clock::formatWib($photo->stamped_at) : null,
                ];
            }
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'items' => ['array'],
            'items.*.rating' => ['nullable', 'string', 'in:good,fair,poor'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
            'summaryNote' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function saveDraft(SaveDraftResult $saveDraft): void
    {
        Gate::authorize('updateDraft', $this->result);

        $this->validate();

        $this->draftSaving = true;
        $this->actionError = null;
        $this->actionSuccess = null;

        try {
            $itemsData = $this->buildItemsData();
            $this->result = $saveDraft->handle(
                Auth::user(),
                $this->result,
                $itemsData,
                $this->summaryNote !== '' ? $this->summaryNote : null,
            );

            $this->lastSavedAt = Clock::toWib(Carbon::now())->format('H:i');
            $this->actionSuccess = __('inspection.form.draft_saved');
        } catch (DomainException $e) {
            $this->actionError = $e->getMessage();
        } finally {
            $this->draftSaving = false;
        }
    }

    public function nextCategory(SaveDraftResult $saveDraft): void
    {
        Gate::authorize('updateDraft', $this->result);

        $this->validate();

        // Auto-save draft when navigating categories (RES-R2)
        try {
            $itemsData = $this->buildItemsData();
            $this->result = $saveDraft->handle(
                Auth::user(),
                $this->result,
                $itemsData,
                $this->summaryNote !== '' ? $this->summaryNote : null,
            );
            $this->lastSavedAt = Clock::toWib(Carbon::now())->format('H:i');
        } catch (DomainException $e) {
            $this->actionError = $e->getMessage();

            return;
        }

        if ($this->currentCategoryIndex < count($this->categories) - 1) {
            $this->currentCategoryIndex++;
            $this->dispatch('inspection-category-changed');
        }
    }

    public function previousCategory(): void
    {
        if ($this->currentCategoryIndex > 0) {
            $this->currentCategoryIndex--;
            $this->dispatch('inspection-category-changed');
        }
    }

    /**
     * @return array<string, array{template_item_id: string, rating: string, note: string}>
     */
    private function buildItemsData(): array
    {
        $data = [];
        foreach ($this->items as $templateItemId => $values) {
            $data[$templateItemId] = [
                'template_item_id' => $templateItemId,
                'rating' => $values['rating'],
                'note' => $values['note'],
            ];
        }

        return $data;
    }

    /**
     * @return Collection<int, TemplateItem>
     */
    public function getCurrentCategoryItems(): Collection
    {
        if ($this->result === null) {
            return collect();
        }

        $currentCategory = $this->categories[$this->currentCategoryIndex] ?? null;
        if ($currentCategory === null) {
            return collect();
        }

        return $this->result->template->items
            ->where('category', $currentCategory)
            ->values();
    }

    /**
     * Menerapkan foto yang sudah diunggah ke item. Mengembalikan data foto
     * (id + url) agar klien menampilkannya langsung dari kanal data —
     * tidak bergantung pada morph yang bisa hilang di jaringan lambat.
     *
     * @return array{id: string, url: string}|null
     */
    public function applyPhoto(
        string $templateItemId,
        UploadResultPhoto $uploadPhoto,
        ?string $stampedAt = null
    ): ?array {
        Gate::authorize('updateDraft', $this->result);

        $file = $this->tempPhotos[$templateItemId] ?? null;
        if ($file === null) {
            $this->actionError = __('result.photos.not_ready');
            $this->actionSuccess = null;

            return null;
        }

        $this->actionError = null;
        $this->actionSuccess = null;

        try {
            $parsedStampedAt = $stampedAt ? Carbon::parse($stampedAt) : now();

            $dto = new UploadPhotoData(
                file: $file,
                templateItemId: $templateItemId,
                stampedAt: $parsedStampedAt,
                isCameraCapture: true,
            );

            /** @var User $inspector */
            $inspector = Auth::user();
            $photo = $uploadPhoto->handle($inspector, $this->result, $dto);

            $this->photos[$templateItemId] = [
                'id' => $photo->id,
                'url' => $photo->temporaryUrl(60),
                'captured_at' => Clock::formatWib($photo->captured_at),
                'stamped_at' => $photo->stamped_at ? Clock::formatWib($photo->stamped_at) : null,
            ];

            unset($this->tempPhotos[$templateItemId]);
            $this->actionSuccess = __('result.photos.upload_success');

            Log::info('Inspection photo uploaded.', [
                'job_id' => (string) $this->job->getKey(),
                'result_id' => (string) $this->result->getKey(),
                'template_item_id' => (string) $templateItemId,
                'photo_id' => (string) $photo->getKey(),
                'inspector_id' => (string) $inspector->getKey(),
            ]);

            return ['id' => (string) $photo->getKey(), 'url' => $photo->temporaryUrl(60)];
        } catch (DomainException $e) {
            $this->actionError = $e->getMessage();

            return null;
        }
    }

    public function retakePhoto(
        string $templateItemId,
        DeleteResultPhoto $deletePhoto
    ): void {
        Gate::authorize('updateDraft', $this->result);

        $photoData = $this->photos[$templateItemId] ?? null;
        if ($photoData !== null) {
            /** @var ResultPhoto|null $photo */
            $photo = ResultPhoto::query()->find($photoData['id']);
            if ($photo !== null) {
                /** @var User $inspector */
                $inspector = Auth::user();
                $deletePhoto->handle($inspector, $this->result, $photo);
            }
        }

        unset($this->photos[$templateItemId]);
        unset($this->tempPhotos[$templateItemId]);
        $this->actionSuccess = __('result.photos.delete_success');
    }

    public function getRatings(): array
    {
        return ItemRating::cases();
    }

    public function openSubmitConfirmation(): void
    {
        // [DIAGNOSTIK SEMENTARA] Jejak pembukaan modal konfirmasi.
        Log::info('Inspection submit modal opened.', [
            'job_id' => $this->job->getKey(),
            'inspector_id' => Auth::id(),
        ]);

        $this->showSubmitConfirmation = true;
        // Naikkan kunci agar DOM modal selalu dibuat ulang. Tanpa ini, bila modal
        // pernah ditutup sepihak di sisi Alpine (backdrop/X/Escape), Alpine
        // menyimpan open=false dan modal tak akan pernah terbuka lagi.
        $this->submitModalKey++;
    }

    public function cancelSubmit(): void
    {
        // [DIAGNOSTIK SEMENTARA] Jejak penutupan modal konfirmasi.
        Log::info('Inspection submit modal closed.', [
            'job_id' => $this->job->getKey(),
            'inspector_id' => Auth::id(),
        ]);

        $this->showSubmitConfirmation = false;
    }

    public function submitResult(SubmitInspectionResult $submitAction): void
    {
        // [DIAGNOSTIK SEMENTARA] Jejak tiap penekanan tombol Kirim Hasil.
        Log::info('Inspection submit pressed.', [
            'job_id' => $this->job->getKey(),
            'result_id' => $this->result?->getKey(),
            'inspector_id' => Auth::id(),
        ]);

        Gate::authorize('updateDraft', $this->result);

        try {
            $this->validate();
        } catch (ValidationException $e) {
            // [DIAGNOSTIK SEMENTARA] Catat bidang yang gagal validasi.
            Log::info('Inspection submit validation failed.', $e->errors());
            // Tutup modal agar ringkasan galat terlihat, lalu teruskan agar
            // galat per-bidang tetap menempel di input masing-masing.
            $this->showSubmitConfirmation = false;
            $this->actionError = __('inspection.form.error_validation');
            throw $e;
        }
        $this->isSubmitting = true;
        $this->actionError = null;
        $this->actionSuccess = null;

        try {
            // Save draft first before submitting
            $itemsData = $this->buildItemsData();
            $saveDraft = app(SaveDraftResult::class);
            $this->result = $saveDraft->handle(
                Auth::user(),
                $this->result,
                $itemsData,
                $this->summaryNote !== '' ? $this->summaryNote : null,
            );

            // Submit result - Action handles validation and atomicity
            /** @var User $inspector */
            $inspector = Auth::user();
            $this->result = $submitAction->handle($inspector, $this->result);

            $this->showSubmitConfirmation = false;
            $this->actionSuccess = __('inspection.form.result_submitted_success');

            // Redirect to job detail after successful submission
            $this->redirect(route('jobs.show', $this->job->id), navigate: true);
        } catch (RequiredItemsMissingException $e) {
            // Tutup modal agar pesan galat terlihat (jangan tertutup backdrop modal).
            $this->showSubmitConfirmation = false;
            $this->actionError = __('inspection.form.error_required_items_missing', [
                'items' => implode(', ', array_map(fn ($item) => $item['label'], $e->getMissingItems())),
            ]);
        } catch (MinPhotosNotMetException $e) {
            // Tutup modal agar pesan galat terlihat (jangan tertutup backdrop modal).
            $this->showSubmitConfirmation = false;
            $this->actionError = __('inspection.form.error_min_photos_not_met', [
                'items' => implode(', ', array_map(fn ($item) => $item['label'], $e->getMissingPhotoItems())),
            ]);
        } catch (DomainException $e) {
            // Tutup modal agar pesan galat terlihat (jangan tertutup backdrop modal).
            $this->showSubmitConfirmation = false;
            $this->actionError = $e->getMessage();
        } catch (\Throwable $e) {
            // Jaring pengaman: galat tak terduga (bukan DomainException) tidak boleh
            // membuat tombol tampak mati. Catat ke log, tutup modal, tampilkan pesan.
            Log::error('Inspection submit failed unexpectedly.', [
                'job_id' => $this->job->getKey(),
                'result_id' => $this->result?->getKey(),
                'inspector_id' => Auth::id(),
                'exception' => $e::class.': '.$e->getMessage(),
            ]);
            $this->showSubmitConfirmation = false;
            $this->actionError = __('inspection.form.error_unexpected');
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function render(): View
    {
        $currentItems = $this->getCurrentCategoryItems();

        return view('result::livewire.inspection-form-page', [
            'job' => $this->job,
            'result' => $this->result,
            'currentItems' => $currentItems,
            'categories' => $this->categories,
            'currentCategoryIndex' => $this->currentCategoryIndex,
            'ratings' => ItemRating::cases(),
            'totalCategories' => count($this->categories),
        ])->layout('components.layouts.app', ['title' => __('inspection.form.title')]);
    }
}
