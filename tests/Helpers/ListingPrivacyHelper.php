<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;

final class ListingPrivacyHelper
{
    /**
     * Asserts that sensitive/private listing fields (lat, lng, address_full, chassis_number)
     * are never present in the given response content or structure.
     */
    public static function assertNoPrivateListingData(mixed $response): void
    {
        $forbiddenKeys = ['lat', 'lng', 'address_full', 'chassis_number'];

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
        }
    }
}

if (! function_exists('assertNoPrivateListingData')) {
    function assertNoPrivateListingData(mixed $response): void
    {
        ListingPrivacyHelper::assertNoPrivateListingData($response);
    }
}
