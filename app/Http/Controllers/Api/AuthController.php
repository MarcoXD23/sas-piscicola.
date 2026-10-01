<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Inicio de sesión vía API RESTful emitiendo un token Sanctum.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $login = trim($validated['email']);

        // Buscar por email, username o documento
        $user = User::withoutGlobalScopes()
            ->where(function ($query) use ($login) {
                $query->where('email', $login)
                    ->orWhere('username', $login)
                    ->orWhere('document_number', $login);
            })
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Verificar si el usuario ha sido aprobado por el propietario
        if (isset($user->aprobado) && ! $user->aprobado) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tu registro está pendiente de aprobación por el propietario de la finca.',
            ], 403);
        }

        $deviceName = $validated['device_name'] ?? 'api-client';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Autenticación exitosa',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tenant_id' => $user->tenant_id ?? $user->finca_id,
                'rol' => $user->rol ?? $user->role,
                'roles_asignados' => $user->roles_asignados ?? [],
                'aprobado' => (bool) $user->aprobado,
            ],
        ], 200);
    }

    /**
     * Cierre de sesión y revocación del token Sanctum actual.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión cerrada y token revocado exitosamente.',
        ]);
    }

    /**
     * Obtiene el perfil y tenant del usuario autenticado.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tenant_id' => $user->tenant_id ?? $user->finca_id,
                'rol' => $user->rol ?? $user->role,
                'roles_asignados' => $user->roles_asignados ?? [],
                'aprobado' => (bool) $user->aprobado,
            ],
        ]);
    }
}
