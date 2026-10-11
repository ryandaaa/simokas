<?php

declare(strict_types=1);

namespace App\Domains\Result\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Rating\Contracts\RatingReader;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\ResultPhoto;
use App\Domains\Result\Policies\ResultAccessPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;
use Livewire\Component;

final class ViewResultPage extends Component
{
    public InspectionResult $result;

    public bool $showAccessModal = false;

    public bool $isRequestingAccess = false;

    public function mount(Listing $listing): void
    {
        // Find the inspection result for this listing
        $result = InspectionResult::query()
            ->with(['inspector', 'photos', 'items.templateItem', 'items.photos'])
            ->whereHas('job', function ($query) use ($listing) {
                $query->where('listing_id', $listing->id);
            })->first();

        if ($result === null) {
            abort(404);
        }

        $this->result = $result;
    }

    public function requestAccess(): mixed
    {
        $policy = app(ResultAccessPolicy::class);
        if (! $policy->canRequestPaidAccess(auth()->user(), $this->result)) {
            return null;
        }

        return redirect(route('results.access', ['listing' => $this->result->job->listing_id]));
    }

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();

        $policy = app(ResultAccessPolicy::class);
        $hasAccess = $policy->viewDetails($user, $this->result);
        $canRequestAccess = $policy->canRequestPaidAccess($user, $this->result);

        $photoUrls = [];
        if ($hasAccess) {
            $this->result->loadMissing(['photos', 'items.templateItem', 'items.photos', 'inspector']);
            $photoUrls = $this->result->photos->mapWithKeys(function (ResultPhoto $photo): array {
                return [
                    $photo->id => URL::temporarySignedRoute(
                        'results.photos.show',
                        now()->addMinutes(15),
                        [
                            'result' => $this->result->id,
                            'photo' => $photo->id,
                        ]
                    ),
                ];
            })->all();
        }

        /** @var RatingReader $ratingReader */
        $ratingReader = app(RatingReader::class);
        $inspectorRating = $ratingReader->getInspectorRating($this->result->inspector_id);

        return view('result::view-result-page', [
            'result' => $this->result,
            'hasAccess' => $hasAccess,
            'canRequestAccess' => $canRequestAccess,
            'accessFee' => $this->result->job->fee_amount,
            'photoUrls' => $photoUrls,
            'inspectorRating' => $inspectorRating,
        ])->layout('components.layouts.app', ['title' => __('result.inspection_results')]);
    }
}
