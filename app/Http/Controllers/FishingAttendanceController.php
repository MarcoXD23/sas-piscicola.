<?php

namespace App\Http\Controllers;

use App\Models\FishingAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FishingAttendanceController extends Controller
{
    /**
     * Listado de asistencias a pesca (lunes o martes festivo).
     */
    public function index(Request $request): JsonResponse
    {
        $query = FishingAttendance::query()
            ->with(['user:id,name,role,employment_type', 'registeredBy:id,name']);

        if ($request->filled('date')) {
            $query->whereDate('attendance_date', $request->date);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')->get();

        return response()->json([
            'message' => 'Registros de asistencia a pesca recuperados.',
            'total' => $attendances->count(),
            'data' => $attendances,
        ]);
    }

    /**
     * Registro de asistencia de trabajadores a la faena de pesca:
     * - Se registra los lunes (o martes si es festivo).
     * - Permite registrar tanto a personal fijo como a destajo semanal.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'attendance_date' => ['nullable', 'date'],
            'attended' => ['nullable', 'boolean'],
            'daily_wage' => ['nullable', 'numeric', 'min:0'],
            'role_in_harvest' => ['nullable', 'string', 'max:100'],
            'is_holiday_catchup' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $date = $validated['attendance_date'] ? Carbon::parse($validated['attendance_date']) : now();
        $user = User::findOrFail($validated['user_id']);

        $dayName = strtolower($date->locale('es')->isoFormat('dddd'));
        $isHoliday = $validated['is_holiday_catchup'] ?? ($date->isTuesday());

        $attendance = FishingAttendance::create([
            'user_id' => $user->id,
            'attendance_date' => $date->toDateString(),
            'attended' => $validated['attended'] ?? true,
            'day_of_week' => $dayName,
            'is_holiday_catchup' => $isHoliday,
            'role_in_harvest' => $validated['role_in_harvest'] ?? 'Faena de Pesca General',
            'daily_wage' => $user->isDestajoSemanal() ? ($validated['daily_wage'] ?? 60000.00) : 0.00,
            'registered_by_user_id' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Asistencia a pesca registrada exitosamente.',
            'tipo_trabajador' => $user->employment_type,
            'impacto_nomina' => $user->isDestajoSemanal()
                ? 'Computará en la liquidación del sábado'
                : 'Control interno de asistencia (excluido de liquidación sabatina)',
            'data' => $attendance->load('user:id,name,employment_type', 'registeredBy:id,name'),
        ], 201);
    }
}
