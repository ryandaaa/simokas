<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Http\Controllers;

use App\Domains\Catalog\Actions\GetFeaturedListings;
use Illuminate\View\View;

final class HomeController
{
    public function __construct(private readonly GetFeaturedListings $featuredListings) {}

    public function __invoke(): View
    {
        return view('catalog::home', [
            'cards' => $this->featuredListings->handle(),
            'navItems' => [
                ['label' => __('home.nav_home'), 'url' => route('home'), 'icon' => 'home', 'active' => true],
                ['label' => __('home.nav_search'), 'url' => route('catalog.index'), 'icon' => 'search', 'active' => false],
                ['label' => __('home.nav_how'), 'url' => route('catalog.how-it-works'), 'icon' => 'document-text', 'active' => false],
                ['label' => __('home.nav_help'), 'url' => route('catalog.help'), 'icon' => 'information-circle', 'active' => false],
            ],
        ]);
    }
}
