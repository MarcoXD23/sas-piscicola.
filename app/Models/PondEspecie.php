<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PondEspecie extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $table = 'pond_especies';

    protected $fillable = [
        'pond_id',
        'especie_id',
        'fingerlings_stocked',
        'fish_population',
        'average_weight',
        'biomass',
        'finca_id',
    ];

    protected function casts(): array
    {
        return [
            'fingerlings_stocked' => 'integer',
            'fish_population' => 'integer',
            'average_weight' => 'decimal:2',
            'biomass' => 'decimal:2',
        ];
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class, 'pond_id');
    }

    public function especie(): BelongsTo
    {
        return $this->belongsTo(Especie::class, 'especie_id');
    }
}

