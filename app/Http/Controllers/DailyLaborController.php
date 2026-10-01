<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyLaborRequest;
use App\Models\DailyLabor;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DailyLaborController extends Controller
{
    /**
     * Libreta de Jornales: Listado de asistencia y labores del personal de apoyo.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DailyLabor::query()->with([
            'pond:id,name',
            'registeredBy:id,name,role',
            'payrollSettlement:id,cutoff_date,status',
        ]);

        if ($request->filled('date')) {
            $query->whereDate('work_date', $request->date);
        }

        if ($request->filled('worker_name')) {
            $query->where('worker_name', 'like', '%'.$request->worker_name.'%');
        }

        if ($request->filled('labor_type')) {
            $query->where('labor_type', $request->labor_type);
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $labors = $query->orderBy('work_date', 'desc')->orderBy('id', 'desc')->get();

        return response()->json([
            'message' => 'Libreta de jornales recuperada exitosamente.',
            'total_jornales' => $labors->count(),
            'total_monto' => round($labors->sum('daily_wage'), 2),
            'data' => $labors,
        ]);
    }

    /**
     * Registrar la asistencia y labor diaria de un trabajador de apoyo
     * (rayadores, lavado de estanques, pesca, empaque, etc.).
     */
    public function store(StoreDailyLaborRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $employmentType = $validated['employment_type'] ?? null;
        if (! $employmentType && ! empty($validated['user_id'])) {
            $linkedUser = User::find($validated['user_id']);
            if ($linkedUser) {
                $employmentType = $linkedUser->employment_type ?? DailyLabor::TYPE_FIJO;
            }
        }
        $employmentType = $employmentType ?? DailyLabor::TYPE_TEMPORAL;

        $labor = DailyLabor::create([
            'worker_name' => $validated['worker_name'],
            'worker_id_card' => $validated['worker_id_card'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'employment_type' => $employmentType,
            'pond_id' => $validated['pond_id'] ?? null,
            'work_date' => $validated['work_date'] ?? now()->toDateString(),
            'labor_type' => $validated['labor_type'],
            'daily_wage' => $validated['daily_wage'],
            'hours_worked' => $validated['hours_worked'] ?? 8.0,
            'payment_status' => DailyLabor::STATUS_PENDIENTE,
            'registered_by_user_id' => $user->id,
            'observations' => $validated['observations'] ?? null,
        ]);

        $labor->load(['pond:id,name', 'registeredBy:id,name']);

        return response()->json([
            'message' => 'Jornal de apoyo registrado exitosamente en la libreta.',
            'data' => $labor,
        ], 201);
    }

    /**
     * Eliminar un jornal no liquidado.
     */
    public function destroy(DailyLabor $dailyLabor): JsonResponse
    {
        if ($dailyLabor->payment_status === DailyLabor::STATUS_LIQUIDADO) {
            return response()->json([
                'message' => 'No se puede eliminar un jornal que ya ha sido liquidado en nómina semanal.',
            ], 422);
        }

        $dailyLabor->delete();

        return response()->json([
            'message' => 'Jornal eliminado de la libreta exitosamente.',
        ]);
    }
}
