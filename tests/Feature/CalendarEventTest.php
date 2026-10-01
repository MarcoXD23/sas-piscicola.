<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\FeedingLog;
use App\Models\FeedInventory;
use App\Models\Pond;
use App\Models\User;
use App\Notifications\CalendarEventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CalendarEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_jefe_finca_can_schedule_harvest_event_and_notifies_admin(): void
    {
        Notification::fake();

        $jefeFinca = User::factory()->jefeFinca()->create(['finca_id' => 1]);
        $admin = User::factory()->admin()->create(['finca_id' => 1]);
        $pond = Pond::factory()->create(['finca_id' => 1, 'name' => 'Lago Principal']);

        $response = $this->actingAs($jefeFinca)->postJson(route('api.calendar_events.store'), [
            'title' => 'Cosecha Programada Lote 1',
            'event_type' => CalendarEvent::TYPE_PESCA,
            'event_date' => '2026-10-25',
            'event_time' => '06:30',
            'pond_id' => $pond->id,
            'estimated_kg' => 1250.0,
            'notes' => 'Tener listas 30 canastillas y hielo suficiente.',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Evento programado exitosamente en el calendario y alerta enviada a los administradores.',
                'notificados_administradores' => 1,
                'data' => [
                    'title' => 'Cosecha Programada Lote 1',
                    'event_type' => 'pesca_cosecha',
                    'event_date' => '2026-10-25',
                    'estimated_kg' => 1250.0,
                    'status' => 'programado',
                ],
            ]);

        $this->assertDatabaseHas('calendar_events', [
            'finca_id' => 1,
            'title' => 'Cosecha Programada Lote 1',
            'event_type' => 'pesca_cosecha',
            'pond_id' => $pond->id,
            'estimated_kg' => 1250.0,
        ]);

        Notification::assertSentTo($admin, CalendarEventNotification::class, function ($notification) {
            $data = $notification->toArray($notification);

            return $data['event_type'] === 'pesca_cosecha'
                && str_contains($data['message'], 'Cosecha Programada Lote 1');
        });
    }

    public function test_jefe_finca_can_schedule_fingerlings_and_feed_events(): void
    {
        $jefeFinca = User::factory()->jefeFinca()->create(['finca_id' => 1]);
        $admin = User::factory()->admin()->create(['finca_id' => 1]);

        // 1. Llegada de Alevinos
        $resAlevinos = $this->actingAs($jefeFinca)->postJson(route('api.calendar_events.store'), [
            'title' => 'Siembra 20.000 alevinos roja',
            'event_type' => CalendarEvent::TYPE_ALEVINOS,
            'event_date' => '2026-10-28',
            'fingerlings_quantity' => 20000,
            'stage' => 'Reversión 1 gramo',
        ]);

        $resAlevinos->assertStatus(201)
            ->assertJson([
                'data' => [
                    'event_type' => 'llegada_alevinos',
                    'fingerlings_quantity' => 20000,
                    'stage' => 'Reversión 1 gramo',
                ],
            ]);

        // 2. Llegada de Alimento
        $resAlimento = $this->actingAs($jefeFinca)->postJson(route('api.calendar_events.store'), [
            'title' => 'Pedido Concentrado Crecimiento',
            'event_type' => CalendarEvent::TYPE_ALIMENTO,
            'event_date' => '2026-10-30',
            'feed_type' => 'Mojarra 34% Proteína',
            'feed_bags_count' => 60,
            'feed_weight_kg' => 2400.0,
        ]);

        $resAlimento->assertStatus(201)
            ->assertJson([
                'data' => [
                    'event_type' => 'llegada_alimento',
                    'feed_type' => 'Mojarra 34% Proteína',
                    'feed_bags_count' => 60,
                    'feed_weight_kg' => 2400.0,
                ],
            ]);
    }

    public function test_jefe_finca_can_schedule_inspection_visit_and_update_it(): void
    {
        $jefeFinca = User::factory()->jefeFinca()->create(['finca_id' => 1]);
        $admin = User::factory()->admin()->create(['finca_id' => 1]);

        $event = CalendarEvent::create([
            'finca_id' => 1,
            'title' => 'Visita Técnica de Biólogo',
            'event_type' => CalendarEvent::TYPE_VISITA,
            'event_date' => '2026-11-05',
            'inspection_notes' => 'Revisión de oxígeno y parámetros físico-químicos.',
            'status' => CalendarEvent::STATUS_PROGRAMADO,
            'created_by_user_id' => $jefeFinca->id,
        ]);

        // Modificar evento
        $response = $this->actingAs($jefeFinca)->putJson(route('api.calendar_events.update', $event), [
            'status' => CalendarEvent::STATUS_EN_PROGRESO,
            'inspection_notes' => 'Revisión adelantada con biólogo y veterinario.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $event->id,
                    'status' => 'en_progreso',
                    'inspection_notes' => 'Revisión adelantada con biólogo y veterinario.',
                ],
            ]);
    }

    public function test_offline_sync_supports_rural_farm_environments(): void
    {
        $jefeFinca = User::factory()->jefeFinca()->create(['finca_id' => 1]);
        $admin = User::factory()->admin()->create(['finca_id' => 1]);

        $offlineEvents = [
            [
                'client_uuid' => 'offline-uuid-001',
                'title' => 'Siembra Offline Estanque 2',
                'event_type' => 'llegada_alevinos',
                'event_date' => '2026-11-10',
                'fingerlings_quantity' => 10000,
                'stage' => 'Alevinaje',
            ],
            [
                'client_uuid' => 'offline-uuid-002',
                'title' => 'Llegada de Alimento en Camión',
                'event_type' => 'llegada_alimento',
                'event_date' => '2026-11-12',
                'feed_type' => 'Iniciación 45%',
                'feed_bags_count' => 40,
                'feed_weight_kg' => 1600.0,
            ],
        ];

        $response = $this->actingAs($jefeFinca)->postJson(route('api.calendar_events.sync'), [
            'events' => $offlineEvents,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Sincronización offline completada exitosamente.',
                'eventos_sincronizados_count' => 2,
            ]);

        $this->assertDatabaseHas('calendar_events', [
            'finca_id' => 1,
            'client_uuid' => 'offline-uuid-001',
            'title' => 'Siembra Offline Estanque 2',
        ]);

        $this->assertDatabaseHas('calendar_events', [
            'finca_id' => 1,
            'client_uuid' => 'offline-uuid-002',
            'feed_type' => 'Iniciación 45%',
        ]);
    }

    public function test_server_time_endpoint_provides_live_clock_sync_data(): void
    {
        $response = $this->getJson(route('api.calendar_events.server_time'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'server_time',
                'server_date',
                'server_time_formatted',
                'server_time_12h',
                'period',
                'timezone',
                'timestamp',
                'status',
            ])
            ->assertJson([
                'status' => 'online',
                'timezone' => 'America/Bogota',
            ]);

        $formattedTime = $response->json('server_time_formatted');
        $this->assertMatchesRegularExpression('/^(0?[1-9]|1[0-2]):[0-5][0-9]:[0-5][0-9]\s(AM|PM)$/', $formattedTime);
    }

    public function test_calendar_events_and_feeding_logs_use_strict_12_hour_am_pm_format(): void
    {
        $jefeFinca = User::factory()->jefeFinca()->create(['finca_id' => 1]);
        $pond = Pond::factory()->create(['finca_id' => 1]);

        // 1. Evento de calendario con hora de la tarde (14:30)
        $event = CalendarEvent::create([
            'finca_id' => 1,
            'title' => 'Cosecha de la Tarde',
            'event_type' => CalendarEvent::TYPE_PESCA,
            'event_date' => '2026-10-25',
            'event_time' => '14:30',
            'pond_id' => $pond->id,
            'estimated_kg' => 500.0,
            'created_by_user_id' => $jefeFinca->id,
        ]);

        $this->assertEquals('2:30 PM', $event->formatted_event_time);

        // 2. Bitácora de alimentación
        $feed = FeedInventory::create([
            'finca_id' => 1,
            'name' => 'Concentrado 38%',
            'brand' => 'Nutreco',
            'feed_type' => 'extruizado',
            'protein_percentage' => 38.0,
            'quantity_kg' => 1000.0,
            'bag_weight_kg' => 40.0,
            'bags_count' => 25,
            'unit_cost' => 120000.0,
        ]);
        $feedingLog = FeedingLog::create([
            'finca_id' => 1,
            'user_id' => $jefeFinca->id,
            'pond_id' => $pond->id,
            'feed_inventory_id' => $feed->id,
            'feeding_date' => '2026-10-25',
            'amount_kg' => 45.0,
            'feed_name' => 'Concentrado 38%',
            'feed_brand' => 'Nutreco',
            'feed_type' => 'extruizado',
            'feed_protein_percentage' => 38.0,
            'feed_bag_weight_kg' => 40.0,
        ]);

        $this->assertNotNull($feedingLog->formatted_feeding_time);
        $this->assertMatchesRegularExpression('/^(0?[1-9]|1[0-2]):[0-5][0-9]\s(AM|PM)$/', $feedingLog->formatted_feeding_time);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}\s(0?[1-9]|1[0-2]):[0-5][0-9]\s(AM|PM)$/', $feedingLog->formatted_created_at);
    }

    public function test_agenda_web_blade_view_loads_correctly(): void
    {
        $jefeFinca = User::factory()->jefeFinca()->create(['finca_id' => 1]);
        $response = $this->actingAs($jefeFinca)->get(route('agenda.index'));

        $response->assertStatus(200)
            ->assertSee('El SAS Piscícola')
            ->assertSee('Agenda Jefe de Finca')
            ->assertSee('live-clock')
            ->assertSee('formatTimeTo12h')
            ->assertSee('sas_offline_agenda_events')
            ->assertSee('/agenda/guardar');
    }

    public function test_agenda_guardar_saves_event_with_spanish_keys_to_mysql(): void
    {
        $jefeFinca = User::factory()->jefeFinca()->create(['finca_id' => 1]);
        $pond = Pond::factory()->create(['finca_id' => 1, 'name' => 'Lago 4 Cachama']);

        $response = $this->actingAs($jefeFinca)->postJson(route('agenda.store'), [
            'titulo' => 'Pesca de Cachama en Lago 4',
            'tipo_evento' => 'pesca_cosecha',
            'fecha_programada' => '2026-11-05',
            'hora_programada' => '07:00 AM',
            'lago_id' => $pond->id,
            'kilos_estimados' => 1850.5,
            'notas' => 'Cosecha con 35 canastillas y cuadrilla completa',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Evento programado exitosamente',
                'evento' => [
                    'title' => 'Pesca de Cachama en Lago 4',
                    'event_type' => 'pesca_cosecha',
                    'event_date' => '2026-11-05',
                    'event_time' => '07:00 AM',
                    'pond_id' => $pond->id,
                    'estimated_kg' => 1850.5,
                    'notes' => 'Cosecha con 35 canastillas y cuadrilla completa',
                ],
            ]);

        $this->assertDatabaseHas('calendar_events', [
            'finca_id' => 1,
            'created_by_user_id' => $jefeFinca->id,
            'title' => 'Pesca de Cachama en Lago 4',
            'event_type' => 'pesca_cosecha',
            'pond_id' => $pond->id,
            'estimated_kg' => 1850.5,
        ]);
    }

    public function test_agenda_sync_endpoint_syncs_offline_events(): void
    {
        $jefeFinca = User::factory()->jefeFinca()->create(['finca_id' => 1]);
        $pond = Pond::factory()->create(['finca_id' => 1, 'name' => 'Lago 2 Mojarra']);

        $response = $this->actingAs($jefeFinca)->postJson(route('agenda.sync'), [
            'events' => [
                [
                    'client_uuid' => 'offline_sync_test_1',
                    'titulo' => 'Pesca offline sincronizada',
                    'tipo_evento' => 'pesca_cosecha',
                    'fecha_programada' => '2026-11-10',
                    'lago_id' => $pond->id,
                    'kilos_estimados' => 900.0,
                ],
                [
                    'client_uuid' => 'offline_sync_test_2',
                    'title' => 'Llegada concentrado offline',
                    'event_type' => 'llegada_alimento',
                    'event_date' => '2026-11-12',
                    'feed_type' => 'Alimento 30%',
                    'feed_bags_count' => 40,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'synced_count' => 2,
            ]);

        $this->assertDatabaseHas('calendar_events', [
            'finca_id' => 1,
            'title' => 'Pesca offline sincronizada',
            'event_type' => 'pesca_cosecha',
            'pond_id' => $pond->id,
        ]);

        $this->assertDatabaseHas('calendar_events', [
            'finca_id' => 1,
            'title' => 'Llegada concentrado offline',
            'event_type' => 'llegada_alimento',
            'feed_type' => 'Alimento 30%',
        ]);
    }
}
