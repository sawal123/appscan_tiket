<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $ticket_id
 * @property int|null $scanner_id
 * @property string|null $qr_code
 * @property string $status
 * @property Carbon $scanned_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['ticket_id', 'scanner_id', 'qr_code', 'status', 'scanned_at'])]
class CheckInLog extends Model
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_ALREADY_CHECKED_IN = 'already_checked_in';

    public const STATUS_INVALID = 'invalid';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanner_id');
    }
}
