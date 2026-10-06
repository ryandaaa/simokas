<?php

declare(strict_types=1);

namespace App\Domains\Rating\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Rating\Actions\HideRating;
use App\Domains\Rating\Actions\UnhideRating;
use App\Domains\Rating\Models\Rating;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

final class AdminRatingList extends Component
{
    use WithPagination;

    public string $filter = 'all';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public function hide(string $ratingId, HideRating $action): void
    {
        /** @var User $admin */
        $admin = Auth::user();

        /** @var Rating $rating */
        $rating = Rating::query()->findOrFail($ratingId);

        Gate::authorize('hide', $rating);

        $action->handle($admin, $rating);

        $this->feedbackMessage = __('rating.admin.hidden_success');
        $this->errorMessage = null;
    }

    public function unhide(string $ratingId, UnhideRating $action): void
    {
        /** @var User $admin */
        $admin = Auth::user();

        /** @var Rating $rating */
        $rating = Rating::query()->findOrFail($ratingId);

        Gate::authorize('unhide', $rating);

        $action->handle($admin, $rating);

        $this->feedbackMessage = __('rating.admin.unhidden_success');
        $this->errorMessage = null;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Rating::query()
            ->with(['transaction', 'inspector', 'buyer'])
            ->latest();

        if ($this->filter === 'visible') {
            $query->where('is_hidden', false);
        } elseif ($this->filter === 'hidden') {
            $query->where('is_hidden', true);
        }

        $ratings = $query->paginate(15);

        return view('rating::livewire.admin-rating-list', [
            'ratings' => $ratings,
        ])->layout('components.layouts.app', ['title' => __('rating.admin.title')]);
    }
}
