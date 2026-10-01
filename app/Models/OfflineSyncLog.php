<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfflineSyncLog extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $table = 'offline_sync_logs';

    public const STATUS_SYNCED = 'synced';

    public const STATUS_IGNORED_LWW = 'ignored_lww';

    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'finca_id',
        'user_id',
        'client_uuid',
        'table_name',
        'action',
        'device_timestamp',
        'processed_at',
        'status',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'device_timestamp' => 'datetime',
            'processed_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
