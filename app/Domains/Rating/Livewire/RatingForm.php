<?php

declare(strict_types=1);

namespace App\Domains\Rating\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Rating\Actions\SubmitRating;
use App\Domains\Rating\Models\Rating;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class RatingForm extends Component
{
    public string $transactionId = '';

    public int $score = 5;

    public string $comment = '';

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public bool $isSubmitted = false;

    public ?int $existingScore = null;

    public ?string $existingComment = null;

    public function mount(string $transactionId): void
    {
        $this->transactionId = $transactionId;

        /** @var Rating|null $existing */
        $existing = Rating::query()->where('transaction_id', $transactionId)->first();
        if ($existing !== null) {
            $this->isSubmitted = true;
            $this->existingScore = $existing->score;
            $this->existingComment = $existing->comment;
            $this->score = $existing->score;
            $this->comment = $existing->comment ?? '';
        }
    }

    public function submit(SubmitRating $action): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            $this->errorMessage = __('rating.unauthorized');

            return;
        }

        /** @var Transaction|null $transaction */
        $transaction = Transaction::query()->find($this->transactionId);
        if ($transaction === null) {
            $this->errorMessage = __('transaction.errors.not_found');

            return;
        }

        if (! Gate::forUser($user)->allows('create', [Rating::class, $transaction])) {
            if ($transaction->status !== TransactionStatus::Completed) {
                $this->errorMessage = __('rating.errors.not_completed');
            } elseif ((string) $transaction->buyer_id !== (string) $user->getKey()) {
                $this->errorMessage = __('rating.errors.unauthorized');
            } elseif ($transaction->inspector_id === null) {
                $this->errorMessage = __('rating.errors.no_inspector');
            } else {
                $this->errorMessage = __('rating.errors.already_submitted');
            }

            return;
        }

        $this->validate([
            'score' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $rating = $action->handle(
                buyer: $user,
                transaction: $transaction,
                score: $this->score,
                comment: $this->comment !== '' ? $this->comment : null,
            );

            $this->isSubmitted = true;
            $this->existingScore = $rating->score;
            $this->existingComment = $rating->comment;
            $this->successMessage = __('rating.success');
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render(): View
    {
        /** @var Transaction|null $transaction */
        $transaction = Transaction::query()
            ->with(['inspector', 'listing'])
            ->find($this->transactionId);

        /** @var User|null $user */
        $user = Auth::user();

        $canRate = false;
        if ($user !== null && $transaction !== null && ! $this->isSubmitted) {
            $canRate = Gate::forUser($user)->allows('create', [Rating::class, $transaction]);
        }

        return view('rating::livewire.rating-form', [
            'transaction' => $transaction,
            'canRate' => $canRate,
        ]);
    }
}
