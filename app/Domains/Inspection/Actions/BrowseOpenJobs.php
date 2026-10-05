<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\DTOs\OpenJobCard;
use App\Domains\Inspection\DTOs\OpenJobFilter;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Exceptions\InspectorNotVerifiedException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Contracts\InspectorEligibility;
use App\Domains\Listing\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Daftar job inspeksi terbuka untuk inspektor terverifikasi (JOB-2).
 *
 * Mengambil job dengan status paid dan inspector_id null yang listing-nya
 * bukan milik inspektor bersangkutan dan sesuai dengan jenis keahlian inspektor.
 * Hanya kolom aman yang di-query, kolom persis (lat, lng, address_full, chassis_number,
 * inspection_place) tidak pernah dimuat atau dikirim ke klien (JOB-R5).
 */
final class BrowseOpenJobs
{
    public const int PER_PAGE = 12;

    /**
     * Kolom aman milik listing yang boleh dibaca publik/inspektor sebelum job diambil.
     *
     * @var list<string>
     */
    public const array SAFE_LISTING_COLUMNS = [
        'id',
        'seller_id',
        'vehicle_type',
        'brand',
        'model',
        'year',
        'area_name',
    ];

    /**
     * Kolom aman milik inspection_jobs.
     *
     * @var list<string>
     */
    public const array SAFE_JOB_COLUMNS = [
        'id',
        'listing_id',
        'inspector_id',
        'status',
        'fee_amount',
        'platform_fee_pct',
        'available_since',
        'created_at',
    ];

    public function __construct(
        private readonly InspectorEligibility $eligibility,
    ) {}

    /**
     * @return LengthAwarePaginator<int, OpenJobCard>
     */
    public function handle(User $inspector, OpenJobFilter $filter): LengthAwarePaginator
    {
        if (! $this->eligibility->canViewAndTakeJobs($inspector)) {
            throw InspectorNotVerifiedException::create();
        }

        $allowedSpecialties = $this->eligibility->getAllowedSpecialties($inspector);

        if (empty($allowedSpecialties)) {
            /** @var LengthAwarePaginator<int, OpenJobCard> $emptyPaginator */
            $emptyPaginator = new LengthAwarePaginator([], 0, self::PER_PAGE, $filter->page);

            return $emptyPaginator;
        }

        $query = InspectionJob::query()
            ->select(self::SAFE_JOB_COLUMNS)
            ->where('status', JobStatus::Paid)
            ->whereNull('inspector_id');

        $query->whereHas('listing', function (Builder $listingQuery) use ($inspector, $allowedSpecialties, $filter): void {
            $listingQuery->where('seller_id', '!=', $inspector->getKey())
                ->whereIn('vehicle_type', $allowedSpecialties);

            if ($filter->vehicleType !== null) {
                $listingQuery->where('vehicle_type', $filter->vehicleType);
            }

            if ($filter->area !== null) {
                $area = self::escapeLike($filter->area);
                $listingQuery->where('area_name', 'like', "%{$area}%");
            }
        });

        if ($filter->feeMin !== null) {
            $query->where('fee_amount', '>=', $filter->feeMin);
        }

        if ($filter->feeMax !== null) {
            $query->where('fee_amount', '<=', $filter->feeMax);
        }

        if ($filter->sort === OpenJobFilter::SORT_HIGHEST_FEE) {
            $query->orderByDesc('fee_amount')->orderByDesc('created_at');
        } else {
            $query->orderByRaw('COALESCE(available_since, created_at) DESC')->orderByDesc('id');
        }

        $query->with(['listing' => fn ($q) => $q->select(self::SAFE_LISTING_COLUMNS)]);

        /** @var LengthAwarePaginator<int, InspectionJob> $paginator */
        $paginator = $query->paginate(self::PER_PAGE, ['*'], 'page', $filter->page);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (InspectionJob $job): OpenJobCard => OpenJobCard::fromModel($job))
        );

        /** @var LengthAwarePaginator<int, OpenJobCard> $paginator */
        return $paginator;
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
