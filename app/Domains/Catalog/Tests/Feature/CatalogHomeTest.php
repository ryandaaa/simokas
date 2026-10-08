<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Helpers\ListingPrivacyHelper;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

function makeHomeListing(array $overrides = []): Listing
{
    return Listing::factory()->create($overrides);
}

it('CAT-T13 shows only active listings as featured on the home page', function () {
    makeHomeListing(['status' => 'active', 'title' => 'CAT home featured active 999']);
    makeHomeListing(['status' => 'in_inspection', 'title' => 'CAT home featured in inspection 999']);
    makeHomeListing(['status' => 'in_transaction', 'title' => 'CAT home featured in transaction 999']);
    makeHomeListing(['status' => 'sold', 'title' => 'CAT home featured sold 999']);

    $response = get(route('home'))->assertOk();

    $response->assertSee('CAT home featured active 999');
    $response->assertDontSee('CAT home featured in inspection 999');
    $response->assertDontSee('CAT home featured in transaction 999');
    $response->assertDontSee('CAT home featured sold 999');
});

it('CAT-T13 limits featured listings to the six newest active listings', function () {
    for ($i = 1; $i <= 8; $i++) {
        makeHomeListing([
            'status' => 'active',
            'title' => "CAT home newest vehicle {$i} 999",
            'created_at' => now()->addMinutes($i),
        ]);
    }

    $content = get(route('home'))->assertOk()->getContent();

    expect(substr_count($content, '<article'))->toBe(6);
    expect($content)->toContain('CAT home newest vehicle 8 999')
        ->and($content)->toContain('CAT home newest vehicle 3 999')
        ->and($content)->not->toContain('CAT home newest vehicle 2 999')
        ->and($content)->not->toContain('CAT home newest vehicle 1 999');
});

it('CAT-T13 keeps private listing data out of the home page', function () {
    makeHomeListing([
        'status' => 'active',
        'title' => 'CAT home privacy vehicle 999',
        'lat' => 0.5071000,
        'lng' => 101.4478000,
        'address_full' => 'Jl. Rahasia Beranda No. 77, Pekanbaru',
        'chassis_number' => 'MHKB7777777777777',
        'inspection_place' => 'Jl. Rahasia Beranda No. 77, rumah penjual',
    ]);

    $response = get(route('home'))->assertOk();

    ListingPrivacyHelper::assertNoPrivateListingData($response, [
        'Jl. Rahasia Beranda No. 77, Pekanbaru',
        'MHKB7777777777777',
        'Jl. Rahasia Beranda No. 77, rumah penjual',
    ]);

    $response->assertSee('CAT home privacy vehicle 999');
});

it('CAT-T13 shows a friendly empty state when there are no featured listings', function () {
    $response = get(route('home'))->assertOk();

    $response->assertSee(__('home.empty_title'))
        ->assertSee(__('home.empty_description'))
        ->assertSee(route('catalog.index'), false);
});

it('CAT-T13 keeps the home query count flat as featured items grow', function () {
    Listing::factory()->active()->count(3)->withPhotos(1)->create();

    DB::flushQueryLog();
    DB::enableQueryLog();
    get(route('home'))->assertOk();
    $small = count(DB::getQueryLog());

    Listing::factory()->active()->count(10)->withPhotos(1)->create();

    DB::flushQueryLog();
    get(route('home'))->assertOk();
    $large = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($large)->toBe($small);
});

it('CAT-T14 renders how-it-works and help pages with a single h1', function (string $role) {
    $viewer = match ($role) {
        'user' => User::factory()->user()->create(),
        'inspector' => User::factory()->inspector()->create(),
        'admin' => User::factory()->admin()->create(),
        default => null,
    };

    foreach (['catalog.how-it-works', 'catalog.help'] as $routeName) {
        $response = $viewer === null
            ? get(route($routeName))->assertOk()
            : actingAs($viewer)->get(route($routeName))->assertOk();

        expect(substr_count($response->getContent(), '<h1'))->toBe(1);
    }

    $howContent = ($viewer === null ? get(route('catalog.how-it-works')) : actingAs($viewer)->get(route('catalog.how-it-works')))->getContent();
    expect($howContent)->toContain(__('home.how.heading'))
        ->and($howContent)->toContain(__('home.how.simulation_message'));

    $helpContent = ($viewer === null ? get(route('catalog.help')) : actingAs($viewer)->get(route('catalog.help')))->getContent();
    expect($helpContent)->toContain(__('home.help.heading'))
        ->and($helpContent)->toContain(__('home.help.faq_q2'));
})->with(['guest', 'user', 'inspector', 'admin']);

it('CAT-T14 renders the home page for guests and every role with a single h1', function (string $role) {
    Listing::factory()->active()->create(['title' => 'CAT home role vehicle 999']);

    $viewer = match ($role) {
        'user' => User::factory()->user()->create(),
        'inspector' => User::factory()->inspector()->create(),
        'admin' => User::factory()->admin()->create(),
        default => null,
    };

    $response = $viewer === null
        ? get(route('home'))->assertOk()
        : actingAs($viewer)->get(route('home'))->assertOk();

    expect(substr_count($response->getContent(), '<h1'))->toBe(1);

    $response->assertSee(__('home.hero_title'))
        ->assertSee(__('home.featured_title'))
        ->assertSee(route('catalog.index'), false)
        ->assertSee(route('catalog.how-it-works'), false)
        ->assertSee(route('catalog.help'), false);
})->with(['guest', 'user', 'inspector', 'admin']);

it('CAT-T15 replaces the welcome view with the catalog home page', function () {
    expect(Route::has('home'))->toBeTrue()
        ->and(Route::has('catalog.how-it-works'))->toBeTrue()
        ->and(Route::has('catalog.help'))->toBeTrue()
        ->and(route('home'))->toBe(url('/'));

    $content = get(url('/'))->assertOk()->getContent();

    expect($content)->toContain(__('home.hero_title'))
        ->and($content)->not->toContain('Laravel');
});

it('CAT-T15 loads home texts from language files instead of hard-coding them', function () {
    $keys = [
        'home.nav_home',
        'home.nav_search',
        'home.nav_how',
        'home.nav_help',
        'home.hero_title',
        'home.hero_subtitle',
        'home.hero_search_car',
        'home.hero_search_motorcycle',
        'home.steps_title',
        'home.step_1_title',
        'home.step_1_description',
        'home.step_2_title',
        'home.step_2_description',
        'home.step_3_title',
        'home.step_3_description',
        'home.step_4_title',
        'home.step_4_description',
        'home.featured_title',
        'home.featured_more',
        'home.empty_title',
        'home.empty_description',
        'home.inspector_title',
        'home.inspector_description',
        'home.inspector_action',
        'home.how.heading',
        'home.how.simulation_message',
        'home.help.heading',
        'home.help.faq_q1',
    ];

    foreach ($keys as $key) {
        expect(__($key))->not->toBe($key, "Missing translation for key: {$key}");
    }

    $content = get(route('home'))->assertOk()->getContent();

    expect($content)->toContain(__('home.hero_title'))
        ->and($content)->toContain(__('home.step_1_title'))
        ->and($content)->toContain(__('home.inspector_title'));
});

it('CAT-T15 renders no raw translation keys on the home page', function () {
    Listing::factory()->active()->create(['title' => 'CAT home keys vehicle 999']);

    $content = get(route('home'))->assertOk()->getContent();

    expect($content)->not->toContain('__(')
        ->and($content)->toContain('CAT home keys vehicle 999');
});

it('CAT-T15 every translation key used by home view exists in lang files', function () {
    $blade = file_get_contents(app_path('Domains/Catalog/Resources/views/home.blade.php'));

    preg_match_all("/__\(\s*'([a-z0-9_.]+)'(?!\s*\.)/", $blade, $matches);

    expect($matches[1])->not->toBeEmpty();

    foreach (array_unique($matches[1]) as $key) {
        expect(__($key))->not->toBe($key, "Missing translation for key: {$key}");
    }
});
