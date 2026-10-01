<?php

namespace App\Http\Controllers;

use App\Models\Finca;
use App\Models\InventarioAlimento;
use App\Models\PlanSuscripcion;
use App\Models\Pond;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuscripcionController extends Controller
{
    /**
     * Panel Super-Admin: Listado de fincas cliente con estado de suscripción, límites de estanques y pagos.
     */
    public function panelSuperAdmin(Request $request): JsonResponse
    {
        $fincas = Finca::withCount(['users', 'ponds'])->with(['suscripciones.plan'])->get();

        $clientes = $fincas->map(function (Finca $finca) {
            $suscripcion = $finca->suscripciones->sortByDesc('id')->first();
            $plan = $suscripcion?->plan;

            return [
                'finca_id' => $finca->id,
                'nombre' => $finca->nombre,
                'nit' => $finca->nit,
                'municipio' => $finca->municipio,
                'departamento' => $finca->departamento,
                'registro_ica' => $finca->registro_ica,
                'plan_actual' => $plan ? [
                    'id' => $plan->id,
                    'nombre' => $plan->nombre,
                    'max_estanques' => $plan->max_estanques,
                    'max_usuarios' => $plan->max_usuarios,
                    'precio_mensual' => (float) $plan->precio_mensual,
                ] : null,
                'uso' => [
                    'estanques_activos' => $finca->ponds_count,
                    'estanques_permitidos' => $plan?->max_estanques ?? 10,
                    'limite_estanques_superado' => $plan ? $finca->ponds_count > $plan->max_estanques : false,
                    'usuarios_registrados' => $finca->users_count,
                    'usuarios_permitidos' => $plan?->max_usuarios ?? 5,
                ],
                'suscripcion' => $suscripcion ? [
                    'id' => $suscripcion->id,
                    'estado' => $suscripcion->estado,
                    'fecha_inicio' => $suscripcion->fecha_inicio?->toDateString(),
                    'fecha_vencimiento' => $suscripcion->fecha_vencimiento?->toDateString(),
                    'esta_vigente' => $suscripcion->estaVigente(),
                    'monto_ultimo_pago' => (float) $suscripcion->monto_ultimo_pago,
                    'ultimo_pago_at' => $suscripcion->ultimo_pago_at?->toIso8601String(),
                ] : [
                    'estado' => 'sin_suscripcion',
                    'esta_vigente' => false,
                ],
            ];
        });

        return response()->json([
            'message' => 'Panel SaaS SuperAdmin: Listado de fincas clientes recuperado.',
            'total_fincas' => $clientes->count(),
            'fincas_activas' => $clientes->where('suscripcion.esta_vigente', true)->count(),
            'fincas_suspendidas' => $clientes->where('suscripcion.estado', 'suspendida')->count(),
            'data' => $clientes,
        ]);
    }

    /**
     * Suspender acceso a una finca cliente por mora o decisión comercial.
     */
    public function suspenderAcceso(int|string $fincaId): JsonResponse
    {
        $finca = Finca::findOrFail($fincaId);
        $suscripcion = $finca->suscripciones()->latest('id')->first();

        if ($suscripcion) {
            $suscripcion->update(['estado' => Suscripcion::ESTADO_SUSPENDIDA]);
        }

        $finca->actualizarConfig('estado_acceso', 'suspendido');

        return response()->json([
            'message' => "Acceso a la finca '{$finca->nombre}' suspendido correctamente.",
            'finca_id' => $finca->id,
            'estado' => 'suspendida',
        ]);
    }

    /**
     * Activar / restaurar acceso de una finca cliente tras pago.
     */
    public function activarAcceso(Request $request, int|string $fincaId): JsonResponse
    {
        $finca = Finca::findOrFail($fincaId);
        $suscripcion = $finca->suscripciones()->latest('id')->first();

        $diasExtension = $request->integer('dias_extension', 30);

        if ($suscripcion) {
            $suscripcion->update([
                'estado' => Suscripcion::ESTADO_ACTIVA,
                'fecha_vencimiento' => now()->addDays($diasExtension)->toDateString(),
                'ultimo_pago_at' => now(),
                'monto_ultimo_pago' => $suscripcion->plan?->precio_mensual ?? 150000,
                'referencia_pago' => $request->input('referencia_pago', 'TRANSAC-'.strtoupper(Str::random(8))),
            ]);
        }

        $finca->actualizarConfig('estado_acceso', 'activo');

        return response()->json([
            'message' => "Acceso a la finca '{$finca->nombre}' reactivado exitosamente.",
            'finca_id' => $finca->id,
            'estado' => 'activa',
            'nueva_fecha_vencimiento' => $suscripcion?->fecha_vencimiento?->toDateString(),
        ]);
    }

    /**
     * Impersonation / Soporte Técnico: Iniciar sesión temporal en nombre de un usuario o administrador de la finca.
     */
    public function impersonate(Request $request, int|string $userId): JsonResponse
    {
        $targetUser = User::findOrFail($userId);
        $currentUser = $request->user();

        // Validar que el usuario que ejecuta sea superadmin o jefe_mayor
        if ($currentUser && ! in_array($currentUser->role, ['superadmin', 'jefe_mayor', 'owner'])) {
            return response()->json([
                'message' => 'No cuenta con privilegios para suplantar o dar soporte técnico a este usuario.',
            ], 403);
        }

        // Crear token de soporte temporal
        $token = $targetUser->createToken('support-impersonation-token', ['*'], now()->addHours(2))->plainTextToken;

        return response()->json([
            'message' => "Sesión de soporte generada exitosamente para el usuario {$targetUser->name}.",
            'impersonated_user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'role' => $targetUser->role,
                'finca_id' => $targetUser->finca_id,
                'finca_nombre' => $targetUser->finca?->nombre,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in_hours' => 2,
        ]);
    }

    /**
     * Flujo de Onboarding completo de una nueva finca cliente:
     * 1. Registro de datos legales e ICA de la finca
     * 2. Selección del plan de suscripción
     * 3. Creación del usuario administrador inicial
     * 4. Creación de estanques iniciales y stock base de alimento
     */
    public function onboardingFinca(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'nit' => ['required', 'string', 'max:50'],
            'departamento' => ['required', 'string', 'max:100'],
            'municipio' => ['required', 'string', 'max:100'],
            'registro_ica' => ['nullable', 'string', 'max:100'],
            'responsable_tecnico' => ['nullable', 'string', 'max:150'],
            'plan_id' => ['required', 'exists:planes_suscripcion,id'],
            'admin_nombre' => ['required', 'string', 'max:150'],
            'admin_email' => ['required', 'email', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],
            'cantidad_estanques_inicial' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $plan = PlanSuscripcion::findOrFail($validated['plan_id']);

        $resultado = DB::transaction(function () use ($validated, $plan) {
            // 1. Crear Finca
            $finca = Finca::create([
                'nombre' => $validated['nombre'],
                'codigo' => 'PIS-'.strtoupper(Str::random(5)),
                'nit' => $validated['nit'],
                'departamento' => $validated['departamento'],
                'municipio' => $validated['municipio'],
                'registro_ica' => $validated['registro_ica'] ?? null,
                'responsable_tecnico' => $validated['responsable_tecnico'] ?? null,
                'ubicacion' => "{$validated['municipio']}, {$validated['departamento']}",
                'configuraciones' => Finca::DEFAULT_CONFIG,
            ]);

            // 2. Crear Suscripción inicial por 30 días
            $suscripcion = Suscripcion::create([
                'finca_id' => $finca->id,
                'plan_id' => $plan->id,
                'estado' => Suscripcion::ESTADO_ACTIVA,
                'fecha_inicio' => now()->toDateString(),
                'fecha_vencimiento' => now()->addDays(30)->toDateString(),
                'ultimo_pago_at' => now(),
                'monto_ultimo_pago' => $plan->precio_mensual,
                'referencia_pago' => 'ONBOARDING-'.strtoupper(Str::random(8)),
            ]);

            // 3. Crear Usuario Administrador de la Finca
            $adminUser = User::create([
                'name' => $validated['admin_nombre'],
                'email' => $validated['admin_email'],
                'username' => strtolower(Str::slug($validated['admin_nombre'], '.')),
                'password' => Hash::make($validated['admin_password']),
                'role' => User::ROLE_ADMINISTRADOR,
                'employment_type' => 'fijo',
                'finca_id' => $finca->id,
            ]);

            // 4. Crear estanques iniciales
            $numPonds = (int) ($validated['cantidad_estanques_inicial'] ?? 3);
            $limitPonds = min($numPonds, $plan->max_estanques);

            for ($i = 1; $i <= $limitPonds; $i++) {
                Pond::create([
                    'finca_id' => $finca->id,
                    'name' => sprintf('Estanque %02d', $i),
                    'code' => sprintf('EST-%02d', $i),
                    'numero_lote' => sprintf('LOTE-%02d', $i),
                    'surface_area' => 500.00,
                    'depth' => 1.50,
                    'fish_population' => 2000,
                    'fingerlings_stocked' => 2000,
                    'average_weight' => 50.00,
                    'biomass' => 100.00,
                    'status' => 'activo',
                ]);
            }

            // 5. Inicializar inventario de alimento base
            InventarioAlimento::create([
                'finca_id' => $finca->id,
                'tipo_concentrado' => 'Iniciación 45%',
                'proteina_porcentaje' => 45.00,
                'stock_actual_kg' => 200.00,
                'stock_minimo_alerta_kg' => 80.00,
                'costo_unitario' => 3800.00,
            ]);

            InventarioAlimento::create([
                'finca_id' => $finca->id,
                'tipo_concentrado' => 'Levante 34%',
                'proteina_porcentaje' => 34.00,
                'stock_actual_kg' => 500.00,
                'stock_minimo_alerta_kg' => 100.00,
                'costo_unitario' => 3200.00,
            ]);

            return [
                'finca' => $finca,
                'suscripcion' => $suscripcion,
                'admin_user' => $adminUser,
                'estanques_creados' => $limitPonds,
            ];
        });

        return response()->json([
            'message' => 'Finca cliente dada de alta exitosamente en AquaSmart SaaS.',
            'data' => [
                'finca' => $resultado['finca'],
                'plan' => $plan->nombre,
                'suscripcion' => $resultado['suscripcion'],
                'admin_user' => [
                    'id' => $resultado['admin_user']->id,
                    'name' => $resultado['admin_user']->name,
                    'email' => $resultado['admin_user']->email,
                    'role' => $resultado['admin_user']->role,
                ],
                'estanques_creados' => $resultado['estanques_creados'],
            ],
        ], 201);
    }
}
