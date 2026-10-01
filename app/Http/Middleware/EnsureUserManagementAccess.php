<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserManagementAccess
{
    /**
     * Valida que solo el rol propietario tenga acceso total a la gestión de personal y usuarios.
     * Los demás roles (tecnico_acuicola, operario_alimentador, celador) tienen el acceso denegado.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }

            return redirect()->route('login');
        }

        // El propietario y el administrador tienen acceso a la gestión de personal
        if (! $user->canManageUsers() && ! $user->isPropietario() && ! $user->isAdmin()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Acceso denegado: solo el personal autorizado tiene permiso para gestionar personal.',
                    'error' => 'FORBIDDEN_USER_MANAGEMENT',
                ], 403);
            }

            abort(403, 'Acceso denegado: solo el personal autorizado tiene permiso para gestionar personal.');
        }

        return $next($request);
    }
}
