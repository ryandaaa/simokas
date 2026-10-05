<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Http\Controllers;

use Illuminate\View\View;

final class HowItWorksController
{
    public function __invoke(): View
    {
        return view('catalog::how-it-works', [
            'navItems' => [
                ['label' => __('home.nav_home'), 'url' => route('home'), 'icon' => 'home', 'active' => false],
                ['label' => __('home.nav_search'), 'url' => route('catalog.index'), 'icon' => 'search', 'active' => false],
                ['label' => __('home.nav_how'), 'url' => route('catalog.how-it-works'), 'icon' => 'document-text', 'active' => true],
                ['label' => __('home.nav_help'), 'url' => route('catalog.help'), 'icon' => 'information-circle', 'active' => false],
            ],
        ]);
    }
}
