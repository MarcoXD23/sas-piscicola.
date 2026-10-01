@extends('layouts.app')

@section('title', 'Editar Módulos de Finca: ' . $finca->nombre)
@section('page_title', 'SuperAdmin - Configurar Plan y Módulos')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <!-- Encabezado con Botón Atrás -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <x-back-button />
            <div class="mt-3">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                    <span class="p-2.5 rounded-2xl bg-purple-500/10 text-purple-600 border border-purple-500/20">
                        <i class="fa-solid fa-toggle-on text-xl"></i>
                    </span>
                    <span>Activar / Desactivar Módulos</span>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Control de funciones y feature flags para <span class="font-bold text-slate-700">{{ $finca->nombre }}</span> (ID #{{ $finca->id }}).
                </p>
            </div>
        </div>

        <a href="{{ route('superadmin.fincas.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200 transition">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver a Lista de Fincas</span>
        </a>
    </div>

    <!-- Formulario de Módulos y Datos Generales -->
    <form action="{{ route('superadmin.fincas.update', $finca->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-xs text-rose-800">
                <div class="font-bold flex items-center gap-2 mb-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-sm"></i>
                    <span>Corrige los siguientes errores:</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-rose-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Card: Información General de la Finca -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-200/80 space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="h-9 w-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm font-bold shadow-xs">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Información del Cliente</h2>
                    <p class="text-[11px] text-slate-500">Datos de identificación de la empresa acuícola</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <div>
                    <label for="nombre" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Nombre de la Finca
                    </label>
                    <input type="text"
                           name="nombre"
                           id="nombre"
                           required
                           value="{{ old('nombre', $finca->nombre) }}"
                           class="block w-full rounded-2xl border-slate-300 text-xs font-semibold text-slate-800 focus:border-purple-500 focus:ring-purple-500 py-2.5">
                </div>
                <div>
                    <label for="codigo" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Código Único
                    </label>
                    <input type="text"
                           name="codigo"
                           id="codigo"
                           value="{{ old('codigo', $finca->codigo ?? 'FINCA-0'.$finca->id) }}"
                           class="block w-full rounded-2xl border-slate-300 text-xs font-mono font-semibold text-slate-800 focus:border-purple-500 focus:ring-purple-500 py-2.5">
                </div>
                <div>
                    <label for="ubicacion" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Ubicación / Municipio
                    </label>
                    <input type="text"
                           name="ubicacion"
                           id="ubicacion"
                           value="{{ old('ubicacion', $finca->ubicacion ?? 'Tolima, Colombia') }}"
                           class="block w-full rounded-2xl border-slate-300 text-xs font-semibold text-slate-800 focus:border-purple-500 focus:ring-purple-500 py-2.5">
                </div>
            </div>
        </div>

        <!-- Card: Interruptores de Módulos (Feature Flags) -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-200/80 space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold shadow-xs">
                        <i class="fa-solid fa-cubes"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Módulos del Sistema (Feature Flags)</h2>
                        <p class="text-[11px] text-slate-500">Activa o desactiva módulos de acuerdo a la suscripción contratada</p>
                    </div>
                </div>
                <span class="text-xs font-semibold text-purple-700 bg-purple-50 px-2.5 py-1 rounded-full border border-purple-200">
                    Control Dinámico
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($modulosDisponibles as $key => $mod)
                    @php
                        $activo = $finca->tieneModulo($key);
                    @endphp
                    <div x-data="{ enabled: {{ $activo ? 'true' : 'false' }} }"
                         class="p-4 rounded-2xl border transition-all duration-200"
                         :class="enabled ? 'border-purple-200 bg-purple-50/20' : 'border-slate-200 bg-slate-50/50 opacity-70'">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <div class="h-10 w-10 rounded-xl bg-white flex items-center justify-center text-base shadow-xs shrink-0 border border-slate-100 {{ $mod['color'] }}">
                                    <i class="{{ $mod['icono'] }}"></i>
                                </div>
                                <div>
                                    <h3 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                                        <span>{{ $mod['nombre'] }}</span>
                                        <span x-show="enabled" class="px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                            ACTIVO
                                        </span>
                                        <span x-show="!enabled" class="px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-slate-200 text-slate-600">
                                            INACTIVO
                                        </span>
                                    </h3>
                                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                        {{ $mod['descripcion'] }}
                                    </p>
                                </div>
                            </div>

                            <!-- Modern Switch Toggle Button -->
                            <div class="shrink-0 pt-1">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           name="modulos[{{ $key }}]"
                                           value="1"
                                           x-model="enabled"
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
            <a href="{{ route('superadmin.fincas.index') }}"
               class="px-5 py-2.5 rounded-2xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                Cancelar
            </a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-bold shadow-lg shadow-purple-600/30 transition flex items-center gap-2 active:scale-95">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Guardar Cambios de Suscripción</span>
            </button>
        </div>

    </form>

</div>
@endsection
