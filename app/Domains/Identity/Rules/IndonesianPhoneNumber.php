<?php

declare(strict_types=1);

namespace App\Domains\Identity\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class IndonesianPhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('validation.phone_invalid', ['attribute' => $attribute]));

            return;
        }

        $cleaned = trim($value);

        // Normalize leading +62 or 62 to 0
        if (str_starts_with($cleaned, '+62')) {
            $cleaned = '0'.substr($cleaned, 3);
        } elseif (str_starts_with($cleaned, '62')) {
            $cleaned = '0'.substr($cleaned, 2);
        }

        // Must start with 08 and be 9 to 14 digits total
        if (! preg_match('/^08[0-9]{7,12}$/', $cleaned)) {
            $fail(__('validation.phone_invalid', ['attribute' => $attribute]));
        }
    }
}
