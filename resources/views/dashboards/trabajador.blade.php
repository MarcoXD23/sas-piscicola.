@extends('layouts.app')

@section('title', 'Panel Operativo - Trabajador')
@section('page_title', 'Mi Portal Operativo de Granja')

@section('content')
<div class="space-y-6" x-data="trabajadorPortalApp()">

    <!-- Barra Superior con Botón de Navegación, Estado y Acceso a Guía -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <x-back-button />
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-cyan-50 text-cyan-700 border border-cyan-200 shadow-sm">
                <i class="fa-solid fa-water text-cyan-600"></i>
                <span>{{ $estanques->count() }} Estanques en Monitoreo</span>
            </span>
            <a href="{{ route('guia-peces.index') }}"
               class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 transition shadow-sm">
                <i class="fa-solid fa-book-bookmark text-cyan-600"></i>
                <span>Guía Técnica Piscícola</span>
            </a>
        </div>
    </div>

    <!-- Banner Toast de Notificación Reactivo -->
    <div x-show="toastMensaje"
         x-transition
         class="p-4 rounded-2xl text-sm font-bold border shadow-lg flex items-center justify-between"
         :class="toastTipo === 'exito' ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-rose-50 text-rose-800 border-rose-300'"
         style="display: none;">
        <div class="flex items-center gap-2">
            <i :class="toastTipo === 'exito' ? 'fa-solid fa-circle-check text-emerald-600 text-lg' : 'fa-solid fa-circle-exclamation text-rose-600 text-lg'"></i>
            <span x-text="toastMensaje"></span>
        </div>
        <button type="button" @click="toastMensaje = ''" class="text-slate-400 hover:text-slate-600">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- 1. Tarjetas KPI de Resumen del Trabajador -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Turno Rotativo de la Semana -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Mi Turno Semanal</p>
                    <h3 class="text-base sm:text-lg font-black text-cyan-700 mt-1 uppercase">
                        {{ $turno_actual ? str_replace('_', ' ', $turno_actual->shift_type) : 'Lunes a Viernes' }}
                    </h3>
                </div>
                <div class="h-11 w-11 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-lg shadow-inner border border-cyan-100">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <p class="mt-2.5 text-xs text-slate-500">Semana del {{ now()->startOfWeek()->format('d/m') }} al {{ now()->endOfWeek()->format('d/m') }}</p>
        </div>

        <!-- Tipo de Contrato -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tipo de Contrato</p>
                    <h3 class="text-base sm:text-lg font-black text-slate-900 mt-1 uppercase">
                        {{ $tipo_contrato === 'destajo_semanal' || $tipo_contrato === 'temporal' ? 'Destajo Semanal' : 'Fijo (Mensual)' }}
                    </h3>
                </div>
                <div class="h-11 w-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shadow-inner border border-indigo-100">
                    <i class="fa-solid fa-id-card"></i>
                </div>
            </div>
            <p class="mt-2.5 text-xs text-indigo-600 font-semibold">
                {{ $tipo_contrato === 'destajo_semanal' || $tipo_contrato === 'temporal' ? 'Liquidación y pago los sábados' : 'Salario fijo mensual' }}
            </p>
        </div>

        <!-- Pescado Fiado Acumulado -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Mi Pescado Llevado</p>
                    <h3 class="text-xl font-black text-slate-900 mt-1">
                        {{ number_format($deuda_pescado_fiado['total_kilos'], 1, ',', '.') }} <span class="text-xs font-medium text-slate-400">kg</span>
                    </h3>
                </div>
                <div class="h-11 w-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shadow-inner border border-amber-100">
                    <i class="fa-solid fa-fish"></i>
                </div>
            </div>
            <p class="mt-2.5 text-xs text-amber-700 font-bold">
                Saldo: ${{ number_format($deuda_pescado_fiado['total_dinero'], 0, ',', '.') }} COP ($7.000/kg)
            </p>
        </div>

        <!-- Tareas Pendientes del Administrador -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tablón de Tareas</p>
                    <h3 class="text-2xl font-black text-teal-600 mt-1">{{ $mis_tareas->count() }}</h3>
                </div>
                <div class="h-11 w-11 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg shadow-inner border border-teal-100">
                    <i class="fa-solid fa-list-check"></i>
                </div>
            </div>
            <p class="mt-2.5 text-xs text-slate-500">Instrucciones de campo activas</p>
        </div>
    </div>

    <!-- 2. CUADRÍCULA COMPLETA Y DINÁMICA DE ESTANQUES (Responsive: 1 col móvil, 2 tablet, 3-4 PC) -->
    <div class="space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <h2 class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-cubes-stacked text-cyan-600"></i>
                    <span>Estanques de Cultivo y Herramientas Operativas</span>
                </h2>
                <p class="text-xs text-slate-500">Selecciona cualquier estanque para registrar raciones de alimentación o reportar bajas matutinas.</p>
            </div>
            <div class="text-xs font-semibold text-slate-500">
                Mostrando <span class="font-bold text-slate-800">{{ $estanques->count() }}</span> estanques registrados
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($estanques as $estanque)
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition p-4 flex flex-col justify-between relative group">
                    <!-- Cabecera de la tarjeta con imagen de pez -->
                    <div>
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <img src="{{ $estanque->foto_especie }}"
                                     alt="{{ $estanque->especie_nombre }}"
                                     class="h-12 w-12 rounded-xl object-cover border border-slate-200 shrink-0 shadow-sm">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ $estanque->code ?? 'L-'.$estanque->id }}
                                        </span>
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold {{ $estanque->status === 'En Cosecha' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                            {{ $estanque->status ?? 'Activo' }}
                                        </span>
                                    </div>
                                    <h3 class="text-sm font-black text-slate-900 truncate mt-1" title="{{ $estanque->name }}">
                                        {{ $estanque->name }}
                                    </h3>
                                    <p class="text-[11px] text-cyan-700 font-semibold truncate">
                                        {{ $estanque->especie_nombre }}
                                    </p>
                                </div>
                            </div>
                        </div>


                        <!-- Ración Recomendada del Día -->
                        <div class="mb-3 px-3 py-2 rounded-xl bg-emerald-50/70 border border-emerald-100 flex items-center justify-between text-xs">
                            <span class="text-emerald-800 font-bold flex items-center gap-1.5">
                                <i class="fa-solid fa-utensils text-emerald-600"></i>
                                Ración sugerida:
                            </span>
                            <span class="font-black text-emerald-700 text-sm">
                                {{ $estanque->racion_sugerida_kg }} kg
                            </span>
                        </div>
                    </div>

                    <!-- Botones de Acción Operativa Rápida -->
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                        <button type="button"
                                @click="abrirModalAlimentacion({{ $estanque->id }}, '{{ addslashes($estanque->name) }}', {{ $estanque->racion_sugerida_kg }})"
                                class="w-full py-2.5 px-2 rounded-xl font-bold text-xs bg-cyan-600 hover:bg-cyan-700 text-white shadow-sm hover:shadow transition flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-bowl-food"></i>
                            <span>Alimentar</span>
                        </button>

                        <button type="button"
                                @click="abrirModalBajas({{ $estanque->id }}, '{{ addslashes($estanque->name) }}')"
                                class="w-full py-2.5 px-2 rounded-xl font-bold text-xs bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                            <span>Reportar Bajas</span>
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full p-8 text-center bg-white rounded-2xl border border-slate-200">
                    <i class="fa-solid fa-water text-3xl text-slate-300 mb-2"></i>
                    <p class="text-sm font-bold text-slate-600">No se encontraron estanques registrados para esta finca.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- 3. TABLÓN DE TAREAS DEL DÍA & MI PESCADO LLEVADO -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Tablón de Tareas del Día (2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="h-9 w-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-thumbtack"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900">Tablón de Tareas e Instrucciones de Campo</h3>
                        <p class="text-xs text-slate-400">Instrucciones directas del Administrador para la jornada</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-teal-100 text-teal-800">
                    {{ $mis_tareas->count() }} pendientes
                </span>
            </div>

            @if($mis_tareas->isEmpty())
                <div class="text-center py-8">
                    <i class="fa-solid fa-circle-check text-4xl text-emerald-400 mb-2"></i>
                    <h4 class="text-sm font-bold text-slate-700">¡Al día! No tienes tareas pendientes</h4>
                    <p class="text-xs text-slate-400 mt-1">El Administrador no ha publicado nuevas instrucciones para hoy.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($mis_tareas as $task)
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-slate-50 transition flex items-start justify-between gap-3">
                            <div class="space-y-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                        @if($task->priority === 'urgente') bg-rose-100 text-rose-800 border border-rose-200
                                        @elseif($task->priority === 'alta') bg-amber-100 text-amber-800 border border-amber-200
                                        @else bg-slate-200 text-slate-700 @endif">
                                        Prioridad {{ $task->priority }}
                                    </span>
                                    @if($task->due_date)
                                        <span class="text-xs text-slate-500 font-semibold">
                                            <i class="fa-regular fa-calendar mr-1"></i>Para hoy ({{ $task->due_date->format('d/m') }})
                                        </span>
                                    @endif
                                </div>
                                <h4 class="text-xs sm:text-sm font-black text-slate-900">{{ $task->title }}</h4>
                                @if($task->description)
                                    <p class="text-xs text-slate-600">{{ $task->description }}</p>
                                @endif
                                @if($task->notes)
                                    <p class="text-[11px] text-slate-400 italic">"{{ $task->notes }}"</p>
                                @endif
                            </div>

                            <button type="button"
                                    @click="completarTarea({{ $task->id }})"
                                    class="shrink-0 px-3 py-2 rounded-xl text-xs font-bold bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 border border-slate-200 hover:border-emerald-300 shadow-sm transition flex items-center gap-1.5">
                                <i class="fa-solid fa-check text-emerald-600"></i>
                                <span>Hecho</span>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Consulta Detallada de Pescado Llevado (1 col) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                <div class="h-9 w-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">Mi Pescado Llevado</h3>
                    <p class="text-xs text-slate-400">Control de descuentos en nómina</p>
                </div>
            </div>

            <!-- Resumen numérico -->
            <div class="p-4 rounded-xl bg-amber-50/60 border border-amber-200 text-center space-y-1">
                <span class="text-xs font-bold text-amber-800 uppercase tracking-wide">Total Kilos Retirados</span>
                <div class="text-2xl font-black text-amber-900">
                    {{ number_format($deuda_pescado_fiado['total_kilos'], 1, ',', '.') }} <span class="text-sm font-medium">kg</span>
                </div>
                <div class="text-xs font-bold text-amber-700 pt-1">
                    Descuento total: ${{ number_format($deuda_pescado_fiado['total_dinero'], 0, ',', '.') }} COP
                </div>
                <div class="text-[11px] text-slate-500 pt-1">
                    Tarifa subsidiada: $7.000 / kg
                </div>
            </div>

            <!-- Últimos retiros -->
            <div class="space-y-2">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Últimos Retiros Registrados</h4>
                @forelse($deuda_pescado_fiado['registros'] as $cr)
                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-slate-800">{{ $cr->credit_date ? $cr->credit_date->format('d/m/Y') : 'Fecha reciente' }}</span>
                            <p class="text-[11px] text-slate-500">{{ $cr->notes ?? 'Pescado fresco para consumo familiar' }}</p>
                        </div>
                        <div class="text-right">
                            <span class="font-black text-slate-900">{{ $cr->kilos }} kg</span>
                            <span class="block text-[11px] font-bold text-amber-600">${{ number_format($cr->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic text-center py-2">No tienes retiros de pescado pendientes.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- 4. GESTIÓN LABORAL: SOLICITAR PERMISOS & REPORTE DE HORAS EXTRAS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-data="{ activeTab: 'horas_extras' }">

        <!-- Formulario e Historial: Horas Extras -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="h-9 w-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-business-time"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900">Mis Horas Extras</h3>
                        <p class="text-xs text-slate-400">Reporta turnos extraordinarios o nocturnos</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs font-bold text-teal-700 bg-teal-50 px-2.5 py-1 rounded-xl border border-teal-200">
                        {{ $total_horas_extras }}h registradas ({{ $horas_extras_aprobadas }}h aprobadas)
                    </span>
                </div>
            </div>

            <!-- Formulario de Horas Extras -->
            <form @submit.prevent="submitOvertime()" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Fecha de la Labor *</label>
                        <input type="date" x-model="overtimeForm.record_date" required class="w-full text-xs rounded-xl border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Cantidad de Horas *</label>
                        <input type="number" step="0.5" min="0.5" max="16" x-model="overtimeForm.hours" required placeholder="Ej: 3.5" class="w-full text-xs rounded-xl border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Motivo / Ocasión *</label>
                    <input type="text" x-model="overtimeForm.occasion" required placeholder="Ej: Cosecha nocturna, desove de alevines, falla eléctrica..." class="w-full text-xs rounded-xl border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Justificación Detallada *</label>
                    <textarea x-model="overtimeForm.justification" rows="2" required placeholder="Detalla las actividades realizadas..." class="w-full text-xs rounded-xl border-slate-300 focus:ring-teal-500 focus:border-teal-500"></textarea>
                </div>

                <button type="submit" :disabled="guardandoOvertime" class="w-full py-2.5 rounded-xl font-bold text-xs bg-teal-600 hover:bg-teal-700 text-white shadow-sm transition disabled:opacity-50">
                    <i class="fa-solid fa-plus-circle mr-1"></i>
                    <span x-text="guardandoOvertime ? 'Guardando...' : 'Anotar Horas Extras'"></span>
                </button>
            </form>

            <!-- Historial de Horas Extras -->
            <div class="pt-3 border-t border-slate-100 space-y-2">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Historial Reciente de Horas Extras</h4>
                <div class="max-h-48 overflow-y-auto space-y-2">
                    @forelse($mis_horas_extras as $ot)
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-slate-800">{{ $ot->record_date ? $ot->record_date->format('d/m/Y') : '' }}</span>
                                <span class="font-extrabold text-teal-700 ml-1">({{ $ot->hours }} horas)</span>
                                <p class="text-[11px] text-slate-600">{{ $ot->occasion }}</p>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                @if($ot->status === 'aprobado') bg-emerald-100 text-emerald-800
                                @elseif($ot->status === 'rechazado') bg-rose-100 text-rose-800
                                @else bg-amber-100 text-amber-800 @endif">
                                {{ $ot->status ?? 'Pendiente' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic text-center py-2">No has reportado horas extras aún.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Formulario e Historial: Solicitar Permisos -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="h-9 w-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900">Solicitar Permisos Laborales</h3>
                        <p class="text-xs text-slate-400">Solicitudes enviadas al Administrador</p>
                    </div>
                </div>
            </div>

            <!-- Formulario de Permiso -->
            <form @submit.prevent="submitLeave()" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Fecha de Inicio *</label>
                        <input type="date" x-model="leaveForm.start_date" required class="w-full text-xs rounded-xl border-slate-300 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Fecha de Fin *</label>
                        <input type="date" x-model="leaveForm.end_date" required class="w-full text-xs rounded-xl border-slate-300 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Motivo del Permiso *</label>
                    <textarea x-model="leaveForm.reason" rows="2" required placeholder="Citas médicas, diligencias personales, calamidad familiar..." class="w-full text-xs rounded-xl border-slate-300 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>

                <button type="submit" :disabled="guardandoPermiso" class="w-full py-2.5 rounded-xl font-bold text-xs bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition disabled:opacity-50">
                    <i class="fa-solid fa-paper-plane mr-1"></i>
                    <span x-text="guardandoPermiso ? 'Enviando...' : 'Enviar Solicitud al Administrador'"></span>
                </button>
            </form>

            <!-- Historial de Permisos con Estado -->
            <div class="pt-3 border-t border-slate-100 space-y-2">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mis Solicitudes y Respuestas</h4>
                <div class="max-h-48 overflow-y-auto space-y-2">
                    @forelse($mis_permisos as $permiso)
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-slate-800">
                                    {{ $permiso->start_date ? $permiso->start_date->format('d/m/Y') : '' }}
                                    al {{ $permiso->end_date ? $permiso->end_date->format('d/m/Y') : '' }}
                                </span>
                                <p class="text-[11px] text-slate-600">{{ $permiso->reason }}</p>
                                @if($permiso->response_notes)
                                    <p class="text-[10px] text-indigo-700 font-semibold italic">Respuesta: "{{ $permiso->response_notes }}"</p>
                                @endif
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase
                                @if($permiso->status === 'aprobado') bg-emerald-100 text-emerald-800 border border-emerald-300
                                @elseif($permiso->status === 'rechazado') bg-rose-100 text-rose-800 border border-rose-300
                                @else bg-amber-100 text-amber-800 border border-amber-300 @endif">
                                {{ $permiso->status ?? 'Pendiente' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic text-center py-2">No has solicitado permisos laborales.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 1: REGISTRAR ALIMENTACIÓN RÁPIDA CON BOTONES DE APETITO -->
    <div x-show="modalAlimentacionAbierto"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-cloak>
        <div class="w-full max-w-md p-6 rounded-3xl bg-white text-slate-800 shadow-2xl space-y-4 border border-slate-200"
             @click.away="modalAlimentacionAbierto = false">

            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="h-10 w-10 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-bowl-food"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">Registrar Alimentación</h3>
                        <p class="text-xs text-cyan-700 font-bold" x-text="alimentacionForm.estanque_nombre"></p>
                    </div>
                </div>
                <button type="button" @click="modalAlimentacionAbierto = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="guardarAlimentacion()" class="space-y-4">
                <!-- Kilos de Alimento -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kilos Reales Suministrados (kg) *</label>
                    <div class="relative">
                        <input type="number" step="0.1" min="0.1" x-model="alimentacionForm.amount_kg" required
                               placeholder="Ej: 25.4"
                               class="w-full text-base font-bold rounded-xl border-slate-300 pr-12 focus:ring-cyan-500 focus:border-cyan-500">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-slate-400">KG</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1" x-show="alimentacionForm.racion_sugerida > 0">
                        Ración sugerida: <span class="font-bold text-slate-700" x-text="alimentacionForm.racion_sugerida + ' kg'"></span>
                    </p>
                </div>

                <!-- 3 Botones de Nivel de Apetito -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Comportamiento / Apetito de los Peces *</label>
                    <div class="grid grid-cols-3 gap-2">
                        <!-- Botón 1: Comieron Bien -->
                        <button type="button"
                                @click="alimentacionForm.appetite_level = 'bueno'"
                                :class="alimentacionForm.appetite_level === 'bueno'
                                    ? 'bg-emerald-600 text-white border-emerald-600 ring-2 ring-emerald-300 shadow-md'
                                    : 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100'"
                                class="py-3 px-2 rounded-xl text-xs font-bold border transition flex flex-col items-center justify-center gap-1">
                            <span class="text-base">🟢</span>
                            <span class="text-[11px] leading-tight text-center">Comieron Bien</span>
                        </button>

                        <!-- Botón 2: Regular -->
                        <button type="button"
                                @click="alimentacionForm.appetite_level = 'regular'"
                                :class="alimentacionForm.appetite_level === 'regular'
                                    ? 'bg-amber-500 text-white border-amber-500 ring-2 ring-amber-300 shadow-md'
                                    : 'bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100'"
                                class="py-3 px-2 rounded-xl text-xs font-bold border transition flex flex-col items-center justify-center gap-1">
                            <span class="text-base">🟡</span>
                            <span class="text-[11px] leading-tight text-center">Regular</span>
                        </button>

                        <!-- Botón 3: Mal / Lento -->
                        <button type="button"
                                @click="alimentacionForm.appetite_level = 'malo'"
                                :class="alimentacionForm.appetite_level === 'malo'
                                    ? 'bg-rose-600 text-white border-rose-600 ring-2 ring-rose-300 shadow-md'
                                    : 'bg-rose-50 text-rose-800 border-rose-200 hover:bg-rose-100'"
                                class="py-3 px-2 rounded-xl text-xs font-bold border transition flex flex-col items-center justify-center gap-1">
                            <span class="text-base">🔴</span>
                            <span class="text-[11px] leading-tight text-center">Mal / Lento</span>
                        </button>
                    </div>
                </div>

                <!-- Observaciones -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Observaciones</label>
                    <input type="text" x-model="alimentacionForm.observations"
                           placeholder="Clima nublado, agua turbia, restos de concentrado..."
                           class="w-full text-xs rounded-xl border-slate-300 focus:ring-cyan-500 focus:border-cyan-500">
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="button" @click="modalAlimentacionAbierto = false"
                            class="flex-1 py-2.5 rounded-xl font-bold text-xs bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="guardandoAlimentacion"
                            class="flex-1 py-2.5 rounded-xl font-bold text-xs bg-cyan-600 hover:bg-cyan-700 text-white shadow-md transition disabled:opacity-50">
                        <i class="fa-solid fa-floppy-disk mr-1"></i>
                        <span x-text="guardandoAlimentacion ? 'Guardando...' : 'Guardar Ración'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: REPORTE DE BAJAS MATUTINAS (MORTALIDAD) -->
    <div x-show="modalBajasAbierto"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-cloak>
        <div class="w-full max-w-md p-6 rounded-3xl bg-white text-slate-800 shadow-2xl space-y-4 border border-rose-200"
             @click.away="modalBajasAbierto = false">

            <div class="flex items-center justify-between pb-3 border-b border-rose-100">
                <div class="flex items-center gap-2.5">
                    <div class="h-10 w-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-skull-crossbones"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-rose-900">Reporte de Bajas Matutinas</h3>
                        <p class="text-xs text-rose-600 font-bold" x-text="bajasForm.estanque_nombre"></p>
                    </div>
                </div>
                <button type="button" @click="modalBajasAbierto = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="guardarBajas()" class="space-y-4">
                <!-- Peces Muertos Recogidos -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Cantidad de Peces Muertos Recogidos *</label>
                    <input type="number" min="1" step="1" x-model="bajasForm.cantidad_peces" required
                           placeholder="Ej: 14"
                           class="w-full text-base font-black text-rose-700 rounded-xl border-slate-300 focus:ring-rose-500 focus:border-rose-500">
                    <p class="text-[11px] text-slate-400 mt-1">Se restará automáticamente de la población del estanque.</p>
                </div>

                <!-- Causa Probable -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Causa Probable *</label>
                    <select x-model="bajasForm.causa_probable" required class="w-full text-xs rounded-xl border-slate-300 focus:ring-rose-500 focus:border-rose-500 font-semibold">
                        <option value="Mortalidad matutina rutinaria">Mortalidad matutina rutinaria</option>
                        <option value="Falta de Oxígeno / Boqueo">Falta de Oxígeno / Boqueo</option>
                        <option value="Estrés por Traslado / Manejo">Estrés por Traslado / Manejo</option>
                        <option value="Depredación (Aves / Nutria)">Depredación (Aves / Nutria)</option>
                        <option value="Signos de Enfermedad / Hongos">Signos de Enfermedad / Hongos</option>
                    </select>
                </div>

                <!-- Método de Disposición -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Disposición Sanitaria *</label>
                    <select x-model="bajasForm.metodo_disposicion" required class="w-full text-xs rounded-xl border-slate-300 focus:ring-rose-500 focus:border-rose-500">
                        <option value="compostaje">Compostaje Orgánico de Granja</option>
                        <option value="fosa">Fosa Sanitaria Sellada</option>
                        <option value="entierro_cal">Entierro Profundo con Cal</option>
                    </select>
                </div>

                <!-- Observaciones -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Detalle / Hallazgo</label>
                    <input type="text" x-model="bajasForm.observaciones"
                           placeholder="Hallados en orilla sur, aletas deshilachadas, etc."
                           class="w-full text-xs rounded-xl border-slate-300 focus:ring-rose-500 focus:border-rose-500">
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="button" @click="modalBajasAbierto = false"
                            class="flex-1 py-2.5 rounded-xl font-bold text-xs bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="guardandoBajas"
                            class="flex-1 py-2.5 rounded-xl font-black text-xs bg-rose-600 hover:bg-rose-700 text-white shadow-md shadow-rose-200 transition disabled:opacity-50">
                        <i class="fa-solid fa-skull mr-1"></i>
                        <span x-text="guardandoBajas ? 'Actualizando...' : 'Confirmar Bajas'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function trabajadorPortalApp() {
    return {
        toastMensaje: '',
        toastTipo: 'exito',

        modalAlimentacionAbierto: false,
        guardandoAlimentacion: false,
        alimentacionForm: {
            pond_id: '',
            estanque_nombre: '',
            racion_sugerida: 0,
            amount_kg: '',
            appetite_level: 'bueno',
            observations: ''
        },

        modalBajasAbierto: false,
        guardandoBajas: false,
        bajasForm: {
            estanque_id: '',
            estanque_nombre: '',
            cantidad_peces: '',
            causa_probable: 'Mortalidad matutina rutinaria',
            metodo_disposicion: 'compostaje',
            observaciones: ''
        },

        leaveForm: {
            start_date: '',
            end_date: '',
            reason: ''
        },
        guardandoPermiso: false,

        overtimeForm: {
            record_date: new Date().toISOString().split('T')[0],
            hours: '',
            occasion: '',
            justification: ''
        },
        guardandoOvertime: false,

        notificar(msg, tipo = 'exito') {
            this.toastMensaje = msg;
            this.toastTipo = tipo;
            setTimeout(() => { this.toastMensaje = ''; }, 4500);
        },

        abrirModalAlimentacion(pondId, pondName, racion) {
            this.alimentacionForm.pond_id = pondId;
            this.alimentacionForm.estanque_nombre = pondName;
            this.alimentacionForm.racion_sugerida = racion;
            this.alimentacionForm.amount_kg = racion > 0 ? racion : '';
            this.alimentacionForm.appetite_level = 'bueno';
            this.alimentacionForm.observations = '';
            this.modalAlimentacionAbierto = true;
        },

        guardarAlimentacion() {
            this.guardandoAlimentacion = true;
            fetch('{{ route("trabajador.alimentacion") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.alimentacionForm)
            })
            .then(res => res.json())
            .then(data => {
                this.modalAlimentacionAbierto = false;
                this.notificar(data.message || 'Alimentación registrada exitosamente.');
                setTimeout(() => location.reload(), 1400);
            })
            .catch(err => {
                this.notificar('Error al guardar la alimentación.', 'error');
            })
            .finally(() => {
                this.guardandoAlimentacion = false;
            });
        },

        abrirModalBajas(pondId, pondName) {
            this.bajasForm.estanque_id = pondId;
            this.bajasForm.estanque_nombre = pondName;
            this.bajasForm.cantidad_peces = '';
            this.bajasForm.causa_probable = 'Mortalidad matutina rutinaria';
            this.bajasForm.metodo_disposicion = 'compostaje';
            this.bajasForm.observaciones = '';
            this.modalBajasAbierto = true;
        },

        guardarBajas() {
            this.guardandoBajas = true;
            fetch('{{ route("trabajador.mortalidad") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.bajasForm)
            })
            .then(res => res.json())
            .then(data => {
                this.modalBajasAbierto = false;
                this.notificar(data.message || 'Bajas registradas y población actualizada.');
                setTimeout(() => location.reload(), 1400);
            })
            .catch(err => {
                this.notificar('Error al registrar bajas de peces.', 'error');
            })
            .finally(() => {
                this.guardandoBajas = false;
            });
        },

        completarTarea(taskId) {
            fetch(`/trabajador/tareas/${taskId}/completar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({})
            })
            .then(res => res.json())
            .then(data => {
                this.notificar(data.message || 'Tarea completada.');
                setTimeout(() => location.reload(), 1200);
            })
            .catch(err => {
                this.notificar('Error al completar la tarea.', 'error');
            });
        },

        submitOvertime() {
            this.guardandoOvertime = true;
            fetch('/api/communication/overtime-records', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.overtimeForm)
            })
            .then(res => res.json())
            .then(data => {
                this.notificar(data.message || 'Horas extras registradas correctamente.');
                this.overtimeForm.hours = '';
                this.overtimeForm.occasion = '';
                this.overtimeForm.justification = '';
                setTimeout(() => location.reload(), 1400);
            })
            .catch(err => {
                this.notificar('Error al registrar horas extras.', 'error');
            })
            .finally(() => {
                this.guardandoOvertime = false;
            });
        },

        submitLeave() {
            this.guardandoPermiso = true;
            fetch('/api/communication/leave-requests', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.leaveForm)
            })
            .then(res => res.json())
            .then(data => {
                this.notificar(data.message || 'Permiso solicitado correctamente.');
                this.leaveForm = { start_date: '', end_date: '', reason: '' };
                setTimeout(() => location.reload(), 1400);
            })
            .catch(err => {
                this.notificar('Error al solicitar permiso laboral.', 'error');
            })
            .finally(() => {
                this.guardandoPermiso = false;
            });
        }
    }
}
</script>
@endsection
