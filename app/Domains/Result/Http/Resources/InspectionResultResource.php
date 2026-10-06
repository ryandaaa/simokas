<?php

declare(strict_types=1);

namespace App\Domains\Result\Http\Resources;

use App\Domains\Result\Models\InspectionResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InspectionResult
 */
final class InspectionResultResource extends JsonResource
{
    /**
     * Whether to include full details or limited summary.
     */
    private bool $includeDetails = true;

    /**
     * Access fee amount for this vehicle type.
     */
    private ?int $accessFee = null;

    /**
     * Set whether to include full details.
     */
    public function withDetails(bool $include = true): self
    {
        $this->includeDetails = $include;

        return $this;
    }

    /**
     * Set the access fee amount.
     */
    public function withAccessFee(?int $fee): self
    {
        $this->accessFee = $fee;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'status' => $this->status->value,
            'submission_date' => $this->submitted_at?->toDateString(),
            'inspector' => [
                'id' => $this->inspector->id,
                'name' => $this->inspector->name,
                // @todo: rating_average added in RTG-1
                'rating' => null,
            ],
            'recommendation' => $this->recommendation?->value,
            'has_access' => true,
        ];

        // Limited summary (for unauthorized users)
        if (! $this->includeDetails) {
            return array_merge($data, [
                'limited_summary' => true,
                'access_fee' => $this->accessFee,
                'vehicle_type' => $this->listing->vehicle_type->value,
            ]);
        }

        // Full details (for authorized users)
        return array_merge($data, [
            'limited_summary' => false,
            'total_score' => $this->total_score,
            'summary_note' => $this->summary_note,
            'vehicle_info' => [
                'type' => $this->listing->vehicle_type->value,
                'brand' => $this->listing->brand,
                'model' => $this->listing->model,
                'year' => $this->listing->year,
            ],
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'template_item_id' => $item->template_item_id,
                'rating' => $item->rating->value,
                'notes' => $item->notes,
            ])->all(),
            'photos' => $this->photos->map(fn ($photo) => [
                'id' => $photo->id,
                'captured_at' => $photo->captured_at?->toIso8601String(),
                'stamped_at' => $photo->stamped_at?->toIso8601String(),
                'url' => route('results.photos.show', ['result' => $this->id, 'photo' => $photo->id]),
            ])->all(),
        ]);
    }
}
