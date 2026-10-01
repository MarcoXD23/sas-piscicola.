<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Finca;
use App\Models\User;
use App\Notifications\NuevoTrabajadorRegistradoNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Muestra la pantalla de registro para nuevos trabajadores.
     */
    public function create(): View
    {
        $fincas = Finca::select('id', 'nombre', 'codigo')->get();

        return view('auth.register', compact('fincas'));
    }

    /**
     * Procesa la solicitud de registro y notifica por correo a los administradores.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'document_number' => ['required', 'string', 'max:50', 'unique:'.User::class.',document_number'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:191', 'unique:'.User::class.',email'],
            'finca_id' => ['nullable', 'integer', 'exists:fincas,id'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'name.required' => 'El nombre completo del trabajador es obligatorio.',
            'document_number.required' => 'La cédula de ciudadanía es obligatoria.',
            'document_number.unique' => 'Esta cédula de ciudadanía ya se encuentra registrada en el sistema.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Este correo electrónico ya se encuentra registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $defaultFincaId = $request->finca_id ?? Finca::first()?->id ?? 1;

        $user = User::create([
            'name' => trim((string) $request->name),
            'document_number' => trim((string) $request->document_number),
            'email' => trim((string) $request->email),
            'username' => trim((string) $request->document_number),
            'finca_id' => $defaultFincaId,
            'password' => Hash::make($request->string('password')),
            'role' => User::ROLE_PENDIENTE,
            'employment_type' => User::TYPE_TEMPORAL,
        ]);

        event(new Registered($user));

        // Notificar por correo electrónico a los Administradores y Jefes de la Finca
        $administradores = User::where('finca_id', $defaultFincaId)
            ->whereIn('role', [
                User::ROLE_ADMIN,
                User::ROLE_ADMINISTRADOR,
                User::ROLE_JEFE,
                User::ROLE_JEFE_MAYOR,
                User::ROLE_JEFE_FINCA,
                User::ROLE_OWNER,
            ])
            ->get();

        if ($administradores->isEmpty()) {
            $administradores = User::whereIn('role', [
                User::ROLE_ADMIN,
                User::ROLE_ADMINISTRADOR,
                User::ROLE_JEFE,
                User::ROLE_JEFE_MAYOR,
                User::ROLE_JEFE_FINCA,
                User::ROLE_OWNER,
            ])->get();
        }

        foreach ($administradores as $admin) {
            try {
                $admin->notify(new NuevoTrabajadorRegistradoNotification($user));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Iniciar sesión autenticada con estado 'pendiente'
        Auth::login($user);

        return redirect()->route('auth.pending-approval')
            ->with('status', 'Registro recibido correctamente. Se ha notificado al Administrador para la asignación de tu rol.');
    }

    /**
     * Muestra la pantalla informativa de espera de aprobación.
     */
    public function pendingApproval(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user && ! $user->isPendiente()) {
            return redirect($user->dashboardRoute());
        }

        return view('auth.pending-approval', compact('user'));
    }
}
