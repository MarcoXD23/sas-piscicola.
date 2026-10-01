@extends('layouts.app')

@section('title', 'Ajustes y Parámetros de Finca')
@section('page_title', 'Configuración de la Finca')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <!-- Navegación y Encabezado con Botón Atrás -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <x-back-button />
            <div class="mt-3">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                    <span class="p-2.5 rounded-2xl bg-cyan-500/10 text-cyan-600 border border-cyan-500/20">
                        <i class="fa-solid fa-sliders text-xl"></i>
                    </span>
                    <span>Parámetros Operativos & Precios</span>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Personaliza los precios de pescado, tara de canastilla y especies habilitadas para <span class="font-bold text-slate-700">{{ $finca->nombre }}</span> (Código: {{ $finca->codigo ?? 'FINCA-01' }}).
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-sm">
                <i class="fa-solid fa-layer-group text-cyan-400"></i>
                Multi-Inquilino Activo
            </span>
        </div>
    </div>

    <!-- Formulario de Configuración -->
    <form action="{{ route('admin.ajustes.update') }}" method="POST" class="space-y-6">
        @csrf

        @if ($errors->any())
            <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-xs text-rose-800">
                <div class="font-bold flex items-center gap-2 mb-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-sm"></i>
                    <span>Por favor corrige los siguientes errores:</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-rose-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Card 1: Tarifas y Precios de Pescado -->
            <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-200/80 space-y-6">
                <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                    <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-bold shadow-xs">
                        <i class="fa-solid fa-tag"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Tarifas de Pescado ($/kg)</h2>
                        <p class="text-xs text-slate-500">Valores para liquidación de caja en ventas y fiado de nómina</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Precio Visitante -->
                    <div>
                        <label for="pescado_visitante_kg" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Precio Venta a Visitantes / Particular (COP / kg)
                        </label>
                        <div class="relative rounded-2xl shadow-xs">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 font-bold text-sm">
                                $
                            </div>
                            <input type="number"
                                   name="pescado_visitante_kg"
                                   id="pescado_visitante_kg"
                                   step="50"
                                   min="1000"
                                   max="100000"
                                   required
                                   value="{{ old('pescado_visitante_kg', $precios['pescado_visitante_kg']) }}"
                                   class="block w-full rounded-2xl border-slate-300 pl-8 pr-12 text-sm font-bold text-slate-900 focus:border-cyan-500 focus:ring-cyan-500 py-2.5">
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-xs text-slate-400 font-medium">
                                COP/kg
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Precio cobrado en portería/caja rápida a clientes externos (Predeterminado: $9,000).</p>
                    </div>

                    <!-- Precio Empleado -->
                    <div>
                        <label for="pescado_empleado_kg" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Precio Pescado a Empleados (Fiado en Nómina) (COP / kg)
                        </label>
                        <div class="relative rounded-2xl shadow-xs">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 font-bold text-sm">
                                $
                            </div>
                            <input type="number"
                                   name="pescado_empleado_kg"
                                   id="pescado_empleado_kg"
                                   step="50"
                                   min="1000"
                                   max="100000"
                                   required
                                   value="{{ old('pescado_empleado_kg', $precios['pescado_empleado_kg']) }}"
                                   class="block w-full rounded-2xl border-slate-300 pl-8 pr-12 text-sm font-bold text-slate-900 focus:border-cyan-500 focus:ring-cyan-500 py-2.5">
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-xs text-slate-400 font-medium">
                                COP/kg
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Monto descontado en la liquidación de destajo del sábado o cierre mensual (Predeterminado: $7,000).</p>
                    </div>
                </div>
            </div>

            <!-- Card 2: Operaciones y Báscula -->
            <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-200/80 space-y-6">
                <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                    <div class="h-10 w-10 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-lg font-bold shadow-xs">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Operación & Báscula</h2>
                        <p class="text-xs text-slate-500">Parámetros de pesaje limpio y calendario laboral</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Peso Tara Canastilla -->
                    <div>
                        <label for="peso_tara_canastilla_kg" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Peso de Tara de Canastilla Plástica (kg)
                        </label>
                        <div class="relative rounded-2xl shadow-xs">
                            <input type="number"
                                   name="peso_tara_canastilla_kg"
                                   id="peso_tara_canastilla_kg"
                                   step="0.05"
                                   min="0.1"
                                   max="20"
                                   required
                                   value="{{ old('peso_tara_canastilla_kg', $operacion['peso_tara_canastilla_kg']) }}"
                                   class="block w-full rounded-2xl border-slate-300 pr-12 text-sm font-bold text-slate-900 focus:border-cyan-500 focus:ring-cyan-500 py-2.5">
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-xs text-slate-400 font-medium">
                                kg
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Peso en vacío de cada canastilla restado automáticamente en cosechas (Predeterminado: 2.0 kg).</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Día Pesca Habitual -->
                        <div>
                            <label for="dia_pesca_habitual" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Día Pesca Habitual
                            </label>
                            <select name="dia_pesca_habitual"
                                    id="dia_pesca_habitual"
                                    class="block w-full rounded-2xl border-slate-300 text-xs font-semibold text-slate-800 focus:border-cyan-500 focus:ring-cyan-500 py-2.5">
                                @foreach (['lunes' => 'Lunes (o Martes festivo)', 'martes' => 'Martes', 'miercoles' => 'Miércoles', 'jueves' => 'Jueves', 'viernes' => 'Viernes', 'sabado' => 'Sábado', 'domingo' => 'Domingo'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('dia_pesca_habitual', $operacion['dia_pesca_habitual']) === $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Día Pago Nómina -->
                        <div>
                            <label for="dia_pago_nomina" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Día Pago Nómina
                            </label>
                            <select name="dia_pago_nomina"
                                    id="dia_pago_nomina"
                                    class="block w-full rounded-2xl border-slate-300 text-xs font-semibold text-slate-800 focus:border-cyan-500 focus:ring-cyan-500 py-2.5">
                                @foreach (['sabado' => 'Sábado (Destajeros)', 'viernes' => 'Viernes', 'domingo' => 'Domingo', 'lunes' => 'Lunes'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('dia_pago_nomina', $operacion['dia_pago_nomina']) === $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Especies Habilitadas en la Finca (Ocupa las 2 columnas) -->
            <div class="lg:col-span-2 bg-white rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-200/80 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-100 gap-2">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg font-bold shadow-xs">
                            <i class="fa-solid fa-fish"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Especies Piscícolas Habilitadas</h2>
                            <p class="text-xs text-slate-500">Selecciona las variedades cultivadas comercialmente en esta finca</p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold text-teal-700 bg-teal-50 px-3 py-1 rounded-full border border-teal-200 self-start sm:self-auto">
                        Control de Siembra & Muestreos
                    </span>
                </div>

                @php
                    $catalogoLista = [
                        'mojarra_roja' => ['nombre' => 'Mojarra Roja (Tilapia)', 'cientifico' => 'Oreochromis sp.', 'badge' => 'Especie Principal'],
                        'mojarra_negra' => ['nombre' => 'Mojarra Negra / Plateada', 'cientifico' => 'Oreochromis niloticus', 'badge' => 'Alta Talla'],
                        'cachama' => ['nombre' => 'Cachama Blanca', 'cientifico' => 'Piaractus brachypomus', 'badge' => 'Resistente'],
                        'bocachico' => ['nombre' => 'Bocachico Criollo', 'cientifico' => 'Prochilodus magdalenae', 'badge' => 'Fondo / Policultivo'],
                        'trucha' => ['nombre' => 'Trucha Arcoíris', 'cientifico' => 'Oncorhynchus mykiss', 'badge' => 'Agua Fría'],
                        'tilapia_nilotica' => ['nombre' => 'Tilapia Nilótica', 'cientifico' => 'Oreochromis niloticus', 'badge' => 'Crecimiento Rápido'],
                    ];
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach ($catalogoLista as $slug => $info)
                        @php
                            $isChecked = in_array($slug, old('especies_habilitadas', $especiesHabilitadas ?? []));
                        @endphp
                        <label class="relative flex items-start p-4 rounded-2xl border-2 cursor-pointer transition-all duration-150 {{ $isChecked ? 'border-cyan-500 bg-cyan-50/30 shadow-xs' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                            <div class="flex items-center h-5">
                                <input type="checkbox"
                                       name="especies_habilitadas[]"
                                       value="{{ $slug }}"
                                       {{ $isChecked ? 'checked' : '' }}
                                       class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                            </div>
                            <div class="ml-3 text-xs leading-5">
                                <span class="font-bold text-slate-800 block">{{ $info['nombre'] }}</span>
                                <span class="italic text-slate-400 block text-[11px]">{{ $info['cientifico'] }}</span>
                                <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                    {{ $info['badge'] }}
                                </span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- Botones de Acción -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
            <a href="{{ route('dashboard.index') }}"
               class="px-5 py-2.5 rounded-2xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                Cancelar
            </a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-2xl bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-500 hover:to-teal-500 text-white text-xs font-bold shadow-lg shadow-cyan-600/30 transition flex items-center gap-2 active:scale-95">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Guardar Parámetros de Finca</span>
            </button>
        </div>

    </form>

</div>
@endsection
