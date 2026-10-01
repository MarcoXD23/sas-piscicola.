<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Muestra la pantalla de inicio de sesión premium con selección de roles.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Procesa la solicitud de autenticación y redirige según el rol del usuario.
     */
    public function store(LoginRequest $request): Response|RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        if ($request->wantsJson() || ! $request->has('_token')) {
            return response()->noContent();
        }

        $user = $request->user();
        $selectedRole = (string) $request->input('role');

        if ($user->isPendiente()) {
            return redirect()->route('auth.pending-approval')
                ->with('info', 'Tu registro fue completado exitosamente. Tu cuenta está en espera de que el Administrador valide tus datos y te asigne tu rol operativo.');
        }

        $welcomeMessage = match (true) {
            $user->role === User::ROLE_JEFE || $user->role === User::ROLE_JEFE_FINCA => 'Bienvenido, Jefe de Finca. Tienes acceso global y gerencial al sistema.',
            $user->role === User::ROLE_PROPIETARIO => 'Bienvenido, Propietario / Gerente General. Tienes acceso global y gerencial al sistema.',
            $user->isJefe() => 'Bienvenido, Jefe Mayor. Tienes acceso global y gerencial al sistema.',
            $user->isAdmin() => 'Bienvenido, Administrador. Panel de control operativo activo.',
            $user->isCelador() => 'Bienvenido, Celador Nocturno. Módulo de guardia activo.',
            default => 'Bienvenido, '.($user->name ?? 'Trabajador').'. Estación de labores asignada.',
        };

        $targetUrl = match ($selectedRole) {
            'propietario' => url('/admin/dashboard'),
            'jefe_mayor' => url('/jefe/dashboard'),
            'administrador' => url('/admin/dashboard'),
            'trabajador', 'operario_campo' => url('/trabajador/dashboard'),
            'celador_nocturno', 'celador' => url('/celador/dashboard'),
            default => route('dashboard.index'),
        };

        return redirect()->intended($targetUrl)
            ->with('success', $welcomeMessage);
    }

    /**
     * Cierra la sesión activa del usuario.
     */
    public function destroy(Request $request): Response|RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($request->wantsJson() || ! $request->has('_token')) {
            return response()->noContent();
        }

        return redirect()->route('login')
            ->with('info', 'Has cerrado tu sesión de forma segura.');
    }
}
