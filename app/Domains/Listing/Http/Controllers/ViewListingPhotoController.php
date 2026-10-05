<?php

declare(strict_types=1);

namespace App\Domains\Listing\Http\Controllers;

use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Models\ListingPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class ViewListingPhotoController
{
    public function __invoke(Request $request, Listing $listing, ListingPhoto $photo): Response
    {
        if ($photo->listing_id !== $listing->id) {
            abort(404, __('listing.photos.not_found'));
        }

        Gate::authorize('viewPhoto', [$listing, $photo]);

        if (! Storage::disk('private')->exists($photo->storage_path)) {
            abort(404, __('listing.photos.not_found'));
        }

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($listing->status->isPhotoPubliclyVisible()) {
            $headers['Cache-Control'] = 'public, max-age=86400, must-revalidate';
        } else {
            $headers['Cache-Control'] = 'private, no-cache, no-store, must-revalidate';
        }

        $filename = basename($photo->storage_path);

        return Storage::disk('private')->response(
            $photo->storage_path,
            $filename,
            $headers,
            'inline'
        );
    }
}
