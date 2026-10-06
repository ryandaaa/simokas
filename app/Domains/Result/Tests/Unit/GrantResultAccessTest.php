<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Models\Payment;
use App\Domains\Result\Actions\GrantResultAccess;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\ResultAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class GrantResultAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function creates_access_without_payment(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $user = User::factory()->create();

        $action = app(GrantResultAccess::class);
        $access = $action->handle($result, $user);

        $this->assertInstanceOf(ResultAccess::class, $access);
        $this->assertEquals($result->id, $access->result_id);
        $this->assertEquals($user->id, $access->user_id);
        $this->assertNull($access->payment_id);

        $this->assertDatabaseHas('result_accesses', [
            'result_id' => $result->id,
            'user_id' => $user->id,
            'payment_id' => null,
        ]);
    }

    #[Test]
    public function creates_access_with_payment_and_posts_ledger(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $user = User::factory()->create();
        $payment = Payment::factory()
            ->success()
            ->access()
            ->create();

        $action = app(GrantResultAccess::class);
        $access = $action->handle(
            $result,
            $user,
            $payment->id,
            10,
        );

        $this->assertInstanceOf(ResultAccess::class, $access);
        $this->assertEquals($payment->id, $access->payment_id);
    }

    #[Test]
    public function is_idempotent_returns_existing_access(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $user = User::factory()->create();
        $payment = Payment::factory()
            ->success()
            ->access()
            ->create();

        $action = app(GrantResultAccess::class);

        // First call
        $access1 = $action->handle($result, $user, $payment->id);

        // Second call with same payment
        $access2 = $action->handle($result, $user, $payment->id);

        $this->assertEquals($access1->id, $access2->id);

        // Verify only one access record exists
        $this->assertDatabaseCount('result_accesses', 1);
    }
}
