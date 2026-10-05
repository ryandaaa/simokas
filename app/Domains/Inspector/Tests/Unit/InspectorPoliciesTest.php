<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Models\InspectorDocument;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Inspector\Policies\InspectorDocumentPolicy;
use App\Domains\Inspector\Policies\InspectorProfilePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('tests InspectorProfilePolicy rules', function () {
    $inspectorOwner = User::factory()->inspector()->pending()->create();
    $inspectorOther = User::factory()->inspector()->pending()->create();
    $regularUser = User::factory()->user()->active()->create();
    $admin = User::factory()->admin()->active()->create();

    $profile = InspectorProfile::factory()->create(['user_id' => $inspectorOwner->id]);
    $policy = new InspectorProfilePolicy;

    // View
    expect($policy->view($inspectorOwner, $profile))->toBeTrue()
        ->and($policy->view($admin, $profile))->toBeTrue()
        ->and($policy->view($inspectorOther, $profile))->toBeFalse()
        ->and($policy->view($regularUser, $profile))->toBeFalse();

    // Create
    expect($policy->create($inspectorOwner))->toBeTrue()
        ->and($policy->create($regularUser))->toBeFalse()
        ->and($policy->create($admin))->toBeFalse();

    // Update
    expect($policy->update($inspectorOwner, $profile))->toBeTrue()
        ->and($policy->update($admin, $profile))->toBeTrue()
        ->and($policy->update($inspectorOther, $profile))->toBeFalse()
        ->and($policy->update($regularUser, $profile))->toBeFalse();
});

it('tests InspectorDocumentPolicy rules', function () {
    $inspectorOwner = User::factory()->inspector()->pending()->create();
    $inspectorOther = User::factory()->inspector()->pending()->create();
    $regularUser = User::factory()->user()->active()->create();
    $admin = User::factory()->admin()->active()->create();

    $document = InspectorDocument::factory()->create(['user_id' => $inspectorOwner->id]);
    $policy = new InspectorDocumentPolicy;

    // View
    expect($policy->view($inspectorOwner, $document))->toBeTrue()
        ->and($policy->view($admin, $document))->toBeTrue()
        ->and($policy->view($inspectorOther, $document))->toBeFalse()
        ->and($policy->view($regularUser, $document))->toBeFalse();

    // Create
    expect($policy->create($inspectorOwner))->toBeTrue()
        ->and($policy->create($admin))->toBeTrue()
        ->and($policy->create($regularUser))->toBeFalse();

    // Delete
    expect($policy->delete($inspectorOwner, $document))->toBeTrue()
        ->and($policy->delete($admin, $document))->toBeTrue()
        ->and($policy->delete($inspectorOther, $document))->toBeFalse()
        ->and($policy->delete($regularUser, $document))->toBeFalse();
});
