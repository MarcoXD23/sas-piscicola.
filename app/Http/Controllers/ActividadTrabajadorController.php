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
     * Bitácora de actividades de trabajadores con filtros por trabajador, rol, acción, fecha y estanque.
     * Acceso exclusivo para Propietario y Administrador.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

        // Control de acceso estricto: Propietario y Administrador. Bloquear operarios y celadores con 403.
        if (! $user) {
            abort(401);
        }

        if ($user->hasRole(['operario_alimentador', 'operario_campo', 'celador', 'trabajador', 'worker', 'guard']) &&
            ! ($user->isOwner() || $user->isAdmin() || $user->hasRole(['propietario', 'administrador', 'admin', 'jefe_mayor', 'owner', 'jefe_finca', 'jefe']))) {
            abort(403, 'Acceso denegado: este módulo es exclusivo para Propietario y Administrador.');
        }

        if (! ($user->isOwner() || $user->isAdmin() || $user->hasRole(['propietario', 'administrador', 'admin', 'jefe_mayor', 'owner', 'jefe_finca', 'jefe']))) {
            abort(403, 'Acceso denegado: este módulo es exclusivo para Propietario y Administrador.');
        }

        $fincaId = $user->finca_id ?? 1;

        // Base query con eager loading y aislamiento estricto por finca (tenant)
        $query = ActividadTrabajador::with([
            'user' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'name', 'role', 'finca_id', 'tenant_id'),
            'estanque:id,name,code,finca_id',
        ])->whereHas('user', function ($q) use ($fincaId) {
            $q->withoutGlobalScopes()->where(function ($sub) use ($fincaId) {
                $sub->where('finca_id', $fincaId)
                    ->orWhere('tenant_id', $fincaId)
                    ->orWhereNull('finca_id');
            });
        });

        // 1. Filtro por Trabajador
        if ($request->filled('user_id') || $request->filled('trabajador_id')) {
            $userId = $request->input('user_id', $request->input('trabajador_id'));
            $query->where('user_id', $userId);
        }

        // 2. Filtro por Rol al momento
        if ($request->filled('rol') || $request->filled('rol_momento')) {
            $rol = trim((string) $request->input('rol', $request->input('rol_momento')));
            $query->where(function ($q) use ($rol) {
                $q->whereRaw('LOWER(rol_momento) LIKE ?', ['%' . mb_strtolower($rol) . '%']);
            });
        }

        // 3. Filtro por Tipo de Acción
        if ($request->filled('tipo_accion') || $request->filled('accion')) {
            $accion = trim((string) $request->input('tipo_accion', $request->input('accion')));
            $mapeo = match (mb_strtolower($accion)) {
                'suministro_alimento', 'alimentacion', 'alimentador' => ['alimentacion', 'suministro_alimento'],
                'traslado_peces', 'traslado', 'desdoble' => ['traslado_peces', 'traslado', 'desdoble'],
                'reporte_mortalidad', 'mortalidad' => ['mortalidad', 'reporte_mortalidad'],
                'ronda_seguridad', 'ronda_nocturna', 'seguridad', 'ronda' => ['ronda_nocturna', 'ronda_seguridad'],
                'ingreso_alimento', 'bodega' => ['ingreso_alimento'],
                'tarea_completada', 'tarea' => ['tarea_completada'],
                default => [$accion],
            };
            $query->whereIn('tipo_accion', $mapeo);
        }

        // 4. Filtro por Estanque / Lago
        if ($request->filled('estanque_id') || $request->filled('lago_id')) {
            $estanqueId = $request->input('estanque_id', $request->input('lago_id'));
            $query->where('estanque_id', $estanqueId);
        }

        // 5. Filtro por Rango de Fechas (fecha_inicio y fecha_fin) o fecha puntual
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('created_at', '>=', $request->input('fecha_inicio'));
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('created_at', '<=', $request->input('fecha_fin'));
        }

        if ($request->filled('fecha') && ! $request->filled('fecha_inicio') && ! $request->filled('fecha_fin')) {
            $query->whereDate('created_at', $request->input('fecha'));
        }

        // Orden cronológico descendente y paginación
        $actividades = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

        // Opciones para filtros filtradas estrictamente por finca
        $trabajadores = User::where(function ($q) use ($fincaId) {
            $q->where('finca_id', $fincaId)->orWhereNull('finca_id');
        })->orderBy('name')->get(['id', 'name', 'role']);
        $estanques = Estanque::where('finca_id', $fincaId)->orderBy('name')->get(['id', 'name', 'code']);

        $rolesRegistrados = ActividadTrabajador::whereHas('user', function ($q) use ($fincaId) {
                $q->where('finca_id', $fincaId)->orWhereNull('finca_id');
            })
            ->select('rol_momento')
            ->distinct()
            ->pluck('rol_momento');

        $data = [
            'actividades' => $actividades,
            'trabajadores' => $trabajadores,
            'estanques' => $estanques,
            'roles' => $rolesRegistrados,
            'filtros' => $request->only(['user_id', 'trabajador_id', 'rol', 'rol_momento', 'tipo_accion', 'accion', 'estanque_id', 'lago_id', 'fecha', 'fecha_inicio', 'fecha_fin']),
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('admin.actividades.index', $data);
    }
}
