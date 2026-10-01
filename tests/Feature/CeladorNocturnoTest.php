<?php

namespace Tests\Feature;

use App\Models\ControlAireador;
use App\Models\FishCredit;
use App\Models\Pond;
use App\Models\User;
use App\Notifications\AlertaBoqueoCriticaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CeladorNocturnoTest extends TestCase
{
    use RefreshDatabase;

    private User $celador;

    private User $trabajadorCampo;

    private User $administrador;

    private User $jefeMayor;

    private Pond $estanque1;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Finca::create([
            'id' => 1,
            'nombre' => 'El SAS Piscícola',
            'codigo' => 'SAS-01',
            'configuraciones' => \App\Models\Finca::DEFAULT_CONFIG,
        ]);

        // 1. Celador Nocturno
        $this->celador = User::factory()->create([
            'name' => 'Don Faustino (Celador)',
            'username' => 'faustino.celador',
            'email' => 'celador@sas-piscicola.com',
            'role' => User::ROLE_CELADOR_NOCTURNO,
            'employment_type' => User::TYPE_FIJO,
            'finca_id' => 1,
        ]);

        // 2. Trabajador de campo (Cubre domingos en la noche y alimenta de lunes a viernes)
        $this->trabajadorCampo = User::factory()->create([
            'name' => 'Alvaro Relevo Domingo',
            'username' => 'alvaro.campo',
            'role' => User::ROLE_TRABAJADOR,
            'employment_type' => User::TYPE_DESTAJO_SEMANAL,
            'finca_id' => 1,
        ]);

        // 3. Administrador
        $this->administrador = User::factory()->create([
            'name' => 'Carlos Administrador',
            'email' => 'admin@sas-piscicola.com',
            'role' => User::ROLE_ADMINISTRADOR,
            'finca_id' => 1,
        ]);

        // 4. Jefe Mayor
        $this->jefeMayor = User::factory()->create([
            'name' => 'Don Fernando Jefe',
            'email' => 'jefe@sas-piscicola.com',
            'role' => User::ROLE_JEFE_MAYOR,
            'finca_id' => 1,
        ]);

        // Estanque de policultivo
        $this->estanque1 = Pond::create([
            'finca_id' => 1,
            'name' => 'Estanque Policultivo 1',
            'code' => 'POLI-01',
            'fingerlings_stocked' => 10000,
            'fish_population' => 10000,
            'average_weight' => 350.0,
            'biomass' => 3500.0,
            'status' => 'Sembrado',
            'stocked_at' => now()->subDays(60)->toDateString(),
        ]);
    }

    public function test_celador_and_sunday_rotating_worker_can_access_nocturno_module(): void
    {
        // 1. Celador nocturno accede a su vista
        $responseCelador = $this->actingAs($this->celador)->get(route('celador.index'));
        $responseCelador->assertOk()->assertSee('Módulo Operativo de Guardia Nocturna');

        // 2. Trabajador de campo (con turno asignado de guardia nocturna en agenda) tiene acceso permitido
        \App\Models\AgendaTurno::create([
            'finca_id' => 1,
            'user_id' => $this->trabajadorCampo->id,
            'fecha' => now()->toDateString(),
            'tipo_dia' => 'domingo',
            'rol_asignado' => \App\Models\AgendaTurno::ROL_SEGURIDAD_NOCHE,
            'estado' => \App\Models\AgendaTurno::ESTADO_ACTIVO,
        ]);

        $responseTrabajador = $this->actingAs($this->trabajadorCampo)->get(route('celador.index'));
        $responseTrabajador->assertOk();

        // 3. Administrador y Jefe Mayor también tienen acceso de supervisión
        $this->actingAs($this->administrador)->get(route('celador.index'))->assertOk();
        $this->actingAs($this->jefeMayor)->get(route('celador.index'))->assertOk();
    }

    public function test_celador_records_night_patrol_checkpoints_and_dam_inspection(): void
    {
        // Checkpoint de las 1:00 AM con verificación de niveles de agua en monje y mallas
        $response = $this->actingAs($this->celador)->postJson('/api/celador/rondas', [
            'hora_ronda' => '01:00 AM',
            'estanque_id' => $this->estanque1->id,
            'estado' => 'normal',
            'nivel_agua_monje' => 'optimo',
            'estado_mallas' => 'bueno',
            'observaciones' => 'Ronda sin novedad. Monje en nivel óptimo, mallas bien fijadas.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('bitacoras_nocturnas', [
            'user_id' => $this->celador->id,
            'hora_ronda' => '01:00 AM',
            'estanque_id' => $this->estanque1->id,
            'estado' => 'normal',
            'nivel_agua_monje' => 'optimo',
        ]);

        // Checkpoint con detección de anomalía (ej. fuga en monje a las 3:30 AM)
        $responseFuga = $this->actingAs($this->celador)->postJson('/api/celador/rondas', [
            'hora_ronda' => '03:30 AM',
            'estanque_id' => $this->estanque1->id,
            'estado' => 'fuga_monje',
            'nivel_agua_monje' => 'fuga',
            'estado_mallas' => 'bueno',
            'observaciones' => 'Fuga de agua por tabla inferior del monje. Se ajustó empaque provisional.',
        ]);

        $responseFuga->assertStatus(201);
        $this->assertTrue($responseFuga->json('alerta_anomalia'));
    }

    public function test_aerator_control_turn_on_and_turn_off_with_automatic_hours_calculation(): void
    {
        // 1. Encender aireador en estanque con red eléctrica
        $responseEncender = $this->actingAs($this->celador)->postJson('/api/celador/aireadores/encender', [
            'estanque_id' => $this->estanque1->id,
            'fuente_energia' => 'red_electrica',
            'corte_luz' => false,
            'observaciones' => 'Encendido nocturno rutinario 11:00 PM',
        ]);

        $responseEncender->assertStatus(201);
        $controlId = $responseEncender->json('data.id');

        $aireador = ControlAireador::findOrFail($controlId);
        $this->assertNull($aireador->hora_apagado);
        $this->assertEquals('red_electrica', $aireador->fuente_energia);

        // Simular que operó durante 4 horas y 30 minutos
        $aireador->hora_encendido = now()->subMinutes(270);
        $aireador->save();

        // 2. Apagar aireador a las 5:30 AM
        $responseApagar = $this->actingAs($this->celador)
            ->postJson("/api/celador/aireadores/{$aireador->id}/apagar", [
                'observaciones' => 'Apagado al amanecer',
            ]);

        $responseApagar->assertOk();
        $aireador->refresh();

        $this->assertNotNull($aireador->hora_apagado);
        // 270 minutos / 60 = 4.5 horas
        $this->assertEquals(4.5, (float) $aireador->total_horas);
    }

    public function test_emergency_panic_button_triggers_critical_alert_and_notifies_management(): void
    {
        Notification::fake();

        // El celador detecta boqueo masivo en superficie y oprime el BOTÓN DE PÁNICO
        $response = $this->actingAs($this->celador)->postJson('/api/celador/alerta-boqueo', [
            'estanque_id' => $this->estanque1->id,
            'oxigeno_mg_l' => 2.1,
            'observaciones' => '¡Peces en superficie boqueando en la orilla norte por falta de oxígeno!',
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'alerta_critica_activada',
        ]);

        // 1. El estado del estanque cambia de inmediato a 'alerta_critica'
        $this->estanque1->refresh();
        $this->assertEquals('alerta_critica', $this->estanque1->status);

        // 2. Se enciende automáticamente el aireador de emergencia
        $this->assertDatabaseHas('control_aireadores', [
            'estanque_id' => $this->estanque1->id,
            'hora_apagado' => null,
            'fuente_energia' => ControlAireador::FUENTE_PLANTA_EMERGENCIA,
        ]);

        // 3. Se genera la notificación prioritaria al Administrador y al Jefe Mayor
        Notification::assertSentTo(
            [$this->administrador, $this->jefeMayor],
            AlertaBoqueoCriticaNotification::class
        );
    }

    public function test_night_attendance_entrance_and_exit_with_fish_discount_at_7000_cop(): void
    {
        // 1. Registro de Entrada Nocturna
        $responseEntrada = $this->actingAs($this->celador)->postJson('/api/celador/entrada');
        $responseEntrada->assertStatus(201);

        $this->assertDatabaseHas('turnos_nocturnos', [
            'user_id' => $this->celador->id,
            'estado' => 'en_turno',
        ]);

        // 2. Registro de Salida de Guardia llevando 3.5 kg de pescado
        // Descuento: 3.5 kg * $7.000 = $24.500 COP
        $responseSalida = $this->actingAs($this->celador)->postJson('/api/celador/salida', [
            'pescado_kilos_llevados' => 3.5,
            'observaciones' => 'Entrega de guardia a relevo matutino',
        ]);

        $responseSalida->assertOk();
        $this->assertEquals(3.5, (float) $responseSalida->json('kilos_pescado'));
        $this->assertEquals(24500.0, (float) $responseSalida->json('descuento_pescado'));

        // Se debe crear automáticamente el registro en fish_credits para que impacte la nómina
        $this->assertDatabaseHas('fish_credits', [
            'user_id' => $this->celador->id,
            'kilos' => 3.5,
            'price_per_kg' => 7000.0,
            'total_amount' => 24500.0,
            'status' => FishCredit::STATUS_PENDIENTE,
        ]);
    }
}
