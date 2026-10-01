<?php

namespace App\Models;

/**
 * Modelo PescadoEmpleado (Alias de FishCredit).
 * Representa los retiros de pescado fiado por los empleados y su saldo descontable.
 */
class PescadoEmpleado extends FishCredit
{
    protected $table = 'fish_credits';
}
