<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Http\Controllers;

use App\Domains\Catalog\Actions\ShowListingDetail;
use App\Domains\Catalog\DTOs\ListingDetail;
use App\Domains\Listing\Contracts\ListingActivityChecker;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Public listing detail page (catalog.show, CAT-2).
 *
 * Action matrix (docs/flows/catalog.md section 3, P0 only):
 * - "Ajukan Inspeksi" appears for eligible requesters (guest, or
 *   authenticated role `user` who is not the owner) on `active` listings
 *   without an active inspection job. Expected JOB-1 route: `jobs.request`
 *   (GET /jobs/request/{listing} or equivalent, created by JOB-1). While
 *   that route is missing, guests link to login (intended URL stored below)
 *   and authenticated users see no button. Inspectors never see it (D-06),
 *   admins and owners neither.
 *   When an active job exists, the button is hidden and replaced by an
 *   informative notice.
 * - "Lihat Hasil" (entitled: owner, admin, job inspector via private
 *   access) and "Buka Hasil" (everyone else, paid access) appear only when
 *   their RES-4 routes exist. Expected RES-4 routes: `results.show` and
 *   `results.access`. "Ajukan Pembelian" (TRX-1) appears for logged-in
 *   role `user` non-owners on `active` or `in_inspection` listings;
 *   inspectors never see it (D-06), admins and owners neither.
 * - "Chat Penjual" (CHT-1) appears for guests (linking to login) and
 *   logged-in role `user` non-owners on non-sold listings; submitting it
 *   POSTs to `conversations.start`, which creates or reuses the listing
 *   conversation. Owners cannot chat with themselves (CHT-E8).
 * - "Edit" appears when ListingPolicy::update passes and the existing
 *   `listings.edit` route is present.
 * - `sold` listings show no action buttons at all (CAT-E5).
 */
final class ShowListingController
{
    public function __construct(
        private readonly ShowListingDetail $details,
        private readonly ListingActivityChecker $activityChecker,
    ) {}

    public function __invoke(Request $request, string $listing): View
    {
        try {
            $detail = $this->details->handle($listing, $request->user());
        } catch (ModelNotFoundException) {
            abort(404);
        }

        if ($request->user() === null) {
            // CAT-E6: guests who press a login-gated button land on login
            // first; Login uses redirect()->intended(), so remember this
            // listing (our own URL only, never an external redirect target).
            $request->session()->put('url.intended', $request->url());
        }

        $canRequestInspection = $this->canRequestInspection($detail);
        $hasActiveJob = ! $detail->isSold && $this->activityChecker->hasActiveJob($detail->id);

        return view('catalog::show', [
            'detail' => $detail,
            'actions' => $this->buildActions($detail, $hasActiveJob),
            'hasActiveJob' => $hasActiveJob,
            'canRequestInspection' => $canRequestInspection,
            'canChatWithSeller' => $this->canChatWithSeller($detail),
            'showChatLogin' => $detail->viewerRole === null && ! $detail->isSold,
        ]);
    }

    /**
     * @return list<array{key: string, label: string, url: string, variant: string, icon: string|null}>
     */
    private function buildActions(ListingDetail $detail, bool $hasActiveJob = false): array
    {
        if ($detail->isSold) {
            return [];
        }

        $actions = [];

        if ($detail->canEdit && Route::has('listings.edit')) {
            $actions[] = [
                'key' => 'edit',
                'label' => __('catalog.detail.action_edit'),
                'url' => route('listings.edit', ['listing' => $detail->id]),
                'variant' => 'secondary',
                'icon' => null,
            ];
        }

        if ($detail->status->value === 'active' && ! $hasActiveJob && $this->canRequestInspection($detail)) {
            if ($detail->viewerRole === null) {
                $actions[] = [
                    'key' => 'request-inspection',
                    'label' => __('catalog.detail.action_request_inspection'),
                    'url' => route('login'),
                    'variant' => 'primary',
                    'icon' => 'search',
                ];
            } elseif (Route::has('jobs.request')) {
                $actions[] = [
                    'key' => 'request-inspection',
                    'label' => __('catalog.detail.action_request_inspection'),
                    'url' => route('jobs.request', ['listing' => $detail->id]),
                    'variant' => 'primary',
                    'icon' => 'search',
                ];
            }
        }

        // TRX-1: "Ajukan Pembelian" only for logged-in role `user` non-owners
        // on active or in_inspection listings. Guests see nothing (the offer
        // form itself requires auth via route middleware).
        if (in_array($detail->status->value, ['active', 'in_inspection'], true) && $this->canRequestPurchase($detail)) {
            if (Route::has('transactions.offer')) {
                $actions[] = [
                    'key' => 'request-purchase',
                    'label' => __('catalog.detail.action_request_purchase'),
                    'url' => route('transactions.offer', ['listing' => $detail->id]),
                    'variant' => 'primary',
                    'icon' => 'tag',
                ];
            }
        }

        if ($detail->isInspected) {
            if ($detail->canSeePrivate) {
                if (Route::has('results.show')) {
                    $actions[] = [
                        'key' => 'view-result',
                        'label' => __('catalog.detail.action_view_result'),
                        'url' => route('results.show', ['listing' => $detail->id]),
                        'variant' => 'secondary',
                        'icon' => 'document-text',
                    ];
                }
            } elseif (Route::has('results.show')) {
                $actions[] = [
                    'key' => 'open-result',
                    'label' => __('catalog.detail.action_open_result'),
                    // results.access hanya menerima POST; halaman hasil menampilkan konfirmasi bayar lalu membuat tagihan.
                    'url' => $detail->viewerRole === null
                        ? route('login')
                        : route('results.show', ['listing' => $detail->id]),
                    'variant' => 'secondary',
                    'icon' => 'document-text',
                ];
            }
        }

        return $actions;
    }

    private function canRequestInspection(ListingDetail $detail): bool
    {
        // D-06: inspectors never request inspections. Admins and owners
        // neither; only guests and role `user` non-owners are eligible.
        if ($detail->isOwner) {
            return false;
        }

        return $detail->viewerRole === null || $detail->viewerRole === 'user';
    }

    private function canRequestPurchase(ListingDetail $detail): bool
    {
        // TRX-R1/TRX-R10: only logged-in role `user` non-owners.
        // Inspectors cannot buy (D-06), admins and owners neither.
        if ($detail->isOwner) {
            return false;
        }

        return $detail->viewerRole === 'user';
    }

    private function canChatWithSeller(ListingDetail $detail): bool
    {
        // CHT-1: guests go through login (see $showChatLogin); logged-in
        // role `user` non-owners on non-sold listings can start a chat.
        // Owners chat with themselves is rejected by StartConversation.
        if ($detail->isSold || $detail->isOwner) {
            return false;
        }

        return $detail->viewerRole === 'user';
    }
}
