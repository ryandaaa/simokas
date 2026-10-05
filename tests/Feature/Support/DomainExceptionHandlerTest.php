<?php

declare(strict_types=1);

use App\Support\Exceptions\DomainException;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::get('/test-domain-exception-conflict', function (): never {
        throw new DomainException(
            message: 'Job sudah diambil oleh inspektor lain.',
            errorCode: 'JOB_ALREADY_TAKEN',
            statusCode: 409,
        );
    });

    Route::get('/test-domain-exception-expired', function (): never {
        throw new DomainException(
            message: 'Tagihan telah kedaluwarsa.',
            errorCode: DomainException::CODE_PAYMENT_EXPIRED,
            statusCode: 410,
        );
    });

    Route::get('/test-domain-exception-forbidden', function (): never {
        throw new DomainException(
            message: 'Inspektor belum diverifikasi.',
            errorCode: 'INSPECTOR_NOT_VERIFIED',
            statusCode: 403,
        );
    });

    Route::get('/test-domain-exception-with-context', function (): never {
        throw new DomainException(
            message: 'Item wajib belum diisi.',
            errorCode: 'REQUIRED_ITEMS_MISSING',
            statusCode: 422,
            context: ['missing_items' => ['item_engine', 'item_brake']],
        );
    });

    Route::get('/api/test-domain-exception', function (): never {
        throw new DomainException(
            message: 'Aturan bisnis gagal pada API.',
            errorCode: DomainException::CODE_BUSINESS_RULE,
            statusCode: 422,
        );
    });
});

it('renders JSON response with proper status code and format when request expects json', function (): void {
    $response = $this->getJson('/test-domain-exception-conflict');

    $response->assertStatus(409)
        ->assertJson([
            'data' => null,
            'error' => [
                'code' => 'JOB_ALREADY_TAKEN',
                'message' => 'Job sudah diambil oleh inspektor lain.',
            ],
        ]);
});

it('translates expired payment exception to 410 JSON response', function (): void {
    $response = $this->getJson('/test-domain-exception-expired');

    $response->assertStatus(410)
        ->assertJson([
            'data' => null,
            'error' => [
                'code' => 'PAYMENT_EXPIRED',
                'message' => 'Tagihan telah kedaluwarsa.',
            ],
        ]);
});

it('translates forbidden domain exception to 403 JSON response', function (): void {
    $response = $this->getJson('/test-domain-exception-forbidden');

    $response->assertStatus(403)
        ->assertJson([
            'data' => null,
            'error' => [
                'code' => 'INSPECTOR_NOT_VERIFIED',
                'message' => 'Inspektor belum diverifikasi.',
            ],
        ]);
});

it('includes context details in error payload when present for JSON requests', function (): void {
    $response = $this->getJson('/test-domain-exception-with-context');

    $response->assertStatus(422)
        ->assertJson([
            'data' => null,
            'error' => [
                'code' => 'REQUIRED_ITEMS_MISSING',
                'message' => 'Item wajib belum diisi.',
                'errors' => [
                    'missing_items' => ['item_engine', 'item_brake'],
                ],
            ],
        ]);
});

it('unwraps errors array if context already contains errors key', function (): void {
    Route::get('/test-domain-exception-unwrapped-errors', function (): never {
        throw new DomainException(
            message: 'Validasi form gagal.',
            errorCode: DomainException::CODE_VALIDATION_ERROR,
            statusCode: 400,
            context: ['errors' => ['amount' => ['Nominal harus angka bulat.']]],
        );
    });

    $response = $this->getJson('/test-domain-exception-unwrapped-errors');

    $response->assertStatus(400)
        ->assertJson([
            'data' => null,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validasi form gagal.',
                'errors' => [
                    'amount' => ['Nominal harus angka bulat.'],
                ],
            ],
        ]);
});

it('automatically renders JSON for api routes even without Accept application/json header', function (): void {
    $response = $this->get('/api/test-domain-exception');

    $response->assertStatus(422)
        ->assertJson([
            'data' => null,
            'error' => [
                'code' => 'BUSINESS_RULE',
                'message' => 'Aturan bisnis gagal pada API.',
            ],
        ]);
});

it('leaves default Laravel error behavior for standard web requests without expecting JSON', function (): void {
    // For standard web requests without JSON expectation, handler must not return JSON
    $response = $this->get('/test-domain-exception-conflict');

    expect($response->headers->get('content-type'))->not->toContain('application/json');
});
