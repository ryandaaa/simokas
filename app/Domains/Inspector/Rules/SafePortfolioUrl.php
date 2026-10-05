<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class SafePortfolioUrl implements ValidationRule
{
    public static function isValid(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || $trimmed !== $value) {
            return false;
        }

        // Panjang maksimal 2048 karakter
        if (strlen($value) > 2048) {
            return false;
        }

        // Tanpa spasi (spasi tidak diperbolehkan)
        if (preg_match('/\s/', $value) === 1) {
            return false;
        }

        // Karakter kontrol pada string asli
        if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            return false;
        }

        // Karakter kontrol pada string yang sudah didekode (termasuk %0A, %0D, dll.)
        $decoded = rawurldecode($value);
        if (preg_match('/[\x00-\x1F\x7F]/', $decoded) === 1) {
            return false;
        }

        // Parsing komponen URL
        $parsed = parse_url($value);
        if (! is_array($parsed)) {
            return false;
        }

        // Hanya skema http dan https (dibandingkan tanpa peka huruf besar-kecil)
        if (! isset($parsed['scheme'])) {
            return false;
        }

        $scheme = strtolower($parsed['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        // Wajib punya host
        if (empty($parsed['host']) || trim($parsed['host']) === '') {
            return false;
        }

        // Tolak userinfo (user:pass@ atau user@)
        if (isset($parsed['user']) || isset($parsed['pass'])) {
            return false;
        }

        // Validasi sintaks URL umum
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return true;
    }

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isValid($value)) {
            $fail(__('inspector.validation.safe_portfolio_url'));
        }
    }
}
