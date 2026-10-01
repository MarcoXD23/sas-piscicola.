<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRecord extends Model
{
    use BelongsToFinca;
    use HasFactory;

    public const STATUS_PENDIENTE = 'pendiente';

    public const STATUS_APROBADO = 'aprobado';

    public const STATUS_RECHAZADO = 'rechazado';

    protected $fillable = [
        'finca_id',
        'user_id',
        'record_date',
        'hours',
        'occasion',
        'justification',
        'status',
        'approved_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'hours' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
