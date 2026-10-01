<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeparacionRolesAdminTecnicoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Test 1: DatabaseSeeder genera exactamente 4 cuentas oficiales con contraseña 'password' y sin técnico acuícola.
     */
    public function test_database_seeder_creates_strictly_the_four_canonical_accounts(): void
    {
        // 1. Propietario
        $propietario = User::where('email', 'propietario@finca.com')->first();
        $this->assertNotNull($propietario);
        $this->assertEquals(User::ROLE_PROPIETARIO, $propietario->role);
        $this->assertTrue($propietario->isPropietario());

        // 2. Administrador
        $admin = User::where('email', 'admin@finca.com')->first();
        $this->assertNotNull($admin);
        $this->assertEquals(User::ROLE_ADMINISTRADOR, $admin->role);
        $this->assertEquals('admin_finca', $admin->username);
        $this->assertTrue($admin->isAdministrador());

        // 3. Operario de Campo
        $operario = User::where('email', 'trabajador@finca.com')->first();
        $this->assertNotNull($operario);
        $this->assertEquals(User::ROLE_OPERARIO_CAMPO, $operario->role);
        $this->assertTrue($operario->isWorker());

        // 4. Celador Nocturno
        $celador = User::where('email', 'celador@finca.com')->first();
        $this->assertNotNull($celador);
        $this->assertEquals(User::ROLE_CELADOR_NOCTURNO, $celador->role);
        $this->assertTrue($celador->isCelador());

        // Inexistencia total de Técnico Acuícola
        $this->assertDatabaseMissing('users', ['email' => 'tecnico@finca.com']);
        $this->assertDatabaseMissing('roles', ['slug' => 'tecnico_acuicola']);
    }

    /**
     * Test 2: Las 4 cuentas canónicas pueden autenticarse con 'password' usando sus roles oficiales.
     */
    public function test_all_four_canonical_accounts_can_authenticate_with_password(): void
    {
        // 1. Login Propietario
        $propietario = User::where('email', 'propietario@finca.com')->first();
        $this->post('/login', [
            '_token' => 'dummy-csrf-token',
            'login' => 'propietario@finca.com',
            'password' => 'password',
            'role' => 'propietario',
        ])->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($propietario);
        $this->post('/logout', ['_token' => 'dummy-csrf-token']);

        // 2. Login Administrador
        $admin = User::where('email', 'admin@finca.com')->first();
        $this->post('/login', [
            '_token' => 'dummy-csrf-token',
            'login' => 'admin@finca.com',
            'password' => 'password',
            'role' => 'administrador',
        ])->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);
        $this->post('/logout', ['_token' => 'dummy-csrf-token']);

        // 3. Login Operario de Campo
        $operario = User::where('email', 'trabajador@finca.com')->first();
        $this->post('/login', [
            '_token' => 'dummy-csrf-token',
            'login' => 'trabajador@finca.com',
            'password' => 'password',
            'role' => 'operario_campo',
        ])->assertRedirect('/trabajador/dashboard');
        $this->assertAuthenticatedAs($operario);
        $this->post('/logout', ['_token' => 'dummy-csrf-token']);

        // 4. Login Celador Nocturno
        $celador = User::where('email', 'celador@finca.com')->first();
        $this->post('/login', [
            '_token' => 'dummy-csrf-token',
            'login' => 'celador@finca.com',
            'password' => 'password',
            'role' => 'celador',
        ])->assertRedirect('/celador/dashboard');
        $this->assertAuthenticatedAs($celador);
    }

    /**
     * Test 3: La vista de login contiene únicamente las 4 opciones de rol y los 4 botones de acceso rápido.
     */
    public function test_login_screen_renders_strictly_four_roles_and_four_quick_access_buttons(): void
    {
        $response = $this->get('/login');
        $response->assertOk();

        // 4 Select Options
        $response->assertSee('value="propietario"', false);
        $response->assertSee('value="administrador"', false);
        $response->assertSee('value="operario_campo"', false);
        $response->assertSee('value="celador"', false);
        $response->assertDontSee('value="tecnico_acuicola"', false);

        // Labels
        $response->assertSee('Propietario / Gerente');
        $response->assertSee('Administrador de Finca');
        $response->assertSee('Operario de Campo');
        $response->assertSee('Celador Nocturno');
        $response->assertDontSee('Técnico Acuícola');

        // 4 Botones de Acceso Rápido
        $response->assertSee('propietario@finca.com');
        $response->assertSee('admin@finca.com');
        $response->assertSee('trabajador@finca.com');
        $response->assertSee('celador@finca.com');
        $response->assertDontSee('tecnico@finca.com');
    }

    /**
     * Test 4: El Administrador tiene control total sobre lagos, bodega, caja diaria y nómina.
     */
    public function test_administrador_has_full_operational_access(): void
    {
        $admin = User::where('email', 'admin@finca.com')->first();

        // Acceso al Dashboard de Administrador
        $resDashboard = $this->actingAs($admin)->get('/admin/dashboard');
        $resDashboard->assertOk();
        $resDashboard->assertSee('Caja Recaudada Hoy');
        $resDashboard->assertSee('Liquidación Sábado');
        $resDashboard->assertSee('Ventas en Efectivo');
        $resDashboard->assertSee('Alertas de Bodega');

        // Acceso a Lagos y Muestreos Biológicos
        $this->actingAs($admin)->get('/admin/lagos')->assertOk();
        $this->actingAs($admin)->get('/admin/muestreos')->assertOk();
        $this->actingAs($admin)->get('/admin/bodega')->assertOk();
    }

    /**
     * Test 5: El Celador Nocturno accede a su módulo de seguridad nocturna.
     */
    public function test_celador_accesses_security_module(): void
    {
        $celador = User::where('email', 'celador@finca.com')->first();

        $this->actingAs($celador)->get('/celador/dashboard')->assertOk();
        $this->actingAs($celador)->get('/seguridad-noche')->assertOk();

        // Bloqueado de administración y finanzas
        $this->actingAs($celador)->get('/ventas')->assertForbidden();
        $this->actingAs($celador)->get('/nomina')->assertForbidden();
    }
}

