<?php

use App\Http\Controllers\AlimentacionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\CeladorController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\DailyLaborController;
use App\Http\Controllers\FeedingLogController;
use App\Http\Controllers\FeedInventoryController;
use App\Http\Controllers\FishCreditController;
use App\Http\Controllers\FishingAttendanceController;
use App\Http\Controllers\FishSaleController;
use App\Http\Controllers\GeminiAiController;
use App\Http\Controllers\HarvestOrderController;
use App\Http\Controllers\IaAssistantController;
use App\Http\Controllers\MortalidadController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\OfflineSyncController;
use App\Http\Controllers\OwnerDashboardController;
use App\Http\Controllers\PondController;
use App\Http\Controllers\PondSamplingController;
use App\Http\Controllers\RoleDashboardController;
use App\Http\Controllers\SanidadController;
use App\Http\Controllers\SuscripcionController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WeeklyPayrollController;
use App\Http\Controllers\WorkScheduleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - El SAS Piscícola ERP
|--------------------------------------------------------------------------
|
| Rutas protegidas con Multi-Tenant (BelongsToFinca) y control de acceso
| basado en roles (RBAC: jefe_mayor/owner, administrador/admin, trabajador/worker).
|
*/

// Ruta pública para verificar estado de la API
Route::get('/health', function () {
    return response()->json([
        'status' => 'online',
        'sistema' => 'El SAS Piscícola ERP',
        'timestamp' => now()->toDateTimeString(),
    ]);
});

// Ruta pública para sincronización de reloj en vivo (12 horas AM/PM America/Bogota)
Route::get('/calendar-events/server-time', [CalendarEventController::class, 'serverTime'])
    ->name('api.calendar_events.server_time');

// Autenticación API RESTful (Sanctum)
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Rutas protegidas por autenticación Sanctum o Sesión Web
Route::middleware(['auth:sanctum,web'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');

    // Perfil del usuario autenticado (todos los roles)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Dashboards por Rol
    Route::prefix('dashboards')->group(function () {
        Route::get('/jefe', [RoleDashboardController::class, 'jefeDashboard'])
            ->middleware('role:jefe_mayor,owner,jefe_finca')
            ->name('api.dashboards.jefe');

        Route::get('/admin', [RoleDashboardController::class, 'adminDashboard'])
            ->middleware('role:administrador,admin')
            ->name('api.dashboards.admin');

        Route::get('/trabajador', [RoleDashboardController::class, 'trabajadorDashboard'])
            ->middleware('role:trabajador,worker')
            ->name('api.dashboards.trabajador');
    });

    // Módulo Gerencial / Dueño / Jefe de Finca (Acceso Total y Exclusivo)
    Route::prefix('owner')->middleware('role:owner,jefe_finca,jefe_mayor')->group(function () {
        Route::get('/overview', [OwnerDashboardController::class, 'overview'])->name('api.owner.overview');
        Route::get('/financial-report', [OwnerDashboardController::class, 'financialReport'])->name('api.owner.financial_report');
        Route::get('/team-members', [OwnerDashboardController::class, 'teamMembers'])->name('api.owner.team_members');
    });

    // Módulo 2: Asistencia a Pesca (Lunes / Martes festivo)
    Route::prefix('fishing-attendances')->group(function () {
        Route::get('/', [FishingAttendanceController::class, 'index'])
            ->name('api.fishing_attendances.index');

        Route::post('/', [FishingAttendanceController::class, 'store'])
            ->middleware('role:administrador,admin,jefe_mayor,owner')
            ->name('api.fishing_attendances.store');
    });

    // Módulo 2: Descuento de Pescado Fiado ($7.000/kg)
    Route::prefix('fish-credits')->group(function () {
        Route::get('/', [FishCreditController::class, 'index'])
            ->name('api.fish_credits.index');

        Route::post('/', [FishCreditController::class, 'store'])
            ->middleware('role:administrador,admin,jefe_mayor,owner')
            ->name('api.fish_credits.store');
    });

    // Módulo 3: Gestión de Estanques y Muestreo Sabatino
    Route::prefix('ponds')->group(function () {
        Route::get('/lifecycle', [PondSamplingController::class, 'pondsIndex'])
            ->name('api.ponds.lifecycle');

        Route::post('/', [PondController::class, 'store'])
            ->middleware('role:admin,administrador,jefe_mayor')
            ->name('api.ponds.store');

        Route::post('/{pond}/calculate-ration', [PondController::class, 'calculateRation'])
            ->middleware('role:admin,administrador,worker,trabajador')
            ->name('api.ponds.calculate_ration');

        Route::post('/{pond}/apply-ration', [PondController::class, 'applyRation'])
            ->middleware('role:admin,administrador,worker,trabajador')
            ->name('api.ponds.apply_ration');
    });

    Route::prefix('pond-samplings')->group(function () {
        Route::get('/', [PondSamplingController::class, 'index'])
            ->name('api.pond_samplings.index');

        Route::post('/', [PondSamplingController::class, 'store'])
            ->middleware('role:admin,administrador,jefe_mayor,owner')
            ->name('api.pond_samplings.store');

        Route::get('/{pondSampling}', [PondSamplingController::class, 'show'])
            ->name('api.pond_samplings.show');
    });

    // Módulo 4: Alimentación, Bodega y Turnos Rotativos
    Route::prefix('warehouse')->group(function () {
        Route::get('/inventory-status', [WarehouseController::class, 'inventoryStatus'])
            ->name('api.warehouse.inventory_status');

        Route::post('/movements', [WarehouseController::class, 'storeMovement'])
            ->middleware('role:admin,administrador,jefe_mayor')
            ->name('api.warehouse.movements');

        Route::post('/feeding-logs', [WarehouseController::class, 'storeFeedingLog'])
            ->middleware('role:admin,administrador,worker,trabajador')
            ->name('api.warehouse.feeding_logs');

        Route::get('/rotative-schedules', [WarehouseController::class, 'rotativeSchedulesIndex'])
            ->name('api.warehouse.rotative_schedules.index');

        Route::post('/rotative-schedules', [WarehouseController::class, 'storeRotativeSchedule'])
            ->middleware('role:admin,administrador,jefe_mayor,owner')
            ->name('api.warehouse.rotative_schedules.store');
    });

    // Inventario tradicional de Alimento
    Route::prefix('feed-inventories')->group(function () {
        Route::get('/', [FeedInventoryController::class, 'index'])
            ->middleware('role:admin,administrador,worker,trabajador,guard')
            ->name('api.feed_inventories.index');

        Route::post('/', [FeedInventoryController::class, 'store'])
            ->middleware('role:admin,administrador')
            ->name('api.feed_inventories.store');
    });

    // Bitácoras e Historial de Alimentación Diaria
    Route::prefix('feeding-logs')->group(function () {
        Route::get('/', [FeedingLogController::class, 'index'])
            ->middleware('role:admin,administrador,worker,trabajador,guard')
            ->name('api.feeding_logs.index');

        Route::get('/{feedingLog}', [FeedingLogController::class, 'show'])
            ->middleware('role:admin,administrador,worker,trabajador,guard')
            ->name('api.feeding_logs.show');

        Route::post('/', [FeedingLogController::class, 'store'])
            ->middleware('role:admin,administrador,worker,trabajador')
            ->name('api.feeding_logs.store');
    });

    // Programación de Turnos y Asignación de Trabajadores
    Route::prefix('work-schedules')->group(function () {
        Route::get('/who-is-on-duty', [WorkScheduleController::class, 'whoIsOnDuty'])
            ->middleware('role:admin,administrador,worker,trabajador,guard')
            ->name('api.work_schedules.who_is_on_duty');

        Route::get('/', [WorkScheduleController::class, 'index'])
            ->middleware('role:admin,administrador,worker,trabajador,guard')
            ->name('api.work_schedules.index');

        Route::get('/{workSchedule}', [WorkScheduleController::class, 'show'])
            ->middleware('role:admin,administrador,worker,trabajador,guard')
            ->name('api.work_schedules.show');

        Route::post('/', [WorkScheduleController::class, 'store'])
            ->middleware('role:admin,administrador')
            ->name('api.work_schedules.store');

        Route::match(['put', 'patch'], '/{workSchedule}', [WorkScheduleController::class, 'update'])
            ->middleware('role:admin,administrador')
            ->name('api.work_schedules.update');

        Route::delete('/{workSchedule}', [WorkScheduleController::class, 'destroy'])
            ->middleware('role:admin,administrador')
            ->name('api.work_schedules.destroy');
    });

    // Módulo 5: Órdenes de Cosecha, Pesaje de Báscula y Despacho
    Route::prefix('harvest-orders')->group(function () {
        Route::get('/', [HarvestOrderController::class, 'index'])
            ->middleware('role:admin,administrador,worker,trabajador,guard')
            ->name('api.harvest_orders.index');

        Route::get('/{harvestOrder}', [HarvestOrderController::class, 'show'])
            ->middleware('role:admin,administrador,worker,trabajador,guard')
            ->name('api.harvest_orders.show');

        // Paso 1: Jefe Mayor programa cosecha
        Route::post('/', [HarvestOrderController::class, 'store'])
            ->middleware('role:owner,jefe_finca,jefe_mayor,admin,administrador')
            ->name('api.harvest_orders.store');

        // Paso 2: Administrador registra pesaje bruto con deducción de tara de canastillas
        Route::post('/{harvestOrder}/gross-weight', [HarvestOrderController::class, 'recordGrossWeight'])
            ->middleware('role:admin,administrador')
            ->name('api.harvest_orders.gross_weight');

        // Paso 3: Proceso de limpieza, kilos limpios, canastas, conductor, comprador y destino
        Route::post('/{harvestOrder}/dispatch', [HarvestOrderController::class, 'recordDispatch'])
            ->middleware('role:admin,administrador')
            ->name('api.harvest_orders.dispatch');

        // Registro directo de sesión de pesaje por tandas con tara
        Route::post('/dispatch-session', [HarvestOrderController::class, 'storeDispatchSession'])
            ->middleware('role:admin,administrador,jefe_mayor,owner,jefe_finca')
            ->name('api.harvest_orders.dispatch_session');
    });

    // Módulo 6: Ventas de Pescado (Caja Diaria con precios diferenciados)
    Route::prefix('fish-sales')->group(function () {
        Route::get('/', [FishSaleController::class, 'index'])
            ->middleware('role:admin,administrador,guard')
            ->name('api.fish_sales.index');

        // Registro de venta diaria (visitante $9.000 vs trabajador $7.000)
        Route::post('/', [FishSaleController::class, 'store'])
            ->middleware('role:admin,administrador')
            ->name('api.fish_sales.store');

        // Registro directo de efectivo visitante con conversión automática a kilos
        Route::post('/visitor-cash', [FishSaleController::class, 'storeVisitorCash'])
            ->middleware('role:admin,administrador,guard')
            ->name('api.fish_sales.visitor_cash');

        // Balance y arqueo de caja diaria por categoría
        Route::get('/daily-cashbox', [FishSaleController::class, 'dailyCashbox'])
            ->middleware('role:admin,administrador')
            ->name('api.fish_sales.daily_cashbox');
    });

    // Libreta de Jornales y Nómina Semanal
    Route::prefix('daily-labors')->group(function () {
        Route::get('/', [DailyLaborController::class, 'index'])
            ->middleware('role:admin,administrador,worker,trabajador,guard')
            ->name('api.daily_labors.index');

        Route::post('/', [DailyLaborController::class, 'store'])
            ->middleware('role:admin,administrador')
            ->name('api.daily_labors.store');

        Route::delete('/{dailyLabor}', [DailyLaborController::class, 'destroy'])
            ->middleware('role:admin,administrador')
            ->name('api.daily_labors.destroy');
    });

    Route::prefix('weekly-payroll')->group(function () {
        Route::get('/', [WeeklyPayrollController::class, 'index'])
            ->middleware('role:admin,administrador')
            ->name('api.weekly_payroll.index');

        Route::get('/calculate', [WeeklyPayrollController::class, 'calculateWeeklyPayroll'])
            ->middleware('role:admin,administrador')
            ->name('api.weekly_payroll.calculate');

        Route::post('/settle', [WeeklyPayrollController::class, 'settleWeeklyPayroll'])
            ->middleware('role:admin,administrador')
            ->name('api.weekly_payroll.settle');

        Route::get('/export-csv', [WeeklyPayrollController::class, 'exportCsv'])
            ->middleware('role:admin,administrador,jefe_mayor,owner')
            ->name('api.weekly_payroll.export_csv');

        Route::get('/{payrollSettlement}', [WeeklyPayrollController::class, 'show'])
            ->middleware('role:admin,administrador')
            ->name('api.weekly_payroll.show');
    });

    // Módulo 7: Comunicación y Novedades (Tareas, Permisos, Horas Extras)
    Route::prefix('communication')->group(function () {
        // Tareas del Administrador
        Route::get('/tasks', [CommunicationController::class, 'tasksIndex'])
            ->name('api.communication.tasks.index');

        Route::post('/tasks', [CommunicationController::class, 'storeTask'])
            ->middleware('role:admin,administrador')
            ->name('api.communication.tasks.store');

        Route::post('/tasks/{adminTask}/complete', [CommunicationController::class, 'completeTask'])
            ->name('api.communication.tasks.complete');

        // Permisos Laborales
        Route::get('/leave-requests', [CommunicationController::class, 'leaveRequestsIndex'])
            ->name('api.communication.leave_requests.index');

        Route::post('/leave-requests', [CommunicationController::class, 'storeLeaveRequest'])
            ->name('api.communication.leave_requests.store');

        Route::post('/leave-requests/{leaveRequest}/review', [CommunicationController::class, 'reviewLeaveRequest'])
            ->middleware('role:jefe_mayor,owner,jefe_finca,admin,administrador')
            ->name('api.communication.leave_requests.review');

        // Horas Extras
        Route::get('/overtime-records', [CommunicationController::class, 'overtimeIndex'])
            ->name('api.communication.overtime.index');

        Route::post('/overtime-records', [CommunicationController::class, 'storeOvertime'])
            ->name('api.communication.overtime.store');
    });

    // Calendario y Agenda Operativa en Vivo
    Route::prefix('calendar-events')->group(function () {
        Route::get('/admin-alerts', [CalendarEventController::class, 'adminAlerts'])
            ->middleware('role:admin,administrador,owner,jefe_finca,jefe_mayor')
            ->name('api.calendar_events.admin_alerts');

        Route::post('/sync', [CalendarEventController::class, 'sync'])
            ->middleware('role:owner,jefe_finca,jefe_mayor,admin,administrador')
            ->name('api.calendar_events.sync');

        Route::get('/', [CalendarEventController::class, 'index'])
            ->middleware('role:owner,jefe_finca,jefe_mayor,admin,administrador,worker,trabajador,guard')
            ->name('api.calendar_events.index');

        Route::post('/', [CalendarEventController::class, 'store'])
            ->middleware('role:owner,jefe_finca,jefe_mayor,admin,administrador')
            ->name('api.calendar_events.store');

        Route::get('/{calendarEvent}', [CalendarEventController::class, 'show'])
            ->middleware('role:owner,jefe_finca,jefe_mayor,admin,administrador,worker,trabajador,guard')
            ->name('api.calendar_events.show');

        Route::match(['put', 'patch'], '/{calendarEvent}', [CalendarEventController::class, 'update'])
            ->middleware('role:owner,jefe_finca,jefe_mayor,admin,administrador')
            ->name('api.calendar_events.update');

        Route::delete('/{calendarEvent}', [CalendarEventController::class, 'destroy'])
            ->middleware('role:owner,jefe_finca,jefe_mayor,admin,administrador')
            ->name('api.calendar_events.destroy');
    });

    // Asistente de Inteligencia Artificial (Google Gemini)
    Route::prefix('ai')->group(function () {
        Route::post('/chat', [GeminiAiController::class, 'chat'])
            ->name('api.ai.chat');
    });

    // Asistente Técnico Acuícola (Zootécnico con contexto en tiempo real)
    Route::post('/asistente-ia/chat', [IaAssistantController::class, 'chat'])
        ->name('api.asistente_ia.chat');

    // Módulo Nocturno de Celador (Guardia, Rondas, Aireadores y Alerta de Boqueo)
    Route::prefix('celador')->group(function () {
        Route::get('/overview', [CeladorController::class, 'index'])
            ->name('api.celador.overview');

        Route::post('/rondas', [CeladorController::class, 'storeRonda'])
            ->name('api.celador.rondas.store');

        Route::post('/aireadores/encender', [CeladorController::class, 'encenderAireador'])
            ->name('api.celador.aireadores.encender');

        Route::post('/aireadores/{controlAireador}/apagar', [CeladorController::class, 'apagarAireador'])
            ->name('api.celador.aireadores.apagar');

        Route::post('/alerta-boqueo', [CeladorController::class, 'alertaBoqueo'])
            ->name('api.celador.alerta_boqueo');

        Route::post('/entrada', [CeladorController::class, 'registrarEntrada'])
            ->name('api.celador.entrada');

        Route::post('/salida', [CeladorController::class, 'registrarSalida'])
            ->name('api.celador.salida');
    });

    // Módulo del Trabajador de Campo
    Route::prefix('trabajador')->group(function () {
        Route::get('/dashboard', [TrabajadorController::class, 'dashboard'])
            ->name('api.trabajador.dashboard');

        Route::post('/mortalidad', [TrabajadorController::class, 'reportarMortalidad'])
            ->name('api.trabajador.mortalidad');

        Route::post('/alimentacion', [TrabajadorController::class, 'storeAlimentacion'])
            ->name('api.trabajador.alimentacion');

        Route::post('/tareas/{adminTask}/completar', [TrabajadorController::class, 'completarTarea'])
            ->name('api.trabajador.tareas.completar');
    });

    /*
    |--------------------------------------------------------------------------
    | AquaSmart SaaS Extended Architecture Routes (API v1)
    |--------------------------------------------------------------------------
    */
    Route::prefix('v1')->group(function () {

        // 1. Módulo de Sanidad y Retiro ICA
        Route::prefix('sanidad')->group(function () {
            Route::get('/', [SanidadController::class, 'index'])->name('api.v1.sanidad.index');
            Route::post('/', [SanidadController::class, 'store'])
                ->middleware('role:admin,administrador,jefe_mayor,owner')
                ->name('api.v1.sanidad.store');
            Route::get('/estanques/{estanque}/verificar-retiro', [SanidadController::class, 'verificarRetiro'])
                ->name('api.v1.sanidad.verificar_retiro');
        });

        // 2. Inventario de Alimento (Bodega y Deducción Automática)
        Route::prefix('alimentacion')->group(function () {
            Route::get('/', [AlimentacionController::class, 'index'])->name('api.v1.alimentacion.index');
            Route::post('/', [AlimentacionController::class, 'store'])
                ->middleware('role:admin,administrador,worker,trabajador')
                ->name('api.v1.alimentacion.store');
            Route::get('/bodega-status', [AlimentacionController::class, 'estadoBodega'])->name('api.v1.alimentacion.bodega_status');
        });

        // 3. Módulo de Ventas / Comercialización
        Route::prefix('ventas')->group(function () {
            Route::get('/', [VentaController::class, 'index'])->name('api.v1.ventas.index');
            Route::post('/', [VentaController::class, 'store'])
                ->middleware('role:admin,administrador,jefe_mayor,owner')
                ->name('api.v1.ventas.store');
        });

        // 4. Módulo de Personal y Nómina (Destajo, Jornal y Cortes Sabatinos)
        Route::prefix('nomina')->group(function () {
            Route::get('/personal', [NominaController::class, 'personalIndex'])->name('api.v1.nomina.personal.index');
            Route::post('/personal', [NominaController::class, 'storePersonal'])
                ->middleware('role:admin,administrador,jefe_mayor,owner')
                ->name('api.v1.nomina.personal.store');
            Route::get('/liquidaciones', [NominaController::class, 'indexLiquidaciones'])->name('api.v1.nomina.liquidaciones.index');
            Route::post('/liquidar', [NominaController::class, 'liquidarSemana'])
                ->middleware('role:admin,administrador,jefe_mayor,owner')
                ->name('api.v1.nomina.liquidar');
        });

        // 5. MortalidadController (Deducción de lote y recálculo de biomasa)
        Route::prefix('mortalidades')->group(function () {
            Route::get('/', [MortalidadController::class, 'index'])->name('api.v1.mortalidades.index');
            Route::post('/', [MortalidadController::class, 'store'])
                ->middleware('role:admin,administrador,worker,trabajador')
                ->name('api.v1.mortalidades.store');
        });

        // 6. Capa de Negocio SaaS y Super-Admin
        Route::prefix('saas')->group(function () {
            Route::get('/fincas', [SuscripcionController::class, 'panelSuperAdmin'])
                ->middleware('role:superadmin,owner,jefe_mayor')
                ->name('api.v1.saas.fincas');
            Route::post('/fincas/{finca}/suspender', [SuscripcionController::class, 'suspenderAcceso'])
                ->middleware('role:superadmin,owner,jefe_mayor')
                ->name('api.v1.saas.fincas.suspender');
            Route::post('/fincas/{finca}/activar', [SuscripcionController::class, 'activarAcceso'])
                ->middleware('role:superadmin,owner,jefe_mayor')
                ->name('api.v1.saas.fincas.activar');
            Route::post('/users/{user}/impersonate', [SuscripcionController::class, 'impersonate'])
                ->middleware('role:superadmin,owner,jefe_mayor')
                ->name('api.v1.saas.users.impersonate');
            Route::post('/onboarding', [SuscripcionController::class, 'onboardingFinca'])
                ->middleware('role:superadmin,owner,jefe_mayor')
                ->name('api.v1.saas.onboarding');
        });

        // 7. Modo Offline para Campo (Sincronización por lotes LWW)
        Route::post('/sync', [OfflineSyncController::class, 'batchSync'])->name('api.v1.sync');
        Route::post('/sync/batch', [OfflineSyncController::class, 'batchSync'])->name('api.v1.sync.batch');
    });
});
