<?php

namespace App\Http\Controllers;

use App\Models\AgendaTurno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgendaOperativaController extends Controller
{
    /**
     * Listado de turnos programados en la agenda.
     */
    public function index(Request $request): JsonResponse
    {
        $fincaId = $request->user()?->finca_id ?? 1;

        $turnos = AgendaTurno::where('finca_id', $fincaId)
            ->with('user:id,name,role')
            ->orderBy('fecha', 'asc')
            ->get();

        return response()->json([
            'message' => 'Turnos operativos de la agenda recuperados exitosamente.',
            'turnos' => $turnos,
        ]);
    }

    /**
     * Programar una semana completa de alimentación (Lunes a Viernes) para un operario.
     * Regla de Negocio: Encadena automáticamente al mismo operario como guardia nocturno
     * (seguridad_noche) para el Domingo inmediatamente anterior.
     */
    public function storeSemanal(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'fecha_lunes' => ['required', 'date'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $fincaId = $request->user()?->finca_id ?? 1;
        $user = User::findOrFail($validated['user_id']);

        $turnos = AgendaTurno::programarSemanaCompleta(
            $fincaId,
            (int) $validated['user_id'],
            Carbon::parse($validated['fecha_lunes']),
            $validated['observaciones'] ?? null
        );

        $msg = "Semana programada con éxito para {$user->name}. Se asignó alimentación de Lunes a Viernes y guardia de seguridad para el Domingo previo.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'turnos_creados' => count($turnos),
                'data' => $turnos,
            ], 201);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Programar turnos independientes de fin de semana (Sábados y Domingos de día).
     */
    public function storeFinDeSemana(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'fecha' => ['required', 'date'],
            'rol_asignado' => ['required', 'in:alimentador,seguridad_noche'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $fincaId = $request->user()?->finca_id ?? 1;
        $user = User::findOrFail($validated['user_id']);

        $turno = AgendaTurno::programarFinDeSemana(
            $fincaId,
            (int) $validated['user_id'],
            Carbon::parse($validated['fecha']),
            $validated['rol_asignado'],
            $validated['observaciones'] ?? null
        );

        $rolLabel = $validated['rol_asignado'] === AgendaTurno::ROL_ALIMENTADOR ? 'Alimentador' : 'Celador Nocturno';
        $msg = "Turno de fin de semana ({$rolLabel}) programado para {$user->name} el {$validated['fecha']}.";

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msg,
                'data' => $turno,
            ], 201);
        }

        return redirect()->back()->with('success', $msg);
    }
}
