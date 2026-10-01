<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'slug',
        'name',
        'descripcion',
    ];

    /**
     * Usuarios asignados a este rol.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    /**
     * Color de badge para la UI de Tailwind.
     */
    public function getBadgeColorAttribute(): string
    {
        return match ($this->slug) {
            'propietario', 'owner', 'jefe_mayor' => 'bg-purple-100 text-purple-800 border-purple-200',
            'tecnico_acuicola', 'administrador', 'admin' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
            'operario_campo', 'trabajador', 'worker' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'celador_nocturno', 'celador', 'guard' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            default => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    }
}
