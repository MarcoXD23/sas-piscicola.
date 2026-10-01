@extends('layouts.app')

@section('title', 'Ficha Técnica - ' . $lago->name)
@section('page_title', 'Ficha Técnica de Lago: ' . $lago->name)

@section('content')
<div class="space-y-6">

    <!-- Encabezado con foto y estado -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <img src="{{ $lago->foto_especie }}"
                     alt="{{ $lago->name }}"
                     class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl object-cover border border-slate-200 shadow-sm shrink-0">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono font-bold text-xs bg-slate-100 text-slate-800 px-2 py-0.5 rounded-lg border border-slate-200">
                            {{ $lago->code ?? 'L-'.$lago->id }}
                        </span>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900">{{ $lago->name }}</h2>
                        @if($listo_pesca)
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-300">
                                🎣 Listo para Cosecha Comercial
                            </span>
                        @endif
                    </div>
                    <p class="text-xs sm:text-sm text-cyan-700 font-bold mt-1">
                        Especie: {{ $lago->especie_nombre }} • Sembrado el: {{ $lago->stocked_at ? $lago->stocked_at->format('d/m/Y') : 'Fecha no registrada' }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.lagos.index') }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                    <i class="fa-solid fa-arrow-left mr-1.5"></i>Volver a Lagos
                </a>
                <a href="{{ route('admin.muestreos.index', ['pond_id' => $lago->id]) }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-cyan-600 hover:bg-cyan-700 transition shadow-sm">
                    <i class="fa-solid fa-plus mr-1.5"></i>Registrar Muestreo
                </a>
            </div>
        </div>
    </div>

    <!-- Indicadores Técnicos y Biológicos -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Población Viva -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Población de Peces Vivos</span>
            <div class="text-2xl font-black text-slate-900 mt-1">
                {{ number_format($lago->fish_population, 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Inicial: {{ number_format($lago->fingerlings_stocked, 0, ',', '.') }} alevinos</p>
        </div>

        <!-- Biomasa Estimada -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Biomasa Actual Estimada</span>
            <div class="text-2xl font-black text-cyan-700 mt-1">
                {{ number_format($lago->biomass, 1, ',', '.') }} kg
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Ración: {{ $lago->racion_sugerida_kg }} kg/día</p>
        </div>

        <!-- Peso Promedio Actual -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Peso Promedio Actual</span>
            <div class="text-2xl font-black text-slate-900 mt-1">
                {{ number_format($lago->average_weight, 1, ',', '.') }} g
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Meta comercial: 450 - 550 g</p>
        </div>

        <!-- Tiempo de Cultivo -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Tiempo Transcurrido</span>
            <div class="text-2xl font-black text-emerald-600 mt-1">
                {{ $lago->days_in_culture }} días
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Aprox. {{ $lago->months_in_culture }} meses de ciclo</p>
        </div>
    </div>

    <!-- Historial de Muestreos Sabatinos -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-clipboard-check text-cyan-600"></i>
                <span>Historial de Muestreos Semanales</span>
            </h3>
            <span class="text-xs text-slate-500 font-semibold">{{ $muestreos->count() }} muestreos registrados</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-500 uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">Fecha</th>
                        <th class="py-3 px-4">Peces Muestra</th>
                        <th class="py-3 px-4">Peso Total (kg)</th>
                        <th class="py-3 px-4">Peso Prom. (g)</th>
                        <th class="py-3 px-4">Registrado Por</th>
                        <th class="py-3 px-4">Notas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($muestreos as $m)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4 font-bold text-slate-800">
                                {{ $m->sampling_date ? $m->sampling_date->format('d/m/Y') : '' }}
                            </td>
                            <td class="py-3 px-4 font-black text-slate-900">{{ $m->sampled_fish_count }} pcs</td>
                            <td class="py-3 px-4 font-semibold text-slate-700">{{ number_format($m->sample_total_weight_kg, 2) }} kg</td>
                            <td class="py-3 px-4 font-black text-cyan-700 text-sm">{{ number_format($m->average_weight_g, 1) }} g</td>
                            <td class="py-3 px-4 text-slate-600">{{ $m->registeredBy->name ?? 'Administración' }}</td>
                            <td class="py-3 px-4 text-slate-500 italic">{{ $m->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-slate-400">No hay muestreos registrados para este lago.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

