<?php

namespace Tests\Feature;

use App\Models\FeedInventory;
use App\Models\Pond;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_schedule_worker_shift(): void
    {
        $admin = User::factory()->admin()->create(['finca_id' => 1]);
        $worker = User::factory()->worker()->create(['finca_id' => 1, 'name' => 'Carlos Operario']);

        $response = $this->actingAs($admin)->postJson(route('api.work_schedules.store'), [
            'user_id' => $worker->id,
            'schedule_date' => '2026-10-12',
            'shift_type' => 'festivo',
            'start_time' => '07:00',
            'end_time' => '16:00',
            'notes' => 'Cubrir tanda de alimentación de día festivo',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Turno programado y asignado exitosamente.',
                'data' => [
                    'user_id' => $worker->id,
                    'shift_type' => 'festivo',
                    'schedule_date' => '2026-10-12',
                ],
            ]);

        $this->assertDatabaseHas('work_schedules', [
            'finca_id' => 1,
            'user_id' => $worker->id,
            'shift_type' => 'festivo',
            'schedule_date' => '2026-10-12',
        ]);
    }

    public function test_worker_cannot_create_shift(): void
    {
        $worker = User::factory()->worker()->create(['finca_id' => 1]);

        $response = $this->actingAs($worker)->postJson(route('api.work_schedules.store'), [
            'user_id' => $worker->id,
            'schedule_date' => '2026-10-12',
            'shift_type' => 'fin_de_semana',
        ]);

        $response->assertStatus(403);
    }

    public function test_can_query_who_is_on_duty_for_a_date(): void
    {
        $worker1 = User::factory()->worker()->create(['finca_id' => 1, 'name' => 'Mario']);
        $worker2 = User::factory()->worker()->create(['finca_id' => 1, 'name' => 'Pedro']);

        WorkSchedule::create([
            'finca_id' => 1,
            'user_id' => $worker1->id,
            'schedule_date' => '2026-10-18',
            'shift_type' => 'fin_de_semana',
            'notes' => 'Turno domingo',
        ]);

        $response = $this->actingAs($worker2)->getJson(route('api.work_schedules.who_is_on_duty', ['date' => '2026-10-18']));

        $response->assertStatus(200)
            ->assertJson([
                'fecha' => '2026-10-18',
                'total_asignados' => 1,
            ]);
    }

    public function test_feeding_log_automatically_links_with_worker_schedule(): void
    {
        $worker = User::factory()->worker()->create(['finca_id' => 1, 'name' => 'Operario de Turno']);

        // Crear turno programado para hoy
        $today = now()->toDateString();
        $schedule = WorkSchedule::create([
            'finca_id' => 1,
            'user_id' => $worker->id,
            'schedule_date' => $today,
            'shift_type' => 'bloque_alimentacion',
            'start_time' => '06:00',
            'end_time' => '14:00',
        ]);

        $pond = Pond::factory()->create([
            'finca_id' => 1,
            'fish_population' => 1000,
            'average_weight' => 200,
            'biomass' => 200,
        ]);

        $feed = FeedInventory::create([
            'finca_id' => 1,
            'name' => 'Italcol 38%',
            'brand' => 'Italcol',
            'quantity_kg' => 50.0,
        ]);

        // Operario aplica la ración
        $response = $this->actingAs($worker)->postJson(route('api.ponds.apply_ration', $pond), [
            'feed_inventory_id' => $feed->id,
            'feeding_rate' => 2.0,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'shift_verification' => [
                    'tiene_turno_programado' => true,
                    'turno' => [
                        'id' => $schedule->id,
                        'shift_type' => 'bloque_alimentacion',
                    ],
                ],
            ]);

        // Verificamos que la bitácora quedó enlazada con el usuario y el turno
        $this->assertDatabaseHas('feeding_logs', [
            'finca_id' => 1,
            'user_id' => $worker->id,
            'work_schedule_id' => $schedule->id,
            'pond_id' => $pond->id,
            'feed_inventory_id' => $feed->id,
        ]);
    }
}
