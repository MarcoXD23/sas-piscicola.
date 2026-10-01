@extends('layouts.app')

@section('title', 'Panel de Control - Administrador')
@section('page_title', 'Operaciones y Gestión Diaria de Granja')

@section('content')
<div class="space-y-6">

    <!-- Botón de navegación global y accesos directos -->
    <div class="flex flex-wrap items-center justify-between gap-2">
        <x-back-button />
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('traslados.index') }}"
               class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-cyan-700 text-white text-xs font-bold hover:bg-cyan-800 transition shadow-sm">
                <i class="fa-solid fa-arrows-split-up-and-left"></i>
                <span>Traslados & Desdobles</span>
            </a>
            <a href="{{ route('sanidad.index') }}"
               class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-600 text-white text-xs font-bold hover:bg-amber-700 transition shadow-sm">
                <i class="fa-solid fa-syringe"></i>
                <span>Sanidad & Retiro ICA</span>
            </a>
            <a href="{{ route('ica.libro-campo') }}"
               class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-teal-700 text-white text-xs font-bold hover:bg-teal-800 transition shadow-sm">
                <i class="fa-solid fa-book-bookmark"></i>
                <span>Libro Oficial ICA</span>
            </a>
            <a href="{{ route('admin.ajustes') }}"
               class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                <i class="fa-solid fa-sliders text-cyan-600"></i>
                <span>Ajustes Finca</span>
            </a>
        </div>
    </div>

    <!-- 1. Métricas Operativas de Administrador -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Alertas de Bodega -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Alertas de Bodega</p>
                    <h3 class="text-2xl font-black {{ $metricas_operativas['alertas_bodega_count'] > 0 ? 'text-rose-600' : 'text-slate-900' }} mt-1">
                        {{ $metricas_operativas['alertas_bodega_count'] }}
                    </h3>
                </div>
                <div class="h-12 w-12 rounded-2xl {{ $metricas_operativas['alertas_bodega_count'] > 0 ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-slate-50 text-slate-500 border-slate-200' }} flex items-center justify-center text-xl shadow-inner border">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <p class="mt-3 text-xs {{ $metricas_operativas['alertas_bodega_count'] > 0 ? 'text-rose-600 font-semibold' : 'text-slate-500' }}">
                {{ $metricas_operativas['alertas_bodega_count'] > 0 ? 'Stock por debajo del umbral mínimo' : 'Inventario en niveles óptimos' }}
            </p>
        </div>

        <!-- Tareas de Ausencia -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tareas en Curso</p>
                    <h3 class="text-2xl font-black text-cyan-600 mt-1">{{ $metricas_operativas['tareas_pendientes_count'] }}</h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-xl shadow-inner border border-cyan-100">
                    <i class="fa-solid fa-list-check"></i>
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-500">Asignadas a trabajadores de guardia</p>
        </div>

        <!-- Alimentación de Hoy -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Alimento Servido Hoy</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($metricas_operativas['kilos_alimentados_hoy'], 1) }} <span class="text-sm font-medium text-slate-400">kg</span></h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl shadow-inner border border-teal-100">
                    <i class="fa-solid fa-wheat-awn"></i>
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-500">Bitácora diaria con nivel de apetito</p>
        </div>

        <!-- Recaudo Ventas Hoy -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Caja Recaudada Hoy</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">${{ number_format($metricas_operativas['recaudo_ventas_hoy'], 0, ',', '.') }}</h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner border border-emerald-100">
                    <i class="fa-solid fa-cash-register"></i>
                </div>
            </div>
            <p class="mt-3 text-xs text-emerald-600 font-semibold"><i class="fa-solid fa-coins mr-1"></i>Ventas directas a visitantes</p>
        </div>
    </div>

    <!-- 2. Accesos Rápidos Operativos -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('nomina.index') }}" class="flex items-center gap-3 p-4 bg-white rounded-2xl border border-slate-200/80 hover:border-indigo-400 hover:shadow-md transition group">
            <div class="h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl group-hover:scale-110 transition">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <div>
                <h4 class="text-sm font-bold text-slate-800">Liquidación Sábado</h4>
                <p class="text-xs text-slate-500">Cierre semanal destajo y descuento fiado</p>
            </div>
        </a>

        <a href="{{ route('cosechas.index') }}" class="flex items-center gap-3 p-4 bg-white rounded-2xl border border-slate-200/80 hover:border-cyan-400 hover:shadow-md transition group">
            <div class="h-12 w-12 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-xl group-hover:scale-110 transition">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
            <div>
                <h4 class="text-sm font-bold text-slate-800">Pesaje Báscula & Tara</h4>
                <p class="text-xs text-slate-500">Bruto - Canastillas * Tara = Neto</p>
            </div>
        </a>

        <a href="{{ route('ventas.index') }}" class="flex items-center gap-3 p-4 bg-white rounded-2xl border border-slate-200/80 hover:border-emerald-400 hover:shadow-md transition group">
            <div class="h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl group-hover:scale-110 transition">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <div>
                <h4 class="text-sm font-bold text-slate-800">Ventas en Efectivo</h4>
                <p class="text-xs text-slate-500">Visitantes $9.000 / Trabajadores $7.000</p>
            </div>
        </a>

        <a href="{{ route('guia-peces.index') }}" class="flex items-center gap-3 p-4 bg-white rounded-2xl border border-slate-200/80 hover:border-amber-400 hover:shadow-md transition group">
            <div class="h-12 w-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl group-hover:scale-110 transition">
                <i class="fa-solid fa-fish"></i>
            </div>
            <div>
                <h4 class="text-sm font-bold text-slate-800">Guía de Especies</h4>
                <p class="text-xs text-slate-500">Parámetros, O₂, proteína y policultivo</p>
            </div>
        </a>
    </div>

    <!-- 3. Alertas de Bodega & Días Restantes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Card: Inventario de Bodega & Días Restantes -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('admin.bodega.index') }}" class="text-base font-bold text-slate-900 hover:text-cyan-700 transition flex items-center gap-2">
                    <i class="fa-solid fa-warehouse text-cyan-600"></i>
                    <span>{{ 'Inventario de Alimento & Días Restantes' }}</span>
                </a>
                <a href="{{ route('admin.bodega.index') }}" class="text-xs font-semibold text-cyan-600 hover:text-cyan-700">
                    <span class="font-black">{{ number_format($total_bultos_bodega ?? 0, 1) }}</span> bultos &rarr;
                </a>
            </div>

            @if($alertas_bodega->isEmpty())
                <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-100 flex items-center gap-3 text-emerald-800">
                    <i class="fa-solid fa-circle-check text-xl text-emerald-500"></i>
                    <div class="text-xs">
                        <span class="font-bold">Bodega Abastecida:</span> Todos los tipos de concentrado cuentan con suficiente stock por encima de su umbral mínimo.
                    </div>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($alertas_bodega as $item)
                        <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200/70 flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-rose-900">{{ $item->name }}</h4>
                                <p class="text-xs text-rose-700">Stock Actual: <span class="font-black">{{ number_format($item->quantity_kg, 1) }} kg</span> (Umbral mínimo: {{ $item->min_stock_alert_kg }} kg)</p>
                            </div>
                            <div class="text-right">
                                <span class="px-2.5 py-1 text-xs font-bold bg-rose-200 text-rose-900 rounded-lg">¡Crítico!</span>
                                <p class="text-[11px] text-rose-800 font-semibold mt-1">{{ $item->daysOfFeedRemaining() }} días restantes</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Card: Tablero de Tareas de Administrador -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6" x-data="{ newTaskModal: false, taskSuccess: '' }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-clipboard-list text-cyan-600"></i>
                    Tablero de Tareas para Ausencias
                </h3>
                <button @click="newTaskModal = true" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-cyan-600 hover:bg-cyan-700 text-white shadow-sm transition">
                    <i class="fa-solid fa-plus mr-1"></i>Asignar Tarea
                </button>
            </div>

            <div x-show="taskSuccess" class="mb-3 p-3 bg-emerald-50 text-emerald-700 text-xs rounded-xl border border-emerald-200 font-medium" x-text="taskSuccess"></div>

            @if($tareas_activas->isEmpty())
                <div class="text-center py-8 text-slate-400">
                    <i class="fa-solid fa-list-check text-3xl mb-2 text-slate-300"></i>
                    <p class="text-sm">No hay tareas pendientes asignadas actualmente.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($tareas_activas as $tarea)
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between hover:bg-slate-100/60 transition">
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">{{ $tarea->title }}</h4>
                                <p class="text-xs text-slate-500">Asignada a: <span class="font-semibold text-slate-700">{{ $tarea->assignedTo->name ?? 'Trabajador' }}</span> • Vence: {{ $tarea->due_date ? $tarea->due_date->format('d/m/Y') : 'Hoy' }}</p>
                            </div>
                            <span class="px-2.5 py-1 text-xs font-bold rounded-lg uppercase
                                {{ $tarea->priority === 'urgente' ? 'bg-rose-100 text-rose-700' : ($tarea->priority === 'alta' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-700') }}">
                                {{ $tarea->priority }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- 4. Gestión Exclusiva de Lagos, Biomasa y Muestreos Sabatinos -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-3 pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-water text-cyan-600"></i>
                    <span>Control de Lagos, Biomasa y Muestreos Sabatinos</span>
                </h3>
                <p class="text-xs text-slate-400">Peces vivos, días/meses de cultivo, biomasa estimada y muestreos de peso promedio.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.muestreos.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-cyan-700 bg-cyan-50 hover:bg-cyan-100 border border-cyan-200 transition">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>Muestreos Sabatinos</span>
                </a>
                <a href="{{ route('admin.lagos.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-cyan-600 hover:bg-cyan-700 transition shadow-sm">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Ver Módulo Completo</span>
                </a>
            </div>
        </div>

        @if($lagos_listos_pesca->isNotEmpty())
            <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900 font-bold flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                <span>Alerta: {{ $lagos_listos_pesca->count() }} lago(s) listos para pesca comercial (peso ≥ 450g o en cosecha): {{ $lagos_listos_pesca->pluck('name')->join(', ') }}</span>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[10px] border-b border-slate-200">
                        <th class="py-2.5 px-3">Lago</th>
                        <th class="py-2.5 px-3">Peces Vivos</th>
                        <th class="py-2.5 px-3">Tiempo Cultivo</th>
                        <th class="py-2.5 px-3">Biomasa Est.</th>
                        <th class="py-2.5 px-3">Peso Promedio</th>
                        <th class="py-2.5 px-3">Último Muestreo</th>
                        <th class="py-2.5 px-3">Estado</th>
                        <th class="py-2.5 px-3 text-right">Ficha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($estanques as $estanque)
                        @php
                            $listo = (float) $estanque->average_weight >= 450 || $estanque->status === 'En Cosecha';
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-2.5 px-3 font-bold text-slate-800">
                                {{ $estanque->name }}
                                <span class="text-[10px] text-slate-400 font-normal block">{{ $estanque->code }}</span>
                            </td>
                            <td class="py-2.5 px-3 font-black text-slate-900">
                                {{ number_format($estanque->fish_population, 0, ',', '.') }} pcs
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="font-bold text-slate-700">{{ $estanque->days_in_culture }} días</span>
                                <span class="text-[10px] text-slate-400 block">({{ $estanque->months_in_culture }} meses)</span>
                            </td>
                            <td class="py-2.5 px-3 font-extrabold text-cyan-700">
                                {{ number_format($estanque->biomass, 1, ',', '.') }} kg
                            </td>
                            <td class="py-2.5 px-3 font-bold text-slate-800">
                                {{ number_format($estanque->average_weight, 1, ',', '.') }} g
                                @if($listo)
                                    <span class="inline-block text-[9px] font-black text-amber-700 bg-amber-100 px-1.5 py-0.2 rounded">Pesca</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-slate-600">
                                {{ $estanque->samplings->first()?->sampling_date ? $estanque->samplings->first()->sampling_date->format('d/m/Y') : 'Sin muestreo' }}
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $estanque->status === 'En Cosecha' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $estanque->status ?? 'Activo' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right">
                                <a href="{{ route('admin.lagos.show', $estanque->id) }}" class="text-cyan-600 hover:text-cyan-800 font-bold">
                                    Ver Ficha →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
