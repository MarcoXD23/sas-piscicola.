<?php

namespace App\Http\Controllers;

use App\Models\AgendaTurno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgendaTurnoController extends Controller
{
    /**
     * Listado de turnos de la agenda operativa (JSON).
     */
    public function index(Request $request): JsonResponse
    {
        $fincaId = $request->user()?->finca_id ?? 1;
        $desde = $request->input('desde', now()->startOfWeek()->toDateString());
        $hasta = $request->input('hasta', now()->addWeeks(2)->endOfWeek()->toDateString());

        $turnos = AgendaTurno::where('finca_id', $fincaId)
            ->whereBetween('fecha', [$desde, $hasta])
            ->with(['user:id,name,role,document_number'])
            ->orderBy('fecha', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'total' => $turnos->count(),
            'data' => $turnos,
        ]);
    }

    /**
     * Programa el turno semanal con encadenamiento automático:
     * - Lunes a Viernes: Alimentador
     * - Domingo inmediatamente anterior: Celador (seguridad_noche)
     */
    public function storeSemanal(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'fecha_lunes' => ['required', 'date'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ], [
            'user_id.required' => 'Debes seleccionar el operario a programar.',
            'fecha_lunes.required' => 'La fecha del lunes de inicio es requerida.',
        ]);

        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;
        $trabajador = User::findOrFail($validated['user_id']);
        $lunes = Carbon::parse($validated['fecha_lunes'])->startOfWeek();
        $domingoAnterior = $lunes->copy()->subDay();

        $turnos = AgendaTurno::programarSemanaCompleta(
            $fincaId,
            $trabajador->id,
            $lunes,
            $validated['observaciones'] ?? null
        );

        $mensaje = "Turno semanal programado para {$trabajador->name}. Se asignó automáticamente Celador Nocturno para el domingo anterior ({$domingoAnterior->format('d/m/Y')}) y Alimentador de Lunes a Viernes ({$lunes->format('d/m/Y')} al {$lunes->copy()->addDays(4)->format('d/m/Y')}).";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'turnos_creados' => count($turnos),
                'data' => $turnos,
            ], 201);
        }

        return redirect()->back()->with('status', $mensaje);
    }

    /**
     * Programa turnos independientes para fines de semana (Sábados y Domingos).
     */
    public function storeFinDeSemana(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'fecha' => ['required', 'date'],
            'rol_asignado' => ['required', 'in:alimentador,seguridad_noche'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ], [
            'user_id.required' => 'Debes seleccionar al trabajador.',
            'fecha.required' => 'La fecha del turno es obligatoria.',
            'rol_asignado.required' => 'Debes indicar el rol asignado para el turno.',
        ]);

        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;
        $trabajador = User::findOrFail($validated['user_id']);
        $fecha = Carbon::parse($validated['fecha']);

        $turno = AgendaTurno::programarFinDeSemana(
            $fincaId,
            $trabajador->id,
            $fecha,
            $validated['rol_asignado'],
            $validated['observaciones'] ?? null
        );

        $rolLabel = $turno->rol_asignado === AgendaTurno::ROL_ALIMENTADOR
            ? 'Alimentador diurno'
            : 'Celador (Seguridad & Noche)';

        $mensaje = "Turno de fin de semana ({$fecha->format('d/m/Y')}) asignado a {$trabajador->name} como {$rolLabel}.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => $turno->load('user:id,name'),
            ], 201);
        }

        return redirect()->back()->with('status', $mensaje);
    }
}
