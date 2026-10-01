<?php

namespace Database\Seeders;

use App\Models\AdminTask;
use App\Models\AgendaTurno;
use App\Models\Especie;
use App\Models\Finca;
use App\Models\Pond;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoPiscicolaSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Carga de datos demostrativos completos para pruebas y presentaciones:
     * - Finca principal
     * - Catálogo de especies colombianas
     * - 4 usuarios de prueba para cada rol
     * - 8 estanques de cultivo (81.050 peces y biomasas reales)
     * - Turnos en la Agenda Operativa
     * - Tareas operativas de campo
     */
    public function run(): void
    {
        // 1. Finca Principal
        Finca::updateOrCreate(
            ['id' => 1],
            [
                'nombre' => 'El SAS Piscícola - Finca Principal',
                'codigo' => 'SAS-01',
                'ubicacion' => 'Espinal, Tolima, Colombia',
                'nit' => '901.884.210-5',
                'departamento' => 'Tolima',
                'municipio' => 'Espinal',
                'responsable_tecnico' => 'Dr. Carlos Mendoza - Zootecnista Mat. 8941',
                'registro_ica' => 'ICA-AQ-73268-2024',
                'configuraciones' => Finca::DEFAULT_CONFIG,
            ]
        );

        // 2. Catálogo oficial de especies
        $this->call(EspecieSeeder::class);

        // 3. Usuarios de demostración para los roles del sistema (4 roles canónicos)
        $propietarioRole = Role::firstOrCreate(['slug' => 'propietario'], ['name' => 'Propietario / Gerente General']);
        $administradorRole = Role::firstOrCreate(['slug' => 'administrador'], ['name' => 'Administrador de Finca']);
        $operarioRole = Role::firstOrCreate(['slug' => 'operario_campo'], ['name' => 'Operario de Campo / Alimentador']);
        $celadorRole = Role::firstOrCreate(['slug' => 'celador_nocturno'], ['name' => 'Celador Nocturno']);

        // Eliminar residuo de técnico acuícola si existe
        User::where('email', 'tecnico@finca.com')->delete();
        Role::where('slug', 'tecnico_acuicola')->delete();

        // Propietario / Gerente General (Acceso Total y Exclusivo a Ventas, Nómina, Báscula)
        $jefe = User::updateOrCreate(
            ['email' => 'propietario@finca.com'],
            [
                'document_number' => '19283746',
                'name' => 'Don Fernando Gómez (Propietario / Gerente General)',
                'username' => 'propietario',
                'password' => Hash::make('password'),
                'role' => User::ROLE_PROPIETARIO,
                'employment_type' => User::TYPE_FIJO,
                'finca_id' => 1,
            ]
        );
        $jefe->roles()->syncWithoutDetaching([$propietarioRole->id]);

        // Administrador de Finca (Operaciones, Bodega, Compras, Caja Diaria, Nómina y Lagos)
        $admin = User::updateOrCreate(
            ['email' => 'admin@finca.com'],
            [
                'document_number' => '79865432',
                'name' => 'Administrador de Finca',
                'username' => 'admin_finca',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMINISTRADOR,
                'employment_type' => User::TYPE_FIJO,
                'finca_id' => 1,
            ]
        );
        $admin->roles()->syncWithoutDetaching([$administradorRole->id]);

        // Operario de Finca (Rol rotativo según la Agenda)
        $operario = User::updateOrCreate(
            ['email' => 'trabajador@finca.com'],
            [
                'document_number' => '1070123456',
                'name' => 'Carlos Pérez (Operario de Campo)',
                'username' => 'operario_campo',
                'password' => Hash::make('password'),
                'role' => User::ROLE_OPERARIO_CAMPO,
                'employment_type' => User::TYPE_DESTAJO_SEMANAL,
                'finca_id' => 1,
            ]
        );
        $operario->roles()->syncWithoutDetaching([$operarioRole->id]);

        // Celador Nocturno
        $celador = User::updateOrCreate(
            ['email' => 'celador@finca.com'],
            [
                'document_number' => '1023456789',
                'name' => 'Don Faustino (Celador Nocturno)',
                'username' => 'celador_nocturno',
                'password' => Hash::make('password'),
                'role' => User::ROLE_CELADOR_NOCTURNO,
                'employment_type' => User::TYPE_FIJO,
                'finca_id' => 1,
            ]
        );
        $celador->roles()->syncWithoutDetaching([$celadorRole->id]);

        // 4. Los 8 Estanques Demostrativos de Cultivo (81.050 peces totales)
        $mojarraRojaId = Especie::where('nombre_cientifico', 'Oreochromis sp.')->value('id')
            ?? Especie::first()?->id ?? 1;
        $cachamaBlancaId = Especie::where('nombre_cientifico', 'Piaractus brachypomus')->value('id')
            ?? $mojarraRojaId;
        $mojarraNegraId = Especie::where('nombre_cientifico', 'Oreochromis niloticus')->value('id')
            ?? $mojarraRojaId;
        $bocachicoId = Especie::where('nombre_cientifico', 'Prochilodus magdalenae')->value('id')
            ?? $mojarraRojaId;
        $bagreId = Especie::where('nombre_cientifico', 'Pseudoplatystoma magdalenietum')->value('id')
            ?? Especie::latest('id')->value('id') ?? $mojarraRojaId;

        $estanquesData = [
            [
                'id' => 1,
                'finca_id' => 1,
                'especie_id' => $mojarraRojaId,
                'name' => 'Estanque 1 - Mojarra Roja',
                'code' => 'EST-01',
                'tipo_estanque' => 'Tierra',
                'numero_lote' => 'LOTE-2026-MR01',
                'alevinera_origen' => 'Piscícola San Jerónimo',
                'fingerlings_stocked' => 12000,
                'fish_population' => 11850,
                'average_weight' => 280.0,
                'biomass' => 3318.0,
                'status' => 'Sembrado',
                'stocked_at' => now()->subDays(45)->toDateString(),
            ],
            [
                'id' => 2,
                'finca_id' => 1,
                'especie_id' => $cachamaBlancaId,
                'name' => 'Estanque 2 - Cachama Blanca',
                'code' => 'EST-02',
                'tipo_estanque' => 'Tierra',
                'numero_lote' => 'LOTE-2026-CB02',
                'alevinera_origen' => 'Alevinos del Llano',
                'fingerlings_stocked' => 8000,
                'fish_population' => 7920,
                'average_weight' => 450.0,
                'biomass' => 3564.0,
                'status' => 'Sembrado',
                'stocked_at' => now()->subDays(90)->toDateString(),
            ],
            [
                'id' => 3,
                'finca_id' => 1,
                'especie_id' => $mojarraNegraId,
                'name' => 'Estanque 3 - Engorde Comercial',
                'code' => 'EST-03',
                'tipo_estanque' => 'Geomembrana',
                'numero_lote' => 'LOTE-2026-MN03',
                'alevinera_origen' => 'Acuícola Tolima',
                'fingerlings_stocked' => 15000,
                'fish_population' => 14800,
                'average_weight' => 520.0,
                'biomass' => 7696.0,
                'status' => 'En Cosecha',
                'stocked_at' => now()->subDays(120)->toDateString(),
            ],
            [
                'id' => 4,
                'finca_id' => 1,
                'especie_id' => $bocachicoId,
                'name' => 'Estanque 4 - Policultivo Bocachico',
                'code' => 'EST-04',
                'tipo_estanque' => 'Tierra',
                'numero_lote' => 'LOTE-2026-BC04',
                'alevinera_origen' => 'Piscícola del Río',
                'fingerlings_stocked' => 6000,
                'fish_population' => 5950,
                'average_weight' => 210.0,
                'biomass' => 1249.5,
                'status' => 'Sembrado',
                'stocked_at' => now()->subDays(60)->toDateString(),
            ],
            [
                'id' => 5,
                'finca_id' => 1,
                'especie_id' => $mojarraRojaId,
                'name' => 'Estanque 5 - Alevinaje Pre-Cría',
                'code' => 'EST-05',
                'tipo_estanque' => 'Geomembrana',
                'numero_lote' => 'LOTE-2026-AL05',
                'alevinera_origen' => 'Piscícola San Jerónimo',
                'fingerlings_stocked' => 25000,
                'fish_population' => 24700,
                'average_weight' => 25.0,
                'biomass' => 617.5,
                'status' => 'Sembrado',
                'stocked_at' => now()->subDays(20)->toDateString(),
            ],
            [
                'id' => 6,
                'finca_id' => 1,
                'especie_id' => $mojarraNegraId,
                'name' => 'Estanque 6 - Levante Intensivo',
                'code' => 'EST-06',
                'tipo_estanque' => 'Tierra',
                'numero_lote' => 'LOTE-2026-LV06',
                'alevinera_origen' => 'Acuícola Tolima',
                'fingerlings_stocked' => 10000,
                'fish_population' => 9900,
                'average_weight' => 180.0,
                'biomass' => 1782.0,
                'status' => 'Sembrado',
                'stocked_at' => now()->subDays(50)->toDateString(),
            ],
            [
                'id' => 7,
                'finca_id' => 1,
                'especie_id' => $cachamaBlancaId,
                'name' => 'Estanque 7 - Cachama Reproductores',
                'code' => 'EST-07',
                'tipo_estanque' => 'Tierra',
                'numero_lote' => 'LOTE-2026-REP07',
                'alevinera_origen' => 'Centro Genético Piscícola',
                'fingerlings_stocked' => 2000,
                'fish_population' => 1980,
                'average_weight' => 1200.0,
                'biomass' => 2376.0,
                'status' => 'Activo',
                'stocked_at' => now()->subDays(210)->toDateString(),
            ],
            [
                'id' => 8,
                'finca_id' => 1,
                'especie_id' => $bagreId,
                'name' => 'Estanque 8 - Bagre Rayado / Yaque',
                'code' => 'EST-08',
                'tipo_estanque' => 'Tierra',
                'numero_lote' => 'LOTE-2026-BG08',
                'alevinera_origen' => 'Alevinos del Llano',
                'fingerlings_stocked' => 4000,
                'fish_population' => 3950,
                'average_weight' => 650.0,
                'biomass' => 2567.5,
                'status' => 'Sembrado',
                'stocked_at' => now()->subDays(150)->toDateString(),
            ],
        ];

        foreach ($estanquesData as $pondItem) {
            Pond::updateOrCreate(['id' => $pondItem['id']], $pondItem);
        }

        // 5. Programar semana actual del Operario en la Agenda Operativa
        // Encadenamiento semanal: Alimentador Lunes a Viernes + Celador Domingo anterior
        $lunesActual = now()->startOfWeek();
        AgendaTurno::programarSemanaCompleta(1, $operario->id, $lunesActual);

        // 6. Tareas operativas de demostración
        AdminTask::updateOrCreate(
            ['title' => 'Revisión y limpieza de monjes y mallas perimetrales'],
            [
                'finca_id' => 1,
                'description' => 'Recorrer aliviaderos de los estanques 1 al 4, retirar ramas u hojas y chequear estado de mallas.',
                'assigned_to_user_id' => $operario->id,
                'created_by_user_id' => $jefe->id,
                'priority' => AdminTask::PRIORITY_ALTA,
                'status' => AdminTask::STATUS_PENDIENTE,
                'due_date' => now()->toDateString(),
                'notes' => 'Prioridad antes de las 11:00 AM.',
            ]
        );

        AdminTask::updateOrCreate(
            ['title' => 'Suministro de ración matutina y muestreo de apetito'],
            [
                'finca_id' => 1,
                'description' => 'Alimentar con ración completa al 2.5% de biomasa y registrar apetito de cada estanque.',
                'assigned_to_user_id' => $operario->id,
                'created_by_user_id' => $admin->id,
                'priority' => AdminTask::PRIORITY_URGENTE,
                'status' => AdminTask::STATUS_PENDIENTE,
                'due_date' => now()->toDateString(),
                'notes' => 'Pesar bultos antes de cargar las carretillas.',
            ]
        );
    }
}
