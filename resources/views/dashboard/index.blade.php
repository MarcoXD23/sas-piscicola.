@extends('layouts.app')

@section('title', 'Dashboard Ejecutivo')
@section('page_title', 'Tablero de Control y Operaciones')

@section('content')
<div class="space-y-6">

    <!-- 1. Métricas Principales (KPI Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        <!-- Card 1: Kilos Vendidos Hoy -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Kilos Vendidos Hoy</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($kilosSoldToday, 1) }} <span class="text-sm font-medium text-slate-400">kg</span></h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner border border-emerald-100">
                    <i class="fa-solid fa-weight-scale"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1.5 text-xs text-emerald-600 font-semibold">
                <i class="fa-solid fa-arrow-trend-up"></i>
                <span>Caja de ventas activa</span>
            </div>
        </div>

        <!-- Card 2: Dinero en Caja Diaria -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Dinero en Caja Hoy</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">${{ number_format($cashTotalToday, 0, ',', '.') }}</h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-xl shadow-inner border border-cyan-100">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1.5 text-xs text-cyan-600 font-semibold">
                <i class="fa-solid fa-tags"></i>
                <span>$9.000 ext / $7.000 int</span>
            </div>
        </div>

        <!-- Card 3: Personal Activo en Cosecha -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Personal Activo</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $activeWorkersCount }} <span class="text-sm font-medium text-slate-400">trabajadores</span></h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-inner border border-indigo-100">
                    <i class="fa-solid fa-people-carry-box"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1.5 text-xs text-indigo-600 font-semibold">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Corte para sábado programado</span>
            </div>
        </div>

        <!-- Card 4: Biomasa en Estanques -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Biomasa en Producción</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalBiomass, 0, ',', '.') }} <span class="text-sm font-medium text-slate-400">kg</span></h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl shadow-inner border border-teal-100">
                    <i class="fa-solid fa-water"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1.5 text-xs text-teal-600 font-semibold">
                <i class="fa-solid fa-layer-group"></i>
                <span>{{ $ponds->count() }} estanques registrados</span>
            </div>
        </div>

    </div>

    <!-- 2. Acciones Rápidas Operativas (Quick Actions Bar) -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-950 to-cyan-950 rounded-2xl p-5 text-white shadow-xl flex flex-wrap items-center justify-between gap-4">
        <div>
            <h4 class="text-base font-bold flex items-center gap-2">
                <i class="fa-solid fa-bolt text-cyan-400"></i> Acciones Inmediatas de Campo
            </h4>
            <p class="text-xs text-slate-400 mt-0.5">Atajos rápidos para los operarios y administrador en la granja.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('cosechas.index') }}" class="bg-cyan-600 hover:bg-cyan-500 text-white font-semibold text-xs px-3.5 py-2 rounded-xl transition duration-150 flex items-center gap-2 shadow-lg shadow-cyan-600/30">
                <i class="fa-solid fa-scale-balanced"></i> Registrar Pesada de Báscula
            </a>
            <a href="{{ route('ventas.index') }}" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs px-3.5 py-2 rounded-xl transition duration-150 flex items-center gap-2 shadow-lg shadow-emerald-600/30">
                <i class="fa-solid fa-cart-plus"></i> Nueva Venta de Pescado
            </a>
            <a href="{{ route('nomina.index') }}" class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs px-3.5 py-2 rounded-xl transition duration-150 flex items-center gap-2 shadow-lg shadow-indigo-600/30">
                <i class="fa-solid fa-calculator"></i> Liquidación de Sábado
            </a>
            <a href="{{ route('agenda.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs px-3.5 py-2 rounded-xl transition duration-150 flex items-center gap-2 border border-slate-700">
                <i class="fa-regular fa-calendar-plus"></i> Programar en Agenda
            </a>
        </div>
    </div>

    <!-- 3. Grid Central: Calendario Operativo en Vivo y Órdenes de Cosecha -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Columna 1 y 2: Próximos Eventos de Agenda (2 Columnas) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center text-sm font-bold">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Agenda Operativa del Jefe de Finca</h3>
                        <p class="text-xs text-slate-500">Eventos programados con hora estricta 12h (AM/PM)</p>
                    </div>
                </div>
                <a href="{{ route('agenda.index') }}" class="text-xs font-semibold text-cyan-600 hover:text-cyan-700 flex items-center gap-1">
                    Ver agenda completa <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <!-- Listado de Eventos -->
            <div class="space-y-3">
                @forelse($upcomingEvents as $event)
                    @php
                        $iconClass = match($event->event_type) {
                            'pesca_cosecha' => 'fa-solid fa-fish text-emerald-600 bg-emerald-50 border-emerald-200',
                            'llegada_alevinos' => 'fa-solid fa-seedling text-cyan-600 bg-cyan-50 border-cyan-200',
                            'llegada_alimento' => 'fa-solid fa-boxes-stacked text-amber-600 bg-amber-50 border-amber-200',
                            default => 'fa-solid fa-clipboard-check text-purple-600 bg-purple-50 border-purple-200',
                        };
                        $typeLabel = match($event->event_type) {
                            'pesca_cosecha' => 'Pesca / Cosecha',
                            'llegada_alevinos' => 'Alevinos',
                            'llegada_alimento' => 'Concentrado',
                            default => 'Visita General',
                        };
                    @endphp
                    <div class="flex items-start justify-between gap-4 p-3.5 rounded-xl border border-slate-100 hover:border-slate-200 bg-slate-50/60 hover:bg-slate-50 transition">
                        <div class="flex items-start gap-3">
                            <div class="h-10 w-10 shrink-0 rounded-xl border flex items-center justify-center text-sm {{ $iconClass }}"></div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="font-bold text-slate-900 text-sm">{{ $event->title }}</h4>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 uppercase">{{ $typeLabel }}</span>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-slate-500 mt-1">
                                    <span><i class="fa-regular fa-calendar text-slate-400 mr-1"></i> {{ $event->event_date?->format('d/m/Y') }}</span>
                                    @if($event->formatted_event_time)
                                        <span class="font-semibold text-slate-700"><i class="fa-regular fa-clock text-cyan-500 mr-1"></i> {{ $event->formatted_event_time }}</span>
                                    @endif
                                </div>
                                @if($event->event_type === 'pesca_cosecha')
                                    <p class="text-xs text-slate-600 mt-1 font-medium">Estanque: {{ $event->pond?->name ?? 'General' }} • Cantidad estimada: <strong class="text-emerald-600">{{ number_format($event->estimated_kg, 1) }} kg</strong></p>
                                @elseif($event->event_type === 'llegada_alimento')
                                    <p class="text-xs text-slate-600 mt-1 font-medium">{{ $event->feed_type }} • {{ $event->feed_bags_count }} bultos ({{ number_format($event->feed_weight_kg, 0) }} kg)</p>
                                @endif
                            </div>
                        </div>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 capitalize shrink-0">
                            {{ $event->status }}
                        </span>
                    </div>
                @empty
                    <div class="text-center py-8 text-slate-400">
                        <i class="fa-regular fa-calendar-xmark text-3xl mb-2 text-slate-300"></i>
                        <p class="text-sm">No hay eventos pendientes para los próximos días.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Columna 3: Cosechas Recientes & Estanques -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-sm font-bold">
                        <i class="fa-solid fa-water"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Estado de Estanques</h3>
                        <p class="text-xs text-slate-500">Biomasa actual monitoreada</p>
                    </div>
                </div>
            </div>

            <!-- Listado Estanques con Foto Real de la Especie -->
            <div class="space-y-3">
                @foreach($ponds->take(4) as $pond)
                    <div class="p-3 rounded-2xl border border-slate-100 bg-slate-50/70 hover:bg-slate-50 transition flex items-center gap-3">
                        <!-- Miniatura Real de la Especie Sembrada -->
                        <div class="relative h-12 w-12 rounded-xl overflow-hidden shrink-0 border border-slate-200 shadow-sm">
                            <img src="{{ $pond->foto_especie }}"
                                 alt="{{ $pond->name }}"
                                 class="h-full w-full object-cover">
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800 truncate">{{ $pond->name }}</span>
                                <span class="font-bold text-teal-600 shrink-0 ml-1.5">{{ number_format($pond->biomass, 1) }} kg</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-1.5 mt-1.5">
                                @php
                                    $percent = min(100, round(($pond->biomass / max(1, $pond->capacity_kg ?? 5000)) * 100));
                                @endphp
                                <div class="bg-teal-500 h-1.5 rounded-full" style="width: {{ $percent }}%"></div>
                            </div>
                            <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                                <span>Densidad: {{ $percent }}%</span>
                                <span class="text-teal-700 font-semibold">{{ $pond->status ?? 'Sembrado' }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pt-2 border-t border-slate-100">
                <a href="{{ route('guia-peces.index') }}"
                   class="w-full py-2 px-3 rounded-xl bg-cyan-50 hover:bg-cyan-100 text-cyan-700 text-xs font-semibold flex items-center justify-between transition">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-book-bookmark text-cyan-600"></i>
                        <span>Consultar Guía de Peces</span>
                    </span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
