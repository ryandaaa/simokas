<?php

declare(strict_types=1);
use Tests\TestCase;

require_once __DIR__.'/Helpers/ListingPrivacyHelper.php';

pest()->extend(TestCase::class)
    ->in('Feature', '../app/Domains/*/Tests/Feature');
