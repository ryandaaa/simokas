<?php

declare(strict_types=1);

$domains = [
    'Identity',
    'Inspector',
    'Listing',
    'Catalog',
    'Inspection',
    'Result',
    'Payment',
    'Transaction',
    'Chat',
    'Rating',
    'Notification',
    'Setting',
    'Admin',
    'Dashboard',
];

foreach ($domains as $domain) {
    $otherInternals = [];
    foreach ($domains as $other) {
        if ($domain === $other) {
            continue;
        }
        $otherInternals[] = "App\\Domains\\{$other}\\Livewire";
        $otherInternals[] = "App\\Domains\\{$other}\\Actions";
        $otherInternals[] = "App\\Domains\\{$other}\\Listeners";
        $otherInternals[] = "App\\Domains\\{$other}\\Http";
    }

    arch("domain {$domain} does not use other domains internals")
        ->expect("App\\Domains\\{$domain}")
        ->not->toUse($otherInternals);
}

arch('support has no domain dependency')
    ->expect('App\Support')
    ->not->toUse('App\Domains');

arch('no debug calls')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('actions are final')
    ->expect('App\Domains\*\Actions')
    ->toBeFinal();
