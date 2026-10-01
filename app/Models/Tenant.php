<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $table = 'tenants';

    protected $fillable = [
        'nombre',
        'codigo',
        'nit',
        'ubicacion',
        'configuraciones',
    ];

    protected $casts = [
        'configuraciones' => 'array',
    ];

    /**
     * Usuarios pertenecientes a este tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }
}
