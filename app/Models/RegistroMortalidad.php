<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroMortalidad extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $table = 'registros_mortalidad';

    public const METODO_COMPOSTAJE = 'compostaje';

    public const METODO_FOSA = 'fosa';

    public const METODO_ENTIERRO_CAL = 'entierro_cal';

    protected $fillable = [
        'finca_id',
        'estanque_id',
        'user_id',
        'fecha',
        'cantidad_peces',
        'causa_probable',
        'metodo_disposicion',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cantidad_peces' => 'integer',
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
