<?php

namespace App\Http\Controllers;

use App\Models\AdminTask;
use App\Models\LeaveRequest;
use App\Models\OvertimeRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunicationController extends Controller
{
    // ==========================================
    // 1. Tareas Asignadas por el Administrador
    // ==========================================

    public function tasksIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = AdminTask::query()->with(['assignedTo:id,name,role', 'createdBy:id,name,role']);

        if ($user->isTrabajador()) {
            $query->where('assigned_to_user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tasks = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Tablero de tareas del Administrador recuperado.',
            'total' => $tasks->count(),
            'pendientes_count' => $tasks->where('status', AdminTask::STATUS_PENDIENTE)->count(),
            'data' => $tasks,
        ]);
    }

    public function storeTask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'assigned_to_user_id' => ['required', 'exists:users,id'],
            'priority' => ['nullable', 'in:baja,media,alta,urgente'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $task = AdminTask::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'assigned_to_user_id' => $validated['assigned_to_user_id'],
            'created_by_user_id' => $request->user()->id,
            'priority' => $validated['priority'] ?? AdminTask::PRIORITY_MEDIA,
            'status' => AdminTask::STATUS_PENDIENTE,
            'due_date' => $validated['due_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Tarea asignada exitosamente para la guardia/ausencia del Administrador.',
            'data' => $task->load('assignedTo:id,name', 'createdBy:id,name'),
        ], 201);
    }

    public function completeTask(Request $request, AdminTask $adminTask): JsonResponse
    {
        $adminTask->markCompleted($request->input('notes'));

        return response()->json([
            'message' => 'Tarea marcada como completada.',
            'data' => $adminTask,
        ]);
    }

    // ==========================================
    // 2. Solicitudes de Permisos Laborales
    // ==========================================

    public function leaveRequestsIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = LeaveRequest::query()->with(['user:id,name,role,employment_type', 'reviewedBy:id,name']);

        if ($user->isTrabajador()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Solicitudes de permisos laborales recuperadas.',
            'total' => $requests->count(),
            'pendientes_count' => $requests->where('status', LeaveRequest::STATUS_PENDIENTE)->count(),
            'data' => $requests,
        ]);
    }

    public function storeLeaveRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $leave = LeaveRequest::create([
            'user_id' => $request->user()->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'status' => LeaveRequest::STATUS_PENDIENTE,
        ]);

        return response()->json([
            'message' => 'Solicitud de permiso enviada. Queda en estado pendiente de aprobación.',
            'data' => $leave->load('user:id,name'),
        ], 201);
    }

    public function reviewLeaveRequest(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:aprobar,rechazar'],
            'response_notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['decision'] === 'aprobar') {
            $leaveRequest->approve($request->user(), $validated['response_notes'] ?? null);
        } else {
            $leaveRequest->reject($request->user(), $validated['response_notes'] ?? null);
        }

        return response()->json([
            'message' => "Solicitud de permiso laboral {$leaveRequest->status} exitosamente.",
            'data' => $leaveRequest->load('user:id,name', 'reviewedBy:id,name'),
        ]);
    }

    // ==========================================
    // 3. Registro de Horas Extras
    // ==========================================

    public function overtimeIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = OvertimeRecord::query()->with(['user:id,name,role', 'approvedBy:id,name']);

        if ($user->isTrabajador()) {
            $query->where('user_id', $user->id);
        }

        $records = $query->orderBy('record_date', 'desc')->get();

        return response()->json([
            'message' => 'Registro de horas extras recuperado.',
            'total_horas' => round($records->sum('hours'), 2),
            'data' => $records,
        ]);
    }

    public function storeOvertime(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'record_date' => ['nullable', 'date'],
            'hours' => ['required', 'numeric', 'min:0.5', 'max:16'],
            'occasion' => ['required', 'string', 'max:191'],
            'justification' => ['required', 'string', 'max:1000'],
        ]);

        $targetUserId = $validated['user_id'] ?? $request->user()->id;

        $overtime = OvertimeRecord::create([
            'user_id' => $targetUserId,
            'record_date' => $validated['record_date'] ?? now()->toDateString(),
            'hours' => $validated['hours'],
            'occasion' => $validated['occasion'],
            'justification' => $validated['justification'],
            'status' => OvertimeRecord::STATUS_PENDIENTE,
        ]);

        return response()->json([
            'message' => 'Registro de horas extras guardado exitosamente con su respectiva justificación.',
            'data' => $overtime->load('user:id,name'),
        ], 201);
    }
}
