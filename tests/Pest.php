<?php

declare(strict_types=1);
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature', '../app/Domains/*/Tests/Feature');
