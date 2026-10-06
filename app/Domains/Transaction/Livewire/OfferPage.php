<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Actions\SubmitOffer;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Purchase offer form for a listing (TRX-1).
 *
 * Buyer enters a price (defaults to listing price) and optional notes,
 * then SubmitOffer creates the transaction. Domain violations surface
 * as a friendly alert; the user is redirected to the transaction detail.
 */
final class OfferPage extends Component
{
    public string $listingId = '';

    public string $priceAmount = '';

    public string $notes = '';

    public ?string $errorMessage = null;

    public function mount(Listing $listing): void
    {
        // TRX-R1: only active or in_inspection listings can receive offers.
        // Anything else 404s so non-public listing data (title, year, area,
        // price) is never rendered on this page.
        if (! in_array($listing->status, [ListingStatus::Active, ListingStatus::InInspection], true)) {
            abort(404);
        }

        $this->listingId = (string) $listing->getKey();
        $this->priceAmount = (string) $listing->price;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'priceAmount' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'priceAmount.required' => __('transaction.offer.price_required'),
            'priceAmount.integer' => __('transaction.offer.price_integer'),
            'priceAmount.min' => __('transaction.offer.price_min'),
            'notes.max' => __('transaction.offer.notes_max'),
        ];
    }

    public function submit(SubmitOffer $action): RedirectResponse|bool
    {
        $this->validate();
        $this->errorMessage = null;

        /** @var Listing $listing */
        $listing = Listing::query()->findOrFail($this->listingId);

        /** @var User $buyer */
        $buyer = Auth::user();

        try {
            $transaction = $action->handle(
                $buyer,
                $listing,
                (int) $this->priceAmount,
                trim($this->notes) === '' ? null : trim($this->notes),
            );
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();

            return false;
        }

        return redirect()->route('transactions.show', ['transaction' => $transaction->getKey()]);
    }

    public function render(): View
    {
        /** @var Listing $listing */
        $listing = Listing::query()->findOrFail($this->listingId);

        return view('transaction::livewire.offer-page', [
            'listing' => $listing,
        ])->layout('components.layouts.app', ['title' => __('transaction.offer.title')]);
    }
}
