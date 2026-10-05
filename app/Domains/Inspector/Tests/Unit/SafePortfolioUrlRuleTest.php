<?php

declare(strict_types=1);

use App\Domains\Inspector\Rules\SafePortfolioUrl;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

describe('SafePortfolioUrl Rule', function () {
    /**
     * @return array<string, array{0: mixed}>
     */
    function dangerousPayloads(): array
    {
        return [
            'javascript scheme' => ['javascript:alert(1)'],
            'javascript with host and encoded newline' => ['javascript://x%0Aalert(1)'],
            'javascript with encoded newline uppercase' => ['javascript://x%0aalert(1)'],
            'data uri scheme' => ['data:text/html,<script>alert(1)</script>'],
            'data scheme with authority' => ['data://text/html,x'],
            'vbscript scheme' => ['vbscript:msgbox(1)'],
            'file scheme' => ['file:///etc/passwd'],
            'ftp scheme' => ['ftp://example.com/file.txt'],
            'protocol relative url without scheme' => ['//evil.com/exploit'],
            'url with spaces' => ['https://example.com/my portfolio'],
            'url with leading spaces' => [' https://example.com'],
            'url with trailing spaces' => ['https://example.com '],
            'url with newline' => ["https://example.com/path\nexploit"],
            'url with decoded carriage return or newline' => ['https://example.com/path%0d%0aexploit'],
            'url with null byte' => ['https://example.com/path%00exploit'],
            'url with userinfo username and password' => ['https://admin:secret@example.com/portfolio'],
            'url with userinfo username only' => ['https://admin@example.com/portfolio'],
            'url longer than 2048 characters' => ['https://example.com/'.str_repeat('a', 2040)],
            'empty string' => [''],
            'whitespace only string' => ['   '],
            'non-string integer' => [12345],
            'non-string array' => [['https://example.com']],
            'null value' => [null],
            'plain string without scheme' => ['not-a-valid-url'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    function validPayloads(): array
    {
        return [
            'standard https url' => ['https://example.com'],
            'standard http url' => ['http://example.com'],
            'https with path and query parameters' => ['https://example.com/portfolio?category=web&page=2'],
            'https with fragment' => ['https://example.com/portfolio#projects'],
            'https with custom port' => ['https://example.com:8443/portfolio'],
            'uppercase scheme and host' => ['HTTPS://EXAMPLE.COM/PORTFOLIO'],
            'mixed case scheme' => ['hTtPs://example.com/portfolio'],
            'subdomains and tlds' => ['https://sub.sub2.portfolio.co.id/project-1'],
            'maximum allowed length (exactly 2048 chars)' => ['https://example.com/'.str_repeat('a', 2048 - strlen('https://example.com/'))],
        ];
    }

    test('rejects all dangerous payloads via isValid()', function (mixed $payload) {
        expect(SafePortfolioUrl::isValid($payload))->toBeFalse();
    })->with(dangerousPayloads());

    test('accepts all valid payloads via isValid()', function (string $payload) {
        expect(SafePortfolioUrl::isValid($payload))->toBeTrue();
    })->with(validPayloads());

    test('fails laravel validation for dangerous payloads with localized error message', function (mixed $payload) {
        $validator = Validator::make(
            ['portfolio_url' => $payload],
            ['portfolio_url' => ['required', new SafePortfolioUrl]]
        );

        expect($validator->fails())->toBeTrue();
        if (is_string($payload) && trim($payload) !== '') {
            expect($validator->errors()->first('portfolio_url'))
                ->toBe(__('inspector.validation.safe_portfolio_url'));
        }
    })->with(dangerousPayloads());

    test('passes laravel validation for valid payloads', function (string $payload) {
        $validator = Validator::make(
            ['portfolio_url' => $payload],
            ['portfolio_url' => ['required', new SafePortfolioUrl]]
        );

        expect($validator->passes())->toBeTrue();
    })->with(validPayloads());
});
