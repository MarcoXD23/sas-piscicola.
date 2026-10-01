<?php

namespace App\Http\Middleware;

use App\Models\AgendaTurno;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarTurnoDiarioAlimentador
{
    /**
     * Valida que el operario tenga turno asignado de alimentación para hoy en la Agenda Operativa.
     * Acceso libre para Jefe Mayor y Técnico Acuícola.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // 1. Acceso libre sin restricciones para Propietario y Técnico Acuícola / Administrador
        if ($user->isPropietario() || $user->isTecnicoAcuicola() || $user->isOwner() || $user->isAdmin()) {
            return $next($request);
        }

        $hoy = now()->toDateString();

        $mensaje = 'Acceso restringido: Hoy no tienes turno asignado para alimentar. Consulta la Agenda Operativa.';

        // 2. Si hoy es domingo y el trabajador tiene turno de guardia nocturna:
        $turnoGuardiaHoy = AgendaTurno::where('user_id', $user->id)
            ->whereDate('fecha', $hoy)
            ->where('rol_asignado', AgendaTurno::ROL_SEGURIDAD_NOCHE)
            ->whereIn('estado', [AgendaTurno::ESTADO_ACTIVO, AgendaTurno::ESTADO_PROGRAMADO, 'activo', 'programado'])
            ->first();

        if ($turnoGuardiaHoy && now()->isSunday()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $mensaje, 'error' => 'TURNO_NOCTURNO_ACTIVO'], 403);
            }

            return redirect()->route('celador.dashboard')->with('error', $mensaje);
        }

        // 3. Verificar si tiene turno activo como alimentador para hoy en agenda_turnos
        $turnoAlimentador = AgendaTurno::where('user_id', $user->id)
            ->whereDate('fecha', $hoy)
            ->where('rol_asignado', AgendaTurno::ROL_ALIMENTADOR)
            ->whereIn('estado', [AgendaTurno::ESTADO_ACTIVO, AgendaTurno::ESTADO_PROGRAMADO, 'activo', 'programado'])
            ->first();

        if (! $turnoAlimentador) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $mensaje, 'error' => 'SIN_TURNO_ALIMENTACION'], 403);
            }

            return redirect()->route('trabajador.dashboard')->with('error', $mensaje);
        }

        return $next($request);
    }
}
