<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Http\Controllers;

use App\Domains\Catalog\Actions\BrowseListings;
use App\Domains\Catalog\DTOs\CatalogFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class CatalogController
{
    public function __construct(private readonly BrowseListings $browseListings) {}

    public function __invoke(Request $request): View|RedirectResponse
    {
        $filter = CatalogFilter::fromQuery($request->query());

        $listings = $this->browseListings->handle($filter);

        if ($listings->total() > 0 && $filter->page > $listings->lastPage()) {
            return redirect()->route('catalog.index', array_merge($filter->toQuery(), [
                'page' => $listings->lastPage(),
            ]));
        }

        $listings->appends($filter->toQuery());

        return view('catalog::index', [
            'filter' => $filter,
            'listings' => $listings,
            'cards' => $listings->items(),
            'typeOptions' => [
                'car' => __('catalog.type_car'),
                'motorcycle' => __('catalog.type_motorcycle'),
            ],
            'inspectedOptions' => [
                'all' => __('catalog.inspected_all'),
                'inspected' => __('catalog.inspected_yes'),
                'uninspected' => __('catalog.inspected_no'),
            ],
            'sortOptions' => [
                'newest' => __('catalog.sort_newest'),
                'cheapest' => __('catalog.sort_cheapest'),
                'priciest' => __('catalog.sort_priciest'),
                'lowest_km' => __('catalog.sort_lowest_km'),
            ],
        ]);
    }
}
