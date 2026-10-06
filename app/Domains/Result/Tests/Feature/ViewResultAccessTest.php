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
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ViewResultAccessTest extends TestCase
{
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
            ->getJson(route('results.show', $result))
            ->assertOk()
            ->assertJsonPath('data.id', $result->id)
            ->assertJsonPath('data.has_access', true)
            ->assertJsonPath('data.limited_summary', false);

        // Inspector should have free access
        $this->actingAs($inspector)
            ->getJson(route('results.show', $result))
            ->assertOk()
            ->assertJsonPath('data.has_access', true)
            ->assertJsonPath('data.limited_summary', false);

        // Requester should have free access
        $this->actingAs($requester)
            ->getJson(route('results.show', $result))
            ->assertOk()
            ->assertJsonPath('data.has_access', true)
            ->assertJsonPath('data.limited_summary', false);

        // Admin should have free access
        $this->actingAs($admin)
            ->getJson(route('results.show', $result))
            ->assertOk()
            ->assertJsonPath('data.has_access', true)
            ->assertJsonPath('data.limited_summary', false);

        // Buyer without payment should see limited summary
        $response = $this->actingAs($buyer)
            ->getJson(route('results.show', $result))
            ->assertOk()
            ->json();

        $this->assertTrue($response['data']['limited_summary']);
        $this->assertArrayHasKey('access_fee', $response['data']);
    }

    #[Test]
    public function re_s_t11_limits_summary_without_access_and_hides_details(): void
    {
        $result = InspectionResult::factory()->submitted()->create();
        $buyer = User::factory()->create();

        $response = $this->actingAs($buyer)
            ->getJson(route('results.show', $result))
            ->assertOk()
            ->json();

        $data = $response['data'];

        // Should show limited summary
        $this->assertTrue($data['limited_summary']);

        // Should NOT include detailed information
        $this->assertArrayNotHasKey('total_score', $data);
        $this->assertArrayNotHasKey('summary_note', $data);
        $this->assertArrayNotHasKey('items', $data);
        $this->assertArrayNotHasKey('photos', $data);

        // Should show limited info only
        $this->assertArrayHasKey('access_fee', $data);
        $this->assertArrayHasKey('submission_date', $data);
        $this->assertArrayHasKey('inspector', $data);
        $this->assertArrayHasKey('recommendation', $data);
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

        // Verify no duplicate ledger entries
        $allEntries = LedgerEntry::query()
            ->where('payment_id', $payment->id)
            ->get();

        $inspectorCount = $allEntries->where('to_user_id', $inspector->id)->count();
        $this->assertEquals(1, $inspectorCount);

        // Verify no second tagihan (idempotent on payment side)
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
            ->getJson(route('results.show', $result))
            ->assertOk()
            ->assertJsonPath('data.limited_summary', false);

        // Mark listing as sold
        $listing->update(['status' => ListingStatus::Sold]);

        // Previous buyer should still have access
        $this->actingAs($buyer)
            ->getJson(route('results.show', $result))
            ->assertOk()
            ->assertJsonPath('data.limited_summary', false);

        // New buyer should NOT be able to request access (limited summary only, no button)
        $newBuyer = User::factory()->create();
        $response = $this->actingAs($newBuyer)
            ->getJson(route('results.show', $result))
            ->assertOk()
            ->json();

        $this->assertTrue($response['data']['limited_summary']);
        $this->assertArrayNotHasKey('access_fee', $response['data']); // No button to pay when sold

        // Verify inspector still has free access
        $this->actingAs($result->inspector)
            ->getJson(route('results.show', $result))
            ->assertOk()
            ->assertJsonPath('data.limited_summary', false);
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
