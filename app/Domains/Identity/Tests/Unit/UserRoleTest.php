<?php

declare(strict_types=1);

namespace App\Domains\Identity\Tests\Unit;

use App\Domains\Identity\Enums\UserRole;
use Tests\TestCase;

final class UserRoleTest extends TestCase
{
    public function test_it_has_expected_enum_cases(): void
    {
        $this->assertSame('user', UserRole::User->value);
        $this->assertSame('inspector', UserRole::Inspector->value);
        $this->assertSame('admin', UserRole::Admin->value);
    }

    public function test_it_returns_labels_colors_and_icons(): void
    {
        foreach (UserRole::cases() as $role) {
            $this->assertNotEmpty($role->label());
            $this->assertContains($role->color(), ['primary', 'warning', 'danger', 'success', 'muted']);
            $this->assertNotEmpty($role->icon());
        }
    }

    public function test_it_identifies_roles_correctly(): void
    {
        $this->assertTrue(UserRole::User->isUser());
        $this->assertFalse(UserRole::User->isInspector());
        $this->assertFalse(UserRole::User->isAdmin());

        $this->assertTrue(UserRole::Inspector->isInspector());
        $this->assertFalse(UserRole::Inspector->isUser());
        $this->assertFalse(UserRole::Inspector->isAdmin());

        $this->assertTrue(UserRole::Admin->isAdmin());
        $this->assertFalse(UserRole::Admin->isUser());
        $this->assertFalse(UserRole::Admin->isInspector());
    }

    public function test_it_enforces_d06_inspector_restrictions(): void
    {
        $this->assertTrue(UserRole::User->canBuy());
        $this->assertTrue(UserRole::User->canSell());
        $this->assertTrue(UserRole::User->canRequestInspection());

        $this->assertFalse(UserRole::Inspector->canBuy());
        $this->assertFalse(UserRole::Inspector->canSell());
        $this->assertFalse(UserRole::Inspector->canRequestInspection());
    }
}
