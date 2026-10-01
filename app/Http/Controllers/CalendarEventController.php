<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalendarEventRequest;
use App\Http\Requests\SyncCalendarEventsRequest;
use App\Http\Requests\UpdateCalendarEventRequest;
use App\Models\AgendaTurno;
use App\Models\CalendarEvent;
use App\Models\Pond;
use App\Models\User;
use App\Notifications\CalendarEventNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CalendarEventController extends Controller
{
    /**
     * Listado de eventos de la agenda operativa del calendario.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CalendarEvent::query()->with([
            'pond:id,name',
            'createdBy:id,name,role',
            'lastModifiedBy:id,name,role',
        ]);

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('event_date', $request->date);
        }

        if ($request->filled('pond_id')) {
            $query->where('pond_id', $request->pond_id);
        }

        if ($request->boolean('upcoming')) {
            $query->upcoming();
        } else {
            $query->orderBy('event_date', 'asc')->orderBy('event_time', 'asc');
        }

        $events = $query->get();

        return response()->json([
            'message' => 'Eventos de calendario recuperados exitosamente.',
            'total_eventos' => $events->count(),
            'data' => $events,
        ]);
    }

    /**
     * Programar y agendar un nuevo evento por el Jefe de Finca.
     * Envía automáticamente una alerta/notificación al panel del Administrador.
     */
    public function store(StoreCalendarEventRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $event = CalendarEvent::create([
            'title' => $validated['title'],
            'event_type' => $validated['event_type'],
            'event_date' => $validated['event_date'],
            'event_time' => $validated['event_time'] ?? null,
            'status' => $validated['status'] ?? CalendarEvent::STATUS_PROGRAMADO,
            'pond_id' => $validated['pond_id'] ?? null,
            'estimated_kg' => $validated['estimated_kg'] ?? null,
            'fingerlings_quantity' => $validated['fingerlings_quantity'] ?? null,
            'stage' => $validated['stage'] ?? null,
            'feed_type' => $validated['feed_type'] ?? null,
            'feed_bags_count' => $validated['feed_bags_count'] ?? null,
            'feed_weight_kg' => $validated['feed_weight_kg'] ?? null,
            'inspection_notes' => $validated['inspection_notes'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by_user_id' => $user->id,
            'client_uuid' => $validated['client_uuid'] ?? null,
            'synced_at' => now(),
        ]);

        $event->load(['pond:id,name', 'createdBy:id,name,role']);

        // Notificar a todos los administradores de la finca
        $notifiedCount = $this->notifyFarmAdmins($event, 'creado');

        return response()->json([
            'message' => 'Evento programado exitosamente en el calendario y alerta enviada a los administradores.',
            'notificados_administradores' => $notifiedCount,
            'data' => $event,
        ], 201);
    }

    /**
     * Detalle de un evento específico.
     */
    public function show(CalendarEvent $calendarEvent): JsonResponse
    {
        $calendarEvent->load(['pond:id,name', 'createdBy:id,name,role', 'lastModifiedBy:id,name,role']);

        return response()->json([
            'data' => $calendarEvent,
        ]);
    }

    /**
     * Modificar un evento de agenda por el Jefe de Finca.
     * Envía automáticamente una alerta de modificación al Administrador.
     */
    public function update(UpdateCalendarEventRequest $request, CalendarEvent $calendarEvent): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $calendarEvent->fill($validated);
        $calendarEvent->last_modified_by_user_id = $user->id;
        $calendarEvent->save();

        $calendarEvent->load(['pond:id,name', 'createdBy:id,name,role', 'lastModifiedBy:id,name,role']);

        // Notificar actualización al panel del Administrador
        $notifiedCount = $this->notifyFarmAdmins($calendarEvent, 'actualizado');

        return response()->json([
            'message' => 'Evento de calendario actualizado exitosamente y notificación enviada al Administrador.',
            'notificados_administradores' => $notifiedCount,
            'data' => $calendarEvent,
        ]);
    }

    /**
     * Eliminar un evento de agenda.
     */
    public function destroy(CalendarEvent $calendarEvent): JsonResponse
    {
        $calendarEvent->delete();

        return response()->json([
            'message' => 'Evento de calendario eliminado exitosamente.',
        ]);
    }

    /**
     * Sincronización en vivo (Offline a Online) para entornos rurales:
     * Recibe los eventos registrados localmente en la granja sin internet
     * y los consolida en la base de datos central sin duplicados.
     */
    public function sync(SyncCalendarEventsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $syncedEvents = [];

        DB::transaction(function () use ($validated, $user, &$syncedEvents) {
            foreach ($validated['events'] as $eventData) {
                $clientUuid = $eventData['client_uuid'];

                $event = CalendarEvent::updateOrCreate(
                    [
                        'client_uuid' => $clientUuid,
                    ],
                    [
                        'title' => $eventData['title'],
                        'event_type' => $eventData['event_type'],
                        'event_date' => $eventData['event_date'],
                        'event_time' => $eventData['event_time'] ?? null,
                        'status' => $eventData['status'] ?? CalendarEvent::STATUS_PROGRAMADO,
                        'pond_id' => $eventData['pond_id'] ?? null,
                        'estimated_kg' => $eventData['estimated_kg'] ?? null,
                        'fingerlings_quantity' => $eventData['fingerlings_quantity'] ?? null,
                        'stage' => $eventData['stage'] ?? null,
                        'feed_type' => $eventData['feed_type'] ?? null,
                        'feed_bags_count' => $eventData['feed_bags_count'] ?? null,
                        'feed_weight_kg' => $eventData['feed_weight_kg'] ?? null,
                        'inspection_notes' => $eventData['inspection_notes'] ?? null,
                        'notes' => $eventData['notes'] ?? null,
                        'created_by_user_id' => $user->id,
                        'synced_at' => now(),
                    ]
                );

                $event->load(['pond:id,name']);
                $this->notifyFarmAdmins($event, 'sincronizado');
                $syncedEvents[] = $event;
            }
        });

        return response()->json([
            'message' => 'Sincronización offline completada exitosamente.',
            'eventos_sincronizados_count' => count($syncedEvents),
            'server_time' => now()->toIso8601String(),
            'data' => $syncedEvents,
        ], 200);
    }

    /**
     * Hora del servidor para reloj en vivo y sincronización temporal bajo zona horaria de Colombia.
     * Estrictamente formato de 12 horas con indicador AM/PM (evitando formato militar / 24 horas).
     */
    public function serverTime(): JsonResponse
    {
        $now = now()->setTimezone('America/Bogota');

        return response()->json([
            'server_time' => $now->toIso8601String(),
            'server_date' => $now->toDateString(),
            'server_time_formatted' => $now->format('g:i:s A'),
            'server_time_12h' => $now->format('g:i A'),
            'period' => $now->format('A'),
            'day_name' => $now->locale('es')->dayName,
            'timezone' => 'America/Bogota',
            'timestamp' => $now->timestamp,
            'status' => 'online',
        ]);
    }

    /**
     * Alertas y Notificaciones directas para el panel del Administrador.
     */
    public function adminAlerts(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->unreadNotifications()
            ->whereIn('type', [
                CalendarEventNotification::class,
                'App\Notifications\CalendarEventNotification',
            ])
            ->take(30)
            ->get();

        return response()->json([
            'total_alertas' => $notifications->count(),
            'alertas' => $notifications,
        ]);
    }

    /**
     * Vista Web interactiva del Calendario y Agenda con reloj en vivo, turnos rotativos y soporte Offline.
     */
    public function agendaView(Request $request): View
    {
        $user = $request->user();
        $fincaId = $user->finca_id ?? 1;
        $ponds = Pond::where('finca_id', $fincaId)->get(['id', 'name']);
        if ($ponds->isEmpty()) {
            $ponds = Pond::all(['id', 'name']);
        }

        $operarios = User::where(function ($query) use ($fincaId) {
            $query->where('finca_id', $fincaId)
                ->orWhereNull('finca_id');
        })
            ->whereIn('role', [
                User::ROLE_OPERARIO_CAMPO,
                'trabajador',
                'worker',
                User::ROLE_CELADOR_NOCTURNO,
                User::ROLE_GUARD,
                'celador',
                User::ROLE_TECNICO_ACUICOLA,
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'document_number']);

        $desde = now()->startOfWeek()->subDay(); // Incluye el domingo inmediatamente anterior
        $hasta = now()->addWeeks(2)->endOfWeek();

        $turnos = AgendaTurno::where('finca_id', $fincaId)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->with('user:id,name,role,document_number')
            ->orderBy('fecha', 'asc')
            ->get();

        $semanaActualLunes = now()->startOfWeek()->toDateString();
        $semanaSiguienteLunes = now()->addWeek()->startOfWeek()->toDateString();

        return view('calendar.index', compact('ponds', 'operarios', 'turnos', 'semanaActualLunes', 'semanaSiguienteLunes'));
    }

    /**
     * Enviar notificación a los administradores de la finca.
     */
    private function notifyFarmAdmins(CalendarEvent $event, string $action): int
    {
        $adminsQuery = User::where('role', User::ROLE_ADMIN);

        if ($event->finca_id) {
            $adminsQuery->where('finca_id', $event->finca_id);
        }

        $admins = $adminsQuery->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new CalendarEventNotification($event, $action));
        }

        return $admins->count();
    }
}
