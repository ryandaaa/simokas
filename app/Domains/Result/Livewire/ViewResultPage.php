<?php

declare(strict_types=1);

namespace App\Domains\Result\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Policies\ResultAccessPolicy;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class ViewResultPage extends Component
{
    public InspectionResult $result;

    public bool $showAccessModal = false;

    public bool $isRequestingAccess = false;

    public function mount(InspectionResult $result): void
    {
        $this->result = $result;
    }

    public function requestAccess(): mixed
    {
        $policy = app(ResultAccessPolicy::class);
        if (! $policy->canRequestPaidAccess(auth()->user(), $this->result)) {
            return;
        }

        return redirect(route('results.request-access', $this->result));
    }

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();

        $policy = app(ResultAccessPolicy::class);
        $hasAccess = $policy->viewDetails($user, $this->result);
        $canRequestAccess = $policy->canRequestPaidAccess($user, $this->result);

        return view('result::view-result-page', [
            'result' => $this->result,
            'hasAccess' => $hasAccess,
            'canRequestAccess' => $canRequestAccess,
            'accessFee' => $this->result->job->fee_amount,
        ]);
    }
}
