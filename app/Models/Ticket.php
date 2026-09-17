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

/**
 * @property int $id
 * @property int $ticket_category_id
 * @property string $code
 * @property Carbon|null $checked_in_at
 * @property int|null $checked_in_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['ticket_category_id', 'code', 'checked_in_at', 'checked_in_by'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
        ];
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
        $query->whereHas('ticketCategory.event', function (Builder $query): void {
            $query->where('status', EventStatus::Active->value);
        });
    }
}
