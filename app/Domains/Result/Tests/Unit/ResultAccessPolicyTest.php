<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\ResultAccess;
use App\Domains\Result\Policies\ResultAccessPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ResultAccessPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function grants_free_access_to_listing_seller(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $seller = $result->listing->seller;

        $policy = app(ResultAccessPolicy::class);

        $this->assertTrue($policy->hasFreeAccess($seller, $result));
        $this->assertTrue($policy->viewDetails($seller, $result));
    }

    #[Test]
    public function grants_free_access_to_inspector(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $inspector = $result->inspector;

        $policy = app(ResultAccessPolicy::class);

        $this->assertTrue($policy->hasFreeAccess($inspector, $result));
        $this->assertTrue($policy->viewDetails($inspector, $result));
    }

    #[Test]
    public function grants_free_access_to_requester(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $requester = $result->job->requester;

        $policy = app(ResultAccessPolicy::class);

        $this->assertTrue($policy->hasFreeAccess($requester, $result));
        $this->assertTrue($policy->viewDetails($requester, $result));
    }

    #[Test]
    public function grants_free_access_to_admin(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $admin = User::factory()->admin()->create();

        $policy = app(ResultAccessPolicy::class);

        $this->assertTrue($policy->hasFreeAccess($admin, $result));
        $this->assertTrue($policy->viewDetails($admin, $result));
    }

    #[Test]
    public function denies_access_to_unauthorized_user_without_payment(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $buyer = User::factory()->create();

        $policy = app(ResultAccessPolicy::class);

        $this->assertFalse($policy->hasFreeAccess($buyer, $result));
        $this->assertFalse($policy->viewDetails($buyer, $result));
    }

    #[Test]
    public function grants_access_to_user_with_payment(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $buyer = User::factory()->create();

        // Create payment and access
        $payment = Payment::factory()
            ->success()
            ->access()
            ->create();
        ResultAccess::factory()
            ->for($result)
            ->for($buyer, 'user')
            ->create(['payment_id' => $payment->id]);

        $policy = app(ResultAccessPolicy::class);

        $this->assertFalse($policy->hasFreeAccess($buyer, $result));
        $this->assertTrue($policy->viewDetails($buyer, $result));
    }

    #[Test]
    public function blocks_new_access_after_listing_sold(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $result->listing->update(['status' => ListingStatus::Sold]);

        $buyer = User::factory()->create();

        $policy = app(ResultAccessPolicy::class);

        $this->assertFalse($policy->viewDetails($buyer, $result));
        $this->assertFalse($policy->canRequestPaidAccess($buyer, $result));
    }

    #[Test]
    public function keeps_existing_access_after_listing_sold(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $buyer = User::factory()->create();

        // Create access while listing is active
        $payment = Payment::factory()
            ->success()
            ->access()
            ->create();
        ResultAccess::factory()
            ->for($result)
            ->for($buyer, 'user')
            ->create(['payment_id' => $payment->id]);

        // Then mark listing as sold
        $result->listing->update(['status' => ListingStatus::Sold]);

        $policy = app(ResultAccessPolicy::class);

        // Access should still be granted
        $this->assertTrue($policy->viewDetails($buyer, $result));
        // But cannot request new access
        $this->assertFalse($policy->canRequestPaidAccess($buyer, $result));
    }

    #[Test]
    public function blocks_paid_access_request_if_already_has_access(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $buyer = User::factory()->create();

        // Create access
        ResultAccess::factory()
            ->for($result)
            ->for($buyer, 'user')
            ->create();

        $policy = app(ResultAccessPolicy::class);

        $this->assertFalse($policy->canRequestPaidAccess($buyer, $result));
    }
}
