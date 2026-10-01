<?php

namespace App\Http\Controllers;

use App\Models\Pond;
use App\Models\TratamientoSanitario;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TratamientoSanitarioController extends Controller
{
    /**
     * Muestra la vista de tratamientos sanitarios y control de tiempos de retiro.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $finca = $user->finca_segura;

        $tratamientos = TratamientoSanitario::where('finca_id', $finca->id)
            ->with(['estanque:id,name,code,status', 'user:id,name'])
            ->orderBy('fecha_aplicacion', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $estanques = Pond::where('finca_id', $finca->id)->orderBy('name')->get();

        // Estanques actualmente en período de retiro
        $estanquesEnRetiro = $estanques->filter(fn (Pond $p) => $p->estaEnTiempoRetiro());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Tratamientos sanitarios recuperados.',
                'total' => $tratamientos->count(),
                'estanques_en_retiro_count' => $estanquesEnRetiro->count(),
                'data' => $tratamientos,
            ]);
        }

        return view('sanidad.index', [
            'finca' => $finca,
            'tratamientos' => $tratamientos,
            'estanques' => $estanques,
            'estanquesEnRetiro' => $estanquesEnRetiro,
        ]);
    }

    /**
     * Registra un nuevo tratamiento sanitario calculando automáticamente la fecha fin de retiro.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'estanque_id' => ['required', 'exists:ponds,id'],
            'fecha_aplicacion' => ['required', 'date'],
            'tipo_tratamiento' => ['required', 'in:bano_sal,encalado,medicamento_veterinario,desinfectante'],
            'producto' => ['required', 'string', 'max:150'],
            'dosis_aplicada' => ['required', 'string', 'max:100'],
            'dias_tiempo_retiro' => ['required', 'integer', 'min:0', 'max:180'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $fincaId = $user->finca_id ?? 1;

        $fechaAplicacion = Carbon::parse($validated['fecha_aplicacion']);
        $diasRetiro = (int) $validated['dias_tiempo_retiro'];
        $fechaFinRetiro = $fechaAplicacion->copy()->addDays($diasRetiro);

        $tratamiento = TratamientoSanitario::create([
            'finca_id' => $fincaId,
            'estanque_id' => $validated['estanque_id'],
            'user_id' => $user->id,
            'fecha_aplicacion' => $fechaAplicacion->toDateString(),
            'tipo_tratamiento' => $validated['tipo_tratamiento'],
            'producto' => $validated['producto'],
            'dosis_aplicada' => $validated['dosis_aplicada'],
            'dias_tiempo_retiro' => $diasRetiro,
            'fecha_fin_retiro' => $fechaFinRetiro->toDateString(),
            'observaciones' => $validated['observaciones'] ?? null,
        ]);

        $estanque = Pond::find($validated['estanque_id']);
        $msg = "Tratamiento sanitario registrado para '{$estanque->name}'. ";
        if ($diasRetiro > 0) {
            $msg .= "Tiempo de retiro de {$diasRetiro} días activo hasta el {$fechaFinRetiro->format('d/m/Y')}. Cosecha bloqueada durante este período.";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msg,
                'fecha_fin_retiro' => $fechaFinRetiro->toDateString(),
                'en_retiro' => $tratamiento->estaEnRetiro(),
                'data' => $tratamiento->load('estanque:id,name', 'user:id,name'),
            ], 201);
        }

        return redirect()->route('sanidad.index')->with('success', $msg);
    }
}
