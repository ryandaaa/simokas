<?php

declare(strict_types=1);

namespace Tests\Feature\Journeys;

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Result\Models\ResultAccess;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Journeys\Concerns\HasCompletedInspectionSetup;

uses(RefreshDatabase::class, HasCompletedInspectionSetup::class);

it('executes full result access journey J2 with paid access and ledger verification', function () {
    // 0. Siapkan data hasil inspeksi yang telah selesai (J1)
    $state = $this->setupCompletedInspectionState();
    $listing = $state['listing'];
    $job = $state['job'];
    $result = $state['result'];
    $photo = $state['photo'];
    $inspector = $state['inspector'];

    // 1. Pembeli lain membuka hasil tanpa akses (halaman results.show menampilkan ringkasan terbatas)
    $buyer2 = User::factory()->user()->create([
        'name' => 'Bambang Pembeli Kedua',
        'email' => 'bambang.buyer2@example.com',
        'status' => UserStatus::Active,
    ]);

    $initialViewResponse = $this->actingAs($buyer2)->get(route('results.show', ['listing' => $listing->id]));
    $initialViewResponse->assertOk()
        ->assertSee(__('result.access_required', ['cost' => Money::format(50000)]))
        ->assertSee(__('result.open_results', ['cost' => Money::format(50000)]))
        ->assertDontSee('Kondisi kendaraan sangat prima dan siap pakai.');

    // 2. Membayar biaya akses lewat jalur resmi (RequestResultAccessController / rute results.access)
    $accessRequestResponse = $this->actingAs($buyer2)->post(route('results.access', ['listing' => $listing->id]));
    $accessRequestResponse->assertRedirect();

    // Tagihan pembayaran akses dibuat di domain Payment
    /** @var Payment $accessPayment */
    $accessPayment = Payment::query()
        ->where('purpose', PaymentPurpose::Access)
        ->where('payer_id', $buyer2->id)
        ->where('ref_id', $result->id)
        ->firstOrFail();

    expect($accessPayment->amount)->toBe(50000);

    // Buka halaman pembayaran simulasi
    $this->actingAs($buyer2)
        ->get(route('payments.show', ['payment' => $accessPayment->id]))
        ->assertOk();

    // Simulasi pembayaran akses berhasil lewat rute payments.simulate
    $simulationResponse = $this->actingAs($buyer2)->postJson(route('payments.simulate'), [
        'payment_id' => $accessPayment->id,
        'method' => 'qris',
        'outcome' => 'success',
    ]);
    $simulationResponse->assertOk();

    // 3. Akses diberikan (ResultAccess tercatat, sekali walau event dipancarkan ganda)
    $accessRecord = ResultAccess::query()
        ->where('result_id', $result->id)
        ->where('user_id', $buyer2->id)
        ->first();

    expect($accessRecord)->not->toBeNull()
        ->and($accessRecord->payment_id)->toBe($accessPayment->id);

    // Event ganda tidak menduplikasi ResultAccess
    event(new PaymentSucceeded(
        paymentId: $accessPayment->id,
        purpose: PaymentPurpose::Access,
        refId: (string) $result->id,
        amount: $accessPayment->amount,
        payerId: (string) $buyer2->id,
    ));

    expect(ResultAccess::query()->where('result_id', $result->id)->where('user_id', $buyer2->id)->count())->toBe(1);

    // 4. Ledger memposting fee akses untuk inspektor dan bagian platform (jumlah entri = jumlah pembayaran)
    $accessLedgerEntries = LedgerEntry::query()
        ->where('payment_id', $accessPayment->id)
        ->get();

    expect($accessLedgerEntries)->toHaveCount(2)
        ->and($accessLedgerEntries->sum('amount'))->toBe($accessPayment->amount);

    $platformEntry = $accessLedgerEntries->firstWhere('entry_type', LedgerEntryType::PlatformFee->value);
    $inspectorEntry = $accessLedgerEntries->firstWhere('entry_type', LedgerEntryType::AccessFee->value);

    expect($platformEntry)->not->toBeNull()
        ->and($platformEntry->amount)->toBe(5000)
        ->and($platformEntry->account_user_id)->toBeNull()
        ->and($inspectorEntry)->not->toBeNull()
        ->and($inspectorEntry->amount)->toBe(45000)
        ->and($inspectorEntry->account_user_id)->toBe($inspector->id);

    // 5. Pembeli membuka detail hasil lengkap setelah memiliki akses
    $paidViewResponse = $this->actingAs($buyer2)->get(route('results.show', ['listing' => $listing->id]));
    $paidViewResponse->assertOk()
        ->assertSee('Kondisi kendaraan sangat prima dan siap pakai.')
        ->assertSee('100.0')
        ->assertSee('Layak')
        ->assertSee($inspector->name)
        ->assertSee('Baik')
        ->assertDontSee(__('result.access_required', ['cost' => Money::format(50000)]));

    // 6. Pihak ketiga tanpa akses tetap ditolak
    $thirdParty = User::factory()->user()->create([
        'name' => 'Charlie Pihak Ketiga',
        'email' => 'charlie.thirdparty@example.com',
        'status' => UserStatus::Active,
    ]);

    // Pihak ketiga membuka results.show hanya mendapat ringkasan terkunci
    $thirdPartyViewResponse = $this->actingAs($thirdParty)->get(route('results.show', ['listing' => $listing->id]));
    $thirdPartyViewResponse->assertOk()
        ->assertSee(__('result.access_required', ['cost' => Money::format(50000)]))
        ->assertDontSee('Kondisi kendaraan sangat prima dan siap pakai.');

    // Pihak ketiga membuka foto bertanda tangan ditolak 403 Forbidden
    $signedUrl = $photo->temporaryUrl(10);
    $this->actingAs($thirdParty)->get($signedUrl)->assertForbidden();

    // 7. Bayar kedua kali tidak membuat akses atau ledger ganda
    $secondAttemptResponse = $this->actingAs($buyer2)->post(route('results.access', ['listing' => $listing->id]));
    $secondAttemptResponse->assertSessionHas('error', __('result.access_not_available'));

    // Invarian: tidak ada pembayaran akses ganda atau entri ledger ganda
    expect(Payment::query()->where('purpose', PaymentPurpose::Access)->where('payer_id', $buyer2->id)->count())->toBe(1)
        ->and(ResultAccess::query()->where('result_id', $result->id)->where('user_id', $buyer2->id)->count())->toBe(1)
        ->and(LedgerEntry::query()->where('payment_id', $accessPayment->id)->count())->toBe(2);

    // 8. Bila listing diubah ke sold: pembeli baru ditolak sedangkan pembeli lama tetap punya akses
    $listing->update(['status' => ListingStatus::Sold]);

    // Pembeli lama ($buyer2) tetap dapat melihat detail hasil lengkap
    $buyer2AfterSoldResponse = $this->actingAs($buyer2)->get(route('results.show', ['listing' => $listing->id]));
    $buyer2AfterSoldResponse->assertOk()
        ->assertSee('Kondisi kendaraan sangat prima dan siap pakai.')
        ->assertSee('100.0')
        ->assertSee('Layak');

    // Pembeli baru ($buyer3) ditolak membuka hasil berbayar (akses ditutup)
    $buyer3 = User::factory()->user()->create([
        'name' => 'Dedi Calon Pembeli Baru',
        'email' => 'dedi.buyer3@example.com',
        'status' => UserStatus::Active,
    ]);

    $buyer3Response = $this->actingAs($buyer3)->get(route('results.show', ['listing' => $listing->id]));
    $buyer3Response->assertOk()
        ->assertSee(__('result.access_not_available'))
        ->assertDontSee(__('result.open_results', ['cost' => Money::format(50000)]));

    $buyer3PostResponse = $this->actingAs($buyer3)->post(route('results.access', ['listing' => $listing->id]));
    $buyer3PostResponse->assertSessionHas('error', __('result.access_not_available'));
});

it('J2 allows buyer who purchased result access to view photos via signed url', function () {
    // 0. Siapkan data inspeksi selesai
    $state = $this->setupCompletedInspectionState();
    $listing = $state['listing'];
    $result = $state['result'];
    $photo = $state['photo'];

    $buyer = User::factory()->user()->create([
        'name' => 'Eko Pembeli',
        'email' => 'eko.buyer@example.com',
        'status' => UserStatus::Active,
    ]);

    // Berikan akses berbayar resmi
    $this->actingAs($buyer)->post(route('results.access', ['listing' => $listing->id]));
    $payment = Payment::query()
        ->where('purpose', PaymentPurpose::Access)
        ->where('payer_id', $buyer->id)
        ->firstOrFail();

    $this->actingAs($buyer)->postJson(route('payments.simulate'), [
        'payment_id' => $payment->id,
        'method' => 'qris',
        'outcome' => 'success',
    ]);

    // Verifikasi akses sudah ada
    expect(ResultAccess::query()->where('result_id', $result->id)->where('user_id', $buyer->id)->exists())->toBeTrue();

    // Buka foto hasil lewat signed URL
    $signedUrl = $photo->temporaryUrl(10);
    $response = $this->actingAs($buyer)->get($signedUrl);

    $response->assertOk();
});
