<?php

declare(strict_types=1);

namespace App\Domains\Catalog\DTOs;

use App\Domains\Catalog\Actions\BrowseListings;

/**
 * Sanitized read-only filter for the public catalog listing.
 *
 * Every value comes from untrusted query strings, so anything unexpected
 * (arrays, unknown options, out-of-range numbers, overlong keywords) is
 * normalized to a safe default instead of failing.
 */
final readonly class CatalogFilter
{
    public const int KEYWORD_MAX_LENGTH = 100;

    public const int TEXT_MAX_LENGTH = 100;

    public const int MIN_YEAR = 1900;

    public const int MAX_PRICE = 999_999_999_999;

    /**
     * @see BrowseListings for the consumers of these keys.
     */
    public const string DEFAULT_SORT = 'newest';

    /**
     * @var list<string>
     */
    public const array SORTS = ['newest', 'cheapest', 'priciest', 'lowest_km'];

    /**
     * @var list<string>
     */
    public const array TYPES = ['car', 'motorcycle'];

    /**
     * @var list<string>
     */
    public const array INSPECTED_OPTIONS = ['all', 'inspected', 'uninspected'];

    public function __construct(
        public ?string $keyword = null,
        public ?string $type = null,
        public ?string $brand = null,
        public ?int $priceMin = null,
        public ?int $priceMax = null,
        public ?int $yearMin = null,
        public ?int $yearMax = null,
        public ?string $area = null,
        public string $inspected = 'all',
        public string $sort = self::DEFAULT_SORT,
        public int $page = 1,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public static function fromQuery(array $query): self
    {
        $priceMin = self::toNonNegativeInt($query['price_min'] ?? null);
        $priceMax = self::toNonNegativeInt($query['price_max'] ?? null);

        if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
            $priceMin = null;
            $priceMax = null;
        }

        $yearMin = self::toYear($query['year_min'] ?? null);
        $yearMax = self::toYear($query['year_max'] ?? null);

        if ($yearMin !== null && $yearMax !== null && $yearMin > $yearMax) {
            $yearMin = null;
            $yearMax = null;
        }

        $inspected = $query['inspected'] ?? 'all';
        if (! is_string($inspected) || ! in_array($inspected, self::INSPECTED_OPTIONS, true)) {
            $inspected = 'all';
        }

        $sort = $query['sort'] ?? self::DEFAULT_SORT;
        if (! is_string($sort) || ! in_array($sort, self::SORTS, true)) {
            $sort = self::DEFAULT_SORT;
        }

        $type = $query['type'] ?? null;
        if (! is_string($type) || ! in_array($type, self::TYPES, true)) {
            $type = null;
        }

        return new self(
            keyword: self::toText($query['q'] ?? null, self::KEYWORD_MAX_LENGTH),
            type: $type,
            brand: self::toText($query['brand'] ?? null, self::TEXT_MAX_LENGTH),
            priceMin: $priceMin,
            priceMax: $priceMax,
            yearMin: $yearMin,
            yearMax: $yearMax,
            area: self::toText($query['area'] ?? null, self::TEXT_MAX_LENGTH),
            inspected: $inspected,
            sort: $sort,
            page: self::toPage($query['page'] ?? null),
        );
    }

    /**
     * Sanitized parameters for pagination links. Page itself is
     * managed by the paginator, so it is excluded here.
     *
     * @return array<string, string|int>
     */
    public function toQuery(): array
    {
        $params = [];

        if ($this->keyword !== null) {
            $params['q'] = $this->keyword;
        }

        if ($this->type !== null) {
            $params['type'] = $this->type;
        }

        if ($this->brand !== null) {
            $params['brand'] = $this->brand;
        }

        if ($this->priceMin !== null) {
            $params['price_min'] = $this->priceMin;
        }

        if ($this->priceMax !== null) {
            $params['price_max'] = $this->priceMax;
        }

        if ($this->yearMin !== null) {
            $params['year_min'] = $this->yearMin;
        }

        if ($this->yearMax !== null) {
            $params['year_max'] = $this->yearMax;
        }

        if ($this->area !== null) {
            $params['area'] = $this->area;
        }

        if ($this->inspected !== 'all') {
            $params['inspected'] = $this->inspected;
        }

        if ($this->sort !== self::DEFAULT_SORT) {
            $params['sort'] = $this->sort;
        }

        return $params;
    }

    private static function toText(mixed $value, int $maxLength): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $text = trim($value);

        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, $maxLength);
    }

    private static function toNonNegativeInt(mixed $value): ?int
    {
        if (is_array($value)) {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(['.', ',', ' '], '', trim($value));
        }

        if (! is_numeric($value)) {
            return null;
        }

        $int = (int) $value;

        if ($int < 0 || $int > self::MAX_PRICE) {
            return null;
        }

        return $int;
    }

    private static function toYear(mixed $value): ?int
    {
        if (is_array($value) || ! is_numeric($value)) {
            return null;
        }

        $year = (int) $value;
        $maxYear = (int) date('Y') + 1;

        if ($year < self::MIN_YEAR || $year > $maxYear) {
            return null;
        }

        return $year;
    }

    private static function toPage(mixed $value): int
    {
        if (is_array($value) || ! is_numeric($value)) {
            return 1;
        }

        $page = (int) $value;

        return $page >= 1 ? $page : 1;
    }
}
