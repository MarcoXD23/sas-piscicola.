<?php

namespace App\Http\Controllers;

use App\Models\Especie;
use App\Models\Finca;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FincaAjustesController extends Controller
{
    /**
     * Muestra la pantalla de ajustes de parámetros y especies de la finca.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $finca = $user->finca_segura;
        $especiesCatalogo = Especie::orderBy('nombre_comun')->get();

        return view('admin.ajustes', [
            'finca' => $finca,
            'especiesCatalogo' => $especiesCatalogo,
            'precios' => [
                'pescado_visitante_kg' => $finca->obtenerConfig('precios.pescado_visitante_kg', 9000),
                'pescado_empleado_kg' => $finca->obtenerConfig('precios.pescado_empleado_kg', 7000),
            ],
            'operacion' => [
                'peso_tara_canastilla_kg' => $finca->obtenerConfig('operacion.peso_tara_canastilla_kg', 2.0),
                'dia_pesca_habitual' => $finca->obtenerConfig('operacion.dia_pesca_habitual', 'lunes'),
                'dia_pago_nomina' => $finca->obtenerConfig('operacion.dia_pago_nomina', 'sabado'),
            ],
            'especiesHabilitadas' => $finca->obtenerConfig('especies_habilitadas', [
                'mojarra_roja',
                'mojarra_negra',
                'cachama',
                'bocachico',
            ]),
        ]);
    }

    /**
     * Guarda los nuevos parámetros operativos y precios de la finca.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pescado_visitante_kg' => ['required', 'numeric', 'min:1000', 'max:100000'],
            'pescado_empleado_kg' => ['required', 'numeric', 'min:1000', 'max:100000'],
            'peso_tara_canastilla_kg' => ['required', 'numeric', 'min:0.1', 'max:25'],
            'dia_pesca_habitual' => ['nullable', 'string', 'in:lunes,martes,miercoles,jueves,viernes,sabado,domingo'],
            'dia_pago_nomina' => ['nullable', 'string', 'in:lunes,martes,miercoles,jueves,viernes,sabado,domingo'],
            'especies_habilitadas' => ['nullable', 'array'],
            'especies_habilitadas.*' => ['string'],
        ]);

        $user = $request->user();
        $finca = $user->finca ?? Finca::find($user->finca_id ?? 1);

        if (! $finca) {
            $finca = Finca::create([
                'id' => $user->finca_id ?? 1,
                'nombre' => 'Finca Piscícola Principal',
                'configuraciones' => Finca::DEFAULT_CONFIG,
            ]);
        }

        $finca->actualizarConfig('precios.pescado_visitante_kg', (float) $validated['pescado_visitante_kg']);
        $finca->actualizarConfig('precios.pescado_empleado_kg', (float) $validated['pescado_empleado_kg']);
        $finca->actualizarConfig('operacion.peso_tara_canastilla_kg', (float) $validated['peso_tara_canastilla_kg']);

        if (! empty($validated['dia_pesca_habitual'])) {
            $finca->actualizarConfig('operacion.dia_pesca_habitual', $validated['dia_pesca_habitual']);
        }

        if (! empty($validated['dia_pago_nomina'])) {
            $finca->actualizarConfig('operacion.dia_pago_nomina', $validated['dia_pago_nomina']);
        }

        $finca->actualizarConfig('especies_habilitadas', $validated['especies_habilitadas'] ?? []);

        return redirect()->route('admin.ajustes')
            ->with('success', 'Parámetros y precios de tu finca actualizados exitosamente.');
    }
}
