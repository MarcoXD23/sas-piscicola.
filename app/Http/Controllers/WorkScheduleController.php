<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkScheduleRequest;
use App\Http\Requests\UpdateWorkScheduleRequest;
use App\Models\WorkSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkScheduleController extends Controller
{
    /**
     * Consulta la programación de turnos y trabajadores asignados.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WorkSchedule::query()->with('user:id,name,email,role');

        // Filtro por trabajador
        if ($request->filled('user_id')) {
            $query->forUser((int) $request->user_id);
        }

        // Filtro por fecha específica
        if ($request->filled('schedule_date')) {
            $query->forDate($request->schedule_date);
        }

        // Filtro por rango de fechas (ej: semana completa o mes)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->forDateRange($request->start_date, $request->end_date);
        }

        // Filtro por tipo de turno (fin_de_semana, festivo, bloque_alimentacion)
        if ($request->filled('shift_type')) {
            $query->where('shift_type', $request->shift_type);
        }

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $schedules = $query->orderBy('schedule_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json([
            'message' => 'Programación de turnos recuperada exitosamente.',
            'total_turnos' => $schedules->count(),
            'data' => $schedules,
        ]);
    }

    /**
     * Asigna y crea un nuevo turno para un trabajador (Solo Administrador).
     */
    public function store(StoreWorkScheduleRequest $request): JsonResponse
    {
        $schedule = WorkSchedule::create($request->validated());
        $schedule->load('user:id,name,email,role');

        return response()->json([
            'message' => 'Turno programado y asignado exitosamente.',
            'data' => $schedule,
        ], 201);
    }

    /**
     * Muestra el detalle de un turno específico junto con sus bitácoras de alimentación asociadas.
     */
    public function show(WorkSchedule $workSchedule): JsonResponse
    {
        $workSchedule->load(['user:id,name,email,role', 'feedingLogs.pond:id,name']);

        return response()->json([
            'data' => $workSchedule,
        ]);
    }

    /**
     * Actualiza la asignación o detalles de un turno (Solo Administrador).
     */
    public function update(UpdateWorkScheduleRequest $request, WorkSchedule $workSchedule): JsonResponse
    {
        $workSchedule->update($request->validated());
        $workSchedule->load('user:id,name,email,role');

        return response()->json([
            'message' => 'Turno actualizado exitosamente.',
            'data' => $workSchedule,
        ]);
    }

    /**
     * Elimina un turno programado (Solo Administrador).
     */
    public function destroy(WorkSchedule $workSchedule): JsonResponse
    {
        $workSchedule->delete();

        return response()->json([
            'message' => 'Turno eliminado exitosamente de la programación.',
        ]);
    }

    /**
     * Consulta rápida de quién está de turno en una fecha determinada (ej: hoy o un festivo).
     */
    public function whoIsOnDuty(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());

        $schedules = WorkSchedule::forDate($date)
            ->with('user:id,name,email,role')
            ->get();

        return response()->json([
            'fecha' => $date,
            'total_asignados' => $schedules->count(),
            'trabajadores_de_turno' => $schedules,
        ]);
    }
}
