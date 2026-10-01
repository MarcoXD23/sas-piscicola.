<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTratamientoSanitarioRequest;
use App\Http\Resources\TratamientoSanitarioResource;
use App\Models\Pond;
use App\Models\TratamientoSanitario;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SanidadController extends Controller
{
    /**
     * Listado de tratamientos sanitarios y bitácora de retiro ICA.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TratamientoSanitario::query()->with(['estanque:id,name,code,numero_lote', 'user:id,name,role']);

        if ($request->filled('estanque_id')) {
            $query->where('estanque_id', $request->estanque_id);
        }

        if ($request->filled('lote_id')) {
            $query->where('lote_id', $request->lote_id);
        }

        if ($request->boolean('solo_activos')) {
            $query->enRetiro();
        }

        $tratamientos = $query->orderBy('fecha_aplicacion', 'desc')->orderBy('id', 'desc')->get();

        return response()->json([
            'message' => 'Tratamientos sanitarios ICA recuperados exitosamente.',
            'total' => $tratamientos->count(),
            'activos_en_retiro' => $tratamientos->filter(fn ($t) => $t->estaEnRetiro())->count(),
            'data' => TratamientoSanitarioResource::collection($tratamientos),
        ]);
    }

    /**
     * Registra un tratamiento sanitario con cálculo automático de fecha hábil de cosecha
     * y aplicación estricta de tiempo de retiro bajo la normativa del ICA.
     */
    public function store(StoreTratamientoSanitarioRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $estanque = Pond::findOrFail($validated['estanque_id']);
        $fechaAplicacion = Carbon::parse($validated['fecha_aplicacion']);
        $diasRetiro = (int) $validated['tiempo_retiro_dias'];
        $fechaHabilCosecha = $fechaAplicacion->copy()->addDays($diasRetiro);

        $tratamiento = DB::transaction(function () use ($validated, $estanque, $user, $fechaAplicacion, $diasRetiro, $fechaHabilCosecha) {
            return TratamientoSanitario::create([
                'finca_id' => $estanque->finca_id ?? $user?->finca_id ?? 1,
                'estanque_id' => $estanque->id,
                'lote_id' => $validated['lote_id'] ?? $estanque->numero_lote ?? "LOTE-{$estanque->id}",
                'user_id' => $user?->id ?? 1,
                'responsable' => $validated['responsable'] ?? $user?->name ?? 'Responsable Sanitario',
                'fecha_aplicacion' => $fechaAplicacion->toDateString(),
                'tipo_tratamiento' => $validated['tipo_tratamiento'] ?? TratamientoSanitario::TIPO_MEDICAMENTO_VETERINARIO,
                'producto' => $validated['producto'],
                'principio_activo' => $validated['principio_activo'] ?? null,
                'dosis' => $validated['dosis'],
                'dosis_aplicada' => $validated['dosis'],
                'tiempo_retiro_dias' => $diasRetiro,
                'dias_tiempo_retiro' => $diasRetiro,
                'fecha_habil_cosecha' => $fechaHabilCosecha->toDateString(),
                'fecha_fin_retiro' => $fechaHabilCosecha->toDateString(),
                'observaciones' => $validated['observaciones'] ?? null,
            ]);
        });

        $tratamiento->load(['estanque', 'user']);

        return response()->json([
            'message' => 'Tratamiento sanitario registrado y tiempo de carencia ICA calculado exitosamente.',
            'bloqueo_cosecha_hasta' => $fechaHabilCosecha->toDateString(),
            'normativa_ica' => 'Cumplimiento Resolución ICA 20186 de 2016 y Buenas Prácticas Acuícolas (BPA).',
            'data' => new TratamientoSanitarioResource($tratamiento),
        ], 201);
    }

    /**
     * Valida si un estanque o lote específico está habilitado para cosecha o venta,
     * o si debe ser BLOQUEADO de manera obligatoria por retiro farmacológico ICA.
     */
    public function verificarRetiro(Request $request, int|string $estanqueId): JsonResponse
    {
        $estanque = Pond::findOrFail($estanqueId);
        $fechaConsulta = $request->filled('fecha') ? Carbon::parse($request->fecha) : now();

        $tratamientoActivo = $estanque->tratamientoEnRetiroActivo($fechaConsulta);

        if ($tratamientoActivo) {
            $fechaHabil = $tratamientoActivo->fecha_habil_cosecha ?? $tratamientoActivo->fecha_fin_retiro;
            $diasRestantes = now()->diffInDays(Carbon::parse($fechaHabil), false) + 1;

            return response()->json([
                'bloqueado' => true,
                'autorizado_cosecha' => false,
                'autorizado_venta' => false,
                'estanque_id' => $estanque->id,
                'estanque_nombre' => $estanque->name,
                'fecha_habil_cosecha' => Carbon::parse($fechaHabil)->toDateString(),
                'dias_restantes' => max(0, (int) $diasRestantes),
                'medicamento_aplicado' => $tratamientoActivo->producto,
                'principio_activo' => $tratamientoActivo->principio_activo,
                'motivo_bloqueo' => "BLOQUEO SANITARIO ICA: El estanque {$estanque->name} se encuentra en período de carencia hasta el {$fechaHabil} por tratamiento con {$tratamientoActivo->producto}. Prohibida su cosecha y comercialización.",
            ], 422);
        }

        return response()->json([
            'bloqueado' => false,
            'autorizado_cosecha' => true,
            'autorizado_venta' => true,
            'estanque_id' => $estanque->id,
            'estanque_nombre' => $estanque->name,
            'mensaje' => 'Estanque sin restricciones sanitarias. Apto para cosecha y comercialización.',
        ]);
    }
}
