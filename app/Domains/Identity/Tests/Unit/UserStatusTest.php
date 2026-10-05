<?php

declare(strict_types=1);

namespace App\Domains\Identity\Tests\Unit;

use App\Domains\Identity\Enums\UserStatus;
use Tests\TestCase;

final class UserStatusTest extends TestCase
{
    public function test_it_has_expected_enum_cases(): void
    {
        $this->assertSame('active', UserStatus::Active->value);
        $this->assertSame('pending', UserStatus::Pending->value);
        $this->assertSame('blocked', UserStatus::Blocked->value);
    }

    public function test_it_returns_labels_colors_and_icons(): void
    {
        foreach (UserStatus::cases() as $status) {
            $this->assertNotEmpty($status->label());
            $this->assertContains($status->color(), ['primary', 'warning', 'danger', 'success', 'muted']);
            $this->assertNotEmpty($status->icon());
        }
    }

    public function test_it_identifies_status_correctly(): void
    {
        $this->assertTrue(UserStatus::Active->isActive());
        $this->assertFalse(UserStatus::Active->isPending());
        $this->assertFalse(UserStatus::Active->isBlocked());

        $this->assertTrue(UserStatus::Pending->isPending());
        $this->assertFalse(UserStatus::Pending->isActive());
        $this->assertFalse(UserStatus::Pending->isBlocked());

        $this->assertTrue(UserStatus::Blocked->isBlocked());
        $this->assertFalse(UserStatus::Blocked->isActive());
        $this->assertFalse(UserStatus::Blocked->isPending());
    }
}
