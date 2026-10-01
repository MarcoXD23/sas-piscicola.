<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedingLog extends Model
{
    use BelongsToFinca;

    public const APPETITE_BUENO = 'bueno';

    public const APPETITE_REGULAR = 'regular';

    public const APPETITE_MALO = 'malo';

    protected $fillable = [
        'finca_id',
        'user_id',
        'work_schedule_id',
        'pond_id',
        'feed_inventory_id',
        'feeding_date',
        'amount_kg',
        'appetite_level',
        'bags_fed',
        'feed_name',
        'feed_brand',
        'feed_type',
        'feed_protein_percentage',
        'feed_bag_weight_kg',
        'observations',
    ];

    protected $appends = [
        'formatted_feeding_time',
        'formatted_created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'feeding_date' => 'date:Y-m-d',
            'amount_kg' => 'decimal:2',
            'bags_fed' => 'decimal:2',
            'feed_protein_percentage' => 'decimal:2',
            'feed_bag_weight_kg' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Retorna la hora de registro en formato estricto de 12 horas con AM/PM (America/Bogota).
     */
    public function getFormattedFeedingTimeAttribute(): ?string
    {
        return $this->created_at?->timezone('America/Bogota')->format('g:i A');
    }

    /**
     * Retorna la fecha y hora en formato 12 horas con AM/PM (America/Bogota).
     */
    public function getFormattedCreatedAtAttribute(): ?string
    {
        return $this->created_at?->timezone('America/Bogota')->format('Y-m-d g:i A');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function feedInventory(): BelongsTo
    {
        return $this->belongsTo(FeedInventory::class);
    }
}
