<?php

namespace App\Http\Controllers;

use App\Models\Finca;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SuperAdminFincaController extends Controller
{
    /**
     * Listado general de fincas para el Super-Administrador.
     */
    public function index(): View
    {
        $fincas = Finca::withCount(['users', 'ponds'])->orderBy('id')->get();

        return view('superadmin.fincas.index', [
            'fincas' => $fincas,
        ]);
    }

    /**
     * Muestra la pantalla de edición de módulos y planes para una finca específica.
     */
    public function edit(int|string $id): View
    {
        $finca = Finca::find($id);

        if (! $finca) {
            $finca = Finca::create([
                'id' => (int) $id,
                'nombre' => "Finca Cliente #{$id}",
                'codigo' => "FINCA-0{$id}",
                'ubicacion' => 'Colombia',
                'configuraciones' => Finca::DEFAULT_CONFIG,
            ]);
        }

        $modulosDisponibles = [
            'celador_nocturno' => [
                'nombre' => 'Módulo Celador Nocturno',
                'descripcion' => 'Rondas nocturnas, control de aireadores por estanque, verificación de mallas y turno de domingo.',
                'icono' => 'fa-solid fa-moon',
                'color' => 'text-purple-500',
            ],
            'asistente_ia' => [
                'nombre' => 'Asistente de IA Gemini',
                'descripcion' => 'Widget flotante de IA con conocimiento experto acuícola, oxigenación y reglas de la finca.',
                'icono' => 'fa-solid fa-robot',
                'color' => 'text-cyan-500',
            ],
            'planta_procesamiento_merma' => [
                'nombre' => 'Módulo de Merma en Planta',
                'descripcion' => 'Control de peso bruto, tara de canastillas y rendimiento neto en mesa de eviscerado.',
                'icono' => 'fa-solid fa-industry',
                'color' => 'text-indigo-500',
            ],
            'exportacion_facturacion' => [
                'nombre' => 'Exportación a Excel y Facturación',
                'descripcion' => 'Descarga de planillas CSV/Excel para nóminas, pesajes de báscula y libro contable.',
                'icono' => 'fa-solid fa-file-excel',
                'color' => 'text-emerald-500',
            ],
            'ventas_visitantes' => [
                'nombre' => 'Módulo de Ventas a Visitantes',
                'descripcion' => 'Caja diaria, venta rápida por efectivo en portería y tarifas diferenciadas de pescado.',
                'icono' => 'fa-solid fa-cash-register',
                'color' => 'text-amber-500',
            ],
            'policultivo_avanzado' => [
                'nombre' => 'Policultivo Avanzado & Enciclopedia',
                'descripcion' => 'Sinergias biológicas entre especies y manual técnico de parámetros físico-químicos.',
                'icono' => 'fa-solid fa-arrows-split-up-and-left',
                'color' => 'text-teal-500',
            ],
        ];

        return view('superadmin.fincas.edit', [
            'finca' => $finca,
            'modulosDisponibles' => $modulosDisponibles,
        ]);
    }

    /**
     * Actualiza los módulos y feature flags asignados a la finca según el plan.
     */
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $finca = Finca::findOrFail($id);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:191'],
            'codigo' => ['nullable', 'string', 'max:50'],
            'ubicacion' => ['nullable', 'string', 'max:191'],
            'modulos' => ['nullable', 'array'],
        ]);

        $finca->nombre = $validated['nombre'];
        $finca->codigo = $validated['codigo'] ?? $finca->codigo;
        $finca->ubicacion = $validated['ubicacion'] ?? $finca->ubicacion;

        // Construir el mapa de flags booleanos
        $modulosKeys = [
            'celador_nocturno',
            'asistente_ia',
            'planta_procesamiento_merma',
            'exportacion_facturacion',
            'ventas_visitantes',
            'policultivo_avanzado',
        ];

        $modulosActivos = [];
        foreach ($modulosKeys as $key) {
            $modulosActivos[$key] = $request->has("modulos.{$key}");
        }

        $finca->actualizarConfig('modulos_activos', $modulosActivos);
        $finca->save();

        return redirect()->route('superadmin.fincas.edit', $finca->id)
            ->with('success', "Módulos y suscripción de '{$finca->nombre}' actualizados correctamente.");
    }
}
