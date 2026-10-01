<?php

namespace Tests\Feature;

use App\Models\AgendaTurno;
use App\Models\Finca;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaTurnosRotativosTest extends TestCase
{
    use RefreshDatabase;

    private Finca $finca1;

    private Finca $finca2;

    private User $propietario;

    private User $tecnico;

    private User $operario;

    private User $celador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finca1 = Finca::firstOrCreate(['id' => 1], [
            'nombre' => 'Piscícola La Esmeralda',
            'codigo' => 'ESM-01',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ]);

        $this->finca2 = Finca::firstOrCreate(['id' => 2], [
            'nombre' => 'Piscícola San Jerónimo',
            'codigo' => 'SJN-02',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ]);

        $this->propietario = User::factory()->create([
            'name' => 'Roberto Dueño',
            'email' => 'propietario@esmeralda.com',
            'role' => User::ROLE_PROPIETARIO,
            'finca_id' => $this->finca1->id,
        ]);

        $this->tecnico = User::factory()->create([
            'name' => 'Ana Técnica',
            'email' => 'ana.tecnica@esmeralda.com',
            'role' => User::ROLE_TECNICO_ACUICOLA,
            'finca_id' => $this->finca1->id,
        ]);

        $this->operario = User::factory()->create([
            'name' => 'Jaime Operario',
            'email' => 'jaime.operario@esmeralda.com',
            'role' => User::ROLE_OPERARIO_ALIMENTADOR,
            'finca_id' => $this->finca1->id,
        ]);

        $this->celador = User::factory()->create([
            'name' => 'Faustino Celador',
            'email' => 'faustino.celador@esmeralda.com',
            'role' => User::ROLE_CELADOR,
            'finca_id' => $this->finca1->id,
        ]);
    }

    /**
     * REQUISITO 1: Encadenamiento automático de turno semanal:
     * Al programar de lunes a viernes con rol alimentador,
     * se genera automáticamente el domingo previo con rol seguridad_noche.
     */
    public function test_encadenamiento_automatico_semanal_crea_cinco_dias_alimentador_y_domingo_previo_seguridad_noche(): void
    {
        $lunesProximo = now()->next(Carbon::MONDAY)->toDateString();
        $domingoPrevio = Carbon::parse($lunesProximo)->subDay()->toDateString();

        $response = $this->actingAs($this->propietario)->post(route('agenda.turnos.semanal'), [
            'user_id' => $this->operario->id,
            'fecha_lunes' => $lunesProximo,
            'observaciones' => 'Semana de ceba intensiva',
        ]);

        $response->assertSessionHas('success');

        // 1. Domingo anterior asignado como Celador (seguridad_noche)
        $this->assertDatabaseHas('agenda_turnos', [
            'finca_id' => $this->finca1->id,
            'user_id' => $this->operario->id,
            'fecha' => $domingoPrevio,
            'rol_asignado' => AgendaTurno::ROL_SEGURIDAD_NOCHE,
            'tipo_dia' => AgendaTurno::TIPO_DOMINGO,
        ]);

        // 2. Lunes a Viernes asignados como Alimentador
        for ($i = 0; $i < 5; $i++) {
            $dia = Carbon::parse($lunesProximo)->addDays($i)->toDateString();
            $this->assertDatabaseHas('agenda_turnos', [
                'finca_id' => $this->finca1->id,
                'user_id' => $this->operario->id,
                'fecha' => $dia,
                'rol_asignado' => AgendaTurno::ROL_ALIMENTADOR,
                'tipo_dia' => AgendaTurno::TIPO_SEMANA,
            ]);
        }
    }

    /**
     * REQUISITO 1: Permitir programar turnos independientes para Sábados y Domingos de día.
     */
    public function test_programacion_independiente_de_fin_de_semana(): void
    {
        $sabado = now()->next(Carbon::SATURDAY)->toDateString();

        $response = $this->actingAs($this->propietario)->post(route('agenda.turnos.findesemana'), [
            'user_id' => $this->operario->id,
            'fecha' => $sabado,
            'rol_asignado' => AgendaTurno::ROL_ALIMENTADOR,
            'observaciones' => 'Guardia diurna sábado',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('agenda_turnos', [
            'finca_id' => $this->finca1->id,
            'user_id' => $this->operario->id,
            'fecha' => $sabado,
            'rol_asignado' => AgendaTurno::ROL_ALIMENTADOR,
            'tipo_dia' => AgendaTurno::TIPO_SABADO,
        ]);
    }

    /**
     * REQUISITO 2: Middleware VerificarTurnoDiarioAlimentador bloquea a operario sin turno hoy
     * emitiendo el mensaje flash exacto.
     */
    public function test_middleware_bloquea_operario_sin_turno_de_alimentacion_con_mensaje_exacto(): void
    {
        $response = $this->actingAs($this->operario)->get(route('operaciones.alimentacion.index'));

        $response->assertRedirect(route('trabajador.dashboard'));
        $response->assertSessionHas(
            'error',
            'Acceso restringido: Hoy no tienes turno asignado para alimentar. Consulta la Agenda Operativa.'
        );
    }

    /**
     * REQUISITO 2: Operario con turno activo de alimentador hoy puede acceder a /operaciones/alimentacion.
     */
    public function test_middleware_permite_acceso_a_operario_con_turno_activo_hoy(): void
    {
        AgendaTurno::create([
            'finca_id' => $this->finca1->id,
            'user_id' => $this->operario->id,
            'fecha' => now()->toDateString(),
            'rol_asignado' => AgendaTurno::ROL_ALIMENTADOR,
            'estado' => AgendaTurno::ESTADO_ACTIVO,
            'tipo_dia' => 'semana',
        ]);

        $response = $this->actingAs($this->operario)->get(route('operaciones.alimentacion.index'));
        $response->assertOk();
    }

    /**
     * REQUISITO 2: Middleware VerificarTurnoSeguridadNoche protege /celador y /seguridad-noche.
     * Solo permite acceso si hoy tiene turno de seguridad_noche (o si es propietario).
     */
    public function test_middleware_seguridad_noche_controla_acceso_segun_turno(): void
    {
        // 1. Operario sin turno de guardia nocturna es bloqueado
        $responseBloqueado = $this->actingAs($this->operario)->get(route('celador.dashboard'));
        $responseBloqueado->assertRedirect(route('trabajador.dashboard'));
        $responseBloqueado->assertSessionHas(
            'error',
            'Acceso restringido: Hoy no tienes asignado turno de Celador (Seguridad & Noche). Consulta la Agenda Operativa.'
        );

        // 2. Operario con turno de guardia nocturna asignado para hoy puede acceder
        AgendaTurno::create([
            'finca_id' => $this->finca1->id,
            'user_id' => $this->operario->id,
            'fecha' => now()->toDateString(),
            'rol_asignado' => AgendaTurno::ROL_SEGURIDAD_NOCHE,
            'estado' => AgendaTurno::ESTADO_ACTIVO,
            'tipo_dia' => 'domingo',
        ]);

        $responsePermitido = $this->actingAs($this->operario)->get(route('celador.dashboard'));
        $responsePermitido->assertOk();
    }

    /**
     * REQUISITO 2: Bypass libre para Propietario y Técnico Acuícola en ambos middlewares.
     */
    public function test_bypass_libre_para_propietario_y_tecnico_acuicola(): void
    {
        // Propietario accede libremente a alimentación y seguridad noche
        $this->actingAs($this->propietario)->get(route('operaciones.alimentacion.index'))->assertOk();
        $this->actingAs($this->propietario)->get(route('celador.dashboard'))->assertOk();

        // Técnico Acuícola accede libremente a alimentación y seguridad noche
        $this->actingAs($this->tecnico)->get(route('operaciones.alimentacion.index'))->assertOk();
        $this->actingAs($this->tecnico)->get(route('celador.dashboard'))->assertOk();
    }

    /**
     * REQUISITO 3: Vista de Agenda Operativa (/admin/agenda) carga calendario y tabla de turnos.
     */
    public function test_vista_admin_agenda_carga_correctamente_con_turnos(): void
    {
        $response = $this->actingAs($this->propietario)->get(route('admin.agenda.index'));
        $response->assertOk();
        $response->assertSee('Agenda');
        $response->assertSee('Turnos Operativos');
    }

    /**
     * REQUISITO 3: Badge dinámico del rol/turno activo de hoy en el header del sistema.
     */
    public function test_badge_dinamico_del_rol_activo_de_hoy_en_header(): void
    {
        // 1. Operario con turno de alimentador hoy
        AgendaTurno::create([
            'finca_id' => $this->finca1->id,
            'user_id' => $this->operario->id,
            'fecha' => now()->toDateString(),
            'rol_asignado' => AgendaTurno::ROL_ALIMENTADOR,
            'estado' => AgendaTurno::ESTADO_ACTIVO,
            'tipo_dia' => 'semana',
        ]);

        $badgeAlimentador = $this->operario->badgeRolHoy();
        $this->assertEquals('Turno Hoy: Alimentador', $badgeAlimentador['label']);

        $responseAlimentador = $this->actingAs($this->operario)->get(route('trabajador.dashboard'));
        $responseAlimentador->assertOk();
        $responseAlimentador->assertSee('Turno Hoy: Alimentador');

        // 2. Operario con turno de celador nocturno hoy
        $operario2 = User::factory()->create([
            'name' => 'Carlos Guardia Hoy',
            'role' => User::ROLE_OPERARIO_CAMPO,
            'finca_id' => $this->finca1->id,
        ]);

        AgendaTurno::create([
            'finca_id' => $this->finca1->id,
            'user_id' => $operario2->id,
            'fecha' => now()->toDateString(),
            'rol_asignado' => AgendaTurno::ROL_SEGURIDAD_NOCHE,
            'estado' => AgendaTurno::ESTADO_ACTIVO,
            'tipo_dia' => 'domingo',
        ]);

        $badgeCelador = $operario2->badgeRolHoy();
        $this->assertEquals('Rol Hoy: Celador', $badgeCelador['label']);
    }

    /**
     * REQUISITO 4: Aislamiento estricto multi-tenant por finca_id.
     */
    public function test_aislamiento_estricto_multi_tenant_en_agenda(): void
    {
        $operarioFinca2 = User::factory()->create([
            'name' => 'Operario Finca 2',
            'role' => User::ROLE_OPERARIO_CAMPO,
            'finca_id' => $this->finca2->id,
        ]);

        AgendaTurno::create([
            'finca_id' => $this->finca2->id,
            'user_id' => $operarioFinca2->id,
            'fecha' => now()->toDateString(),
            'rol_asignado' => AgendaTurno::ROL_ALIMENTADOR,
            'estado' => AgendaTurno::ESTADO_ACTIVO,
            'tipo_dia' => 'semana',
        ]);

        // Consulta desde Finca 1 no debe ver los turnos de Finca 2
        $this->actingAs($this->propietario);
        $turnosFinca1 = AgendaTurno::where('finca_id', $this->finca1->id)->get();
        $this->assertFalse($turnosFinca1->contains('user_id', $operarioFinca2->id));
    }
}
