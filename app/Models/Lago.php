<?php

namespace App\Models;

/**
 * Modelo Lago (Alias de Estanque / Pond).
 * Representa los lagos y estanques de cultivo acuícola de la finca.
 */
class Lago extends Estanque
{
    protected $table = 'ponds';
}
