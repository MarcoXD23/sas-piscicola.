<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseMovement extends Model
{
    use BelongsToFinca;
    use HasFactory;

    public const TYPE_ENTRADA = 'entrada';

    public const TYPE_SALIDA = 'salida';

    public const TYPE_AJUSTE = 'ajuste';

    protected $fillable = [
        'finca_id',
        'feed_inventory_id',
        'movement_type',
        'quantity_kg',
        'bags_count',
        'unit_cost',
        'movement_date',
        'reference',
        'notes',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
            'quantity_kg' => 'decimal:2',
            'bags_count' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (WarehouseMovement $movement) {
            $inventory = $movement->feedInventory;
            if (! $inventory) {
                return;
            }

            if ($movement->movement_type === self::TYPE_ENTRADA) {
                $inventory->increment('quantity_kg', $movement->quantity_kg);
            } elseif ($movement->movement_type === self::TYPE_SALIDA) {
                $inventory->decrement('quantity_kg', $movement->quantity_kg);
            } elseif ($movement->movement_type === self::TYPE_AJUSTE) {
                $inventory->quantity_kg = $movement->quantity_kg;
                $inventory->save();
            }
        });
    }

    public function feedInventory(): BelongsTo
    {
        return $this->belongsTo(FeedInventory::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
