<?php

declare(strict_types=1);

namespace App\Domains\Notification\Tests\Unit;

use App\Domains\Notification\Enums\NotificationType;

it('NTF-T01 defines a snake_case value for every notification type', function (): void {
    $values = array_map(fn (NotificationType $type): string => $type->value, NotificationType::cases());

    expect($values)->toHaveCount(14);

    foreach ($values as $value) {
        expect($value)->toMatch('/^[a-z]+(_[a-z]+)*$/');
    }

    expect($values)->toContain('job_taken', 'message_received', 'payment_succeeded');
});

it('NTF-T01 every notification type has a non-empty Indonesian label', function (): void {
    foreach (NotificationType::cases() as $type) {
        expect($type->label())->toBeString();
        expect($type->label())->not->toBe('');
    }
});
