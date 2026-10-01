<?php

namespace App\Http\Controllers;

use App\Models\AdminTask;
use App\Models\AlimentoBodega;
use App\Models\Estanque;
use App\Models\FeedingLog;
use App\Models\FeedInventory;
use App\Models\Finca;
use App\Models\FishCredit;
use App\Models\LeaveRequest;
use App\Models\MovimientoBodega;
use App\Models\OvertimeRecord;
use App\Models\RegistroMortalidad;
use App\Models\RotativeSchedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TrabajadorController extends Controller
{
    /**
     * Panel Principal y Operativo del Trabajador de Campo.
     * Muestra cuadrícula completa de estanques, raciones de alimentación,
     * reporte de bajas matutinas, tablón de tareas, horas extras y permisos.
     */
    public function dashboard(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $fincaId = $user->finca_id ?? 1;

        // 1. Consulta DINÁMICA de TODOS los estanques de la finca con su especie principal
        $estanques = Estanque::where('finca_id', $fincaId)
            ->with(['especiePrincipal', 'aireadorActivo'])
            ->orderBy('id', 'asc')
            ->get();

        // 2. Turno rotativo de la semana
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        $endOfWeek = Carbon::now()->endOfWeek()->toDateString();

        $currentShift = RotativeSchedule::query()
            ->where('user_id', $user->id)
            ->where('week_start_date', '<=', $endOfWeek)
            ->where('week_end_date', '>=', $startOfWeek)
            ->first();

        // 3. Tablón de tareas del día (asignadas al trabajador o generales de la finca)
        $myTasks = AdminTask::query()
            ->where('finca_id', $fincaId)
            ->where(function ($q) use ($user) {
                $q->where('assigned_to_user_id', $user->id)
                    ->orWhereNull('assigned_to_user_id');
            })
            ->where('status', '!=', AdminTask::STATUS_COMPLETADA)
            ->orderBy('due_date', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        // 4. Saldo y detalle de pescado fiado
        $myFishCredits = FishCredit::query()
            ->where('user_id', $user->id)
            ->orderBy('credit_date', 'desc')
            ->get();

        $totalKilosFiados = round((float) $myFishCredits->sum('kilos'), 2);
        $totalDineroFiado = round((float) $myFishCredits->sum('total_amount'), 2);

        // 5. Mis solicitudes de permiso laboral
        $myLeaves = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // 6. Mis horas extras registradas
        $myOvertime = OvertimeRecord::query()
            ->where('user_id', $user->id)
            ->orderBy('record_date', 'desc')
            ->take(10)
            ->get();

        $totalHorasExtrasAcumuladas = round((float) $myOvertime->sum('hours'), 2);
        $horasExtrasAprobadas = round((float) $myOvertime->where('status', OvertimeRecord::STATUS_APROBADO)->sum('hours'), 2);

        // 7. Bajas matutinas registradas en la granja hoy
        $mortalidadesHoy = RegistroMortalidad::query()
            ->where('finca_id', $fincaId)
            ->whereDate('fecha', now()->toDateString())
            ->with(['estanque:id,name,code', 'user:id,name'])
            ->orderBy('id', 'desc')
            ->get();

        // 8. Inventario de alimentos disponibles
        $feeds = FeedInventory::where('finca_id', $fincaId)->get();

        $data = [
            'user' => $user,
            'rol_usuario' => 'trabajador',
            'estanques' => $estanques,
            'turno_actual' => $currentShift,
            'tipo_contrato' => $user->employment_type,
            'deuda_pescado_fiado' => [
                'total_kilos' => $totalKilosFiados,
                'total_dinero' => $totalDineroFiado > 0 ? $totalDineroFiado : ($totalKilosFiados * 7000),
                'saldo_acumulado_mensual' => (float) ($user->accumulated_fish_credit ?? 0),
                'registros' => $myFishCredits->take(5),
            ],
            'mis_tareas' => $myTasks,
            'mis_permisos' => $myLeaves,
            'mis_horas_extras' => $myOvertime,
            'total_horas_extras' => $totalHorasExtrasAcumuladas,
            'horas_extras_aprobadas' => $horasExtrasAprobadas,
            'mortalidades_hoy' => $mortalidadesHoy,
            'alimentos' => $feeds,
            'alimentos_bodega' => AlimentoBodega::where('finca_id', $fincaId)->get(),
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('dashboards.trabajador', $data);
    }

    /**
     * Vista dedicada de Reporte de Bajas / Mortalidad Matutina.
     */
    public function mortalidad(Request $request): View
    {
        $user = $request->user();
        $fincaId = $user->finca_id ?? 1;

        $estanques = Estanque::where('finca_id', $fincaId)
            ->with('especiePrincipal')
            ->orderBy('name', 'asc')
            ->get();

        $mortalidades = RegistroMortalidad::where('finca_id', $fincaId)
            ->with(['estanque', 'user'])
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('trabajador.mortalidad', [
            'estanques' => $estanques,
            'mortalidades' => $mortalidades,
        ]);
    }

    /**
     * Reporte de Mortalidad Matutina (Bajas recogidas en la mañana).
     * Actualiza automáticamente la población real y recalcula la biomasa del estanque.
     */
    public function reportarMortalidad(Request $request): JsonResponse|RedirectResponse
    {
        // Soporte dual de inputs: estanque_id o lago_id; causa o causa_probable
        if (! $request->has('estanque_id') && $request->has('lago_id')) {
            $request->merge(['estanque_id' => $request->input('lago_id')]);
        }
        if (! $request->has('causa_probable') && $request->has('causa')) {
            $request->merge(['causa_probable' => $request->input('causa')]);
        }

        $validated = $request->validate([
            'estanque_id' => ['required', 'exists:ponds,id'],
            'cantidad_peces' => ['required', 'integer', 'min:1'],
            'causa_probable' => ['nullable', 'string', 'max:191'],
            'metodo_disposicion' => ['nullable', 'string', 'max:191'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $estanque = Estanque::findOrFail($validated['estanque_id']);
        $cantidadBajas = (int) $validated['cantidad_peces'];

        $registro = DB::transaction(function () use ($user, $estanque, $cantidadBajas, $validated) {
            // 1. Reducir población de peces en el estanque
            $nuevaPoblacion = max(0, ((int) $estanque->fish_population) - $cantidadBajas);
            $estanque->fish_population = $nuevaPoblacion;

            // Recalcular biomasa en base al peso promedio actual
            $estanque->biomass = round(($nuevaPoblacion * (float) $estanque->average_weight) / 1000, 2);
            $estanque->save();

            // 2. Registrar en la bitácora oficial de mortalidad
            $mortalidad = RegistroMortalidad::create([
                'finca_id' => $user->finca_id ?? $estanque->finca_id ?? 1,
                'estanque_id' => $estanque->id,
                'user_id' => $user->id,
                'fecha' => now()->toDateString(),
                'cantidad_peces' => $cantidadBajas,
                'causa_probable' => $validated['causa_probable'] ?? 'Mortalidad matutina rutinaria',
                'metodo_disposicion' => $validated['metodo_disposicion'] ?? RegistroMortalidad::METODO_COMPOSTAJE,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            \App\Models\ActividadTrabajador::registrar(
                $user,
                \App\Models\ActividadTrabajador::ACCION_MORTALIDAD,
                "Reportó mortalidad de {$cantidadBajas} peces en estanque {$estanque->name}. Causa: ".($validated['causa_probable'] ?? 'Mortalidad matutina rutinaria'),
                $estanque->id
            );

            return $mortalidad;
        });

        if (! $request->wantsJson()) {
            return redirect()->back()->with('success', 'Reporte de mortalidad registrado correctamente.');
        }

        return response()->json([
            'message' => "Reporte de mortalidad registrado correctamente. Se registraron {$cantidadBajas} bajas en {$estanque->name}. Población actualizada a {$estanque->fish_population} peces.",
            'estanque_id' => $estanque->id,
            'poblacion_actual' => $estanque->fish_population,
            'biomasa_actual_kg' => (float) $estanque->biomass,
            'data' => $registro->load('estanque:id,name,code', 'user:id,name'),
        ], 201);
    }

    /**
     * Alias para storeMortalidad.
     */
    public function storeMortalidad(Request $request): JsonResponse|RedirectResponse
    {
        return $this->reportarMortalidad($request);
    }

    /**
     * Muro de Tareas Asignadas por el Administrador.
     */
    public function tareas(Request $request): View
    {
        $user = $request->user();
        $fincaId = $user->finca_id ?? 1;

        $tareas = AdminTask::query()
            ->where('finca_id', $fincaId)
            ->where(function ($q) use ($user) {
                $q->where('assigned_to_user_id', $user->id)
                    ->orWhereNull('assigned_to_user_id');
            })
            ->with(['createdBy', 'assignedTo'])
            ->orderBy('status', 'asc')
            ->orderBy('due_date', 'asc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('trabajador.tareas', [
            'tareas' => $tareas,
        ]);
    }

    /**
     * Marcar tarea asignada como completada.
     */
    public function completarTarea(Request $request, $adminTaskId): JsonResponse|RedirectResponse
    {
        $adminTask = $adminTaskId instanceof AdminTask ? $adminTaskId : AdminTask::findOrFail($adminTaskId);
        $adminTask->markCompleted($request->input('notes', 'Completada por el trabajador'));

        if (! $request->wantsJson()) {
            return redirect()->back()->with('success', 'Tarea marcada como realizada exitosamente.');
        }

        return response()->json([
            'message' => '¡Tarea marcada como completada exitosamente!',
            'data' => $adminTask,
        ]);
    }

    /**
     * Módulo de Horas Extras del Trabajador.
     */
    public function horasExtras(Request $request): View
    {
        $user = $request->user();

        $horas = OvertimeRecord::query()
            ->where('user_id', $user->id)
            ->orderBy('record_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $totalHorasMes = (float) OvertimeRecord::where('user_id', $user->id)
            ->whereBetween('record_date', [$startOfMonth, $endOfMonth])
            ->sum('hours');

        $totalAprobadasMes = (float) OvertimeRecord::where('user_id', $user->id)
            ->where('status', OvertimeRecord::STATUS_APROBADO)
            ->whereBetween('record_date', [$startOfMonth, $endOfMonth])
            ->sum('hours');

        return view('trabajador.horas_extras.index', [
            'horas' => $horas,
            'totalHorasMes' => $totalHorasMes,
            'totalAprobadasMes' => $totalAprobadasMes,
        ]);
    }

    /**
     * Registrar Horas Extras.
     */
    public function storeHorasExtras(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $request->has('record_date') && $request->has('fecha')) {
            $request->merge(['record_date' => $request->input('fecha')]);
        }
        if (! $request->has('hours') && $request->has('horas')) {
            $request->merge(['hours' => $request->input('horas')]);
        }
        if (! $request->has('occasion') && $request->has('motivo')) {
            $request->merge(['occasion' => $request->input('motivo')]);
        }

        $validated = $request->validate([
            'record_date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'occasion' => ['required', 'string', 'max:191'],
            'justification' => ['nullable', 'string', 'max:500'],
        ]);

        $record = OvertimeRecord::create([
            'finca_id' => $user->finca_id ?? 1,
            'user_id' => $user->id,
            'record_date' => $validated['record_date'],
            'hours' => $validated['hours'],
            'occasion' => $validated['occasion'],
            'justification' => $validated['justification'] ?? $validated['occasion'] ?? 'Labor suplementaria de campo',
            'status' => OvertimeRecord::STATUS_PENDIENTE,
        ]);

        if (! $request->wantsJson()) {
            return redirect()->back()->with('success', 'Horas extras registradas correctamente y enviadas para aprobación.');
        }

        return response()->json([
            'message' => 'Horas extras registradas correctamente.',
            'data' => $record,
        ], 201);
    }

    /**
     * Módulo de Solicitud de Permisos.
     */
    public function permisos(Request $request): View
    {
        $user = $request->user();

        $permisos = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->with('reviewedBy')
            ->orderBy('start_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('trabajador.permisos', [
            'permisos' => $permisos,
        ]);
    }

    /**
     * Registrar Solicitud de Permiso.
     */
    public function storePermiso(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $request->has('start_date') && $request->has('fecha_inicio')) {
            $request->merge(['start_date' => $request->input('fecha_inicio')]);
        }
        if (! $request->has('end_date') && $request->has('fecha_fin')) {
            $request->merge(['end_date' => $request->input('fecha_fin')]);
        }
        if (! $request->has('reason') && $request->has('motivo')) {
            $request->merge(['reason' => $request->input('motivo')]);
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $permiso = LeaveRequest::create([
            'finca_id' => $user->finca_id ?? 1,
            'user_id' => $user->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'status' => LeaveRequest::STATUS_PENDIENTE,
        ]);

        if (! $request->wantsJson()) {
            return redirect()->back()->with('success', 'Solicitud de permiso registrada correctamente.');
        }

        return response()->json([
            'message' => 'Solicitud de permiso registrada correctamente.',
            'data' => $permiso,
        ], 201);
    }

    /**
     * Módulo de Mi Saldo de Pescado Llevado / Fiado.
     */
    public function saldoPescado(Request $request): View
    {
        $user = $request->user();
        $finca = $user->finca ?? Finca::find($user->finca_id ?? 1);
        $precioKg = (float) ($finca?->obtenerConfig('precios.pescado_empleado_kg') ?? 7000.0);

        $credits = FishCredit::query()
            ->where('user_id', $user->id)
            ->with('registeredBy')
            ->orderBy('credit_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $totalKilos = (float) FishCredit::where('user_id', $user->id)->sum('kilos');
        $totalDinero = (float) FishCredit::where('user_id', $user->id)->sum('total_amount');
        if ($totalDinero <= 0 && $totalKilos > 0) {
            $totalDinero = $totalKilos * $precioKg;
        }

        return view('trabajador.saldo-pescado', [
            'credits' => $credits,
            'totalKilos' => $totalKilos,
            'totalDinero' => $totalDinero,
            'precioKg' => $precioKg,
        ]);
    }

    /**
     * Registro Rápido de Alimentación con nivel de apetito.
     */
    public function storeAlimentacion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pond_id' => ['required', 'exists:ponds,id'],
            'amount_kg' => ['required', 'numeric', 'min:0.1'],
            'appetite_level' => ['required', 'in:bueno,regular,malo'],
            'alimento_id' => ['nullable', 'exists:alimentos_bodega,id'],
            'feed_inventory_id' => ['nullable', 'exists:feed_inventories,id'],
            'observations' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $pond = Estanque::findOrFail($validated['pond_id']);
        $amountKg = (float) $validated['amount_kg'];
        $appetite = $validated['appetite_level'];
        $fincaId = $pond->finca_id ?? $user->finca_id ?? 1;

        // 1. Identificar o deducir el tipo de alimento en bodega
        $alimento = null;
        if (! empty($validated['alimento_id'])) {
            $alimento = AlimentoBodega::find($validated['alimento_id']);
        }

        if (! $alimento) {
            $avgWeight = (float) $pond->average_weight;
            if ($avgWeight < 80) {
                $alimento = AlimentoBodega::where('finca_id', $fincaId)->where('nombre_concentrado', 'like', '%Iniciación%')->first();
            } elseif ($avgWeight < 300) {
                $alimento = AlimentoBodega::where('finca_id', $fincaId)->where('nombre_concentrado', 'like', '%Levante%')->first();
            } else {
                $alimento = AlimentoBodega::where('finca_id', $fincaId)->where('nombre_concentrado', 'like', '%Engorde%')->first();
            }

            if (! $alimento) {
                $alimento = AlimentoBodega::where('finca_id', $fincaId)->first() ?? AlimentoBodega::first();
            }
        }

        // 2. Descuento automático de stock en AlimentoBodega y registro en MovimientoBodega
        if ($alimento) {
            $pesoBulto = (float) ($alimento->peso_bulto_kg ?: 40.00);
            $bultosDescontar = round($amountKg / $pesoBulto, 2);

            $nuevoStockKg = max(0, (float) $alimento->stock_kilos_actual - $amountKg);
            $nuevoStockBultos = max(0, round((float) $alimento->stock_bultos - ($amountKg / $pesoBulto), 2));

            $alimento->stock_kilos_actual = $nuevoStockKg;
            $alimento->stock_bultos = $nuevoStockBultos;
            $alimento->save();

            MovimientoBodega::create([
                'finca_id' => $alimento->finca_id,
                'alimento_id' => $alimento->id,
                'user_id' => $user->id,
                'tipo_movimiento' => MovimientoBodega::TIPO_SALIDA_ALIMENTACION,
                'cantidad_bultos' => $bultosDescontar,
                'cantidad_kilos' => $amountKg,
                'fecha' => now()->toDateString(),
                'proveedor' => null,
                'observaciones' => "Alimentación a {$pond->name} ({$pond->code}) - Apetito: {$appetite}",
            ]);
        }

        // 3. Sincronización con FeedInventory tradicional
        $feed = isset($validated['feed_inventory_id'])
            ? FeedInventory::find($validated['feed_inventory_id'])
            : FeedInventory::where('finca_id', $pond->finca_id)->first() ?? FeedInventory::first();

        if ($feed && $feed->quantity_kg >= $amountKg) {
            $feed->decrement('quantity_kg', $amountKg);
        }

        $log = FeedingLog::create([
            'finca_id' => $pond->finca_id,
            'user_id' => $user->id,
            'pond_id' => $pond->id,
            'feed_inventory_id' => $feed?->id,
            'feeding_date' => now()->toDateString(),
            'amount_kg' => $amountKg,
            'appetite_level' => $appetite,
            'feed_name' => $alimento?->nombre_concentrado ?? ($feed?->name ?? 'Concentrado Piscícola Estándar'),
            'feed_brand' => $feed?->brand ?? 'Italcol',
            'feed_type' => $feed?->feed_type ?? 'Engorde',
            'feed_protein_percentage' => $alimento?->proteina_porcentaje ?? ($feed?->protein_percentage ?? 30.0),
            'observations' => $validated['observations'] ?? null,
        ]);

        \App\Models\ActividadTrabajador::registrar(
            $user,
            \App\Models\ActividadTrabajador::ACCION_ALIMENTACION,
            "Alimentó estanque {$pond->name} con {$amountKg} kg de concentrado (Apetito: {$appetite}).",
            $pond->id
        );

        return response()->json([
            'message' => "Alimentación de {$amountKg} kg registrada para {$pond->name} con apetito '{$appetite}'. Stock de bodega actualizado.",
            'data' => $log->load('pond:id,name', 'user:id,name'),
            'alimento_bodega' => $alimento ? [
                'id' => $alimento->id,
                'nombre' => $alimento->nombre_concentrado,
                'stock_kilos_actual' => $alimento->stock_kilos_actual,
                'stock_bultos' => $alimento->stock_bultos,
            ] : null,
        ], 201);
    }
}
