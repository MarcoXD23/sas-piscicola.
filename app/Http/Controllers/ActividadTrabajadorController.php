<?php

namespace App\Http\Controllers;

use App\Models\ActividadTrabajador;
use App\Models\Estanque;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActividadTrabajadorController extends Controller
{
    /**
     * Bitácora de actividades de trabajadores con filtros por trabajador, rol, fecha y estanque.
     */
    public function index(Request $request): View|JsonResponse
    {
        $fincaId = $request->user()?->finca_id ?? 1;

        $query = ActividadTrabajador::with(['user:id,name,role', 'estanque:id,name,code']);

        // Filtro por Trabajador
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtro por Rol al momento
        if ($request->filled('rol')) {
            $query->where('rol_momento', 'like', '%'.$request->rol.'%');
        }

        // Filtro por Fecha
        if ($request->filled('fecha')) {
            $query->whereDate('created_at', $request->fecha);
        }

        // Filtro por Estanque
        if ($request->filled('estanque_id')) {
            $query->where('estanque_id', $request->estanque_id);
        }

        $actividades = $query->orderBy('created_at', 'desc')->paginate(30)->withQueryString();

        $trabajadores = User::where(function ($q) use ($fincaId) {
            $q->where('finca_id', $fincaId)->orWhereNull('finca_id');
        })->orderBy('name')->get(['id', 'name', 'role']);

        $estanques = Estanque::where('finca_id', $fincaId)->orderBy('name')->get(['id', 'name', 'code']);

        $roles = ActividadTrabajador::select('rol_momento')->distinct()->pluck('rol_momento');

        $data = [
            'actividades' => $actividades,
            'trabajadores' => $trabajadores,
            'estanques' => $estanques,
            'roles' => $roles,
            'filtros' => $request->only(['user_id', 'rol', 'fecha', 'estanque_id']),
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('admin.actividades.index', $data);
    }
}
