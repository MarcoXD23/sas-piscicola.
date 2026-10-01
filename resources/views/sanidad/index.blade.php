@extends('layouts.app')

@section('title', 'Registro Sanitario y Tiempos de Retiro')
@section('page_title', 'Módulo de Sanidad Acuícola y Tiempo de Retiro (ICA)')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ modalOpen: false, diasRetiro: 0, fechaAplicacion: '{{ date('Y-m-d') }}' }">

    <!-- Navegación y Encabezado con Botón Atrás -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <x-back-button />
            <div class="mt-3">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                    <span class="p-2.5 rounded-2xl bg-rose-500/10 text-rose-600 border border-rose-500/20">
                        <i class="fa-solid fa-notes-medical text-xl"></i>
                    </span>
                    <span>Sanidad & Tiempos de Retiro</span>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Control de medicamentos, baños terapéuticos y bloqueo legal de cosecha según tiempos de retiro exigidos por el ICA.
                </p>
            </div>
        </div>

        <button @click="modalOpen = true"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-gradient-to-r from-rose-600 to-amber-600 hover:from-rose-500 hover:to-amber-500 text-white text-xs font-bold shadow-lg shadow-rose-600/30 transition active:scale-95 self-start sm:self-auto">
            <i class="fa-solid fa-plus text-sm"></i>
            <span>Nuevo Tratamiento Sanitario</span>
        </button>
    </div>

    <!-- Banner Crítico si hay estanques en Tiempo de Retiro -->
    @if ($estanquesEnRetiro->isNotEmpty())
        <div class="rounded-3xl bg-rose-50 border-2 border-rose-300 p-5 shadow-sm space-y-3">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl bg-rose-600 text-white flex items-center justify-center text-lg animate-pulse">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-rose-900 uppercase tracking-wide">
                        Estanques en Período de Carencia / Tiempo de Retiro Activo (ICA)
                    </h2>
                    <p class="text-xs text-rose-700">
                        Está estrictamente prohibida la pesca o cosecha comercial en estos lagos hasta el cumplimiento total de los días de retiro.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 pt-1">
                @foreach ($estanquesEnRetiro as $pRetiro)
                    @php
                        $trat = $pRetiro->tratamientoEnRetiroActivo();
                    @endphp
                    <div class="p-3.5 rounded-2xl bg-white border border-rose-200 shadow-2xs flex items-center justify-between">
                        <div>
                            <span class="font-extrabold text-slate-900 text-xs block">{{ $pRetiro->name }}</span>
                            <span class="text-[11px] text-slate-500 block">Producto: {{ $trat->producto ?? 'Tratamiento' }}</span>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-600 text-white">
                                Bloqueado
                            </span>
                            <span class="text-[10px] text-rose-700 font-bold block mt-1">
                                Hasta: {{ $trat->fecha_fin_retiro ? $trat->fecha_fin_retiro->format('d/m/Y') : '' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Tabla Histórica de Tratamientos -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Libro Sanitario de la Granja</h2>
                <p class="text-xs text-slate-500">Historial de productos aplicados, dosis y cumplimiento de carencias</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">Fecha Aplicación</th>
                        <th class="py-3.5 px-6">Estanque</th>
                        <th class="py-3.5 px-6">Tipo / Tratamiento</th>
                        <th class="py-3.5 px-6">Producto y Dosis</th>
                        <th class="py-3.5 px-6 text-center">Días Retiro</th>
                        <th class="py-3.5 px-6">Fecha Fin Retiro</th>
                        <th class="py-3.5 px-6 text-center">Estado Sanitario</th>
                        <th class="py-3.5 px-6">Responsable</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($tratamientos as $item)
                        @php
                            $enRetiro = $item->estaEnRetiro();
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition {{ $enRetiro ? 'bg-rose-50/30' : '' }}">
                            <td class="py-4 px-6 font-semibold text-slate-700">
                                {{ $item->fecha_aplicacion ? $item->fecha_aplicacion->format('d/m/Y') : 'N/A' }}
                            </td>
                            <td class="py-4 px-6 font-bold text-slate-900">
                                {{ $item->estanque->name ?? 'Estanque #'.$item->estanque_id }}
                            </td>
                            <td class="py-4 px-6">
                                @php
                                    $tipoLabel = match($item->tipo_tratamiento) {
                                        'bano_sal' => 'Baño de Sal',
                                        'encalado' => 'Encalado',
                                        'medicamento_veterinario' => 'Medicamento Veterinario',
                                        'desinfectante' => 'Desinfectante',
                                        default => $item->tipo_tratamiento,
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $tipoLabel }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="font-bold text-slate-800 block">{{ $item->producto }}</span>
                                <span class="text-[11px] text-slate-400 block">Dosis: {{ $item->dosis_aplicada }}</span>
                            </td>
                            <td class="py-4 px-6 text-center font-bold font-mono">
                                {{ $item->dias_tiempo_retiro }} d
                            </td>
                            <td class="py-4 px-6 font-semibold font-mono {{ $enRetiro ? 'text-rose-600 font-bold' : 'text-slate-600' }}">
                                {{ $item->fecha_fin_retiro ? $item->fecha_fin_retiro->format('d/m/Y') : 'N/A' }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if($enRetiro)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-ping"></span>
                                        EN RETIRO
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Liberado / Apto
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-slate-600">
                                {{ $item->user->name ?? 'Técnico' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-400">
                                <i class="fa-solid fa-notes-medical text-3xl mb-2 text-slate-300"></i>
                                <p class="text-xs">No hay tratamientos sanitarios registrados.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para Registrar Tratamiento Sanitario -->
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
                    <div class="h-10 w-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg font-bold">
                        <i class="fa-solid fa-syringe"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Nuevo Tratamiento Sanitario</h3>
                        <p class="text-[11px] text-slate-400">Cumplimiento normativa ICA y período de carencia</p>
                    </div>
                </div>
                <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-700">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('sanidad.store') }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="estanque_id" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Estanque *
                        </label>
                        <select name="estanque_id" id="estanque_id" required
                                class="w-full rounded-2xl border-slate-300 text-xs font-semibold text-slate-800 py-2.5">
                            <option value="">Seleccionar Estanque</option>
                            @foreach ($estanques as $pond)
                                <option value="{{ $pond->id }}">{{ $pond->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tipo_tratamiento" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Tipo de Tratamiento *
                        </label>
                        <select name="tipo_tratamiento" id="tipo_tratamiento" required
                                class="w-full rounded-2xl border-slate-300 text-xs font-semibold text-slate-800 py-2.5">
                            <option value="bano_sal">Baño de Sal (Parasitario/Estrés)</option>
                            <option value="encalado">Encalado (Cal Viva / Cal Agrícola)</option>
                            <option value="medicamento_veterinario">Medicamento Veterinario / Antibiótico</option>
                            <option value="desinfectante">Desinfectante / Yodóforo / Azul de Metileno</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="producto" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Producto Comercial *
                        </label>
                        <input type="text" name="producto" id="producto" required placeholder="Ej: Sal Marina / Oxitetraciclina"
                               class="w-full rounded-2xl border-slate-300 text-xs font-bold text-slate-800 py-2.5">
                    </div>

                    <div>
                        <label for="dosis_aplicada" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Dosis Aplicada *
                        </label>
                        <input type="text" name="dosis_aplicada" id="dosis_aplicada" required placeholder="Ej: 5 g/L o 200 kg/ha"
                               class="w-full rounded-2xl border-slate-300 text-xs font-bold text-slate-800 py-2.5">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="fecha_aplicacion" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Fecha de Aplicación *
                        </label>
                        <input type="date" name="fecha_aplicacion" id="fecha_aplicacion" required x-model="fechaAplicacion"
                               class="w-full rounded-2xl border-slate-300 text-xs font-semibold text-slate-800 py-2.5">
                    </div>

                    <div>
                        <label for="dias_tiempo_retiro" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Días Tiempo de Retiro *
                        </label>
                        <input type="number" name="dias_tiempo_retiro" id="dias_tiempo_retiro" required min="0" max="180" x-model.number="diasRetiro"
                               class="w-full rounded-2xl border-slate-300 text-xs font-bold text-slate-800 py-2.5" placeholder="0 si no requiere">
                        <span class="text-[10px] text-slate-400 mt-1 block">Días de carencia exigidos por la ley antes de cosechar</span>
                    </div>
                </div>

                <div>
                    <label for="observaciones" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Motivo / Observaciones Clínicas
                    </label>
                    <textarea name="observaciones" id="observaciones" rows="2"
                              class="w-full rounded-2xl border-slate-300 text-xs text-slate-800 py-2" placeholder="Ej: Tratamiento preventivo por presencia de hongos tras muestreo de peso."></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl bg-gradient-to-r from-rose-600 to-amber-600 hover:from-rose-500 hover:to-amber-500 text-white text-xs font-bold shadow-md shadow-rose-600/30">
                        Registrar Tratamiento y Activar Carencia
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
