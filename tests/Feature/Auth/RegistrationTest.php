<?php

namespace Tests\Feature\Auth;

use App\Models\Finca;
use App\Models\User;
use App\Notifications\CuentaAprobadaNotification;
use App\Notifications\NuevoTrabajadorRegistradoNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $finca = Finca::create([
            'nombre' => 'Finca Piscícola La Esperanza',
            'codigo' => 'ESP-01',
        ]);

        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertSee('Registro de Personal de Finca');
        $response->assertSee('Nombre Completo');
        $response->assertSee('Cédula de Ciudadanía');
        $response->assertSee('Correo Electrónico');
        $response->assertSee('Contraseña');
    }

    public function test_new_worker_can_register_and_administrator_is_notified(): void
    {
        Notification::fake();

        $finca = Finca::create([
            'nombre' => 'Finca Piscícola Principal',
            'codigo' => 'SAS-01',
        ]);

        $admin = User::factory()->create([
            'name' => 'Administrador Finca',
            'email' => 'admin@finca.com',
            'role' => User::ROLE_ADMINISTRADOR,
            'finca_id' => $finca->id,
        ]);

        $response = $this->post(route('register'), [
            'name' => 'Carlos Rodríguez',
            'document_number' => '1070987654',
            'email' => 'carlos.rodriguez@trabajador.com',
            'finca_id' => $finca->id,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertAuthenticated();

        $newUser = User::where('email', 'carlos.rodriguez@trabajador.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Carlos Rodríguez', $newUser->name);
        $this->assertEquals('1070987654', $newUser->document_number);
        $this->assertEquals(User::ROLE_PENDIENTE, $newUser->role);
        $this->assertTrue($newUser->isPendiente());

        // El trabajador es redirigido a la pantalla de espera de aprobación
        $response->assertRedirect(route('auth.pending-approval'));

        // Se verifica que la notificación por correo fue despachada al administrador
        Notification::assertSentTo($admin, NuevoTrabajadorRegistradoNotification::class, function ($notification) use ($newUser) {
            return $notification->worker->id === $newUser->id;
        });
    }

    public function test_pending_worker_is_restricted_to_pending_approval_view(): void
    {
        $finca = Finca::create([
            'nombre' => 'Finca Piscícola Principal',
            'codigo' => 'SAS-01',
        ]);

        $worker = User::factory()->create([
            'name' => 'Operario Pendiente',
            'email' => 'pendiente@finca.com',
            'document_number' => '99887766',
            'role' => User::ROLE_PENDIENTE,
            'finca_id' => $finca->id,
        ]);

        // Intentar ingresar al dashboard de trabajador
        $response = $this->actingAs($worker)->get(route('trabajador.dashboard'));
        $response->assertRedirect(route('auth.pending-approval'));

        // Intentar ingresar a la ruta home
        $homeResponse = $this->actingAs($worker)->get('/');
        $homeResponse->assertRedirect(route('auth.pending-approval'));

        // Ver pantalla de aprobación pendiente
        $pendingViewResponse = $this->actingAs($worker)->get(route('auth.pending-approval'));
        $pendingViewResponse->assertStatus(200);
        $pendingViewResponse->assertSee('Cuenta Pendiente de Activación');
        $pendingViewResponse->assertSee('Operario Pendiente');
        $pendingViewResponse->assertSee('99887766');
    }

    public function test_administrator_can_view_pending_workers_and_assign_role(): void
    {
        Notification::fake();

        $finca = Finca::create([
            'nombre' => 'Finca Piscícola Principal',
            'codigo' => 'SAS-01',
        ]);

        $admin = User::factory()->create([
            'name' => 'Ing. Administrador',
            'email' => 'admin@finca.com',
            'role' => User::ROLE_PROPIETARIO,
            'rol' => User::ROLE_PROPIETARIO,
            'finca_id' => $finca->id,
        ]);

        $pendingWorker = User::factory()->create([
            'name' => 'Aspirante Juan',
            'email' => 'juan@aspirante.com',
            'document_number' => '10998877',
            'role' => User::ROLE_PENDIENTE,
            'finca_id' => $finca->id,
        ]);

        // El administrador ingresa al módulo de personal
        $indexResponse = $this->actingAs($admin)->get(route('admin.personal.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Aspirante Juan');
        $indexResponse->assertSee('10998877');
        $indexResponse->assertSee('Aprobar y Asignar Rol');

        // El administrador asigna el rol de Trabajador de Campo con contrato a destajo
        $assignResponse = $this->actingAs($admin)->post(route('admin.personal.asignar-rol', $pendingWorker), [
            'role' => 'trabajador',
            'employment_type' => 'destajo_semanal',
        ]);

        $assignResponse->assertRedirect(route('admin.personal.index'));
        $assignResponse->assertSessionHas('success');

        // Verificar que el usuario ahora tiene el rol asignado
        $pendingWorker->refresh();
        $this->assertContains($pendingWorker->rol, ['operario_alimentador', 'trabajador']);
        $this->assertEquals('destajo_semanal', $pendingWorker->employment_type);
        $this->assertFalse($pendingWorker->isPendiente());
        $this->assertTrue($pendingWorker->isWorker());

        // Se verifica que se notificó al trabajador de la activación de su cuenta
        Notification::assertSentTo($pendingWorker, CuentaAprobadaNotification::class);

        // Ahora el trabajador puede acceder a su dashboard de campo
        $workerAccessResponse = $this->actingAs($pendingWorker)->get(route('trabajador.dashboard'));
        $workerAccessResponse->assertStatus(200);
    }
}
