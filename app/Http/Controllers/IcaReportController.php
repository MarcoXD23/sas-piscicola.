<?php

namespace App\Http\Controllers;

use App\Models\FeedingLog;
use App\Models\Pond;
use App\Models\RegistroCalidadAgua;
use App\Models\RegistroMortalidad;
use App\Models\TratamientoSanitario;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class IcaReportController extends Controller
{
    /**
     * Generador del Libro de Campo Oficial del ICA (Buenas Prácticas Acuícolas BPAP).
     */
    public function libroCampo(Request $request): View
    {
        $user = $request->user();
        $finca = $user->finca_segura;

        $startDateStr = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDateStr = $request->input('end_date', now()->endOfMonth()->toDateString());
        $pondId = $request->input('pond_id');

        $startDate = Carbon::parse($startDateStr);
        $endDate = Carbon::parse($endDateStr);

        // 1. Estanques seleccionados o todos los de la finca
        $estanquesQuery = Pond::where('finca_id', $finca->id);
        if ($pondId) {
            $estanquesQuery->where('id', $pondId);
        }
        $estanques = $estanquesQuery->orderBy('name')->get();
        $pondIds = $estanques->pluck('id');

        // 2. Planilla de Calidad de Agua en el rango
        $calidadAgua = RegistroCalidadAgua::where('finca_id', $finca->id)
            ->whereIn('estanque_id', $pondIds)
            ->whereDate('fecha', '>=', $startDate->toDateString())
            ->whereDate('fecha', '<=', $endDate->toDateString())
            ->with(['estanque:id,name,code', 'user:id,name'])
            ->orderBy('fecha', 'asc')
            ->orderBy('hora', 'asc')
            ->get();

        // 3. Planilla de Alimentación en el rango
        $alimentacion = FeedingLog::where('finca_id', $finca->id)
            ->whereIn('pond_id', $pondIds)
            ->whereDate('feeding_date', '>=', $startDate->toDateString())
            ->whereDate('feeding_date', '<=', $endDate->toDateString())
            ->with(['pond:id,name,code', 'feedInventory:id,name,brand,feed_type,protein_percentage'])
            ->orderBy('feeding_date', 'asc')
            ->get();

        // 4. Bitácora de Mortalidad y Disposición
        $mortalidades = RegistroMortalidad::where('finca_id', $finca->id)
            ->whereIn('estanque_id', $pondIds)
            ->whereDate('fecha', '>=', $startDate->toDateString())
            ->whereDate('fecha', '<=', $endDate->toDateString())
            ->with(['estanque:id,name,code', 'user:id,name'])
            ->orderBy('fecha', 'asc')
            ->get();

        // 5. Registro Sanitario y Tiempos de Retiro
        $tratamientos = TratamientoSanitario::where('finca_id', $finca->id)
            ->whereIn('estanque_id', $pondIds)
            ->whereDate('fecha_aplicacion', '>=', $startDate->toDateString())
            ->whereDate('fecha_aplicacion', '<=', $endDate->toDateString())
            ->with(['estanque:id,name,code', 'user:id,name'])
            ->orderBy('fecha_aplicacion', 'asc')
            ->get();

        $todosEstanques = Pond::where('finca_id', $finca->id)->orderBy('name')->get();

        return view('reports.ica-libro-campo', [
            'finca' => $finca,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedPondId' => $pondId,
            'estanques' => $estanques,
            'todosEstanques' => $todosEstanques,
            'calidadAgua' => $calidadAgua,
            'alimentacion' => $alimentacion,
            'mortalidades' => $mortalidades,
            'tratamientos' => $tratamientos,
            'generadoPor' => $user->name,
            'fechaGeneracion' => now()->timezone('America/Bogota')->format('d/m/Y g:i A'),
        ]);
    }
}
