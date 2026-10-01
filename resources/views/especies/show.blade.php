@extends('layouts.app')

@section('title', 'Ficha Técnica - ' . $especie->nombre_comun)
@section('page_title', 'Ficha de Cultivo: ' . $especie->nombre_comun)

@section('content')
<div class="space-y-6">

    <!-- Botón Volver a la Guía -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <x-back-button :href="route('guia-peces.index')" label="Volver a la Guía de Peces" />

        <div class="flex items-center gap-2">
            <button onclick="window.print()"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white hover:bg-slate-900 hover:text-white border border-slate-200 shadow-sm transition dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                <i class="fa-solid fa-print"></i>
                <span>Imprimir Ficha</span>
            </button>
        </div>
    </div>

    <!-- Header Principal con Imagen Real de Alta Calidad -->
    <div class="relative overflow-hidden rounded-3xl bg-slate-950 border border-slate-800 shadow-2xl text-white">
        <div class="grid grid-cols-1 lg:grid-cols-12 items-stretch min-h-[360px]">
            <!-- Columna de Información -->
            <div class="lg:col-span-7 p-6 sm:p-8 lg:p-10 flex flex-col justify-between z-10">
                <div>
                    <div class="flex items-center gap-2 flex-wrap mb-3">
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-400/30">
                            Familia: {{ $especie->familia ?? 'Piscícola' }}
                        </span>
                        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $especie->clima === 'cálido' ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-sky-500/20 text-sky-300 border-sky-500/30' }} border">
                            Clima {{ ucfirst($especie->clima) }}
                        </span>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            {{ $especie->meses_cosecha_promedio }}
                        </span>
                    </div>

                    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
                        {{ $especie->nombre_comun }}
                    </h1>
                    <p class="text-base italic text-cyan-300 font-serif mt-1">
                        {{ $especie->nombre_cientifico }}
                    </p>

                    <p class="mt-4 text-sm text-slate-300 leading-relaxed max-w-xl">
                        {{ $especie->rol_policultivo }}
                    </p>
                </div>

                <!-- Métricas Clave de Cabecera -->
                <div class="mt-6 pt-5 border-t border-slate-800/80 grid grid-cols-3 gap-3 text-center">
                    <div class="p-3 rounded-2xl bg-slate-900/80 border border-slate-800">
                        <span class="text-[10px] uppercase font-bold text-amber-400 block">Temperatura</span>
                        <span class="text-sm font-black text-white mt-0.5 block">{{ $especie->rango_temperatura }}</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-900/80 border border-slate-800">
                        <span class="text-[10px] uppercase font-bold text-cyan-400 block">Oxígeno Mín.</span>
                        <span class="text-sm font-black text-white mt-0.5 block">&gt; {{ $especie->oxigeno_min_mg_l }} mg/L</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-900/80 border border-slate-800">
                        <span class="text-[10px] uppercase font-bold text-emerald-400 block">Rango pH</span>
                        <span class="text-sm font-black text-white mt-0.5 block">{{ $especie->rango_ph }}</span>
                    </div>
                </div>
            </div>

            <!-- Columna de Foto Real -->
            <div class="lg:col-span-5 relative bg-slate-900 min-h-[250px] lg:min-h-full">
                <img src="{{ asset($especie->foto_url) }}"
                     alt="{{ $especie->nombre_comun }}"
                     class="h-full w-full object-cover object-center filter brightness-95">
                <div class="absolute inset-0 bg-gradient-to-t lg:bg-gradient-to-r from-slate-950 via-transparent to-transparent"></div>
            </div>
        </div>
    </div>

    <!-- 4 Secciones Técnicas Detalladas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- 1. Calidad del Agua y Parámetros Ideales -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="h-10 w-10 rounded-2xl bg-cyan-50 dark:bg-cyan-950/40 text-cyan-600 dark:text-cyan-400 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-droplet"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">1. Calidad del Agua y Densidad</h3>
                    <p class="text-xs text-slate-500">Condiciones ambientales para el máximo crecimiento</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Temperatura Óptima</span>
                    <p class="text-base font-black text-amber-600 dark:text-amber-300 mt-1">{{ $especie->rango_temperatura }}</p>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Oxígeno Crítico</span>
                    <p class="text-base font-black text-cyan-600 dark:text-cyan-300 mt-1">&gt; {{ $especie->oxigeno_min_mg_l }} mg/L</p>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Acidez / Alcalinidad (pH)</span>
                    <p class="text-base font-black text-emerald-600 dark:text-emerald-300 mt-1">{{ $especie->rango_ph }}</p>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Densidad Estanque Tierra</span>
                    <p class="text-xs font-black text-indigo-600 dark:text-indigo-300 mt-1">{{ $especie->densidad_tierra_m2 }}</p>
                </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-cyan-50/60 dark:bg-cyan-950/20 border border-cyan-200 dark:border-cyan-900/30 text-xs">
                <span class="text-[10px] font-bold uppercase text-cyan-800 dark:text-cyan-400 block">Densidad en Tanques / Geomembrana</span>
                <span class="font-bold text-slate-800 dark:text-slate-100">{{ $especie->densidad_geomembrana_m3 }}</span>
            </div>
        </div>

        <!-- 2. Nutrición y Requerimientos de Proteína -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="h-10 w-10 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-wheat-awn"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">2. Alimentación y Proteína</h3>
                    <p class="text-xs text-slate-500">Porcentaje de proteína bruta recomendada por etapa</p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-bold uppercase text-cyan-600 dark:text-cyan-400 block">Iniciación</span>
                    <p class="text-lg font-black text-slate-900 dark:text-white mt-1">{{ $especie->proteina_iniciacion }}</p>
                    <span class="text-[10px] text-slate-400">Alevinos</span>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-bold uppercase text-emerald-600 dark:text-emerald-400 block">Levante</span>
                    <p class="text-lg font-black text-slate-900 dark:text-white mt-1">{{ $especie->proteina_levante }}</p>
                    <span class="text-[10px] text-slate-400">Juveniles</span>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-bold uppercase text-amber-600 dark:text-amber-400 block">Engorde</span>
                    <p class="text-lg font-black text-slate-900 dark:text-white mt-1">{{ $especie->proteina_engorde }}</p>
                    <span class="text-[10px] text-slate-400">Comercial</span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/30 flex items-center justify-between text-xs">
                <div>
                    <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase">Cosecha y Peso Comercial</span>
                    <p class="text-sm font-extrabold text-slate-900 dark:text-emerald-200">{{ $especie->peso_comercial_gramos }} en {{ $especie->meses_cosecha_promedio }}</p>
                </div>
                <div class="h-10 w-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-300 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-scale-balanced"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- 3. Guía Paso a Paso de Cultivo & 4. Consejos de Policultivo -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Guía de Cultivo Paso a Paso (8 Cols) -->
        <div class="lg:col-span-8 bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="h-10 w-10 rounded-2xl bg-teal-50 dark:bg-teal-950/40 text-teal-600 dark:text-teal-400 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">3. Protocolo Completo de Cultivo y Manejo</h3>
                    <p class="text-xs text-slate-500">Preparación del estanque, aclimatación, sanidad y cosecha</p>
                </div>
            </div>

            <div class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800 text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line font-sans">
                {{ $especie->guia_manejo_cultivo }}
            </div>
        </div>

        <!-- Policultivo y Especies Relacionadas (4 Cols) -->
        <div class="lg:col-span-4 space-y-6">

            <!-- Card: Consejos de Policultivo -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-arrows-split-up-and-left text-cyan-600"></i>
                    4. Consejos para Policultivo
                </h4>
                <div class="text-xs text-slate-600 dark:text-slate-300 bg-cyan-50/50 dark:bg-cyan-950/20 p-3.5 rounded-2xl border border-cyan-100 dark:border-cyan-900/30">
                    <strong class="text-slate-800 dark:text-white block mb-1">Estrategia SAS Piscícola:</strong>
                    {{ $especie->rol_policultivo }}
                </div>
            </div>

            <!-- Especies Relacionadas -->
            @if($relacionadas->isNotEmpty())
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                    <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        Otras Especies de Clima {{ ucfirst($especie->clima) }}
                    </h4>
                    <div class="space-y-2.5">
                        @foreach($relacionadas as $rel)
                            <a href="{{ route('guia-peces.show', $rel->id) }}"
                               class="flex items-center gap-3 p-2.5 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800/70 border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition group">
                                <img src="{{ asset($rel->foto_url) }}"
                                     alt="{{ $rel->nombre_comun }}"
                                     class="h-12 w-12 rounded-xl object-cover shrink-0">
                                <div class="min-w-0 flex-1">
                                    <h5 class="text-xs font-bold text-slate-800 dark:text-white truncate group-hover:text-cyan-600 transition">
                                        {{ $rel->nombre_comun }}
                                    </h5>
                                    <p class="text-[11px] italic text-slate-400 truncate">{{ $rel->nombre_cientifico }}</p>
                                </div>
                                <i class="fa-solid fa-chevron-right text-xs text-slate-400 group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

    </div>

</div>
@endsection
