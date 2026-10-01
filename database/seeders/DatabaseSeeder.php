<?php

namespace Database\Seeders;

use App\Models\Finca;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Sembrado de Producción Limpio:
     * - Finca Principal configurada
     * - Roles canónicos del sistema: propietario, tecnico_acuicola, operario_campo (y celador_nocturno)
     * - Catálogo oficial de Especies Piscícolas de Colombia
     * - Usuario Administrador Inicial con rol 'propietario'
     *
     * (NO incluye estanques ficticios ni peces de prueba. Para datos de demostración,
     * ejecute: php artisan db:seed --class=DemoPiscicolaSeeder)
     */
    public function run(): void
    {
        // 1. Finca Principal de Operaciones
        Finca::updateOrCreate(
            ['id' => 1],
            [
                'nombre' => 'El SAS Piscícola - Finca Principal',
                'codigo' => 'SAS-01',
                'ubicacion' => 'Espinal, Tolima, Colombia',
                'nit' => '901.884.210-5',
                'departamento' => 'Tolima',
                'municipio' => 'Espinal',
                'responsable_tecnico' => 'Dirección Técnica Piscícola',
                'registro_ica' => 'ICA-AQ-73268-2024',
                'configuraciones' => Finca::DEFAULT_CONFIG,
            ]
        );

        // 2. Roles del Sistema (4 roles canónicos)
        $roles = [
            [
                'slug' => 'propietario',
                'name' => 'Propietario / Gerente General',
                'descripcion' => 'Máxima autoridad con control total sobre ventas, nómina, báscula y finanzas.',
            ],
            [
                'slug' => 'administrador',
                'name' => 'Administrador de Finca',
                'descripcion' => 'Gestión operativa total: lagos, biomasa, bodega, caja diaria y nómina de campo.',
            ],
            [
                'slug' => 'operario_campo',
                'name' => 'Operario de Campo / Alimentador',
                'descripcion' => 'Trabajador operativo para labores de campo y alimentación según agenda.',
            ],
            [
                'slug' => 'celador_nocturno',
                'name' => 'Celador Nocturno',
                'descripcion' => 'Seguridad perimetral y supervisión nocturna de estanques y aireadores.',
            ],
        ];

        foreach ($roles as $r) {
            Role::updateOrCreate(['slug' => $r['slug']], $r);
        }

        // Eliminar residuo de técnico acuícola si existe
        User::where('email', 'tecnico@finca.com')->delete();
        Role::where('slug', 'tecnico_acuicola')->delete();

        // 3. Catálogo Oficial de Especies Piscícolas de Colombia
        $this->call(EspecieSeeder::class);

        // 4. Usuario Inicial Propietario General
        $propietarioUser = User::updateOrCreate(
            ['email' => 'propietario@finca.com'],
            [
                'name' => 'Propietario General',
                'document_number' => '19283746',
                'username' => 'propietario',
                'password' => Hash::make('password'),
                'role' => User::ROLE_PROPIETARIO,
                'employment_type' => User::TYPE_FIJO,
                'finca_id' => 1,
            ]
        );

        $propietarioRole = Role::where('slug', 'propietario')->first();
        if ($propietarioRole) {
            $propietarioUser->roles()->syncWithoutDetaching([$propietarioRole->id]);
        }

        // 5. Usuario Inicial Administrador de Finca (Operaciones, Lagos, Bodega, Caja y Nómina)
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@finca.com'],
            [
                'name' => 'Administrador de Finca',
                'document_number' => '79865432',
                'username' => 'admin_finca',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMINISTRADOR,
                'employment_type' => User::TYPE_FIJO,
                'finca_id' => 1,
            ]
        );

        $adminRole = Role::where('slug', 'administrador')->first();
        if ($adminRole) {
            $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        // 7. Usuario Inicial Operario de Campo
        $operarioUser = User::updateOrCreate(
            ['email' => 'trabajador@finca.com'],
            [
                'name' => 'Carlos Pérez (Operario de Campo)',
                'document_number' => '1070123456',
                'username' => 'operario_campo',
                'password' => Hash::make('password'),
                'role' => User::ROLE_OPERARIO_CAMPO,
                'employment_type' => User::TYPE_DESTAJO_SEMANAL,
                'finca_id' => 1,
            ]
        );
        $operarioRole = Role::where('slug', 'operario_campo')->first();
        if ($operarioRole) {
            $operarioUser->roles()->syncWithoutDetaching([$operarioRole->id]);
        }

        // 8. Usuario Inicial Celador Nocturno
        $celadorUser = User::updateOrCreate(
            ['email' => 'celador@finca.com'],
            [
                'name' => 'Don Faustino (Celador Nocturno)',
                'document_number' => '1023456789',
                'username' => 'celador_nocturno',
                'password' => Hash::make('password'),
                'role' => User::ROLE_CELADOR_NOCTURNO,
                'employment_type' => User::TYPE_FIJO,
                'finca_id' => 1,
            ]
        );
        $celadorRole = Role::where('slug', 'celador_nocturno')->first();
        if ($celadorRole) {
            $celadorUser->roles()->syncWithoutDetaching([$celadorRole->id]);
        }
    }
}
