@extends('layouts.app')

@section('title', 'Desdobles y Traslados de Peces')
@section('page_title', 'Módulo de Traslados y Desdobles de Peces')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ modalOpen: false }">

    <!-- Navegación y Encabezado con Botón Atrás -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <x-back-button />
            <div class="mt-3">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                    <span class="p-2.5 rounded-2xl bg-cyan-500/10 text-cyan-600 border border-cyan-500/20">
                        <i class="fa-solid fa-arrows-split-up-and-left text-xl"></i>
                    </span>
                    <span>Desdobles & Traslados de Estanques</span>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Control de densidad poblacional, cambio de etapas (Alevinaje → Levante → Engorde) y recálculo automático de biomasa.
                </p>
            </div>
        </div>

        <button @click="modalOpen = true"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-500 hover:to-teal-500 text-white text-xs font-bold shadow-lg shadow-cyan-600/30 transition active:scale-95 self-start sm:self-auto">
            <i class="fa-solid fa-plus text-sm"></i>
            <span>Nuevo Traslado / Desdoble</span>
        </button>
    </div>

    <!-- Resumen Operativo de Estanques -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Traslados</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-0.5">{{ $traslados->count() }}</h3>
                </div>
                <div class="h-10 w-10 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <p class="text-[11px] text-slate-400 mt-2">Movimientos registrados en la finca</p>
        </div>

        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Peces Movilizados</span>
                    <h3 class="text-2xl font-black text-teal-600 mt-0.5">{{ number_format($traslados->sum('cantidad_peces_trasladados')) }}</h3>
                </div>
                <div class="h-10 w-10 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-fish"></i>
                </div>
            </div>
            <p class="text-[11px] text-slate-400 mt-2">Biomasa redistribuida por manejo</p>
        </div>

        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Merma Total de Manejo</span>
                    <h3 class="text-2xl font-black text-rose-600 mt-0.5">{{ number_format($traslados->sum('merma_traslado_peces')) }}</h3>
                </div>
                <div class="h-10 w-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-skull-crossbones"></i>
                </div>
            </div>
            <p class="text-[11px] text-slate-400 mt-2">Peces muertos durante captura/traslado</p>
        </div>
    </div>

    <!-- Historial de Traslados -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Historial de Traslados y Desdobles</h2>
                <p class="text-xs text-slate-500">Trazabilidad de movimientos entre estanques con fecha y responsable</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">Fecha</th>
                        <th class="py-3.5 px-6">Estanque Origen</th>
                        <th class="py-3.5 px-6">Estanque Destino</th>
                        <th class="py-3.5 px-6 text-center">Peces Trasladados</th>
                        <th class="py-3.5 px-6 text-center">Peso Prom. (g)</th>
                        <th class="py-3.5 px-6 text-center">Merma (Manejo)</th>
                        <th class="py-3.5 px-6">Motivo</th>
                        <th class="py-3.5 px-6">Responsable</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($traslados as $item)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-4 px-6 font-semibold text-slate-700">
                                {{ $item->fecha ? $item->fecha->format('d/m/Y') : 'N/A' }}
                            </td>
                            <td class="py-4 px-6">
                                <span class="font-bold text-slate-900">{{ $item->estanqueOrigen->name ?? 'Estanque #'.$item->estanque_origen_id }}</span>
                                <span class="text-[11px] text-slate-400 block font-mono">{{ $item->estanqueOrigen->code ?? '' }}</span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="font-bold text-teal-700">{{ $item->estanqueDestino->name ?? 'Estanque #'.$item->estanque_destino_id }}</span>
                                <span class="text-[11px] text-teal-500 block font-mono">{{ $item->estanqueDestino->code ?? '' }}</span>
                            </td>
                            <td class="py-4 px-6 text-center font-bold text-slate-900">
                                {{ number_format($item->cantidad_peces_trasladados) }}
                            </td>
                            <td class="py-4 px-6 text-center font-mono font-semibold text-slate-700">
                                {{ number_format($item->peso_promedio_gramos, 1) }} g
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if($item->merma_traslado_peces > 0)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        -{{ number_format($item->merma_traslado_peces) }} peces
                                    </span>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @php
                                    $motivoLabel = match($item->motivo) {
                                        'desdoble_densidad' => 'Desdoble de Densidad',
                                        'cambio_etapa' => 'Cambio de Etapa',
                                        'limpieza_estanque' => 'Limpieza / Mantenimiento',
                                        default => $item->motivo,
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded-xl text-[10px] font-bold bg-cyan-50 text-cyan-800 border border-cyan-200">
                                    {{ $motivoLabel }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-slate-600">
                                {{ $item->user->name ?? 'Administrador' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-400">
                                <i class="fa-solid fa-arrows-split-up-and-left text-3xl mb-2 text-slate-300"></i>
                                <p class="text-xs">No hay traslados o desdobles registrados aún.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para Registrar Nuevo Traslado -->
    <div x-show="modalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-cloak>
        <div @click.away="modalOpen = false"
             class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200/80 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-lg font-bold">
                        <i class="fa-solid fa-shuffle"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Registrar Traslado / Desdoble</h3>
                        <p class="text-[11px] text-slate-400">Actualiza automáticamente poblaciones y biomasas</p>
                    </div>
                </div>
                <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-700">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('traslados.store') }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="estanque_origen_id" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Estanque Origen *
                        </label>
                        <select name="estanque_origen_id" id="estanque_origen_id" required
                                class="w-full rounded-2xl border-slate-300 text-xs font-semibold text-slate-800 py-2.5">
                            <option value="">Seleccionar Origen</option>
                            @foreach ($estanques as $pond)
                                <option value="{{ $pond->id }}">
                                    {{ $pond->name }} ({{ number_format($pond->fish_population) }} peces)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="estanque_destino_id" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Estanque Destino *
                        </label>
                        <select name="estanque_destino_id" id="estanque_destino_id" required
                                class="w-full rounded-2xl border-slate-300 text-xs font-semibold text-slate-800 py-2.5">
                            <option value="">Seleccionar Destino</option>
                            @foreach ($estanques as $pond)
                                <option value="{{ $pond->id }}">
                                    {{ $pond->name }} (Estado: {{ $pond->status ?? 'Disponible' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="fecha" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Fecha de Traslado *
                        </label>
                        <input type="date" name="fecha" id="fecha" required value="{{ date('Y-m-d') }}"
                               class="w-full rounded-2xl border-slate-300 text-xs font-semibold text-slate-800 py-2.5">
                    </div>

                    <div>
                        <label for="motivo" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Motivo *
                        </label>
                        <select name="motivo" id="motivo" required
                                class="w-full rounded-2xl border-slate-300 text-xs font-semibold text-slate-800 py-2.5">
                            <option value="desdoble_densidad">Desdoble por Densidad</option>
                            <option value="cambio_etapa">Cambio de Etapa (Crecimiento)</option>
                            <option value="limpieza_estanque">Limpieza / Mantenimiento</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label for="cantidad_peces_trasladados" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Cant. Peces *
                        </label>
                        <input type="number" name="cantidad_peces_trasladados" id="cantidad_peces_trasladados" required min="1" step="1"
                               class="w-full rounded-2xl border-slate-300 text-xs font-bold text-slate-800 py-2.5" placeholder="Ej: 2500">
                    </div>

                    <div>
                        <label for="peso_promedio_gramos" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Peso Prom. (g) *
                        </label>
                        <input type="number" name="peso_promedio_gramos" id="peso_promedio_gramos" required min="0.1" step="0.1"
                               class="w-full rounded-2xl border-slate-300 text-xs font-bold text-slate-800 py-2.5" placeholder="Ej: 180.5">
                    </div>

                    <div>
                        <label for="merma_traslado_peces" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Merma (Peces)
                        </label>
                        <input type="number" name="merma_traslado_peces" id="merma_traslado_peces" min="0" step="1" value="0"
                               class="w-full rounded-2xl border-slate-300 text-xs font-bold text-slate-800 py-2.5">
                    </div>
                </div>

                <div>
                    <label for="observaciones" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Observaciones / Detalle Operativo
                    </label>
                    <textarea name="observaciones" id="observaciones" rows="2"
                              class="w-full rounded-2xl border-slate-300 text-xs text-slate-800 py-2" placeholder="Ej: Traslado con red de copo, salinidad preventiva en destino."></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-500 hover:to-teal-500 text-white text-xs font-bold shadow-md shadow-cyan-600/30">
                        Confirmar y Aplicar Traslado
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
