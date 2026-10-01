<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Notifications\CuentaAprobadaNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PersonalController extends Controller
{
    /**
     * Muestra el panel de administración de personal y solicitudes pendientes.
     * Restringido exclusivamente al rol propietario del tenant.
     */
    public function index(Request $request): View|JsonResponse
    {
        $currentUser = $request->user();

        // Verificar que solo el propietario pueda acceder
        if ($currentUser && ! $currentUser->canManageUsers()) {
            abort(403, 'Acceso denegado: solo el rol propietario tiene autorización para gestionar personal y usuarios.');
        }

        // Roles de campo asignables (el rol propietario nunca aparece en esta lista)
        $rolesCampoPermitidos = [
            'operario_alimentador' => 'Operario Alimentador',
            'celador' => 'Celador',
            'tecnico_acuicola' => 'Técnico Acuícola',
        ];

        // Solicitudes pendientes de aprobación del tenant
        $pendingUsers = User::where(function ($q) {
            $q->where('aprobado', false)
                ->orWhere('role', User::ROLE_PENDIENTE)
                ->orWhere('rol', User::ROLE_PENDIENTE);
        })
            ->with('roles')
            ->orderBy('created_at', 'desc')
            ->get();

        // Personal activo y aprobado en el tenant
        $activeUsers = User::where(function ($q) {
            $q->where('aprobado', true)
                ->where('role', '!=', User::ROLE_PENDIENTE)
                ->where('rol', '!=', User::ROLE_PENDIENTE);
        })
            ->with('roles')
            ->orderBy('name', 'asc')
            ->get();

        $data = [
            'user' => $currentUser,
            'rolesCampoPermitidos' => $rolesCampoPermitidos,
            'pendingUsers' => $pendingUsers,
            'activeUsers' => $activeUsers,
            'totalPendientes' => $pendingUsers->count(),
            'totalTrabajadores' => $activeUsers->filter(fn (User $u) => $u->hasRole(['operario_alimentador', 'operario_campo', 'trabajador']))->count(),
            'totalCeladores' => $activeUsers->filter(fn (User $u) => $u->hasRole(['celador', 'celador_nocturno']))->count(),
            'totalTecnicos' => $activeUsers->filter(fn (User $u) => $u->hasRole('tecnico_acuicola'))->count(),
            'totalAdministracion' => $activeUsers->filter(fn (User $u) => $u->isPropietario())->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('admin.personal.index', $data);
    }

    /**
     * Aprueba el personal y asigna roles de campo con selección múltiple.
     * Roles permitidos: operario_alimentador, celador, tecnico_acuicola.
     * El rol propietario NUNCA puede ser asignado desde este flujo.
     */
    public function aprobar(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $currentUser = $request->user();

        if ($currentUser && ! $currentUser->canManageUsers()) {
            abort(403, 'Acceso denegado: solo el rol propietario tiene autorización para gestionar personal y usuarios.');
        }

        // Seguridad estricta: El rol de propietario o administrador nunca puede asignarse desde personal
        $rawRoles = (array) ($request->roles ?? $request->role ?? []);
        foreach ($rawRoles as $r) {
            $rSlug = is_numeric($r) ? (Role::find($r)?->slug ?? '') : (string) $r;
            if (in_array(strtolower($rSlug), ['propietario', 'owner', 'administrador_general', 'jefe_mayor'], true)) {
                abort(422, 'El rol de propietario no puede ser asignado a personal de campo.');
            }
        }

        if ($request->filled('role') && ! $request->has('roles')) {
            $request->merge(['roles' => (array) $request->role]);
        }

        if ($request->has('roles')) {
            $rolesNormalized = array_map(function ($r) {
                if (is_numeric($r)) {
                    return Role::find($r)?->slug ?? (string) $r;
                }

                return (string) $r;
            }, (array) $request->roles);
            $request->merge(['roles' => $rolesNormalized]);
        }

        $validated = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'in:operario_alimentador,celador,tecnico_acuicola,operario_campo,celador_nocturno,trabajador,worker'],
            'employment_type' => ['nullable', 'string', 'in:fijo,destajo_semanal,temporal'],
        ], [
            'roles.required' => 'Debes seleccionar al menos un rol de campo para el colaborador.',
            'roles.*.in' => 'El rol seleccionado no es válido. Solo se permiten roles de campo.',
        ]);

        $rolesSeleccionados = array_unique($validated['roles']);

        // Mapear nombres canónicos según AGENTS.md
        $rolesMapeados = array_map(function ($r) {
            return match ($r) {
                'operario_campo', 'trabajador', 'worker' => 'operario_alimentador',
                'celador_nocturno' => 'celador',
                default => $r,
            };
        }, $rolesSeleccionados);
        $rolesMapeados = array_unique($rolesMapeados);

        // Seguridad: El rol propietario nunca puede asignarse a personal de campo
        if (in_array('propietario', $rolesMapeados, true)) {
            abort(422, 'El rol de propietario no puede ser asignado a personal de campo.');
        }

        $wasPending = (! $user->aprobado || $user->role === User::ROLE_PENDIENTE || $user->rol === User::ROLE_PENDIENTE);

        // Actualizar datos del usuario
        $user->aprobado = true;
        $user->aprobado_at = now();
        $user->roles_asignados = $rolesMapeados;
        $user->rol = $rolesMapeados[0];
        $user->role = $rolesMapeados[0];

        if ($request->filled('employment_type')) {
            $user->employment_type = $request->employment_type;
        }

        $user->save();

        // Sincronizar con la tabla pivote de roles si existen los modelos
        $rolesEnBD = Role::whereIn('slug', array_merge($rolesMapeados, $rolesSeleccionados))->get();
        if ($rolesEnBD->isNotEmpty()) {
            $user->roles()->sync($rolesEnBD->pluck('id')->toArray());
        }

        $nombresRoles = implode(', ', array_map(function ($r) {
            return match ($r) {
                'operario_alimentador' => 'Operario Alimentador',
                'celador' => 'Celador',
                'tecnico_acuicola' => 'Técnico Acuícola',
                default => ucfirst(str_replace('_', ' ', $r)),
            };
        }, $rolesMapeados));

        if ($wasPending && $user->email) {
            try {
                $user->notify(new CuentaAprobadaNotification($user, $nombresRoles, $user->employment_type ?? 'Fijo'));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $mensajeAccion = $wasPending
            ? "Cuenta de {$user->name} aprobada exitosamente con roles [{$nombresRoles}]."
            : "Roles de {$user->name} actualizados a [{$nombresRoles}].";

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $mensajeAccion,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'tenant_id' => $user->tenant_id,
                    'rol' => $user->rol,
                    'roles_asignados' => $user->roles_asignados,
                    'aprobado' => (bool) $user->aprobado,
                ],
            ]);
        }

        return redirect()->route('admin.personal.index')->with('success', $mensajeAccion);
    }

    /**
     * Alias de asignación para compatibilidad de rutas y formularios.
     */
    public function asignarRol(Request $request, User $user): RedirectResponse|JsonResponse
    {
        return $this->aprobar($request, $user);
    }
}
