<?php

declare(strict_types=1);

namespace App\Domains\Listing\Rules;

use App\Domains\Listing\ValueObjects\PekanbaruBoundary;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Server-side rule ensuring a listing pin is inside Kota Pekanbaru (PRD 16.1,
 * LST-R3). Validates one axis ('lat' or 'lng'); the sibling axis is read from
 * the surrounding payload so the boundary box can be checked as a pair.
 */
final class WithinPekanbaru implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    public function __construct(
        private readonly string $axis = 'lat',
        private readonly string $siblingField = 'lng',
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            $fail(__('listing.validation.location_invalid'));

            return;
        }

        $coordinate = (float) $value;

        if (! is_finite($coordinate)) {
            $fail(__('listing.validation.location_invalid'));

            return;
        }

        [$min, $max] = $this->axis === 'lng' ? [-180.0, 180.0] : [-90.0, 90.0];

        if ($coordinate < $min || $coordinate > $max) {
            $fail(__('listing.validation.location_out_of_range', [
                'min' => $min,
                'max' => $max,
            ]));

            return;
        }

        $sibling = $this->data[$this->siblingField] ?? null;

        // The sibling field has its own rule instance; skip the pair check
        // until both axes hold usable numbers.
        if (! is_numeric($sibling) || ! is_finite((float) $sibling)) {
            return;
        }

        $lat = $this->axis === 'lat' ? $coordinate : (float) $sibling;
        $lng = $this->axis === 'lng' ? $coordinate : (float) $sibling;

        if (! PekanbaruBoundary::contains($lat, $lng)) {
            $fail(__('listing.validation.location_outside_pekanbaru'));
        }
    }
}
