<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoPiscicolaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerDeliveryAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoPiscicolaSeeder::class);
    }

    /**
     * Prueba Integral 1: El Propietario / Gerente General puede acceder
     * a todas y cada una de las vistas de control gerencial y operativo sin errores (HTTP 200).
     */
    public function test_propietario_can_access_all_core_owner_views(): void
    {
        $propietario = User::where('email', 'propietario@finca.com')->first();
        $this->assertNotNull($propietario);

        // 1. Redirección en la raíz
        $this->actingAs($propietario)->get('/')->assertRedirect('/jefe/dashboard');

        // 2. Dashboards
        $this->actingAs($propietario)->get('/jefe/dashboard')->assertOk();
        $this->actingAs($propietario)->get('/admin/dashboard')->assertOk();
        $this->actingAs($propietario)->get('/dashboard')->assertOk();

        // 3. Operación y Lagos
        $this->actingAs($propietario)->get('/agenda')->assertOk();
        $this->actingAs($propietario)->get('/admin/lagos')->assertOk();
        $this->actingAs($propietario)->get('/admin/muestreos')->assertOk();
        $this->actingAs($propietario)->get('/traslados')->assertOk();
        $this->actingAs($propietario)->get('/desdobles')->assertOk();

        // 4. Bodega y Alimento
        $this->actingAs($propietario)->get('/admin/bodega')->assertOk();
        $this->actingAs($propietario)->get('/admin/inventario-alimento')->assertOk();

        // 5. Módulos Financieros y de Control Exclusivos
        $this->actingAs($propietario)->get('/cosechas')->assertOk();
        $this->actingAs($propietario)->get('/admin/bascula')->assertOk();
        $this->actingAs($propietario)->get('/ventas')->assertOk();
        $this->actingAs($propietario)->get('/admin/ventas')->assertOk();
        $this->actingAs($propietario)->get('/nomina')->assertOk();
        $this->actingAs($propietario)->get('/admin/nomina')->assertOk();

        // 6. Personal y Bitácora de Actividades
        $this->actingAs($propietario)->get('/admin/personal')->assertOk();
        $this->actingAs($propietario)->get('/admin/actividades')->assertOk();

        // 7. Sanidad y Cumplimiento ICA
        $this->actingAs($propietario)->get('/sanidad')->assertOk();
        $this->actingAs($propietario)->get('/admin/reportes/ica-libro-campo')->assertOk();

        // 8. Informes y Ajustes
        $this->actingAs($propietario)->get('/jefe/reporte-mensual')->assertOk();
        $this->actingAs($propietario)->get('/admin/ajustes')->assertOk();
        $this->actingAs($propietario)->get('/guia-peces')->assertOk();
    }

    /**
     * Prueba Integral 2: Autenticación completa con credenciales de Propietario.
     */
    public function test_propietario_login_and_logout_flow(): void
    {
        $response = $this->post('/login', [
            '_token' => 'dummy-csrf-token',
            'login' => 'propietario@finca.com',
            'password' => 'password123',
            'role' => 'propietario',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();

        // Cerrar sesión
        $logoutResponse = $this->post('/logout', ['_token' => 'dummy-csrf-token']);
        $logoutResponse->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Prueba Integral 3: Todos los usuarios de las tarjetas de acceso rápido
     * autentican correctamente con la contraseña predeterminada password123.
     */
    public function test_quick_access_accounts_can_authenticate(): void
    {
        // 1. Propietario
        $this->post('/login', [
            '_token' => 'dummy-csrf-token',
            'login' => 'propietario@finca.com',
            'password' => 'password123',
            'role' => 'propietario',
        ])->assertRedirect();
        $this->assertAuthenticated();
        $this->post('/logout', ['_token' => 'dummy-csrf-token']);

        // 2. Administrador / Técnico Acuícola
        $this->post('/login', [
            '_token' => 'dummy-csrf-token',
            'login' => 'admin@finca.com',
            'password' => 'password123',
            'role' => 'administrador',
        ])->assertRedirect();
        $this->assertAuthenticated();
        $this->post('/logout', ['_token' => 'dummy-csrf-token']);

        // 3. Operario de Campo
        $this->post('/login', [
            '_token' => 'dummy-csrf-token',
            'login' => 'trabajador@finca.com',
            'password' => 'password123',
            'role' => 'trabajador',
        ])->assertRedirect();
        $this->assertAuthenticated();
        $this->post('/logout', ['_token' => 'dummy-csrf-token']);

        // 4. Celador Nocturno
        $this->post('/login', [
            '_token' => 'dummy-csrf-token',
            'login' => 'celador@finca.com',
            'password' => 'password123',
            'role' => 'celador_nocturno',
        ])->assertRedirect();
        $this->assertAuthenticated();
        $this->post('/logout', ['_token' => 'dummy-csrf-token']);
    }

    /**
     * Prueba Integral 4: Protección de privilegios exclusivos.
     * Ningún usuario distinto a Propietario puede acceder a finanzas ni ventas.
     */
    public function test_non_owner_users_strictly_forbidden_from_financials(): void
    {
        $tecnico = User::where('email', 'admin@finca.com')->first();
        $operario = User::where('email', 'trabajador@finca.com')->first();
        $celador = User::where('email', 'celador@finca.com')->first();

        foreach ([$tecnico, $operario, $celador] as $user) {
            $this->actingAs($user)->get('/ventas')->assertStatus(403);
            $this->actingAs($user)->get('/cosechas')->assertStatus(403);
            $this->actingAs($user)->get('/nomina')->assertStatus(403);
        }
    }
}
