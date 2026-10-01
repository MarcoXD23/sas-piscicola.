<?php

namespace App\Models;

/**
 * Modelo HoraExtra (Alias de OvertimeRecord).
 * Representa las horas suplementarias o extras registradas por los trabajadores de campo.
 */
class HoraExtra extends OvertimeRecord
{
    protected $table = 'overtime_records';
}
