<?php

namespace Tests\Feature;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantIsolationAndPersonalTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantScope::setActiveTenantId(null);
        parent::tearDown();
    }

    public function test_tenant_isolation_user_of_tenant_a_cannot_see_users_of_tenant_b(): void
    {
        $tenantA = Tenant::create([
            'nombre' => 'Finca Acuícola Los Andes',
            'codigo' => 'ANDES-01',
        ]);

        $tenantB = Tenant::create([
            'nombre' => 'Finca Acuícola El Mirador',
            'codigo' => 'MIRADOR-02',
        ]);

        $userA = User::withoutGlobalScopes()->create([
            'name' => 'Operario Andes',
            'email' => 'andes@aquasmart.com',
            'password' => Hash::make('password123'),
            'tenant_id' => $tenantA->id,
            'finca_id' => $tenantA->id,
            'rol' => 'operario_alimentador',
            'aprobado' => true,
        ]);

        $userB = User::withoutGlobalScopes()->create([
            'name' => 'Operario Mirador',
            'email' => 'mirador@aquasmart.com',
            'password' => Hash::make('password123'),
            'tenant_id' => $tenantB->id,
            'finca_id' => $tenantB->id,
            'rol' => 'operario_alimentador',
            'aprobado' => true,
        ]);

        // Simular contexto de sesión de Tenant A
        $this->actingAs($userA);

        $visibleUsersA = User::all();
        $this->assertTrue($visibleUsersA->contains('id', $userA->id));
        $this->assertFalse($visibleUsersA->contains('id', $userB->id));

        // Simular contexto de sesión de Tenant B
        $this->actingAs($userB);

        $visibleUsersB = User::all();
        $this->assertTrue($visibleUsersB->contains('id', $userB->id));
        $this->assertFalse($visibleUsersB->contains('id', $userA->id));
    }

    public function test_api_sanctum_login_authentication_and_user_profile(): void
    {
        $tenant = Tenant::create([
            'nombre' => 'Finca Santa Ana',
            'codigo' => 'SANTA-01',
        ]);

        $user = User::withoutGlobalScopes()->create([
            'name' => 'Carlos Técnico',
            'email' => 'carlos@santaana.com',
            'password' => Hash::make('secreto123'),
            'tenant_id' => $tenant->id,
            'finca_id' => $tenant->id,
            'rol' => 'tecnico_acuicola',
            'roles_asignados' => ['tecnico_acuicola'],
            'aprobado' => true,
        ]);

        // Login vía API
        $response = $this->postJson('/api/login', [
            'email' => 'carlos@santaana.com',
            'password' => 'secreto123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'token',
                'token_type',
                'user' => ['id', 'name', 'email', 'tenant_id', 'rol', 'roles_asignados', 'aprobado'],
            ]);

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        // Perfil autenticado /api/me
        $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/me');
        $meResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'user' => [
                    'id' => $user->id,
                    'email' => 'carlos@santaana.com',
                    'tenant_id' => $tenant->id,
                    'rol' => 'tecnico_acuicola',
                ],
            ]);

        // Logout vía API
        $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/logout');
        $logoutResponse->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    public function test_api_login_rejects_pending_unapproved_user(): void
    {
        $tenant = Tenant::create([
            'nombre' => 'Finca Delicias',
            'codigo' => 'DEL-01',
        ]);

        User::withoutGlobalScopes()->create([
            'name' => 'Pendiente Registro',
            'email' => 'pendiente@delicias.com',
            'password' => Hash::make('clave123'),
            'tenant_id' => $tenant->id,
            'finca_id' => $tenant->id,
            'rol' => 'operario_alimentador',
            'aprobado' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'pendiente@delicias.com',
            'password' => 'clave123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Tu registro está pendiente de aprobación por el propietario de la finca.',
            ]);
    }

    public function test_personal_screen_is_accessible_only_by_propietario(): void
    {
        $tenant = Tenant::create([
            'nombre' => 'Finca San Pedro',
            'codigo' => 'PEDRO-01',
        ]);

        $propietario = User::withoutGlobalScopes()->create([
            'name' => 'Dueño de Finca',
            'email' => 'dueno@sanpedro.com',
            'password' => Hash::make('clave123'),
            'tenant_id' => $tenant->id,
            'finca_id' => $tenant->id,
            'rol' => 'propietario',
            'role' => 'propietario',
            'aprobado' => true,
        ]);

        $operario = User::withoutGlobalScopes()->create([
            'name' => 'Operario Campo',
            'email' => 'operario@sanpedro.com',
            'password' => Hash::make('clave123'),
            'tenant_id' => $tenant->id,
            'finca_id' => $tenant->id,
            'rol' => 'operario_alimentador',
            'role' => 'operario_alimentador',
            'aprobado' => true,
        ]);

        // Propietario tiene acceso
        $this->actingAs($propietario)
            ->get(route('admin.personal.index'))
            ->assertStatus(200);

        // Operario no puede gestionar personal
        $this->actingAs($operario)
            ->get(route('admin.personal.index'))
            ->assertStatus(403);
    }

    public function test_propietario_approves_staff_and_assigns_multiple_field_roles(): void
    {
        $tenant = Tenant::create([
            'nombre' => 'Finca Central',
            'codigo' => 'CEN-01',
        ]);

        $propietario = User::withoutGlobalScopes()->create([
            'name' => 'Gerente Propietario',
            'email' => 'propietario@central.com',
            'password' => Hash::make('clave123'),
            'tenant_id' => $tenant->id,
            'finca_id' => $tenant->id,
            'rol' => 'propietario',
            'role' => 'propietario',
            'aprobado' => true,
        ]);

        $aspirante = User::withoutGlobalScopes()->create([
            'name' => 'Juan Solicitante',
            'email' => 'juan@central.com',
            'password' => Hash::make('clave123'),
            'tenant_id' => $tenant->id,
            'finca_id' => $tenant->id,
            'rol' => 'operario_alimentador',
            'role' => 'pendiente',
            'aprobado' => false,
        ]);

        // Aprobar asignando múltiples roles de campo
        $response = $this->actingAs($propietario)->postJson(route('admin.personal.aprobar', $aspirante), [
            'roles' => ['operario_alimentador', 'celador'],
            'employment_type' => 'fijo',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $aspirante->refresh();
        $this->assertTrue($aspirante->aprobado);
        $this->assertContains('operario_alimentador', $aspirante->roles_asignados);
        $this->assertContains('celador', $aspirante->roles_asignados);
    }

    public function test_propietario_cannot_assign_propietario_role_in_personal_screen(): void
    {
        $tenant = Tenant::create([
            'nombre' => 'Finca La Palma',
            'codigo' => 'PALMA-01',
        ]);

        $propietario = User::withoutGlobalScopes()->create([
            'name' => 'Dueño',
            'email' => 'dueno@palma.com',
            'password' => Hash::make('clave123'),
            'tenant_id' => $tenant->id,
            'finca_id' => $tenant->id,
            'rol' => 'propietario',
            'role' => 'propietario',
            'aprobado' => true,
        ]);

        $aspirante = User::withoutGlobalScopes()->create([
            'name' => 'Empleado',
            'email' => 'empleado@palma.com',
            'password' => Hash::make('clave123'),
            'tenant_id' => $tenant->id,
            'finca_id' => $tenant->id,
            'rol' => 'operario_alimentador',
            'aprobado' => false,
        ]);

        // Intentar asignar 'propietario' debe fallar
        $response = $this->actingAs($propietario)->postJson(route('admin.personal.aprobar', $aspirante), [
            'roles' => ['propietario'],
        ]);

        $response->assertStatus(422);
    }
}
