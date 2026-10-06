<?php

declare(strict_types=1);

namespace App\Domains\Identity\Http\Controllers;

use Illuminate\View\View;

final class PrivacyPolicyController
{
    public function __invoke(): View
    {
        return view('identity::legal.privacy');
    }
}
