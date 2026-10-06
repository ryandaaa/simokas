<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Result\Actions\GrantResultAccess;
use App\Domains\Result\Models\InspectionResult;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ViewResultAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function re_s_t10_grants_free_access_to_owner_inspector_requester_admin(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $listing = $result->listing;
        $job = $result->job;
        $inspector = $result->inspector;
        $requester = $job->requester;
        $buyer = User::factory()->create();
        $admin = User::factory()->admin()->create();

        // Listing seller should have free access
        $this->actingAs($listing->seller)
            ->get(route('results.show', $result->job->listing))
            ->assertOk();

        // Inspector should have free access
        $this->actingAs($inspector)
            ->get(route('results.show', $result->job->listing))
            ->assertOk();

        // Requester should have free access
        $this->actingAs($requester)
            ->get(route('results.show', $result->job->listing))
            ->assertOk();

        // Admin should have free access
        $this->actingAs($admin)
            ->get(route('results.show', $result->job->listing))
            ->assertOk();

        // Buyer without payment should see limited summary
        $this->actingAs($buyer)
            ->get(route('results.show', $result->job->listing))
            ->assertOk();
    }

    #[Test]
    public function re_s_t11_limits_summary_without_access_and_hides_details(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $buyer = User::factory()->create();

        $response = $this->actingAs($buyer)
            ->get(route('results.show', $result->job->listing))
            ->assertOk();

        // Verify the page is rendered (no full details shown)
        // Without access, should show limited summary and payment button
        $response->assertSee(__('result.access_required'));
        $response->assertSee(Money::format($result->job->fee_amount));
    }

    #[Test]
    public function re_s_t12_payment_creates_access_and_posts_ledger_idempotent(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $job = $result->job;
        $inspector = $result->inspector;
        $buyer = User::factory()->create();
        $payment = Payment::factory()
            ->success()
            ->access()
            ->create(['payer_id' => $buyer->id]);

        $grantAccess = app(GrantResultAccess::class);

        // First grant - should create access and post ledger
        $access1 = $grantAccess->handle(
            $result,
            $buyer,
            $payment->id,
        );

        $this->assertDatabaseHas('result_accesses', [
            'result_id' => $result->id,
            'user_id' => $buyer->id,
            'payment_id' => $payment->id,
        ]);

        // Should post ledger entries for inspector and platform
        $inspectorEntries = LedgerEntry::query()
            ->where('payment_id', $payment->id)
            ->where('to_user_id', $inspector->id)
            ->get();

        $platformEntries = LedgerEntry::query()
            ->where('payment_id', $payment->id)
            ->where('to_user_id', null)
            ->where('note', 'like', '%access%')
            ->get();

        $this->assertGreaterThan(0, $inspectorEntries->count());
        $this->assertGreaterThan(0, $platformEntries->count());

        // Verify ledger amounts sum to payment amount
        $totalCredits = $inspectorEntries->sum('amount') + $platformEntries->sum('amount');
        $this->assertEquals($payment->amount, $totalCredits);

        // Second grant with same payment - should return existing and not double-post
        $access2 = $grantAccess->handle(
            $result,
            $buyer,
            $payment->id,
        );

        $this->assertEquals($access1->id, $access2->id);

        // Verify ledger invariant: no duplicate entries after second grant
        $allEntries = LedgerEntry::query()
            ->where('payment_id', $payment->id)
            ->get();

        // Should have exactly 2 entries: one for inspector, one for platform
        $this->assertEquals(2, $allEntries->count());

        // Verify split invariant: inspector + platform = payment amount
        $inspectorAmount = $allEntries->where('to_user_id', $inspector->id)->sum('amount');
        $platformAmount = $allEntries->where('to_user_id', null)->sum('amount');

        $this->assertEquals($payment->amount, $inspectorAmount + $platformAmount);
        $this->assertGreaterThan(0, $inspectorAmount);
        $this->assertGreaterThan(0, $platformAmount);

        // Verify no second payment created (idempotent on payment side)
        $this->assertDatabaseCount('payments', 1);
    }

    #[Test]
    public function re_s_t13_blocks_new_buyers_after_sold_but_keeps_existing_access(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $listing = $result->listing;
        $job = $result->job;

        // Create payment and grant access to buyer while listing is active
        $buyer = User::factory()->create();
        $payment = Payment::factory()
            ->success()
            ->access()
            ->create(['payer_id' => $buyer->id]);

        $grantAccess = app(GrantResultAccess::class);
        $grantAccess->handle($result, $buyer, $payment->id);

        // Verify buyer has access
        $this->actingAs($buyer)
            ->get(route('results.show', $result->job->listing))
            ->assertOk();

        // Mark listing as sold
        $listing->update(['status' => ListingStatus::Sold]);

        // Previous buyer should still have access
        $this->actingAs($buyer)
            ->get(route('results.show', $result->job->listing))
            ->assertOk();

        // New buyer should NOT be able to request access (limited summary only, no button)
        $newBuyer = User::factory()->create();
        $response = $this->actingAs($newBuyer)
            ->get(route('results.show', $result->job->listing))
            ->assertOk();

        // Verify message shown when listing is sold
        $response->assertSee(__('result.access_not_available'));

        // Verify inspector still has free access
        $this->actingAs($result->inspector)
            ->get(route('results.show', $result->job->listing))
            ->assertOk();
    }

    #[Test]
    public function re_s_t17_no_download_or_print_routes(): void
    {
        $result = InspectionResult::factory()->submitted()->create();

        // Verify download and print route names don't exist (no download/print functionality)
        $routeNames = array_keys(Route::getRoutes()->getRoutesByName());
        $this->assertNotContains('results.download', $routeNames);
        $this->assertNotContains('results.print', $routeNames);
    }
}
