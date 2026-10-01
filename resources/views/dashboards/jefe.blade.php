@extends('layouts.app')

@section('title', 'Panel Macro - Jefe Mayor')
@section('page_title', 'Dirección General y Planificación Piscícola')

@section('content')
<div class="space-y-6">

    <!-- Botón de navegación global y accesos directos -->
    <div class="flex flex-wrap items-center justify-between gap-2">
        <x-back-button />
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('jefe.reporte-mensual') }}"
               class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-xs font-bold hover:brightness-105 transition shadow-sm">
                <i class="fa-solid fa-chart-line"></i>
                <span>Cierre Financiero Mensual</span>
            </a>
            <a href="{{ route('ica.libro-campo') }}"
               class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-cyan-700 text-white text-xs font-bold hover:bg-cyan-800 transition shadow-sm">
                <i class="fa-solid fa-book-bookmark"></i>
                <span>Libro Oficial ICA (BPAP)</span>
            </a>
            <a href="{{ route('admin.ajustes') }}"
               class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                <i class="fa-solid fa-sliders text-cyan-600"></i>
                <span>Parámetros & Precios</span>
            </a>
        </div>
    </div>

    <!-- 1. Métricas Macro del Jefe Mayor -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Biomasa Total -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Biomasa Total en Lagos</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($metricas_macro['biomasa_total_kg'], 1, ',', '.') }} <span class="text-sm font-medium text-slate-400">kg</span></h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-xl shadow-inner border border-cyan-100">
                    <i class="fa-solid fa-water"></i>
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-500">{{ $metricas_macro['estanques_activos_count'] }} estanques en producción activa</p>
        </div>

        <!-- Cosechas Proyectadas -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Proyección de Cosecha</p>
                    <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($metricas_macro['kilos_cosecha_proyectados'], 1, ',', '.') }} <span class="text-sm font-medium text-slate-400">kg</span></h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner border border-emerald-100">
                    <i class="fa-solid fa-fish"></i>
                </div>
            </div>
            <p class="mt-3 text-xs text-emerald-600 font-semibold"><i class="fa-solid fa-calendar-check mr-1"></i>Programada por Jefe Mayor</p>
        </div>

        <!-- Recaudo Ventas Mes -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ventas Acumuladas Mes</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">${{ number_format($metricas_macro['ventas_mes_dinero'], 0, ',', '.') }}</h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-inner border border-amber-100">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-500">Caja de visitantes y mayoristas</p>
        </div>

        <!-- Permisos Pendientes -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Permisos por Revisar</p>
                    <h3 class="text-2xl font-black text-indigo-600 mt-1">{{ $metricas_macro['permisos_pendientes_count'] }}</h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-inner border border-indigo-100">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </div>
            </div>
            <p class="mt-3 text-xs text-indigo-600 font-semibold">{{ $metricas_macro['personal_activo_count'] }} trabajadores registrados</p>
        </div>
    </div>

    <!-- Autonomía de Bodega & Control de Concentrados -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="h-12 w-12 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-warehouse"></i>
            </div>
            <div>
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Gestión de Insumos</span>
                <h3 class="text-base font-bold text-slate-900">Autonomía de Bodega: {{ $bodega_resumen['total_bultos'] ?? $metricas_macro['bultos_bodega_total'] ?? 0 }} bultos en stock</h3>
                <p class="text-xs text-slate-500 mt-0.5">Proyección aproximada: {{ $bodega_resumen['dias_restantes'] ?? $metricas_macro['dias_restantes_bodega'] ?? 0 }} días restantes de ración</p>
            </div>
        </div>
        <a href="{{ route('admin.bodega.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition shadow-sm">
            <span>Ver Inventario de Bodega</span>
            <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </a>
    </div>

    <!-- Alerta de Cosecha Comercial si hay lagos listos -->
    @if(isset($lagos_listos_pesca) && $lagos_listos_pesca->isNotEmpty())
        <div class="p-4 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent rounded-2xl border border-amber-300 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-fish text-xl animate-pulse"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-amber-950 uppercase tracking-wide">
                        ALERTA: {{ $lagos_listos_pesca->count() }} Lago(s) Listos para Pesca Comercial
                    </h3>
                    <p class="text-xs text-amber-800 mt-0.5">
                        Lagos con talla de cosecha (≥ 450g) o programados:
                        <span class="font-bold underline">{{ $lagos_listos_pesca->pluck('name')->join(', ') }}</span>.
                    </p>
                </div>
            </div>
            <a href="{{ route('cosechas.index') }}"
               class="shrink-0 px-3.5 py-1.5 rounded-xl font-bold text-xs bg-amber-600 hover:bg-amber-700 text-white transition shadow-sm">
                Programar Pesca
            </a>
        </div>
    @endif

    <!-- 2. Sección Principal: Cosechas Proyectadas & Permisos Laborales -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Card: Agenda y Proyección de Pesca -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-star text-cyan-600"></i>
                    Agenda de Pesca & Proyección de Kilos
                </h3>
                <a href="{{ route('cosechas.index') }}" class="text-xs font-semibold text-cyan-600 hover:text-cyan-700">Ver Todas &rarr;</a>
            </div>

            @if($cosechas_proyectadas->isEmpty())
                <div class="text-center py-8 text-slate-400">
                    <i class="fa-solid fa-calendar-xmark text-3xl mb-2 text-slate-300"></i>
                    <p class="text-sm">No hay cosechas programadas para los próximos días.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($cosechas_proyectadas as $cosecha)
                        <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-100 hover:bg-slate-100/60 transition">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center font-bold text-sm">
                                    {{ $loop->iteration }}
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800">{{ $cosecha->pond->name ?? 'Estanque' }}</h4>
                                    <p class="text-xs text-slate-500">Fecha: {{ $cosecha->scheduled_date }} • Programado por: {{ $cosecha->scheduledBy->name ?? 'Jefe Mayor' }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-extrabold text-slate-900">{{ number_format($cosecha->estimated_kg, 1) }} kg</span>
                                <span class="block text-[11px] font-semibold text-emerald-600 uppercase">{{ $cosecha->status }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Card: Solicitudes de Permisos Laborales (Aprobación) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6" x-data="{ reviewSuccess: '' }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-user-clock text-indigo-600"></i>
                    Permisos Laborales de Trabajadores
                </h3>
                <span class="text-xs bg-indigo-50 text-indigo-700 font-bold px-2.5 py-1 rounded-full border border-indigo-100">
                    {{ $permisos_pendientes->count() }} pendientes
                </span>
            </div>

            <div x-show="reviewSuccess" class="mb-3 p-3 bg-emerald-50 text-emerald-700 text-xs rounded-xl border border-emerald-200 font-medium" x-text="reviewSuccess"></div>

            @if($permisos_pendientes->isEmpty())
                <div class="text-center py-8 text-slate-400">
                    <i class="fa-solid fa-check-circle text-3xl mb-2 text-emerald-400"></i>
                    <p class="text-sm">Al día. No hay solicitudes pendientes de aprobación.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($permisos_pendientes as $permiso)
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="font-bold text-sm text-slate-800">{{ $permiso->user->name ?? 'Trabajador' }}</div>
                                <span class="text-xs text-slate-500">{{ $permiso->start_date }} al {{ $permiso->end_date }}</span>
                            </div>
                            <p class="text-xs text-slate-600 italic bg-white p-2 rounded-lg border border-slate-100">"{{ $permiso->reason }}"</p>
                            <div class="flex items-center justify-end gap-2 pt-1">
                                <button type="button"
                                        @click="fetch('/api/communication/leave-requests/{{ $permiso->id }}/review', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                            body: JSON.stringify({ decision: 'aprobar', response_notes: 'Aprobado por el Jefe Mayor' })
                                        }).then(res => res.json()).then(data => { reviewSuccess = data.message; setTimeout(() => location.reload(), 1200); })"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition">
                                    <i class="fa-solid fa-check mr-1"></i>Aprobar
                                </button>
                                <button type="button"
                                        @click="fetch('/api/communication/leave-requests/{{ $permiso->id }}/review', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                            body: JSON.stringify({ decision: 'rechazar', response_notes: 'Rechazado por necesidades operativas' })
                                        }).then(res => res.json()).then(data => { reviewSuccess = data.message; setTimeout(() => location.reload(), 1200); })"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-sm transition">
                                    <i class="fa-solid fa-xmark mr-1"></i>Rechazar
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- 3. Registro de Lagos, Tiempo Transcurrido y Biomasa -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-layer-group text-cyan-600"></i>
                Estado de Lagos: Siembra, Días de Cultivo y Biomasa Actual
            </h3>
            <a href="{{ route('guia-peces.index') }}"
               class="text-xs font-bold text-cyan-700 hover:text-cyan-800 bg-cyan-50 hover:bg-cyan-100 px-3 py-1.5 rounded-xl border border-cyan-200 flex items-center gap-1.5 transition">
                <i class="fa-solid fa-book-bookmark text-cyan-600"></i>
                <span>Ver Enciclopedia de Especies</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Código / Lago</th>
                        <th class="py-3 px-4">Fecha Siembra</th>
                        <th class="py-3 px-4">Días de Cultivo</th>
                        <th class="py-3 px-4">Meses</th>
                        <th class="py-3 px-4">Alevinos Sembrados</th>
                        <th class="py-3 px-4">Peso Prom. (g)</th>
                        <th class="py-3 px-4 text-right">Biomasa Est. (kg)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($estanques as $estanque)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4 font-bold text-slate-800">
                                <div class="flex items-center gap-2.5">
                                    <img src="{{ $estanque->foto_especie }}" alt="{{ $estanque->name }}" class="h-9 w-9 rounded-xl object-cover border border-slate-200 shadow-sm shrink-0">
                                    <div>
                                        <span class="inline-block px-1.5 py-0.5 rounded bg-cyan-50 text-cyan-700 font-mono text-[11px] mr-1">{{ $estanque->code ?? 'L-'.$estanque->id }}</span>
                                        <span>{{ $estanque->name }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-500">{{ $estanque->stocked_at ? $estanque->stocked_at->format('d/m/Y') : 'Sin fecha' }}</td>
                            <td class="py-3 px-4 font-semibold text-slate-700">{{ $estanque->days_in_culture }} días</td>
                            <td class="py-3 px-4 text-xs text-slate-500">{{ $estanque->months_in_culture }} m</td>
                            <td class="py-3 px-4">{{ number_format($estanque->fingerlings_stocked ?: $estanque->fish_population) }}</td>
                            <td class="py-3 px-4 font-mono font-medium">{{ number_format($estanque->average_weight, 1) }} g</td>
                            <td class="py-3 px-4 text-right font-black text-slate-900">{{ number_format($estanque->biomass, 1, ',', '.') }} kg</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-400">No hay registros de lagos actualmente.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
