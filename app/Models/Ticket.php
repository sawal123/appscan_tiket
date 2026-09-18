<?php

namespace App\Models;

use App\Enums\EventStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $event_id
 * @property int $ticket_category_id
 * @property string $code
 * @property string $qr_code
 * @property string $status
 * @property Carbon|null $registered_at
 * @property int|null $registered_by
 * @property Carbon|null $checked_in_at
 * @property int|null $checked_in_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['event_id', 'ticket_category_id', 'code', 'qr_code', 'status', 'registered_at', 'registered_by', 'checked_in_at', 'checked_in_by'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    public const STATUS_REGISTERED = 'registered';

    public const STATUS_CHECKED_IN = 'checked_in';

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            if ($ticket->qr_code) {
                $ticket->qr_code = static::normalizeQrCode($ticket->qr_code);
            }

            if ($ticket->code) {
                $ticket->code = static::normalizeQrCode($ticket->code);
            }

            if (! $ticket->qr_code && $ticket->code) {
                $ticket->qr_code = $ticket->code;
            }

            if (! $ticket->code && $ticket->qr_code) {
                $ticket->code = $ticket->qr_code;
            }

            if (! $ticket->event_id && $ticket->ticket_category_id) {
                $ticket->event_id = TicketCategory::query()->whereKey($ticket->ticket_category_id)->value('event_id');
            }

            if (! $ticket->status) {
                $ticket->status = $ticket->checked_in_at ? static::STATUS_CHECKED_IN : static::STATUS_REGISTERED;
            }

            if (! $ticket->registered_at) {
                $ticket->registered_at = now();
            }
        });

        static::saving(function (Ticket $ticket): void {
            if ($ticket->qr_code) {
                $ticket->qr_code = static::normalizeQrCode($ticket->qr_code);
            }

            if ($ticket->code) {
                $ticket->code = static::normalizeQrCode($ticket->code);
            }

            if ($ticket->checked_in_at && $ticket->status !== static::STATUS_CHECKED_IN) {
                $ticket->status = static::STATUS_CHECKED_IN;
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'checked_in_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<TicketCategory, $this>
     */
    public function ticketCategory(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    /**
     * Limit the query to tickets belonging to the active event.
     *
     * @param  Builder<Ticket>  $query
     */
    public function scopeForActiveEvent(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query
                ->whereHas('event', fn (Builder $query) => $query->where('status', EventStatus::Active->value))
                ->orWhereHas('ticketCategory.event', fn (Builder $query) => $query->where('status', EventStatus::Active->value));
        });
    }

    public static function normalizeQrCode(string $qrCode): string
    {
        return Str::upper(trim($qrCode));
    }
}
