<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

final class UserTest extends TestCase
{
    public function test_user_casts_attributes(): void
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret',
        ]);

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertArrayHasKey('email_verified_at', $user->getCasts());
        $this->assertArrayHasKey('password', $user->getCasts());
    }
}
