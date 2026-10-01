<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleBasedLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200)
            ->assertSee('El SAS Piscícola')
            ->assertSee('Administrador')
            ->assertSee('Trabajador')
            ->assertSee('Contraseña');
    }

    public function test_jefe_can_login_with_corporate_email_and_redirects_to_dashboard(): void
    {
        $jefe = User::factory()->create([
            'email' => 'jefe@el-sas.com',
            'role' => User::ROLE_JEFE,
            'password' => Hash::make('Segura123*'),
        ]);

        $response = $this->post(route('login'), [
            '_token' => csrf_token(),
            'login' => 'jefe@el-sas.com',
            'password' => 'Segura123*',
            'access_type' => 'admin',
        ]);

        $this->assertAuthenticatedAs($jefe);
        $response->assertRedirect(route('dashboard.index'));
        $response->assertSessionHas('success', 'Bienvenido, Jefe de Finca. Tienes acceso global y gerencial al sistema.');
    }

    public function test_admin_can_login_with_corporate_email_and_redirects_to_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@el-sas.com',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('AdminPass123!'),
        ]);

        $response = $this->post(route('login'), [
            '_token' => csrf_token(),
            'login' => 'admin@el-sas.com',
            'password' => 'AdminPass123!',
            'access_type' => 'admin',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('dashboard.index'));
        $response->assertSessionHas('success', 'Bienvenido, Administrador. Panel de control operativo activo.');
    }

    public function test_admin_or_jefe_cannot_login_without_corporate_email(): void
    {
        User::factory()->create([
            'email' => 'gerente@el-sas.com',
            'username' => 'gerente_sas',
            'role' => User::ROLE_JEFE_FINCA,
            'password' => Hash::make('Password123!'),
        ]);

        // Intentar ingresar con username en vez de correo corporativo
        $response = $this->post(route('login'), [
            '_token' => csrf_token(),
            'login' => 'gerente_sas',
            'password' => 'Password123!',
            'access_type' => 'admin',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors([
            'login' => 'El Jefe de Finca y los Administradores deben ingresar obligatoriamente con su correo electrónico corporativo.',
        ]);
    }

    public function test_worker_can_login_with_username_and_redirects_to_dashboard(): void
    {
        $worker = User::factory()->create([
            'name' => 'Carlos Pérez',
            'username' => 'carlos.perez',
            'role' => User::ROLE_WORKER,
            'password' => Hash::make('MiClavePersonal456'),
        ]);

        $response = $this->post(route('login'), [
            '_token' => csrf_token(),
            'login' => 'carlos.perez',
            'password' => 'MiClavePersonal456',
            'access_type' => 'worker',
        ]);

        $this->assertAuthenticatedAs($worker);
        $response->assertRedirect(route('dashboard.index'));
        $response->assertSessionHas('success', 'Bienvenido, Carlos Pérez. Estación de labores asignada.');
    }

    public function test_worker_can_login_with_document_number_and_redirects_to_dashboard(): void
    {
        $worker = User::factory()->create([
            'name' => 'Luis Mendoza',
            'username' => 'luis.mendoza',
            'document_number' => '1070987654',
            'role' => User::ROLE_TRABAJADOR,
            'password' => Hash::make('ClaveLuis2026'),
        ]);

        // Iniciar sesión con número de cédula
        $response = $this->post(route('login'), [
            '_token' => csrf_token(),
            'login' => '1070987654',
            'password' => 'ClaveLuis2026',
            'access_type' => 'worker',
        ]);

        $this->assertAuthenticatedAs($worker);
        $response->assertRedirect(route('dashboard.index'));
    }

    public function test_tab_validation_prevents_worker_from_using_admin_tab(): void
    {
        User::factory()->create([
            'email' => 'operario@ejemplo.com',
            'username' => 'operario1',
            'role' => User::ROLE_WORKER,
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->post(route('login'), [
            '_token' => csrf_token(),
            'login' => 'operario@ejemplo.com',
            'password' => 'Password123!',
            'access_type' => 'admin',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['login']);
    }

    public function test_tab_validation_prevents_admin_from_using_worker_tab(): void
    {
        User::factory()->create([
            'email' => 'admin@el-sas.com',
            'username' => 'admin_juan',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->post(route('login'), [
            '_token' => csrf_token(),
            'login' => 'admin@el-sas.com',
            'password' => 'Password123!',
            'access_type' => 'worker',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['login']);
    }

    public function test_invalid_password_fails_login(): void
    {
        User::factory()->create([
            'email' => 'admin@el-sas.com',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('CorrectPassword'),
        ]);

        $response = $this->post(route('login'), [
            '_token' => csrf_token(),
            'login' => 'admin@el-sas.com',
            'password' => 'WrongPassword',
            'access_type' => 'admin',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['login']);
    }

    public function test_login_with_role_dropdown_redirects_to_exact_role_dashboards(): void
    {
        // 1. Jefe Mayor
        $jefe = User::factory()->create([
            'email' => 'jefe.test@finca.com',
            'role' => User::ROLE_JEFE_MAYOR,
            'password' => Hash::make('password123'),
        ]);

        $resJefe = $this->post(route('login'), [
            '_token' => csrf_token(),
            'login' => 'jefe.test@finca.com',
            'password' => 'password123',
            'role' => 'jefe_mayor',
        ]);

        $this->assertAuthenticatedAs($jefe);
        $resJefe->assertRedirect(url('/jefe/dashboard'));
        $this->post(route('logout'));

        // 2. Administrador
        $admin = User::factory()->create([
            'email' => 'admin.test@finca.com',
            'role' => User::ROLE_ADMINISTRADOR,
            'password' => Hash::make('password123'),
        ]);

        $resAdmin = $this->post(route('login'), [
            '_token' => csrf_token(),
            'login' => 'admin.test@finca.com',
            'password' => 'password123',
            'role' => 'administrador',
        ]);

        $this->assertAuthenticatedAs($admin);
        $resAdmin->assertRedirect(url('/admin/dashboard'));
        $this->post(route('logout'));

        // 3. Trabajador de Campo
        $worker = User::factory()->create([
            'email' => 'worker.test@finca.com',
            'role' => User::ROLE_TRABAJADOR,
            'password' => Hash::make('password123'),
        ]);

        $resWorker = $this->post(route('login'), [
            '_token' => csrf_token(),
            'login' => 'worker.test@finca.com',
            'password' => 'password123',
            'role' => 'trabajador',
        ]);

        $this->assertAuthenticatedAs($worker);
        $resWorker->assertRedirect(url('/trabajador/dashboard'));
        $this->post(route('logout'));

        // 4. Celador Nocturno
        $celador = User::factory()->create([
            'email' => 'celador.test@finca.com',
            'role' => User::ROLE_CELADOR_NOCTURNO,
            'password' => Hash::make('password123'),
        ]);

        $resCelador = $this->post(route('login'), [
            '_token' => csrf_token(),
            'login' => 'celador.test@finca.com',
            'password' => 'password123',
            'role' => 'celador_nocturno',
        ]);

        $this->assertAuthenticatedAs($celador);
        $resCelador->assertRedirect(url('/celador/dashboard'));
    }

    public function test_login_rejects_when_selected_role_does_not_match_user_account(): void
    {
        User::factory()->create([
            'email' => 'jefe.falso@finca.com',
            'role' => User::ROLE_JEFE_MAYOR,
            'password' => Hash::make('password123'),
        ]);

        // El usuario es jefe mayor pero selecciona "trabajador" en el dropdown
        $response = $this->post(route('login'), [
            '_token' => csrf_token(),
            'login' => 'jefe.falso@finca.com',
            'password' => 'password123',
            'role' => 'trabajador',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors([
            'role' => 'El rol seleccionado no corresponde a este usuario.',
        ]);
    }
}
