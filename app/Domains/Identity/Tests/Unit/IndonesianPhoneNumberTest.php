<?php

declare(strict_types=1);

use App\Domains\Identity\Rules\IndonesianPhoneNumber;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

it('passes for valid indonesian phone numbers', function (string $number) {
    $validator = Validator::make(
        ['phone' => $number],
        ['phone' => ['required', new IndonesianPhoneNumber]]
    );

    expect($validator->passes())->toBeTrue("Expected '{$number}' to be valid.");
})->with([
    '08 format 10 digits' => '0812345678',
    '08 format 11 digits' => '08123456789',
    '08 format 12 digits' => '081234567890',
    '08 format 9 digits min' => '081234567',
    '08 format 14 digits max' => '08123456789012',
    '+62 format 12 digits' => '+6281234567890',
    '+62 format 9 digits min' => '+6281234567',
    '+62 format 14 digits max' => '+628123456789012',
    '62 format without plus' => '628123456789',
    'with leading spaces' => ' 08123456789 ',
]);

it('fails for invalid phone numbers', function (mixed $number) {
    $validator = Validator::make(
        ['phone' => $number],
        ['phone' => ['required', new IndonesianPhoneNumber]]
    );

    expect($validator->fails())->toBeTrue("Expected '{$number}' to be invalid.");
})->with([
    'too short 8 digits' => '08123456',
    'too long 15 digits' => '081234567890123',
    'starts with 07' => '0712345678',
    'starts with 021' => '0212345678',
    'contains letters' => '0812345abcde',
    'random string' => 'invalidphone',
    'empty string' => '',
    'plus without country code' => '+08123456789',
    'foreign phone number' => '+14155552671',
]);
