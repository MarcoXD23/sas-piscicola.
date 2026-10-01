<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroCalidadAgua extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $table = 'registros_calidad_agua';

    protected $fillable = [
        'finca_id',
        'estanque_id',
        'user_id',
        'fecha',
        'hora',
        'oxigeno_mg_l',
        'temperatura_c',
        'ph',
        'disco_secchi_cm',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'oxigeno_mg_l' => 'decimal:2',
            'temperatura_c' => 'decimal:1',
            'ph' => 'decimal:2',
            'disco_secchi_cm' => 'decimal:2',
        ];
    }

    public function estanque(): BelongsTo
    {
        return $this->belongsTo(Pond::class, 'estanque_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
