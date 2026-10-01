<?php

namespace App\Models;

/**
 * Modelo Permiso (Alias de LeaveRequest).
 * Representa las solicitudes de permisos y ausencias laborales del trabajador.
 */
class Permiso extends LeaveRequest
{
    protected $table = 'leave_requests';
}
