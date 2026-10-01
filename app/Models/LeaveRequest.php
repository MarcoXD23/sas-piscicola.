<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use BelongsToFinca;
    use HasFactory;

    public const STATUS_PENDIENTE = 'pendiente';

    public const STATUS_APROBADO = 'aprobado';

    public const STATUS_RECHAZADO = 'rechazado';

    protected $fillable = [
        'finca_id',
        'user_id',
        'start_date',
        'end_date',
        'reason',
        'status',
        'reviewed_by_user_id',
        'response_notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function approve(User $reviewer, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APROBADO,
            'reviewed_by_user_id' => $reviewer->id,
            'response_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    public function reject(User $reviewer, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_RECHAZADO,
            'reviewed_by_user_id' => $reviewer->id,
            'response_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }
}
