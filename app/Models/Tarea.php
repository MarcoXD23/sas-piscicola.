<?php

namespace App\Models;

/**
 * Modelo Tarea (Alias de AdminTask).
 * Representa las actividades e instrucciones asignadas por el Administrador a los trabajadores.
 */
class Tarea extends AdminTask
{
    protected $table = 'admin_tasks';
}
