<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;

final class ListingPrivacyHelper
{
    /**
     * Asserts that sensitive/private listing fields (lat, lng, address_full,
     * chassis_number, inspection_place) are never present in the given
     * response content or structure.
     *
     * Works for JSON arrays, raw JSON strings, TestResponse, and rendered
     * HTML. Pass known sensitive values (exact address text, chassis number,
     * exact coordinates) as $sensitiveValues to also catch leaks where the
     * value appears without its field key, e.g. inside HTML.
     *
     * @param  array<int, string>  $sensitiveValues
     */
    public static function assertNoPrivateListingData(mixed $response, array $sensitiveValues = []): void
    {
        $forbiddenKeys = ['lat', 'lng', 'address_full', 'chassis_number', 'inspection_place'];

        $content = '';
        $json = null;

        if ($response instanceof TestResponse) {
            $content = $response->getContent();
            $json = rescue(fn () => $response->json(), null, false);
        } elseif (is_string($response)) {
            $content = $response;
            $json = json_decode($response, true);
        } elseif (is_array($response)) {
            $json = $response;
            $content = (string) json_encode($response);
        }

        if (is_array($json)) {
            $checkArray = function (array $data, callable $self) use ($forbiddenKeys): void {
                foreach ($data as $key => $value) {
                    Assert::assertFalse(
                        in_array((string) $key, $forbiddenKeys, true),
                        "Response contains private listing field key: '{$key}'"
                    );

                    if (is_array($value)) {
                        $self($value, $self);
                    }
                }
            };

            $checkArray($json, $checkArray);
        }

        if ($content !== '') {
            foreach ($forbiddenKeys as $key) {
                Assert::assertDoesNotMatchRegularExpression(
                    '/"'.preg_quote($key, '/').'"\s*:/',
                    $content,
                    "Response raw content contains private listing key: '{$key}'"
                );
            }

            foreach ($sensitiveValues as $value) {
                if ($value === '') {
                    continue;
                }

                Assert::assertStringNotContainsString(
                    $value,
                    $content,
                    'Response content leaks a private listing value.'
                );
                Assert::assertStringNotContainsString(
                    (string) e($value),
                    $content,
                    'Response content leaks an HTML-escaped private listing value.'
                );
            }
        }
    }
}

if (! function_exists('assertNoPrivateListingData')) {
    /**
     * @param  array<int, string>  $sensitiveValues
     */
    function assertNoPrivateListingData(mixed $response, array $sensitiveValues = []): void
    {
        ListingPrivacyHelper::assertNoPrivateListingData($response, $sensitiveValues);
    }
}
