<?php

namespace App\Models;

/**
 * Modelo Mortalidad (Alias de RegistroMortalidad).
 * Representa los reportes de bajas matutinas o mortandad en los estanques.
 */
class Mortalidad extends RegistroMortalidad
{
    protected $table = 'registros_mortalidad';
}
