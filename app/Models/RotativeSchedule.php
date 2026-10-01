<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RotativeSchedule extends Model
{
    use BelongsToFinca;
    use HasFactory;

    public const SHIFT_LUNES_A_VIERNES = 'lunes_a_viernes';

    public const SHIFT_FIN_DE_SEMANA = 'fin_de_semana';

    public const SHIFT_GUARDIA_NOCTURNA = 'guardia_nocturna';

    protected $fillable = [
        'finca_id',
        'user_id',
        'week_start_date',
        'week_end_date',
        'shift_type',
        'notes',
        'assigned_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'week_start_date' => 'date',
            'week_end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
