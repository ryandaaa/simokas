<?php

declare(strict_types=1);

namespace App\Domains\Notification\Tests\Unit;

use App\Domains\Notification\Enums\NotificationType;
use Tests\TestCase;

it('NTF-T01 defines a snake_case value for every notification type', function (): void {
    $values = array_map(fn (NotificationType $type): string => $type->value, NotificationType::cases());

    expect($values)->toHaveCount(22);

    foreach ($values as $value) {
        expect($value)->toMatch('/^[a-z]+(_[a-z]+)*$/');
    }

    expect($values)->toContain('job_taken', 'message_received', 'payment_succeeded', 'transaction_offered', 'transaction_completed', 'inspector_submitted');
});

it('NTF-T01 every notification type has a non-empty Indonesian label', function (): void {
    foreach (NotificationType::cases() as $type) {
        expect($type->label())->toBeString();
        expect($type->label())->not->toBe('');
    }
});

uses(TestCase::class);
