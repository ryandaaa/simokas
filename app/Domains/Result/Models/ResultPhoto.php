<?php

declare(strict_types=1);

namespace App\Domains\Result\Models;

use App\Domains\Result\Database\factories\ResultPhotoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * @property string $id
 * @property string $result_id
 * @property string $result_item_id
 * @property string $storage_path
 * @property Carbon $captured_at
 * @property Carbon|null $stamped_at
 * @property bool $is_camera_capture
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read InspectionResult $result
 * @property-read ResultItem $resultItem
 */
final class ResultPhoto extends Model
{
    /** @use HasFactory<ResultPhotoFactory> */
    use HasFactory;

    use HasUuids;

    protected static function newFactory(): ResultPhotoFactory
    {
        return ResultPhotoFactory::new();
    }

    protected $table = 'result_photos';

    protected $fillable = [
        'result_id',
        'result_item_id',
        'storage_path',
        'captured_at',
        'stamped_at',
        'is_camera_capture',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'stamped_at' => 'datetime',
            'is_camera_capture' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<InspectionResult, $this>
     */
    public function result(): BelongsTo
    {
        return $this->belongsTo(InspectionResult::class, 'result_id');
    }

    /**
     * @return BelongsTo<ResultItem, $this>
     */
    public function resultItem(): BelongsTo
    {
        return $this->belongsTo(ResultItem::class, 'result_item_id');
    }

    /**
     * Generate a temporary signed URL for authorized access.
     */
    public function temporaryUrl(int $minutes = 30): string
    {
        return URL::temporarySignedRoute(
            'results.photos.show',
            now()->addMinutes($minutes),
            [
                'result' => $this->result_id,
                'photo' => $this->id,
            ]
        );
    }
}
