<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Especie extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'especies';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'nombre_comun',
        'nombre_cientifico',
        'familia',
        'clima',
        'foto_url',
        'imagen_url',
        'temperatura_min',
        'temperatura_max',
        'oxigeno_min_mg_l',
        'ph_min',
        'ph_max',
        'densidad_tierra_m2',
        'densidad_geomembrana_m3',
        'proteina_iniciacion',
        'proteina_levante',
        'proteina_engorde',
        'meses_cosecha_promedio',
        'peso_comercial_gramos',
        'rol_policultivo',
        'guia_manejo_cultivo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'temperatura_min' => 'decimal:1',
            'temperatura_max' => 'decimal:1',
            'oxigeno_min_mg_l' => 'decimal:2',
            'ph_min' => 'decimal:2',
            'ph_max' => 'decimal:2',
        ];
    }

    public function setNombreAttribute($value): void
    {
        $this->attributes['nombre_comun'] = $value;
    }

    /**
     * Ruta relativa de la imagen local para asset().
     */
    public function getImagenUrlAttribute(): string
    {
        return $this->foto_url ?? 'images/peces/mojarra_roja.jpg';
    }

    public function setImagenUrlAttribute($value): void
    {
        $this->attributes['foto_url'] = $value;
    }

    /**
     * Retorna la URL absoluta de la foto o una imagen representativa local por defecto.
     */
    public function getFotoAttribute(): string
    {
        if (! empty($this->foto_url)) {
            return asset($this->foto_url);
        }

        return asset('images/peces/mojarra_roja.jpg');
    }

    /**
     * Rango de temperatura formateado.
     */
    public function getRangoTemperaturaAttribute(): string
    {
        return "{$this->temperatura_min} - {$this->temperatura_max} °C";
    }

    /**
     * Rango de pH formateado.
     */
    public function getRangoPhAttribute(): string
    {
        return "{$this->ph_min} - {$this->ph_max}";
    }
}
