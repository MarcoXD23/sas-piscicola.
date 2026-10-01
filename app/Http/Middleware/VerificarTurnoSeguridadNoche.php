<?php

namespace App\Http\Middleware;

use App\Models\AgendaTurno;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarTurnoSeguridadNoche
{
    /**
     * Valida el acceso al módulo de Seguridad & Noche:
     * - Acceso libre para Jefe Mayor y Técnico Acuícola
     * - Celador nocturno fijo
     * - O trabajadores con turno activo de 'seguridad_noche' asignado en la agenda para hoy.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // 1. Acceso libre para Propietario, Dueño, Administrador y Técnico Acuícola
        if ($user->isPropietario() || $user->isTecnicoAcuicola() || $user->isOwner() || $user->isAdmin()) {
            return $next($request);
        }

        // 2. Celadores con rol fijo
        if ($user->role === User::ROLE_CELADOR_NOCTURNO || $user->role === User::ROLE_CELADOR || $user->role === User::ROLE_GUARD) {
            return $next($request);
        }

        // 3. Verificar turno asignado en la Agenda Operativa para hoy
        $hoy = now()->toDateString();
        $turnoSeguridadHoy = AgendaTurno::where('user_id', $user->id)
            ->whereDate('fecha', $hoy)
            ->where('rol_asignado', AgendaTurno::ROL_SEGURIDAD_NOCHE)
            ->whereIn('estado', [AgendaTurno::ESTADO_ACTIVO, AgendaTurno::ESTADO_PROGRAMADO, 'activo', 'programado'])
            ->first();

        if ($turnoSeguridadHoy) {
            return $next($request);
        }

        $mensaje = 'Acceso restringido: Hoy no tienes asignado turno de Celador (Seguridad & Noche). Consulta la Agenda Operativa.';
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $mensaje, 'error' => 'SIN_TURNO_SEGURIDAD'], 403);
        }

        return redirect()->route('trabajador.dashboard')->with('error', $mensaje);
    }
}
