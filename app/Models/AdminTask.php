<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminTask extends Model
{
    use BelongsToFinca;
    use HasFactory;

    public const PRIORITY_BAJA = 'baja';

    public const PRIORITY_MEDIA = 'media';

    public const PRIORITY_ALTA = 'alta';

    public const PRIORITY_URGENTE = 'urgente';

    public const STATUS_PENDIENTE = 'pendiente';

    public const STATUS_EN_PROGRESO = 'en_progreso';

    public const STATUS_COMPLETADA = 'completada';

    public const STATUS_CANCELADA = 'cancelada';

    protected $fillable = [
        'finca_id',
        'title',
        'description',
        'assigned_to_user_id',
        'created_by_user_id',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function markCompleted(?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETADA,
            'completed_at' => now(),
            'notes' => $notes ?? $this->notes,
        ]);
    }
}
