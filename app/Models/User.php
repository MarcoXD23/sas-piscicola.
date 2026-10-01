<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\BelongsToTenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'username', 'document_number', 'password', 'tenant_id', 'finca_id', 'rol', 'role', 'roles_asignados', 'aprobado', 'aprobado_at', 'employment_type', 'accumulated_fish_credit'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant, HasApiTokens, HasFactory, Notifiable;

    public const ROLE_PROPIETARIO = 'propietario';

    public const ROLE_JEFE_MAYOR = 'propietario';

    public const ROLE_OWNER = 'owner';

    public const ROLE_JEFE_FINCA = 'jefe_finca';

    public const ROLE_JEFE = 'jefe';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_ADMINISTRADOR = 'administrador';

    public const ROLE_TECNICO_ACUICOLA = 'tecnico_acuicola';

    public const ROLE_WORKER = 'worker';

    public const ROLE_TRABAJADOR = 'trabajador';

    public const ROLE_OPERARIO_CAMPO = 'operario_campo';

    public const ROLE_OPERARIO_ALIMENTADOR = 'operario_alimentador';

    public const ROLE_GUARD = 'guard';

    public const ROLE_CELADOR_NOCTURNO = 'celador_nocturno';

    public const ROLE_CELADOR = 'celador';

    public const ROLE_PENDIENTE = 'pendiente';

    public const TYPE_FIJO = 'fijo';

    public const TYPE_DESTAJO_SEMANAL = 'destajo_semanal';

    public const TYPE_TEMPORAL = 'temporal';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'accumulated_fish_credit' => 'decimal:2',
            'roles_asignados' => 'array',
            'aprobado' => 'boolean',
            'aprobado_at' => 'datetime',
        ];
    }

    /**
     * Relación muchos a muchos con roles del sistema.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Verifica si el usuario tiene un rol o varios roles específicos.
     * Soporta alias como propietario/jefe_mayor/owner, tecnico_acuicola/administrador, operario_campo/trabajador, celador_nocturno/guard.
     *
     * @param  string|array<int, string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        $allowed = is_array($roles) ? $roles : [$roles];

        $expandedRoles = [];
        foreach ($allowed as $r) {
            $expandedRoles[] = $r;
            if (in_array($r, [self::ROLE_PROPIETARIO, self::ROLE_JEFE_MAYOR, self::ROLE_OWNER, self::ROLE_JEFE, self::ROLE_JEFE_FINCA, 'jefe_mayor'], true)) {
                $expandedRoles = array_merge($expandedRoles, [self::ROLE_PROPIETARIO, self::ROLE_JEFE_MAYOR, self::ROLE_OWNER, self::ROLE_JEFE, self::ROLE_JEFE_FINCA, 'jefe_mayor', 'propietario']);
            }
            if (in_array($r, [self::ROLE_ADMIN, self::ROLE_ADMINISTRADOR, self::ROLE_TECNICO_ACUICOLA], true)) {
                $expandedRoles = array_merge($expandedRoles, [self::ROLE_ADMIN, self::ROLE_ADMINISTRADOR, self::ROLE_TECNICO_ACUICOLA]);
            }
            if (in_array($r, [self::ROLE_WORKER, self::ROLE_TRABAJADOR, self::ROLE_OPERARIO_CAMPO, self::ROLE_OPERARIO_ALIMENTADOR], true)) {
                $expandedRoles = array_merge($expandedRoles, [self::ROLE_WORKER, self::ROLE_TRABAJADOR, self::ROLE_OPERARIO_CAMPO, self::ROLE_OPERARIO_ALIMENTADOR]);
            }
            if (in_array($r, [self::ROLE_GUARD, self::ROLE_CELADOR_NOCTURNO, self::ROLE_CELADOR], true)) {
                $expandedRoles = array_merge($expandedRoles, [self::ROLE_GUARD, self::ROLE_CELADOR_NOCTURNO, self::ROLE_CELADOR]);
            }
        }

        $allUserRoles = array_filter([$this->rol, $this->role]);
        if (! empty($this->roles_asignados) && is_array($this->roles_asignados)) {
            $allUserRoles = array_merge($allUserRoles, $this->roles_asignados);
        }
        if ($this->relationLoaded('roles')) {
            $allUserRoles = array_merge($allUserRoles, $this->roles->pluck('slug')->toArray());
        } elseif (Schema::hasTable('role_user')) {
            $allUserRoles = array_merge($allUserRoles, $this->roles()->pluck('slug')->toArray());
        }

        return count(array_intersect(array_unique($allUserRoles), array_unique($expandedRoles))) > 0;
    }

    public function canManageUsers(): bool
    {
        return $this->isPropietario() || $this->isAdmin();
    }

    public function isOwner(): bool
    {
        return $this->hasRole([self::ROLE_PROPIETARIO, self::ROLE_OWNER, self::ROLE_JEFE_FINCA, self::ROLE_JEFE, self::ROLE_JEFE_MAYOR, 'jefe_mayor', 'propietario']);
    }

    public function isPropietario(): bool
    {
        return $this->isOwner();
    }

    public function isJefe(): bool
    {
        return $this->isOwner();
    }

    public function isJefeMayor(): bool
    {
        return $this->isOwner();
    }

    public function isAdmin(): bool
    {
        return $this->isAdministrador();
    }

    public function isAdministrador(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_ADMINISTRADOR, self::ROLE_TECNICO_ACUICOLA], true)
            || in_array($this->rol, [self::ROLE_ADMIN, self::ROLE_ADMINISTRADOR, self::ROLE_TECNICO_ACUICOLA], true);
    }

    public function isTecnicoAcuicola(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_ADMINISTRADOR, self::ROLE_TECNICO_ACUICOLA], true)
            || in_array($this->rol, [self::ROLE_ADMIN, self::ROLE_ADMINISTRADOR, self::ROLE_TECNICO_ACUICOLA], true);
    }

    public function isWorker(): bool
    {
        if ($this->isOwner() || $this->isAdmin()) {
            return false;
        }

        return in_array($this->role, [self::ROLE_WORKER, self::ROLE_TRABAJADOR, self::ROLE_OPERARIO_CAMPO, self::ROLE_OPERARIO_ALIMENTADOR], true)
            || in_array($this->rol, [self::ROLE_WORKER, self::ROLE_TRABAJADOR, self::ROLE_OPERARIO_CAMPO, self::ROLE_OPERARIO_ALIMENTADOR], true);
    }

    public function isTrabajador(): bool
    {
        return $this->isWorker();
    }

    public function isOperarioCampo(): bool
    {
        return $this->isWorker();
    }

    public function isGuard(): bool
    {
        if ($this->isOwner() || $this->isAdmin()) {
            return false;
        }

        return in_array($this->role, [self::ROLE_GUARD, self::ROLE_CELADOR_NOCTURNO, self::ROLE_CELADOR], true)
            || in_array($this->rol, [self::ROLE_GUARD, self::ROLE_CELADOR_NOCTURNO, self::ROLE_CELADOR], true);
    }

    public function isCelador(): bool
    {
        return $this->isGuard();
    }

    /**
     * Relación con turnos operativos de la agenda.
     */
    public function turnosAsignados(): HasMany
    {
        return $this->hasMany(AgendaTurno::class, 'user_id');
    }

    /**
     * Retorna el turno activo o programado para hoy en la Agenda Operativa.
     */
    public function turnoHoy(): ?AgendaTurno
    {
        return $this->turnosAsignados()
            ->whereDate('fecha', now()->toDateString())
            ->whereIn('estado', [AgendaTurno::ESTADO_ACTIVO, AgendaTurno::ESTADO_PROGRAMADO, 'activo', 'programado'])
            ->first();
    }

    /**
     * Badge dinámico del rol y turno actual según la fecha.
     */
    public function badgeRolHoy(): array
    {
        if ($this->isPropietario()) {
            return [
                'label' => 'Propietario / Gerente General',
                'sublabel' => 'Acceso Global & Directivo',
                'color' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
            ];
        }

        if ($this->isAdministrador()) {
            return [
                'label' => 'Administrador de Finca',
                'sublabel' => 'Operaciones & Gestión',
                'color' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
            ];
        }

        $turno = $this->turnoHoy();
        if ($turno) {
            if ($turno->rol_asignado === AgendaTurno::ROL_SEGURIDAD_NOCHE) {
                return [
                    'label' => 'Rol Hoy: Celador',
                    'sublabel' => 'Seguridad & Noche',
                    'color' => 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
                ];
            }

            if ($turno->rol_asignado === AgendaTurno::ROL_ALIMENTADOR) {
                return [
                    'label' => 'Turno Hoy: Alimentador',
                    'sublabel' => 'Labor de Campo Activa',
                    'color' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                ];
            }
        }

        if ($this->isCelador()) {
            return [
                'label' => 'Celador Nocturno',
                'sublabel' => 'Seguridad & Noche',
                'color' => 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
            ];
        }

        return [
            'label' => 'Operario de Campo',
            'sublabel' => 'Sin Turno Programado Hoy',
            'color' => 'bg-slate-700 text-slate-300 border-slate-600',
        ];
    }

    /**
     * Permiso de acceso al Módulo Nocturno:
     * - Celador nocturno (Lunes a Sábado)
     * - Trabajadores de campo (Domingos en la noche o relevos)
     * - Administrador y Jefe Mayor (Supervisión)
     */
    public function canAccessNocturno(): bool
    {
        return $this->isCelador() || $this->isTrabajador() || $this->isAdmin() || $this->isOwner();
    }

    public function isFijo(): bool
    {
        return ($this->employment_type ?? self::TYPE_FIJO) === self::TYPE_FIJO;
    }

    public function isDestajoSemanal(): bool
    {
        return in_array($this->employment_type, [self::TYPE_DESTAJO_SEMANAL, self::TYPE_TEMPORAL], true);
    }

    public function isTemporal(): bool
    {
        return in_array($this->employment_type, [self::TYPE_DESTAJO_SEMANAL, self::TYPE_TEMPORAL], true);
    }

    public function isPendiente(): bool
    {
        return $this->role === self::ROLE_PENDIENTE;
    }

    /**
     * Devuelve la ruta adecuada de redirección según el rol del usuario.
     */
    public function dashboardRoute(): string
    {
        if ($this->isPendiente()) {
            return route('auth.pending-approval');
        }

        if ($this->isJefe()) {
            return url('/jefe/dashboard');
        }

        if ($this->isAdmin()) {
            return url('/admin/dashboard');
        }

        if ($this->isCelador()) {
            return url('/celador/dashboard');
        }

        if ($this->isTrabajador()) {
            return url('/trabajador/dashboard');
        }

        return route('dashboard.index');
    }

    /**
     * Relaciones del sistema piscícola
     */
    public function bitacorasNocturnas(): HasMany
    {
        return $this->hasMany(BitacoraNocturna::class);
    }

    public function controlesAireadores(): HasMany
    {
        return $this->hasMany(ControlAireador::class);
    }

    public function turnosNocturnos(): HasMany
    {
        return $this->hasMany(TurnoNocturno::class);
    }

    public function fishingAttendances(): HasMany
    {
        return $this->hasMany(FishingAttendance::class);
    }

    public function fishCredits(): HasMany
    {
        return $this->hasMany(FishCredit::class);
    }

    public function rotativeSchedules(): HasMany
    {
        return $this->hasMany(RotativeSchedule::class);
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(AdminTask::class, 'assigned_to_user_id');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(AdminTask::class, 'created_by_user_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function overtimeRecords(): HasMany
    {
        return $this->hasMany(OvertimeRecord::class);
    }

    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class, 'finca_id');
    }

    /**
     * Retorna la Finca asociada o una instancia de fallback con configuración por defecto.
     */
    public function getFincaSeguraAttribute(): Finca
    {
        return $this->finca ?? new Finca([
            'id' => $this->finca_id ?? 1,
            'nombre' => 'Finca Piscícola Principal',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ]);
    }
}
