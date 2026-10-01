<?php

use App\Http\Controllers\ActividadTrabajadorController;
use App\Http\Controllers\AdminLagosController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AgendaOperativaController;
use App\Http\Controllers\AlimentacionController;
use App\Http\Controllers\BodegaController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\CeladorController;
use App\Http\Controllers\DesdobleController;
use App\Http\Controllers\EspecieController;
use App\Http\Controllers\FincaAjustesController;
use App\Http\Controllers\FishSaleController;
use App\Http\Controllers\GeminiAiController;
use App\Http\Controllers\HarvestOrderController;
use App\Http\Controllers\IaAssistantController;
use App\Http\Controllers\IcaReportController;
use App\Http\Controllers\InventarioAlimentoController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\MonthlyFinancialReportController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\RoleDashboardController;
use App\Http\Controllers\SuperAdminFincaController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\TratamientoSanitarioController;
use App\Http\Controllers\WebDashboardController;
use App\Http\Controllers\WeeklyPayrollController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->isPendiente()) {
            return redirect()->route('auth.pending-approval');
        }
        if ($user->isOwner()) {
            return redirect()->route('jefe.dashboard');
        } elseif ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isCelador()) {
            return redirect()->route('celador.dashboard');
        } elseif ($user->isWorker()) {
            return redirect()->route('trabajador.dashboard');
        }

        return redirect()->route('dashboard.index');
    }

    return redirect()->route('login');
})->name('home');

Route::get('/agenda', [CalendarEventController::class, 'agendaView'])->name('agenda.index');

Route::middleware(['auth'])->group(function () {
    // Dashboard principal con KPIs
    Route::get('/dashboard', [WebDashboardController::class, 'index'])->name('dashboard.index');

    // Paneles dedicados por rol (URLs estándar y aliases)
    Route::get('/jefe/dashboard', [RoleDashboardController::class, 'jefeDashboard'])
        ->middleware('role:jefe_mayor,owner,jefe_finca,jefe')
        ->name('jefe.dashboard');

    Route::get('/dashboard/jefe', [RoleDashboardController::class, 'jefeDashboard'])
        ->middleware('role:jefe_mayor,owner,jefe_finca,jefe')
        ->name('dashboard.jefe');

    Route::get('/admin/dashboard', [RoleDashboardController::class, 'adminDashboard'])
        ->middleware('role:administrador,admin')
        ->name('admin.dashboard');

    Route::get('/dashboard/admin', [RoleDashboardController::class, 'adminDashboard'])
        ->middleware('role:administrador,admin')
        ->name('dashboard.admin');

    Route::get('/trabajador/dashboard', [TrabajadorController::class, 'dashboard'])
        ->middleware('role:trabajador,worker,administrador,admin,jefe_mayor,owner')
        ->name('trabajador.dashboard');

    Route::get('/dashboard/trabajador', [TrabajadorController::class, 'dashboard'])
        ->middleware('role:trabajador,worker,administrador,admin,jefe_mayor,owner')
        ->name('dashboard.trabajador');

    // Agenda de Finca, Sincronización y Asignación de Turnos Operativos
    Route::get('/admin/agenda', [CalendarEventController::class, 'agendaView'])->name('admin.agenda.index');
    Route::post('/agenda/guardar', [AgendaController::class, 'store'])->name('agenda.store');
    Route::post('/agenda/sync', [AgendaController::class, 'sync'])->name('agenda.sync');
    Route::post('/agenda/turnos/semanal', [AgendaOperativaController::class, 'storeSemanal'])->name('agenda.turnos.semanal');
    Route::post('/admin/agenda/turnos', [AgendaOperativaController::class, 'storeSemanal'])->name('admin.agenda.turnos');
    Route::post('/agenda/turnos/fin-de-semana', [AgendaOperativaController::class, 'storeFinDeSemana'])->name('agenda.turnos.findesemana');
    Route::get('/api/agenda/turnos', [AgendaOperativaController::class, 'index'])->name('api.agenda.turnos');

    // Acciones operativas directas del trabajador de campo
    Route::get('/trabajador/mortalidad', [TrabajadorController::class, 'mortalidad'])->name('trabajador.mortalidad.index');
    Route::post('/trabajador/mortalidad', [TrabajadorController::class, 'reportarMortalidad'])->name('trabajador.mortalidad');
    Route::get('/trabajador/tareas', [TrabajadorController::class, 'tareas'])->name('trabajador.tareas.index');
    Route::post('/trabajador/tareas/{adminTask}/completar', [TrabajadorController::class, 'completarTarea'])->name('trabajador.tareas.completar');
    Route::get('/trabajador/horas-extras', [TrabajadorController::class, 'horasExtras'])->name('trabajador.horas-extras.index');
    Route::post('/trabajador/horas-extras', [TrabajadorController::class, 'storeHorasExtras'])->name('trabajador.horas-extras.store');
    Route::get('/trabajador/permisos', [TrabajadorController::class, 'permisos'])->name('trabajador.permisos.index');
    Route::post('/trabajador/permisos', [TrabajadorController::class, 'storePermiso'])->name('trabajador.permisos.store');
    Route::get('/trabajador/saldo-pescado', [TrabajadorController::class, 'saldoPescado'])->name('trabajador.saldo-pescado.index');

    // Módulo de Alimentación: Validado por Turno Diario en Agenda
    Route::middleware(['verificar.turno.diario'])->group(function () {
        Route::post('/trabajador/alimentacion', [TrabajadorController::class, 'storeAlimentacion'])->name('trabajador.alimentacion');
        Route::get('/operaciones/alimentacion', [TrabajadorController::class, 'dashboard'])->name('operaciones.alimentacion.index');
    });

    // Módulos Financieros y de Control Exclusivos del Propietario (Sin rol cajero)
    Route::middleware(['role:propietario'])->group(function () {
        Route::get('/cosechas', [WebDashboardController::class, 'harvests'])->name('cosechas.index');
        Route::get('/admin/bascula', [WebDashboardController::class, 'harvests'])->name('admin.bascula.index');

        Route::get('/nomina', [WebDashboardController::class, 'payroll'])->name('nomina.index');
        Route::get('/admin/nomina', [WebDashboardController::class, 'payroll'])->name('admin.nomina.index');
        Route::get('/nomina/export-csv', [WeeklyPayrollController::class, 'exportCsv'])->name('nomina.export_csv');

        Route::get('/ventas', [WebDashboardController::class, 'sales'])->name('ventas.index');
        Route::get('/admin/ventas', [WebDashboardController::class, 'sales'])->name('admin.ventas.index');

        // Control de Alimento sin Costos Unitarios (Exclusivo Propietario)
        Route::post('/admin/inventario-alimento/ingresar', [InventarioAlimentoController::class, 'ingresar'])->name('admin.inventario-alimento.ingresar');
        Route::get('/admin/inventario-alimento', [InventarioAlimentoController::class, 'index'])->name('admin.inventario-alimento.index');
    });

    // Módulo de Guardia Nocturna (Seguridad & Noche): Validado por Turno o Rol Celador
    Route::middleware(['verificar.turno.seguridad'])->group(function () {
        Route::get('/celador/dashboard', [CeladorController::class, 'index'])->name('celador.dashboard');
        Route::get('/celador', [CeladorController::class, 'index'])->name('celador.index');
        Route::get('/seguridad-noche', [CeladorController::class, 'index'])->name('seguridad-noche.index');
    });

    // Módulo: Enciclopedia / Guía de Especies Piscícolas de Colombia
    Route::get('/guia-peces', [EspecieController::class, 'index'])->name('guia-peces.index');
    Route::get('/guia-peces/{especie}', [EspecieController::class, 'show'])->name('guia-peces.show');

    // Módulo: Configuración y Ajustes de la Finca (Jefe Mayor / Administrador)
    Route::get('/admin/ajustes', [FincaAjustesController::class, 'index'])
        ->middleware('role:jefe_mayor,owner,jefe_finca,jefe,administrador,admin')
        ->name('admin.ajustes');
    Route::post('/admin/ajustes', [FincaAjustesController::class, 'update'])
        ->middleware('role:jefe_mayor,owner,jefe_finca,jefe,administrador,admin')
        ->name('admin.ajustes.update');

    // Módulo de Alimentación y Control de Bodega de Concentrados
    Route::get('/admin/alimentacion/historial', [AlimentacionController::class, 'historial'])
        ->middleware('role:propietario,administrador,admin,jefe_mayor,owner,jefe_finca,jefe,tecnico_acuicola')
        ->name('admin.alimentacion.historial');

    Route::get('/admin/bodega', [BodegaController::class, 'index'])
        ->middleware('role:administrador,admin,jefe_mayor,owner,jefe_finca,jefe')
        ->name('admin.bodega.index');
    Route::get('/bodega', [BodegaController::class, 'index'])
        ->middleware('role:administrador,admin,jefe_mayor,owner,jefe_finca,jefe')
        ->name('bodega.index');
    Route::post('/admin/bodega/despacho', [BodegaController::class, 'storeDespacho'])
        ->middleware('role:propietario,jefe_mayor,owner,jefe_finca,jefe')
        ->name('admin.bodega.despacho');
    Route::post('/admin/bodega/despachos/{id}/confirmar', [BodegaController::class, 'confirmarRecepcion'])
        ->middleware('role:administrador,admin,propietario,jefe_mayor,owner,jefe_finca,jefe')
        ->name('admin.bodega.despachos.confirmar');
    Route::post('/admin/bodega/{id}/confirmar', [BodegaController::class, 'confirmarRecepcion'])
        ->middleware('role:administrador,admin,propietario,jefe_mayor,owner,jefe_finca,jefe')
        ->name('admin.bodega.confirmar');
    Route::post('/admin/bodega/entrada', [BodegaController::class, 'storeEntrada'])
        ->middleware('role:administrador,admin,jefe_mayor,owner,jefe_finca,jefe')
        ->name('admin.bodega.entrada');
    Route::post('/admin/bodega/{id}/ajuste', [BodegaController::class, 'ajuste'])
        ->middleware('role:administrador,admin,jefe_mayor,owner,jefe_finca,jefe')
        ->name('admin.bodega.ajuste');

    // Módulo: SuperAdmin / Gestión Multi-Inquilino y Módulos de Fincas
    Route::get('/superadmin/fincas', [SuperAdminFincaController::class, 'index'])
        ->middleware('role:jefe_mayor,owner,administrador,admin')
        ->name('superadmin.fincas.index');
    Route::get('/superadmin/fincas/{id}/editar', [SuperAdminFincaController::class, 'edit'])
        ->middleware('role:jefe_mayor,owner,administrador,admin')
        ->name('superadmin.fincas.edit');
    Route::put('/superadmin/fincas/{id}', [SuperAdminFincaController::class, 'update'])
        ->middleware('role:jefe_mayor,owner,administrador,admin')
        ->name('superadmin.fincas.update');

    // Módulo 1: Desdobles y Traslados de Peces entre Estanques
    Route::get('/traslados', [DesdobleController::class, 'index'])->name('traslados.index');
    Route::post('/traslados', [DesdobleController::class, 'store'])->name('traslados.store');
    Route::get('/desdobles', [DesdobleController::class, 'index'])->name('desdobles.index');
    Route::post('/desdobles', [DesdobleController::class, 'store'])->name('desdobles.store');
    Route::post('/admin/desdobles', [DesdobleController::class, 'store'])->name('admin.desdobles.store');

    // Módulo 2: Registro Sanitario, Tratamientos y Tiempo de Retiro
    Route::get('/sanidad', [TratamientoSanitarioController::class, 'index'])->name('sanidad.index');
    Route::post('/sanidad', [TratamientoSanitarioController::class, 'store'])->name('sanidad.store');

    // Módulo 3: Generador de Libro de Campo Oficial del ICA (BPAP)
    Route::get('/admin/reportes/ica-libro-campo', [IcaReportController::class, 'libroCampo'])
        ->middleware('role:jefe_mayor,owner,jefe_finca,jefe,administrador,admin')
        ->name('ica.libro-campo');
    Route::get('/admin/reportes/ica-libro-campo-alias', fn () => redirect()->route('ica.libro-campo'))
        ->middleware('role:jefe_mayor,owner,jefe_finca,jefe,administrador,admin')
        ->name('admin.reportes.ica');

    // Módulo 5: Informe Ejecutivo Mensual de Rentabilidad (Para el Dueño)
    Route::get('/jefe/reporte-mensual', [MonthlyFinancialReportController::class, 'index'])
        ->middleware('role:jefe_mayor,owner,jefe_finca,jefe,administrador,admin')
        ->name('jefe.reporte-mensual');
    Route::get('/jefe/reporte-mensual-alias', fn () => redirect()->route('jefe.reporte-mensual'))
        ->middleware('role:jefe_mayor,owner,jefe_finca,jefe,administrador,admin')
        ->name('jefe.reporte_mensual');

    // Bitácora de Actividades y Trazabilidad de Trabajadores
    Route::get('/admin/actividades', [ActividadTrabajadorController::class, 'index'])
        ->middleware('role:propietario,administrador,admin,jefe_mayor,owner,jefe_finca,jefe')
        ->name('admin.actividades.index');

    // Módulo de Gestión de Lagos, Biomasa, Tiempo de Cultivo y Muestreos Sabatinos
    Route::middleware('role:administrador,jefe_mayor,admin,owner,jefe_finca,jefe,tecnico_acuicola,propietario')->group(function () {
        Route::get('/admin/lagos', [AdminLagosController::class, 'index'])->name('admin.lagos.index');
        Route::post('/admin/lagos', [AdminLagosController::class, 'store'])->name('admin.lagos.store');
        Route::post('/admin/lotes', [LoteController::class, 'store'])->name('admin.lotes.store');
        Route::get('/admin/lagos/{id}', [AdminLagosController::class, 'show'])->name('admin.lagos.show');
        Route::get('/admin/muestreos', [AdminLagosController::class, 'muestreosIndex'])->name('admin.muestreos.index');
        Route::post('/admin/muestreos', [AdminLagosController::class, 'storeMuestreo'])->name('admin.muestreos.store');

        // Módulo de Gestión de Personal, Registro de Trabajadores y Asignación de Roles (Exclusivo Propietario)
        Route::middleware(['gestion.usuarios'])->group(function () {
            Route::get('/admin/personal', [PersonalController::class, 'index'])->name('admin.personal.index');
            Route::post('/admin/personal/{user}/aprobar', [PersonalController::class, 'aprobar'])->name('admin.personal.aprobar');
            Route::post('/admin/personal/{id}/aprobar', [PersonalController::class, 'aprobar'])->name('admin.personal.aprobar.id');
            Route::post('/admin/personal/{user}/asignar-rol', [PersonalController::class, 'asignarRol'])->name('admin.personal.asignar-rol');
        });
    });

    // Acciones operativas directas desde la interfaz web (sesión activa con CSRF)
    Route::post('/cosechas/despacho', [HarvestOrderController::class, 'storeDispatchSession'])->name('web.cosechas.dispatch');
    Route::post('/ventas', [FishSaleController::class, 'store'])->name('web.ventas.store');
    Route::post('/ventas/visitor-cash', [FishSaleController::class, 'storeVisitorCash'])->name('web.ventas.visitor_cash');
    Route::post('/nomina/settle', [WeeklyPayrollController::class, 'settleWeeklyPayroll'])->name('web.nomina.settle');
    Route::post('/agenda/events', [CalendarEventController::class, 'store'])->name('web.agenda.store');

    // Asistente IA Técnico Piscícola (Consulta Técnica Zootécnica en tiempo real y offline)
    Route::post('/asistente-ia/chat', [IaAssistantController::class, 'chat'])->name('asistente-ia.chat');
    Route::post('/api/asistente-ia/chat', [IaAssistantController::class, 'chat'])->name('api.asistente-ia.chat');
});

// Asistente de IA (Google Gemini) - Web Chat
Route::post('/ai/chat', [GeminiAiController::class, 'chat'])->name('web.ai.chat');

require __DIR__.'/auth.php';
