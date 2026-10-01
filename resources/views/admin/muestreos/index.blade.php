@extends('layouts.app')

@section('title', 'Muestreos Sabatinos de Crecimiento')
@section('page_title', 'Muestreos Sabatinos y Control Biométrico')

@section('content')
<div class="space-y-6" x-data="{ modalOpen: false }">

    <!-- Encabezado y Botón Nuevo Muestreo -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <span class="p-2 rounded-xl bg-cyan-600 text-white shadow-md shadow-cyan-600/30">
                    <i class="fa-solid fa-clipboard-check text-lg"></i>
                </span>
                <span>Bitácora de Muestreos Sabatinos</span>
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Registro semanal de biometría, peso promedio y actualización automática de biomasa en estanques.
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <button @click="modalOpen = true"
                    class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-cyan-600 hover:bg-cyan-700 transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                <span>Nuevo Muestreo Sabatino</span>
            </button>
            <a href="{{ route('admin.lagos.index') }}"
               class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-water text-cyan-600"></i>
                <span>Ver Lagos</span>
            </a>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex items-center justify-between flex-wrap gap-3">
        <form method="GET" action="{{ route('admin.muestreos.index') }}" class="flex items-center gap-3 flex-wrap flex-1">
            <div class="w-full sm:w-64">
                <select name="pond_id" class="w-full rounded-xl border-slate-200 text-xs py-2 px-3 focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="">-- Todos los Lagos / Estanques --</option>
                    @foreach($lagos as $lago)
                        <option value="{{ $lago->id }}" {{ request('pond_id') == $lago->id ? 'selected' : '' }}>
                            {{ $lago->name }} ({{ $lago->code ?? 'L-'.$lago->id }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-48">
                <input type="date" name="date" value="{{ request('date') }}"
                       class="w-full rounded-xl border-slate-200 text-xs py-2 px-3 focus:ring-cyan-500 focus:border-cyan-500">
            </div>
            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 text-white hover:bg-slate-900 transition">
                <i class="fa-solid fa-filter mr-1.5"></i>Filtrar
            </button>
            @if(request('pond_id') || request('date'))
                <a href="{{ route('admin.muestreos.index') }}" class="text-xs text-slate-500 hover:text-slate-700 font-semibold">
                    Limpiar Filtros
                </a>
            @endif
        </form>
        <div class="text-xs text-slate-400 font-semibold">
            {{ $total_muestreos }} registros encontrados
        </div>
    </div>

    <!-- Tabla Histórica de Muestreos -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">Fecha</th>
                        <th class="py-3 px-4">Estanque</th>
                        <th class="py-3 px-4">Población Estanque</th>
                        <th class="py-3 px-4">Muestra (Peces)</th>
                        <th class="py-3 px-4">Peso Total (kg)</th>
                        <th class="py-3 px-4">Peso Promedio (g)</th>
                        <th class="py-3 px-4">Responsable</th>
                        <th class="py-3 px-4">Observaciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($muestreos as $muestreo)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3.5 px-4 font-bold text-slate-800">
                                {{ $muestreo->sampling_date ? $muestreo->sampling_date->format('d/m/Y') : '' }}
                            </td>
                            <td class="py-3.5 px-4 font-black text-slate-900">
                                <a href="{{ route('admin.lagos.show', $muestreo->pond_id) }}" class="text-cyan-700 hover:underline">
                                    {{ $muestreo->pond->name ?? 'Estanque #'.$muestreo->pond_id }}
                                </a>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                {{ number_format($muestreo->pond->fish_population ?? 0, 0, ',', '.') }} pcs
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-800">
                                {{ $muestreo->sampled_fish_count }} pcs
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-700">
                                {{ number_format($muestreo->sample_total_weight_kg, 2) }} kg
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-black text-cyan-700 text-sm">
                                    {{ number_format($muestreo->average_weight_g, 1) }} g
                                </span>
                                @if((float)$muestreo->average_weight_g >= 450)
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-black bg-amber-100 text-amber-800 ml-1">
                                        Talla Comercial
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                {{ $muestreo->registeredBy->name ?? 'Admin' }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 italic max-w-xs truncate">
                                {{ $muestreo->notes ?? 'Sin observaciones' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-slate-400">
                                No se encontraron registros de muestreos biométricos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para Registrar Muestreo Sabatino -->
    <div x-show="modalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         x-cloak>
        <div @click.away="modalOpen = false" class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-scale-balanced text-cyan-600"></i>
                    <span>Registrar Muestreo Sabatino</span>
                </h3>
                <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.muestreos.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Estanque / Lago a Muestrear *</label>
                    <select name="pond_id" required class="w-full rounded-xl border-slate-200 text-xs py-2.5 px-3 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">Seleccione el estanque...</option>
                        @foreach($lagos as $lago)
                            <option value="{{ $lago->id }}" {{ request('pond_id') == $lago->id ? 'selected' : '' }}>
                                {{ $lago->name }} (Población: {{ number_format($lago->fish_population, 0, ',', '.') }} pcs)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Fecha del Muestreo *</label>
                    <input type="date" name="sampling_date" value="{{ date('Y-m-d') }}" required
                           class="w-full rounded-xl border-slate-200 text-xs py-2.5 px-3 focus:ring-cyan-500 focus:border-cyan-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Cantidad Peces Muestreados *</label>
                        <input type="number" name="sampled_fish_count" min="1" step="1" placeholder="Ej: 50" required
                               class="w-full rounded-xl border-slate-200 text-xs py-2.5 px-3 focus:ring-cyan-500 focus:border-cyan-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Peso Total Muestra (kg) *</label>
                        <input type="number" name="sample_total_weight_kg" min="0.01" step="0.01" placeholder="Ej: 22.5" required
                               class="w-full rounded-xl border-slate-200 text-xs py-2.5 px-3 focus:ring-cyan-500 focus:border-cyan-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Observaciones / Condición de Crecimiento</label>
                    <textarea name="notes" rows="2" placeholder="Observaciones de coloración, agallas, voracidad..."
                              class="w-full rounded-xl border-slate-200 text-xs py-2 px-3 focus:ring-cyan-500 focus:border-cyan-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-cyan-600 hover:bg-cyan-700 transition shadow-sm">
                        Guardar y Recalcular Biomasa
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

