<?php

declare(strict_types=1);

namespace App\Domains\Setting\Models;

use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $key
 * @property mixed $value
 * @property string|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $updater
 */
final class AppSetting extends Model
{
    use HasUuids;

    protected $table = 'app_settings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /**
     * Relasi ke pengguna (admin) yang terakhir kali mengubah pengaturan ini.
     *
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Mengambil nilai pengaturan sebagai integer.
     */
    public function intValue(): int
    {
        $val = $this->value;

        if (is_array($val) && array_key_exists('value', $val)) {
            return (int) $val['value'];
        }

        return (int) $val;
    }
}
