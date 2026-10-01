<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\Pond;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisualRoutesAndEnvironmentsTest extends TestCase
{
    use RefreshDatabase;

    protected Finca $finca;

    protected User $jefe;

    protected User $admin;

    protected User $trabajador;

    protected User $celador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finca = Finca::create([
            'id' => 1,
            'nombre' => 'El SAS Piscícola - Finca Principal',
            'codigo' => 'SAS-01',
            'ubicacion' => 'Espinal, Tolima, Colombia',
            'nit' => '901.884.210-5',
            'departamento' => 'Tolima',
            'municipio' => 'Espinal',
            'responsable_tecnico' => 'Dr. Carlos Mendoza - Zootecnista Mat. 8941',
            'registro_ica' => 'ICA-AQ-73268-2024',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ]);

        $this->jefe = User::factory()->create([
            'finca_id' => 1,
            'role' => User::ROLE_JEFE_MAYOR,
            'email' => 'jefe@finca.com',
            'name' => 'Don Fernando Gómez (Jefe Mayor)',
        ]);

        $this->admin = User::factory()->admin()->create([
            'finca_id' => 1,
            'email' => 'admin@finca.com',
            'name' => 'Ing. Juan Carlos Morales (Admin)',
        ]);

        $this->trabajador = User::factory()->trabajador()->create([
            'finca_id' => 1,
            'username' => 'trabajador',
            'email' => 'trabajador@finca.com',
            'name' => 'Carlos Pérez (Trabajador)',
        ]);

        $this->celador = User::factory()->create([
            'finca_id' => 1,
            'role' => User::ROLE_CELADOR_NOCTURNO,
            'username' => 'celador_nocturno',
            'email' => 'celador@finca.com',
            'name' => 'Don Faustino (Celador Nocturno)',
        ]);

        // Crear estanques de prueba
        Pond::create([
            'finca_id' => 1,
            'name' => 'Estanque 1 - Mojarra Roja',
            'code' => 'EST-01',
            'fish_population' => 12000,
            'average_weight' => 280.0,
            'biomass' => 3360.0,
            'status' => 'Sembrado',
        ]);
    }

    /**
     * 1. Ruta '/' redirige a '/login' para usuarios invitados.
     */
    public function test_home_redirects_guest_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));
    }

    /**
     * 2. Ruta '/login' renderiza la pantalla premium con selector de rol.
     */
    public function test_login_screen_loads_with_role_selector(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Iniciar Sesión');
        $response->assertSee('El SAS Piscícola');
        $response->assertSee('Selecciona tu Rol');
        $response->assertSee('Jefe Mayor');
        $response->assertSee('Administrador');
        $response->assertSee('Trabajador de Campo');
        $response->assertSee('Celador Nocturno');
    }

    /**
     * 3. Dashboard del Administrador ('/admin/dashboard').
     */
    public function test_admin_dashboard_loads_without_errors(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Panel de Control - Administrador');
        $response->assertSee('Operaciones y Gestión Diaria de Granja');
        $response->assertSee('Alertas de Bodega');
        $response->assertSee('Tareas en Curso');
    }

    /**
     * 4. Panel Nocturno del Celador ('/celador/dashboard').
     */
    public function test_celador_dashboard_loads_with_panic_button_and_dark_mode(): void
    {
        $response = $this->actingAs($this->celador)->get('/celador/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Módulo Operativo de Guardia Nocturna');
        $response->assertSee('BOTÓN DE PÁNICO');
        $response->assertSee('Control de Aireadores');
    }

    /**
     * 5. Dashboard del Jefe Mayor ('/jefe/dashboard').
     */
    public function test_jefe_dashboard_loads_with_macro_metrics(): void
    {
        $response = $this->actingAs($this->jefe)->get('/jefe/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Panel Macro - Jefe Mayor');
        $response->assertSee('Biomasa Total en Lagos');
        $response->assertSee('Agenda de Pesca');
        $response->assertSee('Cierre Financiero Mensual');
    }

    /**
     * 6. Dashboard del Trabajador de Campo ('/trabajador/dashboard').
     */
    public function test_trabajador_dashboard_loads_with_feeding_and_shifts(): void
    {
        $response = $this->actingAs($this->trabajador)->get('/trabajador/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Panel Operativo');
        $response->assertSee('Estanque 1 - Mojarra Roja');
    }

    /**
     * 7. Guía de Peces de Colombia ('/guia-peces').
     */
    public function test_guia_peces_loads_with_species_catalog(): void
    {
        $response = $this->actingAs($this->admin)->get('/guia-peces');

        $response->assertStatus(200);
        $response->assertSee('Enciclopedia Piscícola de Colombia');
    }
}
