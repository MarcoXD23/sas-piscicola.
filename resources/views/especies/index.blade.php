@extends('layouts.app')

@section('title', 'Enciclopedia Piscícola de Colombia')
@section('page_title', 'Guía Técnica de Especies Piscícolas')

@section('content')
<div class="space-y-6"
     x-data="{
         search: '{{ addslashes($currentSearch) }}',
         climaFilter: '{{ addslashes($currentClima) }}',
         activeModal: null,
         modalTab: 1,

         matchesItem(nombre, cientifico, familia, clima) {
             const s = this.search.toLowerCase().trim();
             const matchesSearch = !s ||
                 nombre.includes(s) ||
                 cientifico.includes(s) ||
                 familia.includes(s);

             const c = this.climaFilter.toLowerCase().trim();
             const matchesClima = !c || clima.startsWith(c);

             return matchesSearch && matchesClima;
         },

         openDetail(item) {
             this.activeModal = item;
             this.modalTab = 1;
         },

         closeDetail() {
             this.activeModal = null;
         }
     }"
     @keydown.escape.window="closeDetail()">

    <!-- Cabecera de Navegación con Botón Volver -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <x-back-button />
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 bg-white/80 dark:bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm backdrop-blur">
            <i class="fa-solid fa-book-bookmark text-cyan-600 dark:text-cyan-400"></i>
            <span>Compendio Acuícola Nacional de Colombia</span>
        </div>
    </div>

    <!-- Banner Hero con Imagen Real de Estanque y Gradiente Premium -->
    <div class="relative overflow-hidden rounded-3xl bg-slate-900 border border-slate-800 shadow-xl text-white">
        <!-- Imagen de Fondo con Overlay -->
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('images/peces/estanque_cultivo.jpg') }}"
                 alt="Estanques Piscícolas"
                 class="h-full w-full object-cover object-center opacity-30 mix-blend-luminosity filter blur-[1px]">
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-900/90 to-cyan-950/70"></div>
        </div>

        <div class="relative z-10 p-6 sm:p-8 lg:p-10 max-w-3xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-500/20 border border-cyan-400/30 text-cyan-300 text-xs font-bold uppercase tracking-wider mb-4">
                <i class="fa-solid fa-fish-fins"></i>
                <span>Acuicultura Tropical & Andina</span>
            </div>
            <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-white leading-tight">
                Enciclopedia & Guía Técnica de Cultivo de Peces
            </h1>
            <p class="mt-2.5 text-sm sm:text-base text-slate-300 leading-relaxed">
                Parámetros físico-químicos, requerimientos nutricionales por etapa de vida, densidades de siembra y protocolos de policultivo de las especies comerciales de Colombia.
            </p>

            <!-- Resumen Rápido en Números -->
            <div class="mt-6 flex flex-wrap gap-4 pt-4 border-t border-slate-800/80 text-xs">
                <div class="flex items-center gap-2 bg-slate-950/60 px-3 py-1.5 rounded-xl border border-slate-800">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-slate-300"><strong>6 Especies</strong> Principales</span>
                </div>
                <div class="flex items-center gap-2 bg-slate-950/60 px-3 py-1.5 rounded-xl border border-slate-800">
                    <i class="fa-solid fa-temperature-arrow-up text-amber-400"></i>
                    <span class="text-slate-300">Clima Cálido & Frío</span>
                </div>
                <div class="flex items-center gap-2 bg-slate-950/60 px-3 py-1.5 rounded-xl border border-slate-800">
                    <i class="fa-solid fa-dna text-cyan-400"></i>
                    <span class="text-slate-300">Monocultivo & Policultivo</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros y Búsqueda en Vivo -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <!-- Input de Búsqueda -->
        <div class="relative w-full sm:w-80">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </div>
            <input type="text"
                   x-model="search"
                   placeholder="Buscar especie (ej. Mojarra, Trucha)..."
                   class="w-full min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 py-2 pl-9 pr-8 text-xs font-medium text-slate-900 dark:text-white placeholder-slate-400 focus:border-cyan-500 focus:bg-white dark:focus:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 transition">
            <button x-show="search.length > 0"
                    @click="search = ''"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600"
                    style="display: none;">
                <i class="fa-solid fa-circle-xmark text-xs"></i>
            </button>
        </div>

        <!-- Filtros por Clima -->
        <div class="flex items-center gap-1.5 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
            <button type="button"
                    @click="climaFilter = ''"
                    :class="climaFilter === '' ? 'bg-cyan-600 text-white font-bold shadow-md shadow-cyan-600/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'"
                    class="min-h-[40px] px-3.5 py-1.5 rounded-xl text-xs font-medium transition cursor-pointer shrink-0">
                Todas las Especies
            </button>

            <button type="button"
                    @click="climaFilter = 'cálido'"
                    :class="climaFilter === 'cálido' ? 'bg-amber-500 text-white font-bold shadow-md shadow-amber-500/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'"
                    class="min-h-[40px] px-3.5 py-1.5 rounded-xl text-xs font-medium transition cursor-pointer flex items-center gap-1.5 shrink-0">
                <i class="fa-solid fa-sun text-xs text-amber-400"></i>
                <span>Clima Cálido</span>
            </button>

            <button type="button"
                    @click="climaFilter = 'frío'"
                    :class="climaFilter === 'frío' ? 'bg-sky-500 text-white font-bold shadow-md shadow-sky-500/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'"
                    class="min-h-[40px] px-3.5 py-1.5 rounded-xl text-xs font-medium transition cursor-pointer flex items-center gap-1.5 shrink-0">
                <i class="fa-solid fa-snowflake text-xs text-sky-400"></i>
                <span>Clima Frío (Andino)</span>
            </button>
        </div>
    </div>

    <!-- Grid Responsivo de Tarjetas de Especies (SSR + Reactividad Alpine) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($especies as $item)
            <div x-show="matchesItem('{{ mb_strtolower($item->nombre_comun) }}', '{{ mb_strtolower($item->nombre_cientifico) }}', '{{ mb_strtolower($item->familia ?? '') }}', '{{ mb_strtolower($item->clima) }}')"
                 class="group bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-xl hover:border-cyan-500/50 transition-all duration-300 overflow-hidden flex flex-col">
                <!-- Imagen Real con Badge de Clima -->
                <div class="relative h-48 w-full overflow-hidden bg-slate-950 rounded-t-xl">
                    <img src="{{ asset($item->foto_url) }}"
                         alt="{{ $item->nombre_comun }}"
                         class="w-full h-48 object-cover rounded-t-xl group-hover:scale-105 transition-transform duration-500 ease-out"
                         loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent"></div>

                    <!-- Badge de Clima -->
                    <div class="absolute top-3.5 left-3.5">
                        @if($item->clima === 'cálido')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500/90 text-white shadow-md backdrop-blur-md">
                                <i class="fa-solid fa-sun text-[10px]"></i> Clima Cálido
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-sky-500/90 text-white shadow-md backdrop-blur-md">
                                <i class="fa-solid fa-snowflake text-[10px]"></i> Clima Frío
                            </span>
                        @endif
                    </div>

                    <!-- Familia Taxonómica -->
                    <div class="absolute top-3.5 right-3.5">
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-slate-900/80 text-cyan-300 border border-slate-700 backdrop-blur-md">
                            {{ $item->familia }}
                        </span>
                    </div>

                    <!-- Nombre en la Imagen -->
                    <div class="absolute bottom-3 left-4 right-4">
                        <h3 class="text-lg font-bold text-white leading-tight drop-shadow-md">
                            {{ $item->nombre_comun }}
                        </h3>
                        <p class="text-xs italic text-cyan-300 font-serif">
                            {{ $item->nombre_cientifico }}
                        </p>
                    </div>
                </div>

                <!-- Cuerpo de Parámetros Rápidos -->
                <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                    <!-- Badges de Parámetros Clave -->
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <!-- Temperatura -->
                        <div class="p-2.5 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-900/40">
                            <span class="text-[10px] uppercase font-bold text-amber-700 dark:text-amber-400 block">
                                <i class="fa-solid fa-temperature-half mr-1"></i> Temp. Óptima
                            </span>
                            <span class="font-extrabold text-slate-800 dark:text-amber-200 text-xs">
                                {{ $item->rango_temperatura }}
                            </span>
                        </div>

                        <!-- Oxígeno -->
                        <div class="p-2.5 rounded-2xl bg-cyan-50/70 dark:bg-cyan-950/30 border border-cyan-200/60 dark:border-cyan-900/40">
                            <span class="text-[10px] uppercase font-bold text-cyan-700 dark:text-cyan-400 block">
                                <i class="fa-solid fa-wind mr-1"></i> Oxígeno (O₂)
                            </span>
                            <span class="font-extrabold text-slate-800 dark:text-cyan-200 text-xs">
                                &gt; {{ $item->oxigeno_min_mg_l }} mg/L
                            </span>
                        </div>

                        <!-- Peso Comercial -->
                        <div class="p-2.5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200/60 dark:border-emerald-900/40">
                            <span class="text-[10px] uppercase font-bold text-emerald-700 dark:text-emerald-400 block">
                                <i class="fa-solid fa-scale-balanced mr-1"></i> Cosecha
                            </span>
                            <span class="font-extrabold text-slate-800 dark:text-emerald-200 text-xs truncate block">
                                {{ $item->peso_comercial_gramos }}
                            </span>
                        </div>

                        <!-- Ciclo Promedio -->
                        <div class="p-2.5 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-200/60 dark:border-indigo-900/40">
                            <span class="text-[10px] uppercase font-bold text-indigo-700 dark:text-indigo-400 block">
                                <i class="fa-regular fa-clock mr-1"></i> Duración Ciclo
                            </span>
                            <span class="font-extrabold text-slate-800 dark:text-indigo-200 text-xs">
                                {{ $item->meses_cosecha_promedio }}
                            </span>
                        </div>
                    </div>

                    <!-- Rol Ecológico / Policultivo Resumen -->
                    <div class="text-xs text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800/60 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 line-clamp-2">
                        <strong class="text-slate-800 dark:text-white font-semibold">Rol:</strong>
                        <span>{{ $item->rol_policultivo }}</span>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="pt-2 flex items-center gap-2">
                        <button type="button"
                                @click="openDetail({{ Js::from($item) }})"
                                class="flex-1 min-h-[44px] flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-cyan-600 to-teal-500 hover:from-cyan-500 hover:to-teal-400 text-white font-bold text-xs shadow-md shadow-cyan-600/20 active:scale-95 transition cursor-pointer">
                            <i class="fa-solid fa-clipboard-list text-xs"></i>
                            <span>Ver Ficha Técnica</span>
                        </button>
                        <a href="{{ route('guia-peces.show', $item->id) }}"
                           class="min-h-[44px] px-3.5 flex items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold transition"
                           title="Abrir página completa">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Empty State si la búsqueda no arroja resultados -->
    <div x-show="filteredEspecies.length === 0"
         class="text-center py-12 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm"
         style="display: none;">
        <i class="fa-solid fa-fish-fins text-4xl text-slate-400 mb-3"></i>
        <h4 class="text-base font-bold text-slate-800 dark:text-white">No se encontraron especies</h4>
        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
            No hay registros que coincidan con "<span x-text="search" class="font-semibold text-cyan-600"></span>". Prueba con otro término o limpia los filtros.
        </p>
        <button type="button"
                @click="search = ''; climaFilter = ''"
                class="mt-4 px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold rounded-xl transition">
            Restablecer Filtros
        </button>
    </div>

    <!-- ==================== MODAL DE FICHA TÉCNICA DETALLADA (4 PESTAÑAS) ==================== -->
    <div x-show="activeModal !== null"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto"
         style="display: none;"
         x-cloak>

        <div @click.away="closeDetail()"
             x-show="activeModal !== null"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="relative w-full max-w-3xl bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden my-6">

            <!-- Cabecera del Modal con Foto y Título -->
            <div class="relative h-44 sm:h-52 bg-slate-950 overflow-hidden">
                <img :src="activeModal ? '/' + activeModal.foto_url : ''"
                     :alt="activeModal ? activeModal.nombre_comun : ''"
                     class="h-full w-full object-cover object-center filter brightness-90">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/50 to-transparent"></div>

                <!-- Botón Cerrar Modal -->
                <button type="button"
                        @click="closeDetail()"
                        class="absolute top-4 right-4 h-9 w-9 rounded-full bg-slate-900/80 hover:bg-rose-600 text-white flex items-center justify-center transition border border-slate-700 shadow-lg cursor-pointer"
                        title="Cerrar Ficha">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>

                <!-- Identificador de la Especie -->
                <div class="absolute bottom-4 left-5 right-16">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-500 text-white uppercase tracking-wider"
                              x-text="activeModal ? activeModal.familia : ''"></span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800/80 text-amber-300 border border-slate-700"
                              x-text="activeModal ? 'Clima ' + activeModal.clima : ''"></span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-white leading-tight"
                        x-text="activeModal ? activeModal.nombre_comun : ''"></h2>
                    <p class="text-xs italic text-cyan-300 font-serif"
                       x-text="activeModal ? activeModal.nombre_cientifico : ''"></p>
                </div>
            </div>

            <!-- Navegación de 4 Pestañas -->
            <div class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-4 flex items-center gap-1 sm:gap-2 overflow-x-auto">
                <button type="button"
                        @click="modalTab = 1"
                        :class="modalTab === 1 ? 'border-cyan-600 text-cyan-600 dark:text-cyan-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'"
                        class="min-h-[44px] py-2.5 px-3 border-b-2 text-xs flex items-center gap-2 transition shrink-0 cursor-pointer">
                    <i class="fa-solid fa-droplet text-xs"></i>
                    <span>1. Calidad del Agua</span>
                </button>

                <button type="button"
                        @click="modalTab = 2"
                        :class="modalTab === 2 ? 'border-cyan-600 text-cyan-600 dark:text-cyan-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'"
                        class="min-h-[44px] py-2.5 px-3 border-b-2 text-xs flex items-center gap-2 transition shrink-0 cursor-pointer">
                    <i class="fa-solid fa-wheat-awn text-xs"></i>
                    <span>2. Nutrición & Proteína</span>
                </button>

                <button type="button"
                        @click="modalTab = 3"
                        :class="modalTab === 3 ? 'border-cyan-600 text-cyan-600 dark:text-cyan-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'"
                        class="min-h-[44px] py-2.5 px-3 border-b-2 text-xs flex items-center gap-2 transition shrink-0 cursor-pointer">
                    <i class="fa-solid fa-book-open text-xs"></i>
                    <span>3. Guía de Cultivo</span>
                </button>

                <button type="button"
                        @click="modalTab = 4"
                        :class="modalTab === 4 ? 'border-cyan-600 text-cyan-600 dark:text-cyan-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'"
                        class="min-h-[44px] py-2.5 px-3 border-b-2 text-xs flex items-center gap-2 transition shrink-0 cursor-pointer">
                    <i class="fa-solid fa-arrows-split-up-and-left text-xs"></i>
                    <span>4. Policultivo</span>
                </button>
            </div>

            <!-- Contenido de las Pestañas -->
            <div class="p-5 sm:p-6 max-h-[55vh] overflow-y-auto space-y-4 text-xs">
                <!-- PESTAÑA 1: Calidad del Agua -->
                <div x-show="modalTab === 1" class="space-y-4">
                    <h4 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                        <i class="fa-solid fa-flask-vial text-cyan-500"></i> Parámetros Físico-Químicos Críticos
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div class="p-3.5 rounded-2xl bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40">
                            <span class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase">Temperatura Óptima</span>
                            <p class="text-base font-black text-slate-900 dark:text-amber-200 mt-1"
                               x-text="activeModal ? activeModal.temperatura_min + ' °C a ' + activeModal.temperatura_max + ' °C' : ''"></p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Controla la tasa metabólica y el apetito del pez.</p>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-cyan-50/70 dark:bg-cyan-950/20 border border-cyan-200 dark:border-cyan-900/40">
                            <span class="text-[10px] font-bold text-cyan-700 dark:text-cyan-400 uppercase">Oxígeno Disuelto (O₂)</span>
                            <p class="text-base font-black text-slate-900 dark:text-cyan-200 mt-1"
                               x-text="activeModal ? 'Mínimo ' + activeModal.oxigeno_min_mg_l + ' mg/L' : ''"></p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Encendido preventivo de aireadores si desciende del mínimo.</p>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/40">
                            <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase">Rango de pH Aceptable</span>
                            <p class="text-base font-black text-slate-900 dark:text-emerald-200 mt-1"
                               x-text="activeModal ? activeModal.ph_min + ' - ' + activeModal.ph_max : ''"></p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Valores fuera de rango generan estrés branquial.</p>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-900/40">
                            <span class="text-[10px] font-bold text-indigo-700 dark:text-indigo-400 uppercase">Densidad en Estanque de Tierra</span>
                            <p class="text-sm font-black text-slate-900 dark:text-indigo-200 mt-1"
                               x-text="activeModal ? activeModal.densidad_tierra_m2 : ''"></p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Con recambio hídrico y control de materia orgánica.</p>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-100 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase">Densidad en Geomembrana / Tanques Intensivos:</span>
                        <p class="font-bold text-slate-800 dark:text-slate-100 text-xs mt-0.5"
                           x-text="activeModal ? activeModal.densidad_geomembrana_m3 : ''"></p>
                    </div>
                </div>

                <!-- PESTAÑA 2: Nutrición & Proteína -->
                <div x-show="modalTab === 2" class="space-y-4" style="display: none;">
                    <h4 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                        <i class="fa-solid fa-wheat-awn text-amber-500"></i> Plan Nutricional y Proteína por Fase de Vida
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-center">
                            <span class="text-[10px] font-bold uppercase text-cyan-600 dark:text-cyan-400">1. Iniciación / Alevinaje</span>
                            <p class="text-xl font-black text-slate-900 dark:text-white mt-2"
                               x-text="activeModal ? activeModal.proteina_iniciacion : ''"></p>
                            <span class="text-[10px] text-slate-400 block mt-1">Harina o micropellet</span>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-center">
                            <span class="text-[10px] font-bold uppercase text-emerald-600 dark:text-emerald-400">2. Levante / Juveniles</span>
                            <p class="text-xl font-black text-slate-900 dark:text-white mt-2"
                               x-text="activeModal ? activeModal.proteina_levante : ''"></p>
                            <span class="text-[10px] text-slate-400 block mt-1">Pellet extruido 1.5 - 2.5 mm</span>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-center">
                            <span class="text-[10px] font-bold uppercase text-amber-600 dark:text-amber-400">3. Engorde Comercial</span>
                            <p class="text-xl font-black text-slate-900 dark:text-white mt-2"
                               x-text="activeModal ? activeModal.proteina_engorde : ''"></p>
                            <span class="text-[10px] text-slate-400 block mt-1">Pellet flotante 4 - 6 mm</span>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/30 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase">Talla y Peso Objetivo de Venta</span>
                            <p class="text-sm font-extrabold text-slate-900 dark:text-emerald-200"
                               x-text="activeModal ? activeModal.peso_comercial_gramos + ' en ' + activeModal.meses_cosecha_promedio : ''"></p>
                        </div>
                        <i class="fa-solid fa-trophy text-2xl text-emerald-500/50"></i>
                    </div>
                </div>

                <!-- PESTAÑA 3: Guía de Cultivo Paso a Paso -->
                <div x-show="modalTab === 3" class="space-y-3" style="display: none;">
                    <h4 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-cyan-500"></i> Protocolo Operativo en Granja
                    </h4>
                    <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-200 dark:border-slate-700 leading-relaxed whitespace-pre-line text-slate-700 dark:text-slate-300 font-sans"
                         x-text="activeModal ? activeModal.guia_manejo_cultivo : ''"></div>
                </div>

                <!-- PESTAÑA 4: Policultivo & Sinergias -->
                <div x-show="modalTab === 4" class="space-y-3" style="display: none;">
                    <h4 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                        <i class="fa-solid fa-arrows-split-up-and-left text-teal-500"></i> Compatibilidad y Sinergias Ecológicas
                    </h4>
                    <div class="p-4 rounded-2xl bg-cyan-50/60 dark:bg-cyan-950/20 border border-cyan-200 dark:border-cyan-900/30">
                        <span class="text-[10px] font-bold uppercase text-cyan-800 dark:text-cyan-400">Función en el Estanque Mixto</span>
                        <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 mt-1"
                           x-text="activeModal ? activeModal.rol_policultivo : ''"></p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 space-y-2">
                        <h5 class="font-bold text-slate-800 dark:text-white">Reglas del Policultivo en SAS Piscícola:</h5>
                        <ul class="list-disc pl-5 space-y-1 text-slate-600 dark:text-slate-300">
                            <li><strong>Mojarra + Bocachico:</strong> La combinación más eficiente; la mojarra aprovecha la columna de agua y el bocachico sanea el lodo del fondo.</li>
                            <li><strong>Cachama + Mojarra:</strong> Mantener tallas homogéneas al sembrar para evitar competencia alimentaria voraz.</li>
                            <li><strong>Bagre Rayado:</strong> Utilizar en baja proporción como regulador de superpoblación de alevinos o en monocultivo.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Footer del Modal -->
            <div class="p-4 bg-slate-50 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <span class="text-[11px] text-slate-400">SAS Piscícola • Manual Técnico de Acuicultura</span>
                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="closeDetail()"
                            class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-xs transition cursor-pointer">
                        Cerrar Ficha
                    </button>
                    <a :href="activeModal ? '/guia-peces/' + activeModal.id : '#'"
                       class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs transition flex items-center gap-1.5 shadow-md shadow-cyan-600/30">
                        <span>Página Completa</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
