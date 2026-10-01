<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Valida que el usuario autenticado posea al menos uno de los roles permitidos.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$roles  Lista de roles permitidos (ej: jefe_mayor, administrador, trabajador)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'No autenticado.',
                ], 401);
            }

            return redirect()->route('login');
        }

        // Si el usuario está registrado pero aún no tiene rol asignado
        if ($user->isPendiente()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Tu cuenta se encuentra pendiente de activación y asignación de rol por parte del Administrador.',
                    'error' => 'ACCOUNT_PENDING_APPROVAL',
                ], 403);
            }

            return redirect()->route('auth.pending-approval');
        }

        // El Jefe Mayor / Dueño posee acceso total e irrestricto sobre todo el sistema
        if ($user->isOwner()) {
            return $next($request);
        }

        // Validación de roles con soporte de alias
        if (! empty($roles) && ! $user->hasRole($roles)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Acceso no autorizado para tu rol en El SAS Piscícola.',
                    'error' => 'FORBIDDEN_ROLE',
                    'tu_rol' => $user->role,
                    'roles_permitidos' => $roles,
                ], 403);
            }

            abort(403, 'Acceso no autorizado para tu rol en El SAS Piscícola.');
        }

        return $next($request);
    }
}
