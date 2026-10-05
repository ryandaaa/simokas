<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Livewire\Register;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('AUTH-T17 shows public legal pages with a single h1, draft label, and lang-sourced text', function (string $routeName, string $titleKey, string $introKey) {
    $response = $this->get(route($routeName));

    $response->assertOk();

    $content = (string) $response->getContent();
    expect(substr_count($content, '<h1'))->toBe(1);

    $response->assertSee(__('legal.draft_label'), false)
        ->assertSee(__($titleKey), false)
        ->assertSee(__($introKey), false);
})->with([
    'privacy policy' => ['legal.privacy', 'legal.privacy.title', 'legal.privacy.intro'],
    'terms and conditions' => ['legal.terms', 'legal.terms.title', 'legal.terms.intro'],
]);

it('AUTH-T17 grants guests and every role access to legal pages', function (?UserRole $role, string $routeName) {
    if ($role !== null) {
        $user = User::factory()->create([
            'name' => 'Pengguna Legal',
            'email' => 'legal-'.$role->value.'@example.com',
            'phone' => '081234567890',
            'role' => $role,
            'status' => UserStatus::Active,
        ]);
        $this->actingAs($user);
    }

    $this->get(route($routeName))->assertOk();
})->with([
    'guest sees privacy' => [null, 'legal.privacy'],
    'guest sees terms' => [null, 'legal.terms'],
    'user sees privacy' => [UserRole::User, 'legal.privacy'],
    'user sees terms' => [UserRole::User, 'legal.terms'],
    'inspector sees privacy' => [UserRole::Inspector, 'legal.privacy'],
    'inspector sees terms' => [UserRole::Inspector, 'legal.terms'],
    'admin sees privacy' => [UserRole::Admin, 'legal.privacy'],
    'admin sees terms' => [UserRole::Admin, 'legal.terms'],
]);

it('AUTH-T18 shows footer links to both legal pages on home and auth pages', function (string $url) {
    $this->get($url)->assertOk()
        ->assertSee(route('legal.privacy'), false)
        ->assertSee(route('legal.terms'), false)
        ->assertSee(__('legal.footer_privacy'), false)
        ->assertSee(__('legal.footer_terms'), false);
})->with([
    'home' => ['/'],
    'register' => ['/register'],
    'login' => ['/login'],
]);

it('AUTH-T18 links the register consent checkbox to both legal pages', function () {
    Livewire::test(Register::class)
        ->assertSee(route('legal.privacy'), false)
        ->assertSee(route('legal.terms'), false);
});
