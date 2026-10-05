<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Http\Controllers;

use App\Domains\Catalog\Actions\ShowListingDetail;
use App\Domains\Catalog\DTOs\ListingDetail;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Public listing detail page (catalog.show, CAT-2).
 *
 * Action matrix (docs/flows/catalog.md section 3, P0 only):
 * - "Ajukan Inspeksi" appears for eligible requesters (guest, or
 *   authenticated role `user` who is not the owner) on `active` listings.
 *   Expected JOB-1 route: `jobs.request` (GET /jobs/request/{listing} or
 *   equivalent, created by JOB-1). While that route is missing, guests link
 *   to login (intended URL stored below) and authenticated users see no
 *   button. Inspectors never see it (D-06), admins and owners neither.
 *   NOTE: the "no active job" condition cannot be checked yet (no
 *   Inspection domain); JOB-1 should add active-job gating.
 * - "Lihat Hasil" (entitled: owner, admin, job inspector via private
 *   access) and "Buka Hasil" (everyone else, paid access) appear only when
 *   their RES-4 routes exist. Expected RES-4 routes: `results.show` and
 *   `results.access`. Chat Penjual and Ajukan Pembelian are P1: never shown.
 * - "Edit" appears when ListingPolicy::update passes and the existing
 *   `listings.edit` route is present.
 * - `sold` listings show no action buttons at all (CAT-E5).
 */
final class ShowListingController
{
    public function __construct(private readonly ShowListingDetail $details) {}

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

        return view('catalog::show', [
            'detail' => $detail,
            'actions' => $this->buildActions($detail),
        ]);
    }

    /**
     * @return list<array{key: string, label: string, url: string, variant: string, icon: string|null}>
     */
    private function buildActions(ListingDetail $detail): array
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

        if ($detail->status->value === 'active' && $this->canRequestInspection($detail)) {
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
            } elseif (Route::has('results.access')) {
                $actions[] = [
                    'key' => 'open-result',
                    'label' => __('catalog.detail.action_open_result'),
                    'url' => $detail->viewerRole === null
                        ? route('login')
                        : route('results.access', ['listing' => $detail->id]),
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
}
