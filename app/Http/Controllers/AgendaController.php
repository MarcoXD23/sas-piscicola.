<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\EventoAgenda;
use App\Models\Pond;
use App\Models\User;
use App\Notifications\CalendarEventNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class AgendaController extends Controller
{
    /**
     * Muestra la vista principal de la agenda del Jefe de Finca.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->wantsJson()) {
            return $this->events($request);
        }

        $ponds = Pond::all(['id', 'name']);

        return view('calendar.index', compact('ponds'));
    }

    /**
     * Listado de eventos de la agenda (JSON).
     */
    public function events(Request $request): JsonResponse
    {
        $user = $request->user();
        $fincaId = $user->finca_id ?? 1;

        $events = EventoAgenda::query()
            ->where('finca_id', $fincaId)
            ->with(['pond:id,name', 'createdBy:id,name,role'])
            ->orderBy('event_date', 'asc')
            ->orderBy('event_time', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events,
            'total' => $events->count(),
        ]);
    }

    /**
     * Guardar un nuevo evento de agenda en la base de datos.
     */
    public function store(Request $request): JsonResponse
    {
        // Normalizar nombres de campos español/inglés
        if (! $request->has('title') && $request->has('titulo')) {
            $request->merge(['title' => $request->input('titulo')]);
        }
        if (! $request->has('event_type') && $request->has('tipo_evento')) {
            $request->merge(['event_type' => $request->input('tipo_evento')]);
        }
        if (! $request->has('event_date') && $request->has('fecha_programada')) {
            $request->merge(['event_date' => $request->input('fecha_programada')]);
        }
        if (! $request->has('event_time') && $request->has('hora_programada')) {
            $request->merge(['event_time' => $request->input('hora_programada')]);
        }
        if (! $request->has('pond_id') && $request->has('lago_id')) {
            $request->merge(['pond_id' => $request->input('lago_id')]);
        }
        if (! $request->has('estimated_kg') && $request->has('kilos_estimados')) {
            $request->merge(['estimated_kg' => $request->input('kilos_estimados')]);
        }
        if (! $request->has('notes') && $request->has('notas')) {
            $request->merge(['notes' => $request->input('notas')]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'event_type' => ['required', 'string'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'string', 'max:20'],
            'pond_id' => ['nullable', 'exists:ponds,id'],
            'estimated_kg' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'fingerlings_quantity' => ['nullable', 'integer'],
            'stage' => ['nullable', 'string', 'max:100'],
            'feed_type' => ['nullable', 'string', 'max:150'],
            'feed_bags_count' => ['nullable', 'integer'],
            'feed_weight_kg' => ['nullable', 'numeric'],
            'inspection_notes' => ['nullable', 'string'],
            'client_uuid' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();

        $evento = new EventoAgenda;
        $evento->finca_id = $user->finca_id ?? 1;
        $evento->created_by_user_id = $user->id;
        $evento->user_id = $user->id;
        $evento->title = $validated['title'];
        $evento->event_type = $validated['event_type'];
        $evento->event_date = $validated['event_date'];
        $evento->event_time = $validated['event_time'] ?? null;
        $evento->status = CalendarEvent::STATUS_PROGRAMADO;
        $evento->pond_id = $validated['pond_id'] ?? null;
        $evento->estimated_kg = $validated['estimated_kg'] ?? null;
        $evento->fingerlings_quantity = $validated['fingerlings_quantity'] ?? null;
        $evento->stage = $validated['stage'] ?? null;
        $evento->feed_type = $validated['feed_type'] ?? null;
        $evento->feed_bags_count = $validated['feed_bags_count'] ?? null;
        $evento->feed_weight_kg = $validated['feed_weight_kg'] ?? null;
        $evento->inspection_notes = $validated['inspection_notes'] ?? null;
        $evento->notes = $validated['notes'] ?? null;
        $evento->client_uuid = $validated['client_uuid'] ?? null;
        $evento->synced_at = now();
        $evento->save();

        $evento->load(['pond:id,name', 'createdBy:id,name,role']);

        // Notificar a los administradores de la finca si existen
        try {
            $admins = User::where('role', User::ROLE_ADMIN)
                ->where('finca_id', $evento->finca_id)
                ->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new CalendarEventNotification($evento, 'creado'));
            }
        } catch (\Throwable) {
            // Ignorar fallo de notificación para no bloquear guardado
        }

        return response()->json([
            'success' => true,
            'evento' => $evento,
            'data' => $evento,
            'message' => 'Evento programado exitosamente',
        ], 200);
    }

    /**
     * Sincronización masiva de eventos guardados offline.
     */
    public function sync(Request $request): JsonResponse
    {
        $user = $request->user();
        $fincaId = $user->finca_id ?? 1;
        $eventsData = $request->input('events', []);

        $synced = [];

        DB::transaction(function () use ($eventsData, $user, $fincaId, &$synced) {
            foreach ($eventsData as $item) {
                $title = $item['title'] ?? $item['titulo'] ?? 'Evento Programado';
                $eventType = $item['event_type'] ?? $item['tipo_evento'] ?? 'visita_general';
                $eventDate = $item['event_date'] ?? $item['fecha_programada'] ?? now()->toDateString();
                $pondId = $item['pond_id'] ?? $item['lago_id'] ?? null;
                $clientUuid = $item['client_uuid'] ?? null;

                $event = EventoAgenda::updateOrCreate(
                    [
                        'client_uuid' => $clientUuid ?: ($item['id'] ?? uniqid('evt_', true)),
                    ],
                    [
                        'finca_id' => $fincaId,
                        'created_by_user_id' => $user->id,
                        'title' => $title,
                        'event_type' => $eventType,
                        'event_date' => $eventDate,
                        'event_time' => $item['event_time'] ?? $item['hora_programada'] ?? null,
                        'status' => CalendarEvent::STATUS_PROGRAMADO,
                        'pond_id' => $pondId,
                        'estimated_kg' => $item['estimated_kg'] ?? $item['kilos_estimados'] ?? null,
                        'fingerlings_quantity' => $item['fingerlings_quantity'] ?? null,
                        'stage' => $item['stage'] ?? null,
                        'feed_type' => $item['feed_type'] ?? null,
                        'feed_bags_count' => $item['feed_bags_count'] ?? null,
                        'feed_weight_kg' => $item['feed_weight_kg'] ?? null,
                        'inspection_notes' => $item['inspection_notes'] ?? null,
                        'notes' => $item['notes'] ?? $item['notas'] ?? null,
                        'synced_at' => now(),
                    ]
                );

                $synced[] = $event;
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Sincronización completada exitosamente',
            'eventos_sincronizados' => count($synced),
            'synced_count' => count($synced),
            'data' => $synced,
        ]);
    }

    /**
     * Eliminar evento de agenda.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $event = EventoAgenda::findOrFail($id);
        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Evento eliminado exitosamente',
        ]);
    }
}
