@extends('layouts.app')

@section('title', 'Gestión de Lagos y Biomasa - Administración')
@section('page_title', 'Control de Lagos, Biomasa y Tiempos de Cultivo')

@section('content')
<div class="space-y-6"
     x-data="{
         showModalNuevoLago: false,
         fechaSiembra: '{{ date('Y-m-d') }}',
         filasEspecies: [
             { especie_id: '{{ $especies->first()?->id ?? 1 }}', cantidad: 10000, peso: 1.5 }
         ],
         agregarEspecie() {
             this.filasEspecies.push({
                 especie_id: '{{ $especies->skip(1)->first()?->id ?? $especies->first()?->id ?? 1 }}',
                 cantidad: 2000,
                 peso: 1.5
             });
         },
         eliminarEspecie(idx) {
             if (this.filasEspecies.length > 1) {
                 this.filasEspecies.splice(idx, 1);
             }
         },
         get totalAlevinos() {
             return this.filasEspecies.reduce((sum, f) => sum + (parseInt(f.cantidad) || 0), 0);
         },
         get biomasaTotalCalc() {
             const total = this.filasEspecies.reduce((sum, f) => {
                 const c = parseFloat(f.cantidad) || 0;
                 const p = parseFloat(f.peso) || 0;
                 return sum + ((c * p) / 1000);
             }, 0);
             return total.toFixed(2);
         },
         get diasCultivo() {
             if (!this.fechaSiembra) return 0;
             const parts = this.fechaSiembra.split('-');
             if (parts.length !== 3) return 0;
             const d1 = new Date(parts[0], parts[1] - 1, parts[2]);
             const d2 = new Date();
             d2.setHours(0,0,0,0);
             const diff = Math.floor((d2 - d1) / (1000 * 60 * 60 * 24));
             return diff >= 0 ? diff : 0;
         }
     }">

    <!-- Barra Superior de Navegación y Accesos -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <span class="p-2 rounded-xl bg-cyan-600 text-white shadow-md shadow-cyan-600/30">
                    <i class="fa-solid fa-water text-lg"></i>
                </span>
                <span>Control Maestro de Lagos y Biomasa</span>
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Monitoreo técnico de población viva, biomasa total, tiempo de engorde y registro de nuevas siembras.
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button"
                    @click="showModalNuevoLago = true"
                    onclick="document.getElementById('modal-nuevo-lago').classList.remove('hidden')"
                    class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                + Registrar Nuevo Lago
            </button>
            <a href="{{ route('admin.muestreos.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-cyan-600 hover:bg-cyan-700 transition shadow-sm">
                <i class="fa-solid fa-clipboard-check"></i>
                <span>Muestreos Sabatinos</span>
            </a>
            <a href="{{ route('guia-peces.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-cyan-700 bg-white hover:bg-slate-50 border border-slate-200 transition shadow-sm">
                <i class="fa-solid fa-book-bookmark text-cyan-600"></i>
                <span>Guía Técnica</span>
            </a>
        </div>
    </div>

    <!-- Mensajes de Estado / Flash -->
    @if(session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2.5">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Alerta Comercial: Lagos Listos para Pesca (>= 450g o en cosecha) -->
    @if(isset($lagos_listos_pesca) && $lagos_listos_pesca->isNotEmpty())
        <div class="p-4 bg-gradient-to-r from-amber-500/15 via-amber-500/10 to-transparent rounded-2xl border border-amber-300 flex items-center justify-between flex-wrap gap-4 shadow-sm">
            <div class="flex items-center gap-3.5">
                <div class="h-11 w-11 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl shrink-0 shadow-inner">
                    <i class="fa-solid fa-fish animate-pulse"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-amber-950 uppercase tracking-wide flex items-center gap-2">
                        <span>Aviso Comercial: {{ $lagos_listos_pesca->count() }} Lago(s) Listos para Pesca Comercial</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-200 text-amber-900 border border-amber-300">Talla Óptima</span>
                    </h3>
                    <p class="text-xs text-amber-900/90 mt-0.5">
                        Alcanzaron peso comercial (≥ 450g) o tienen cosecha programada:
                        <span class="font-extrabold underline">{{ $lagos_listos_pesca->pluck('name')->join(', ') }}</span>.
                    </p>
                </div>
            </div>
            @if(auth()->user()->hasRole(['jefe_mayor', 'owner']))
            <a href="{{ route('cosechas.index') }}"
               class="shrink-0 px-4 py-2 rounded-xl font-bold text-xs bg-amber-600 hover:bg-amber-700 text-white transition shadow-sm">
                <i class="fa-solid fa-scale-balanced mr-1.5"></i>Programar Cosecha
            </a>
            @endif
        </div>
    @endif

    <!-- 1. Tarjetas de Indicadores Macro -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Biomasa Total en Finca -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Biomasa Total Estimada</p>
                    <h3 class="text-2xl font-black text-cyan-700 mt-1">
                        {{ number_format($total_biomasa_kg, 1, ',', '.') }} <span class="text-xs font-medium text-slate-400">kg</span>
                    </h3>
                </div>
                <div class="h-11 w-11 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-lg shadow-inner border border-cyan-100">
                    <i class="fa-solid fa-weight-scale"></i>
                </div>
            </div>
            <p class="mt-2.5 text-xs text-slate-500">Acumulado en los {{ $total_lagos_count }} estanques</p>
        </div>

        <!-- Total Peces Vivos -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Peces Vivos</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">
                        {{ number_format($total_peces_vivos, 0, ',', '.') }} <span class="text-xs font-medium text-slate-400">peces</span>
                    </h3>
                </div>
                <div class="h-11 w-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shadow-inner border border-indigo-100">
                    <i class="fa-solid fa-fish"></i>
                </div>
            </div>
            <p class="mt-2.5 text-xs text-indigo-600 font-semibold">Censo poblacional real en agua</p>
        </div>

        <!-- Lagos Listos para Pesca -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Listos para Cosecha</p>
                    <h3 class="text-2xl font-black text-amber-600 mt-1">{{ $lagos_listos_pesca->count() }}</h3>
                </div>
                <div class="h-11 w-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shadow-inner border border-amber-100">
                    <i class="fa-solid fa-basket-shopping"></i>
                </div>
            </div>
            <p class="mt-2.5 text-xs text-amber-700 font-semibold">Talla comercial ≥ 450 gramos</p>
        </div>

        <!-- Lagos en Producción Activa -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Lagos Activos</p>
                    <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ $total_lagos_count }}</h3>
                </div>
                <div class="h-11 w-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shadow-inner border border-emerald-100">
                    <i class="fa-solid fa-water"></i>
                </div>
            </div>
            <p class="mt-2.5 text-xs text-slate-500">100% inventariados en finca</p>
        </div>
    </div>

    <!-- 2. Tabla Completa de Gestión de Lagos y Métricas Biológicas -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
            <div>
                <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-layer-group text-cyan-600"></i>
                    <span>Control Biológico y Productivo por Lago</span>
                </h3>
                <p class="text-xs text-slate-400">Datos técnicos actualizados en tiempo real según siembras y biometrías.</p>
            </div>
            <span class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1 rounded-xl">
                {{ $lagos->count() }} estanques registrados
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-500 uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">Lago / Especie</th>
                        <th class="py-3 px-4">Tipo Estanque</th>
                        <th class="py-3 px-4">Peces Vivos</th>
                        <th class="py-3 px-4">Tiempo de Cultivo</th>
                        <th class="py-3 px-4">Biomasa Total</th>
                        <th class="py-3 px-4">Peso Promedio</th>
                        <th class="py-3 px-4">Último Muestreo</th>
                        <th class="py-3 px-4">Estado</th>
                        <th class="py-3 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($lagos as $lago)
                        @php
                            $pond = $lago;
                            $listoPesca = (float) $lago->average_weight >= 450 || $lago->status === 'En Cosecha';
                            $ultimoMuestreo = $lago->samplings->first();
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition {{ $listoPesca ? 'bg-amber-50/20' : '' }}">
                            <!-- Nombre y Especie -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    @if($lago->es_policultivo && $lago->especiesDetalle->isNotEmpty())
                                        <!-- Miniaturas circulares superpuestas para Policultivo -->
                                        <div class="flex -space-x-2.5 overflow-hidden shrink-0 py-1">
                                            @foreach($lago->especiesDetalle as $detalle)
                                                <img src="{{ asset($detalle->especie->imagen_url ?? $detalle->especie->foto_url ?? 'images/peces/mojarra_roja.jpg') }}" 
                                                     alt="{{ $detalle->especie->nombre_comun ?? 'Especie' }}" 
                                                     title="{{ $detalle->especie->nombre_comun ?? 'Especie' }}: {{ number_format($detalle->fish_population, 0, ',', '.') }} peces ({{ number_format($detalle->biomass, 1, ',', '.') }} kg)" 
                                                     class="inline-block w-9 h-9 rounded-full ring-2 ring-white object-cover shadow-sm">
                                            @endforeach
                                        </div>
                                    @else
                                        <!-- Miniatura única para Monocultivo -->
                                        <img src="{{ asset($pond->especie->imagen_url ?? $pond->especie->foto_url ?? $pond->foto_especie ?? 'images/peces/mojarra_roja.jpg') }}" 
                                             alt="{{ $pond->especie->nombre_comun ?? $pond->name }}" 
                                             class="w-10 h-10 rounded-full object-cover border border-slate-200 shadow-sm shrink-0">
                                    @endif

                                    <div>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="font-mono font-bold text-[10px] bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded">
                                                {{ $lago->code ?? 'L-'.$lago->id }}
                                            </span>
                                            <h4 class="font-extrabold text-slate-900 text-sm">{{ $lago->name }}</h4>

                                            @if($lago->es_policultivo)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-800 border border-blue-200">
                                                    Policultivo ({{ $lago->especiesDetalle->count() ?: 2 }} especies)
                                                </span>
                                            @endif
                                        </div>

                                        @if($lago->es_policultivo && $lago->especiesDetalle->isNotEmpty())
                                            <!-- Desglose compacto por especie en Policultivo -->
                                            <ul class="text-[10px] text-slate-600 mt-1 space-y-0.5">
                                                @foreach($lago->especiesDetalle as $detalle)
                                                    <li class="flex items-center gap-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 shrink-0"></span>
                                                        <span class="font-bold text-slate-800">{{ $detalle->especie->nombre_comun ?? 'Especie' }}:</span>
                                                        <span>{{ number_format($detalle->fish_population, 0, ',', '.') }}</span>
                                                        <span class="text-slate-400">({{ number_format($detalle->biomass, 1, ',', '.') }} kg)</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="text-[11px] text-cyan-700 font-semibold">{{ $lago->especie_nombre }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Tipo de Estanque -->
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $lago->tipo_estanque ?? 'Tierra' }}
                                </span>
                            </td>

                            <!-- Cantidad Total de Peces Vivos -->
                            <td class="py-3.5 px-4 font-black text-slate-900 text-sm">
                                {{ number_format($lago->fish_population, 0, ',', '.') }}
                                <span class="text-[10px] font-normal text-slate-400 block">
                                    Sembrados: {{ number_format($lago->fingerlings_stocked, 0, ',', '.') }}
                                </span>
                            </td>

                            <!-- Tiempo de Cultivo (Días y Meses) -->
                            <td class="py-3.5 px-4">
                                <span class="font-black text-slate-800 text-xs">{{ $lago->days_in_culture }} días</span>
                                <span class="text-[11px] text-slate-400 block">({{ $lago->months_in_culture }} meses)</span>
                            </td>

                            <!-- Biomasa Total Estimada (kg) -->
                            <td class="py-3.5 px-4">
                                <span class="font-extrabold text-cyan-700 text-sm">{{ number_format($lago->biomass, 1, ',', '.') }} kg</span>
                                <span class="text-[10px] text-slate-400 block">Ración: {{ $lago->racion_sugerida_kg }} kg/d</span>
                            </td>

                            <!-- Peso Promedio Actual -->
                            <td class="py-3.5 px-4">
                                <span class="font-black text-slate-900 text-sm">{{ number_format($lago->average_weight, 1, ',', '.') }} g</span>
                                @if($listoPesca)
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-200 mt-0.5">
                                        Talla Comercial
                                    </span>
                                @endif
                            </td>

                            <!-- Último Muestreo Sabatino -->
                            <td class="py-3.5 px-4 text-xs">
                                @if($ultimoMuestreo)
                                    <span class="font-bold text-slate-700">{{ $ultimoMuestreo->sampling_date ? $ultimoMuestreo->sampling_date->format('d/m/Y') : '' }}</span>
                                    <span class="text-[11px] text-slate-500 block">Muestra: {{ $ultimoMuestreo->sampled_fish_count }} pcs</span>
                                @else
                                    <span class="text-slate-400 italic">Sin muestreos</span>
                                @endif
                            </td>

                            <!-- Estado -->
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase
                                    @if($lago->status === 'En Cosecha') bg-amber-100 text-amber-800 border border-amber-300
                                    @elseif($lago->status === 'alerta_critica') bg-rose-100 text-rose-800 border border-rose-300
                                    @else bg-emerald-100 text-emerald-800 border border-emerald-300 @endif">
                                    {{ $lago->status ?? 'Activo' }}
                                </span>
                            </td>

                            <!-- Acciones -->
                            <td class="py-3.5 px-4 text-right space-x-1">
                                <a href="{{ route('admin.lagos.show', $lago->id) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-cyan-700 bg-cyan-50 hover:bg-cyan-100 border border-cyan-200 transition">
                                    <i class="fa-solid fa-chart-line"></i>
                                    <span>Ficha</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-12 text-slate-400">
                                <div class="flex flex-col items-center justify-center py-6 text-center max-w-md mx-auto">
                                    <div class="h-16 w-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mb-3 shadow-inner border border-emerald-100">
                                        <i class="fa-solid fa-water"></i>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-800">No hay lagos registrados aún</h4>
                                    <p class="text-xs text-slate-500 mt-1">El sistema está limpio y preparado para iniciar operaciones. Registra el primer lago o estanque de la finca haciendo clic a continuación.</p>
                                    <button type="button"
                                            @click="showModalNuevoLago = true"
                                            onclick="document.getElementById('modal-nuevo-lago').classList.remove('hidden')"
                                            class="mt-4 inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                                        <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        + Registrar Nuevo Lago
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: REGISTRAR NUEVO LAGO / SIEMBRA -->
    <div id="modal-nuevo-lago"
         x-show="showModalNuevoLago"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden"
         :class="{ 'hidden': !showModalNuevoLago }"
         x-cloak>

        <div @click.away="showModalNuevoLago = false; document.getElementById('modal-nuevo-lago')?.classList.add('hidden')"
             class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-xl overflow-hidden animate-scale-up">

            <!-- Encabezado del Modal -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-white text-sm">
                        <i class="fa-solid fa-water"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Registrar Nuevo Lago / Siembra</h3>
                        <p class="text-[11px] text-slate-500">Apertura de estanque y siembra inicial de alevinos</p>
                    </div>
                </div>
                <button type="button"
                        @click="showModalNuevoLago = false; document.getElementById('modal-nuevo-lago')?.classList.add('hidden')"
                        onclick="document.getElementById('modal-nuevo-lago')?.classList.add('hidden')"
                        class="text-slate-400 hover:text-slate-700">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <!-- Formulario -->
            <form method="POST" action="{{ route('admin.lagos.store') }}" class="p-6 space-y-4">
                @csrf

                <!-- Fila 1: Nombre y Código -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Código / Nombre del Estanque <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               required
                               placeholder="ej. Estanque 1 o Lago Norte"
                               class="w-full rounded-lg border border-slate-300 py-2 px-3 text-xs text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Identificador / Código Breve
                        </label>
                        <input type="text"
                               name="code"
                               placeholder="ej. EST-01"
                               class="w-full rounded-lg border border-slate-300 py-2 px-3 text-xs text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 font-mono">
                    </div>
                </div>

                <!-- Fila 2: Tipo de Estanque y Fecha de Siembra -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Tipo de Estanque <span class="text-rose-500">*</span>
                        </label>
                        <select name="tipo_estanque" required class="w-full rounded-lg border border-slate-300 py-2 px-3 text-xs text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                            <option value="tierra">Tierra</option>
                            <option value="geomembrana">Geomembrana</option>
                            <option value="concreto">Concreto</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Fecha de Siembra <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               name="stocked_at"
                               x-model="fechaSiembra"
                               required
                               class="w-full rounded-lg border border-slate-300 py-2 px-3 text-xs text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">
                            Días de Cultivo (Tiempo real)
                        </label>
                        <div class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-xs font-bold text-slate-800 flex items-center justify-between">
                            <span x-text="diasCultivo + ' días'">0 días</span>
                            <span class="text-[10px] text-slate-400 font-normal">now()->diffInDays()</span>
                        </div>
                    </div>
                </div>

                <!-- Sección Dinámica de Siembra de Especies (Monocultivo / Policultivo) -->
                <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <h4 class="text-xs font-black text-slate-900 flex items-center gap-1.5 uppercase tracking-wide">
                                <i class="fa-solid fa-fish text-cyan-600"></i>
                                <span>Siembra de Especies Acuícolas</span>
                            </h4>
                            <p class="text-[11px] text-slate-500">Agrega más especies para registrar un lago en <strong>Policultivo</strong>.</p>
                        </div>
                        <button type="button"
                                @click="agregarEspecie()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-cyan-800 bg-white hover:bg-cyan-50 border border-cyan-300 transition shadow-xs">
                            <i class="fa-solid fa-plus text-[10px]"></i>
                            <span>+ Agregar otra especie (Policultivo)</span>
                        </button>
                    </div>

                    <!-- Filas Dinámicas -->
                    <div class="space-y-2.5">
                        <template x-for="(fila, index) in filasEspecies" :key="index">
                            <div class="p-3 bg-white rounded-lg border border-slate-200 shadow-xs relative">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[11px] font-bold text-slate-600 flex items-center gap-1.5">
                                        <span class="w-4 h-4 rounded-full bg-cyan-100 text-cyan-800 font-black flex items-center justify-center text-[10px]" x-text="index + 1">1</span>
                                        <span x-text="index === 0 ? 'Especie Principal' : 'Especie Acompañante'"></span>
                                    </span>
                                    <button type="button"
                                            x-show="filasEspecies.length > 1"
                                            @click="eliminarEspecie(index)"
                                            class="text-rose-500 hover:text-rose-700 text-xs font-semibold inline-flex items-center gap-1">
                                        <i class="fa-solid fa-trash-can text-[11px]"></i>
                                        <span>Quitar</span>
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-end">
                                    <!-- Selector de Especie -->
                                    <div class="sm:col-span-6">
                                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                            Especie <span class="text-rose-500">*</span>
                                        </label>
                                        <select :name="'especies[' + index + '][especie_id]'"
                                                x-model="fila.especie_id"
                                                required
                                                class="w-full rounded-lg border border-slate-300 py-1.5 px-2.5 text-xs text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                                            @foreach($especies as $especie)
                                                <option value="{{ $especie->id }}">{{ $especie->nombre_comun }} ({{ $especie->nombre_cientifico }})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Cantidad de Alevinos -->
                                    <div class="sm:col-span-3">
                                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                            Alevinos <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="number"
                                               :name="'especies[' + index + '][cantidad]'"
                                               x-model.number="fila.cantidad"
                                               min="1"
                                               required
                                               placeholder="ej. 8000"
                                               class="w-full rounded-lg border border-slate-300 py-1.5 px-2.5 text-xs text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                                    </div>

                                    <!-- Peso Promedio Inicial -->
                                    <div class="sm:col-span-3">
                                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                            Peso Inicial (g) <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="number"
                                               step="0.01"
                                               min="0.01"
                                               :name="'especies[' + index + '][peso]'"
                                               x-model.number="fila.peso"
                                               required
                                               placeholder="ej. 1.50"
                                               class="w-full rounded-lg border border-slate-300 py-1.5 px-2.5 text-xs text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Indicador visual de Policultivo -->
                    <div x-show="filasEspecies.length > 1" class="p-2 bg-blue-50 border border-blue-200 rounded-lg flex items-center justify-between text-xs text-blue-900">
                        <span class="font-bold flex items-center gap-1.5">
                            <i class="fa-solid fa-layer-group text-blue-600"></i>
                            <span>Lago configurado en Modo Policultivo:</span>
                        </span>
                        <span class="font-black" x-text="filasEspecies.length + ' especies combinadas'"></span>
                    </div>

                    <!-- Inputs de compatibilidad hacia atrás -->
                    <input type="hidden" name="especie_id" :value="filasEspecies[0]?.especie_id">
                    <input type="hidden" name="fingerlings_stocked" :value="totalAlevinos">
                    <input type="hidden" name="average_weight" :value="filasEspecies[0]?.peso">
                </div>

                <!-- Fila: Origen de Alevinos y Lote -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Alevinera / Origen
                        </label>
                        <input type="text"
                               name="alevinera_origen"
                               placeholder="ej. Piscícola San Jerónimo"
                               class="w-full rounded-lg border border-slate-300 py-2 px-3 text-xs text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Número de Lote (Opcional)
                        </label>
                        <input type="text"
                               name="numero_lote"
                               placeholder="Auto: LOTE-{{ date('Y') }}-EST"
                               class="w-full rounded-lg border border-slate-300 py-2 px-3 text-xs text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 font-mono">
                    </div>
                </div>

                <!-- Totalizadores en Tiempo Real -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="p-3 bg-slate-100/90 rounded-xl border border-slate-200 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-slate-800 block">Total Alevinos Sembrados:</span>
                            <span class="text-slate-500 text-[10px]">Suma consolidada de todas las especies</span>
                        </div>
                        <div class="text-right">
                            <span class="text-base font-black text-slate-900" x-text="totalAlevinos.toLocaleString('es-CO')">0</span>
                        </div>
                    </div>

                    <div class="p-3 bg-cyan-50 rounded-xl border border-cyan-200 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-cyan-950 block">Biomasa Total Estimada:</span>
                            <span class="text-cyan-600 text-[10px]">Σ (Peces × Peso / 1.000)</span>
                        </div>
                        <div class="text-right">
                            <span class="text-base font-black text-cyan-800" x-text="biomasaTotalCalc + ' kg'">0.00 kg</span>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button"
                            @click="showModalNuevoLago = false; document.getElementById('modal-nuevo-lago')?.classList.add('hidden')"
                            onclick="document.getElementById('modal-nuevo-lago')?.classList.add('hidden')"
                            class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-xs font-bold text-white transition shadow-sm">
                        <i class="fa-solid fa-check mr-1.5"></i>Guardar Lago y Siembra
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
