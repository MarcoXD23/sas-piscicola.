<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FishingAttendance extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $fillable = [
        'finca_id',
        'user_id',
        'attendance_date',
        'attended',
        'day_of_week',
        'is_holiday_catchup',
        'role_in_harvest',
        'daily_wage',
        'registered_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'attended' => 'boolean',
            'is_holiday_catchup' => 'boolean',
            'daily_wage' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }
}
