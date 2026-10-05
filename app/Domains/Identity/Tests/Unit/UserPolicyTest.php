<?php

declare(strict_types=1);

namespace App\Domains\Identity\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Identity\Policies\UserPolicy;
use Tests\TestCase;

final class UserPolicyTest extends TestCase
{
    public function test_view_policy(): void
    {
        $policy = new UserPolicy;

        $user1 = new User(['id' => '00000000-0000-0000-0000-000000000001', 'role' => 'user']);
        $user2 = new User(['id' => '00000000-0000-0000-0000-000000000002', 'role' => 'user']);
        $admin = new User(['id' => '00000000-0000-0000-0000-000000000003', 'role' => 'admin']);

        $this->assertTrue($policy->view($user1, $user1));
        $this->assertFalse($policy->view($user1, $user2));
        $this->assertTrue($policy->view($admin, $user1));
    }

    public function test_update_policy(): void
    {
        $policy = new UserPolicy;

        $user1 = new User(['id' => '00000000-0000-0000-0000-000000000001', 'role' => 'user']);
        $user2 = new User(['id' => '00000000-0000-0000-0000-000000000002', 'role' => 'user']);
        $admin = new User(['id' => '00000000-0000-0000-0000-000000000003', 'role' => 'admin']);

        $this->assertTrue($policy->update($user1, $user1));
        $this->assertFalse($policy->update($user1, $user2));
        $this->assertTrue($policy->update($admin, $user1));
    }

    public function test_delete_policy(): void
    {
        $policy = new UserPolicy;

        $user = new User(['id' => '00000000-0000-0000-0000-000000000001', 'role' => 'user']);
        $admin = new User(['id' => '00000000-0000-0000-0000-000000000002', 'role' => 'admin']);

        $this->assertFalse($policy->delete($user, $user));
        $this->assertTrue($policy->delete($admin, $user));
    }

    public function test_d06_inspector_restrictions(): void
    {
        $policy = new UserPolicy;

        $user = new User(['role' => 'user']);
        $inspector = new User(['role' => 'inspector']);

        $this->assertTrue($policy->buy($user));
        $this->assertTrue($policy->sell($user));
        $this->assertTrue($policy->requestInspection($user));

        $this->assertFalse($policy->buy($inspector));
        $this->assertFalse($policy->sell($inspector));
        $this->assertFalse($policy->requestInspection($inspector));
    }
}
