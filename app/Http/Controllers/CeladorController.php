<?php

namespace App\Http\Controllers;

use App\Models\BitacoraNocturna;
use App\Models\ControlAireador;
use App\Models\Estanque;
use App\Models\FishCredit;
use App\Models\Pond;
use App\Models\TurnoNocturno;
use App\Models\User;
use App\Notifications\AlertaBoqueoCriticaNotification;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class CeladorController extends Controller
{
    /**
     * Panel Principal del Celador Nocturno (Vista Móvil en Modo Oscuro).
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $today = now()->toDateString();
        $fincaId = $user->finca_id ?? 1;

        $estanques = Estanque::where('finca_id', $fincaId)
            ->with(['especiePrincipal', 'aireadorActivo'])
            ->orderBy('id', 'asc')
            ->get();

        // Rondas nocturnas de hoy / anoche
        $rondasRecientes = BitacoraNocturna::query()
            ->with(['estanque:id,name,code', 'user:id,name'])
            ->whereDate('fecha', '>=', now()->subDay()->toDateString())
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        // Aireadores actualmente encendidos
        $aireadoresActivos = ControlAireador::query()
            ->with(['estanque:id,name,code', 'user:id,name'])
            ->activos()
            ->get();

        // Turno nocturno activo del celador
        $turnoActivo = TurnoNocturno::query()
            ->where('user_id', $user->id)
            ->where('estado', 'en_turno')
            ->latest('id')
            ->first();

        // Total de pescado fiado de este usuario
        $pescadoFiadoPendiente = FishCredit::query()
            ->where('user_id', $user->id)
            ->where('status', FishCredit::STATUS_PENDIENTE)
            ->sum('kilos');

        $data = [
            'user' => $user,
            'estanques' => $estanques,
            'rondas_recientes' => $rondasRecientes,
            'aireadores_activos' => $aireadoresActivos,
            'turno_activo' => $turnoActivo,
            'pescado_fiado_kilos' => round((float) $pescadoFiadoPendiente, 2),
            'pescado_fiado_total' => round((float) $pescadoFiadoPendiente * 7000.0, 2),
            'checkpoints' => ['10:00 PM', '01:00 AM', '03:30 AM', '05:00 AM'],
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('celador.index', $data);
    }

    /**
     * 1. Registro de Rondas Nocturnas con Checkpoints horarios y revisión de monjes/mallas.
     */
    public function storeRonda(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hora_ronda' => ['required', 'string', 'max:25'],
            'estanque_id' => ['nullable', 'exists:ponds,id'],
            'estado' => ['required', 'in:normal,anomalia,fuga_monje,depredador'],
            'nivel_agua_monje' => ['nullable', 'in:optimo,bajo,rebose,fuga'],
            'estado_mallas' => ['nullable', 'in:bueno,danada,ajustada'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $ronda = BitacoraNocturna::create([
            'user_id' => $request->user()->id,
            'fecha' => now()->toDateString(),
            'hora_ronda' => $validated['hora_ronda'],
            'estanque_id' => $validated['estanque_id'] ?? null,
            'estado' => $validated['estado'],
            'nivel_agua_monje' => $validated['nivel_agua_monje'] ?? 'optimo',
            'estado_mallas' => $validated['estado_mallas'] ?? 'bueno',
            'observaciones' => $validated['observaciones'] ?? null,
        ]);

        \App\Models\ActividadTrabajador::registrar(
            $request->user(),
            \App\Models\ActividadTrabajador::ACCION_RONDA_NOCTURNA,
            "Ronda nocturna de las {$ronda->hora_ronda}. Estado: {$ronda->estado}, Monje: {$ronda->nivel_agua_monje}, Mallas: {$ronda->estado_mallas}.",
            $ronda->estanque_id
        );

        return response()->json([
            'message' => "Ronda de las {$ronda->hora_ronda} registrada exitosamente.",
            'alerta_anomalia' => $ronda->estado !== BitacoraNocturna::ESTADO_NORMAL,
            'data' => $ronda->load('estanque:id,name', 'user:id,name'),
        ], 201);
    }

    /**
     * 2. Control de Aireadores: Encender aireador en estanque con fuente de energía y corte de luz.
     */
    public function encenderAireador(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'estanque_id' => ['required', 'exists:ponds,id'],
            'fuente_energia' => ['nullable', 'in:red_electrica,planta_emergencia'],
            'corte_luz' => ['nullable', 'boolean'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $estanque = Pond::findOrFail($validated['estanque_id']);

        // Verificar si ya existe un aireador encendido en este estanque
        $existente = ControlAireador::where('estanque_id', $estanque->id)
            ->activos()
            ->first();

        if ($existente) {
            return response()->json([
                'message' => "El aireador de {$estanque->name} ya se encuentra encendido desde las {$existente->hora_encendido->format('g:i A')}.",
                'data' => $existente,
            ], 200);
        }

        $corteLuz = $validated['corte_luz'] ?? (($validated['fuente_energia'] ?? '') === ControlAireador::FUENTE_PLANTA_EMERGENCIA);

        $aireador = ControlAireador::create([
            'estanque_id' => $estanque->id,
            'user_id' => $request->user()->id,
            'fecha' => now()->toDateString(),
            'hora_encendido' => now(),
            'fuente_energia' => $validated['fuente_energia'] ?? ControlAireador::FUENTE_RED_ELECTRICA,
            'corte_luz' => $corteLuz,
            'observaciones' => $validated['observaciones'] ?? null,
        ]);

        return response()->json([
            'message' => "Aireador de {$estanque->name} ENCENDIDO exitosamente con {$aireador->fuente_energia}.",
            'data' => $aireador->load('estanque:id,name', 'user:id,name'),
        ], 201);
    }

    /**
     * 2. Control de Aireadores: Apagar aireador y calcular automáticamente horas de trabajo.
     */
    public function apagarAireador(Request $request, ControlAireador $controlAireador): JsonResponse
    {
        $validated = $request->validate([
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $controlAireador->apagar(now(), $validated['observaciones'] ?? null);

        return response()->json([
            'message' => "Aireador apagado. Tiempo total de operación: {$controlAireador->total_horas} horas.",
            'total_horas' => $controlAireador->total_horas,
            'data' => $controlAireador->fresh(['estanque:id,name', 'user:id,name']),
        ]);
    }

    /**
     * 3. Botón de Pánico / Alerta Crítica de Boqueo por Falta de Oxígeno.
     * En un solo toque cambia el estado del estanque a 'alerta_critica' y notifica a Administrador y Jefe Mayor.
     */
    public function alertaBoqueo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'estanque_id' => ['required', 'exists:ponds,id'],
            'oxigeno_mg_l' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $estanque = Pond::findOrFail($validated['estanque_id']);
        $oxigeno = isset($validated['oxigeno_mg_l']) ? (float) $validated['oxigeno_mg_l'] : null;

        // 1. Cambiar estanque a alerta crítica
        $estanque->update([
            'status' => 'alerta_critica',
        ]);

        // 2. Encender automáticamente el aireador si estaba apagado
        $aireadorActivo = ControlAireador::where('estanque_id', $estanque->id)->activos()->first();
        if (! $aireadorActivo) {
            ControlAireador::create([
                'estanque_id' => $estanque->id,
                'user_id' => $user->id,
                'fecha' => now()->toDateString(),
                'hora_encendido' => now(),
                'fuente_energia' => ControlAireador::FUENTE_PLANTA_EMERGENCIA,
                'corte_luz' => false,
                'observaciones' => 'Encendido automático por activación de BOTÓN DE PÁNICO (Boqueo)',
            ]);
        }

        // 3. Registrar en bitácora nocturna la anomalía crítica
        BitacoraNocturna::create([
            'user_id' => $user->id,
            'fecha' => now()->toDateString(),
            'hora_ronda' => now()->timezone('America/Bogota')->format('g:i A'),
            'estanque_id' => $estanque->id,
            'estado' => BitacoraNocturna::ESTADO_ANOMALIA,
            'observaciones' => 'BOTÓN DE PÁNICO ACTIVADO: Boqueo crítico de peces por asfixia/déficit de oxígeno. '.($validated['observaciones'] ?? ''),
        ]);

        // 4. Notificar con máxima prioridad a todos los administradores y jefes mayores
        $destinatarios = User::whereIn('role', [
            User::ROLE_ADMIN,
            User::ROLE_ADMINISTRADOR,
            User::ROLE_JEFE_MAYOR,
            User::ROLE_JEFE_FINCA,
            User::ROLE_OWNER,
        ])->get();

        if ($destinatarios->isNotEmpty()) {
            Notification::send($destinatarios, new AlertaBoqueoCriticaNotification(
                $estanque,
                $user,
                $validated['observaciones'] ?? null,
                $oxigeno
            ));

            // Notificación prioritaria por WhatsApp al Administrador y Jefe Mayor
            app(WhatsAppNotificationService::class)->sendCriticalBoqueoAlert(
                $estanque,
                $oxigeno,
                $destinatarios
            );
        }

        return response()->json([
            'status' => 'alerta_critica_activada',
            'message' => "¡ALERTA DE BOQUEO TRANSMITIDA! Se notificó a {$destinatarios->count()} administradores y jefes por Sistema y WhatsApp. Aireadores activados de emergencia.",
            'estanque' => $estanque->name,
            'notificados_count' => $destinatarios->count(),
            'whatsapp_notificado' => true,
        ], 200);
    }

    /**
     * 4. Registro de Entrada a Turno Nocturno.
     */
    public function registrarEntrada(Request $request): JsonResponse
    {
        $user = $request->user();

        $turno = TurnoNocturno::create([
            'user_id' => $user->id,
            'fecha' => now()->toDateString(),
            'hora_entrada' => now(),
            'estado' => 'en_turno',
            'observaciones' => $request->input('observaciones', 'Entrada a guardia nocturna'),
        ]);

        return response()->json([
            'message' => 'Entrada a guardia nocturna registrada exitosamente a las '.now()->timezone('America/Bogota')->format('g:i A'),
            'data' => $turno,
        ], 201);
    }

    /**
     * 4. Registro de Salida de Turno Nocturno con Descuento de Pescado ($7.000/kg).
     */
    public function registrarSalida(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pescado_kilos_llevados' => ['nullable', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $kilos = isset($validated['pescado_kilos_llevados']) ? (float) $validated['pescado_kilos_llevados'] : 0.0;
        $descuento = round($kilos * TurnoNocturno::PRECIO_KG_PESCADO, 2);

        $turno = TurnoNocturno::where('user_id', $user->id)
            ->where('estado', 'en_turno')
            ->latest('id')
            ->first();

        if ($turno) {
            $turno->update([
                'hora_salida' => now(),
                'pescado_kilos_llevados' => $kilos,
                'descuento_pescado' => $descuento,
                'estado' => 'finalizado',
                'observaciones' => $validated['observaciones'] ?? $turno->observaciones,
            ]);
        } else {
            $turno = TurnoNocturno::create([
                'user_id' => $user->id,
                'fecha' => now()->toDateString(),
                'hora_entrada' => now()->subHours(8),
                'hora_salida' => now(),
                'pescado_kilos_llevados' => $kilos,
                'descuento_pescado' => $descuento,
                'estado' => 'finalizado',
                'observaciones' => $validated['observaciones'] ?? 'Salida de guardia',
            ]);
        }

        // Si el celador lleva pescado, registrar en fish_credits para aplicar el descuento correspondiente
        if ($kilos > 0) {
            FishCredit::create([
                'user_id' => $user->id,
                'credit_date' => now()->toDateString(),
                'kilos' => $kilos,
                'price_per_kg' => TurnoNocturno::PRECIO_KG_PESCADO,
                'total_amount' => $descuento,
                'status' => FishCredit::STATUS_PENDIENTE,
                'registered_by_user_id' => $user->id,
                'notes' => "Pescado retirado por celador en salida de turno nocturno ({$kilos} kg)",
            ]);
        }

        return response()->json([
            'message' => "Salida registrada exitosamente. Pescado retirado: {$kilos} kg (Descuento: $ {$descuento} COP).",
            'kilos_pescado' => $kilos,
            'descuento_pescado' => $descuento,
            'data' => $turno,
        ]);
    }
}
