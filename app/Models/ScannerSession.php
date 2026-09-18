<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $device_name
 * @property string|null $device_id
 * @property Carbon $last_seen_at
 * @property Carbon|null $last_scan_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'device_name', 'device_id', 'last_seen_at', 'last_scan_at', 'ip_address', 'user_agent'])]
class ScannerSession extends Model
{
    public const ONLINE_THRESHOLD_MINUTES = 2;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'last_scan_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at->greaterThanOrEqualTo(now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES));
    }
}
