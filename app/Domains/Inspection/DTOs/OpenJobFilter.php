<?php

declare(strict_types=1);

namespace App\Domains\Inspection\DTOs;

final readonly class OpenJobFilter
{
    public const string SORT_LATEST = 'latest';

    public const string SORT_HIGHEST_FEE = 'highest_fee';

    public const array ALLOWED_SORTS = [
        self::SORT_LATEST,
        self::SORT_HIGHEST_FEE,
    ];

    public function __construct(
        public ?string $vehicleType = null,
        public ?string $area = null,
        public ?int $feeMin = null,
        public ?int $feeMax = null,
        public string $sort = self::SORT_LATEST,
        public int $page = 1,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $vehicleType = isset($input['vehicle_type']) && is_string($input['vehicle_type']) && $input['vehicle_type'] !== ''
            ? strtolower(trim($input['vehicle_type']))
            : null;

        $area = isset($input['area']) && is_string($input['area']) && trim($input['area']) !== ''
            ? trim($input['area'])
            : null;

        $feeMin = isset($input['fee_min']) && is_numeric($input['fee_min'])
            ? max(0, (int) $input['fee_min'])
            : null;

        $feeMax = isset($input['fee_max']) && is_numeric($input['fee_max'])
            ? max(0, (int) $input['fee_max'])
            : null;

        $sort = isset($input['sort']) && is_string($input['sort']) && in_array($input['sort'], self::ALLOWED_SORTS, true)
            ? $input['sort']
            : self::SORT_LATEST;

        $page = isset($input['page']) && is_numeric($input['page'])
            ? max(1, (int) $input['page'])
            : 1;

        return new self(
            vehicleType: $vehicleType,
            area: $area,
            feeMin: $feeMin,
            feeMax: $feeMax,
            sort: $sort,
            page: $page,
        );
    }
}
