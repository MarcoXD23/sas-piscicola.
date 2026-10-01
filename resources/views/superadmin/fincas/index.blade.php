@extends('layouts.app')

@section('title', 'Gestión de Fincas y Módulos Multi-Inquilino')
@section('page_title', 'Panel SuperAdmin - Fincas')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Encabezado con Botón Atrás -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <x-back-button />
            <div class="mt-3">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                    <span class="p-2.5 rounded-2xl bg-purple-500/10 text-purple-600 border border-purple-500/20">
                        <i class="fa-solid fa-building-flag text-xl"></i>
                    </span>
                    <span>Gestión de Fincas y Feature Flags</span>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Control centralizado de suscripciones, módulos activos y parámetros por finca cliente.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-600 text-white text-xs font-bold shadow-md shadow-purple-600/30">
                <i class="fa-solid fa-crown text-amber-300"></i>
                Super-Administrador
            </span>
        </div>
    </div>

    <!-- Lista / Tabla de Fincas -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Fincas Clientes Registradas</h2>
                <p class="text-xs text-slate-500">Visualiza el estado de módulos de cada predio o empresa acuícola</p>
            </div>
            <div class="text-xs font-semibold text-slate-500">
                Total: <span class="font-bold text-slate-800">{{ $fincas->count() }}</span> finca(s)
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">ID / Código</th>
                        <th class="py-3.5 px-6">Nombre de Finca</th>
                        <th class="py-3.5 px-6">Ubicación</th>
                        <th class="py-3.5 px-6">Módulos Activos</th>
                        <th class="py-3.5 px-6">Tarifas ($/kg)</th>
                        <th class="py-3.5 px-6 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($fincas as $item)
                        @php
                            $mods = $item->configuraciones['modulos_activos'] ?? [];
                            $activeCount = count(array_filter($mods));
                            $totalMods = 6;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-4 px-6 font-mono font-bold text-slate-700">
                                <span class="px-2 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-800">
                                    #{{ $item->id }} • {{ $item->codigo ?? 'FINCA-0'.$item->id }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-bold text-slate-900 text-sm">{{ $item->nombre }}</div>
                                <div class="text-[11px] text-slate-400">
                                    {{ $item->users_count ?? 0 }} usuario(s) • {{ $item->ponds_count ?? 0 }} estanque(s)
                                </div>
                            </td>
                            <td class="py-4 px-6 text-slate-600">
                                <i class="fa-solid fa-location-dot text-rose-500 mr-1"></i>
                                {{ $item->ubicacion ?? 'Tolima, Colombia' }}
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2">
                                    <div class="w-20 bg-slate-200 rounded-full h-2 overflow-hidden">
                                        <div class="bg-purple-600 h-2 rounded-full" style="width: {{ round(($activeCount / $totalMods) * 100) }}%"></div>
                                    </div>
                                    <span class="font-bold text-slate-800 text-[11px]">{{ $activeCount }}/{{ $totalMods }}</span>
                                </div>
                                <div class="flex flex-wrap gap-1 mt-1.5">
                                    @if($item->tieneModulo('celador_nocturno'))
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">Nocturno</span>
                                    @endif
                                    @if($item->tieneModulo('asistente_ia'))
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200">IA Gemini</span>
                                    @endif
                                    @if($item->tieneModulo('ventas_visitantes'))
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">Ventas</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-6 text-slate-600 font-mono text-[11px]">
                                <div>Visitante: <strong class="text-slate-800">${{ number_format($item->obtenerConfig('precios.pescado_visitante_kg', 9000)) }}</strong></div>
                                <div>Empleado: <strong class="text-slate-800">${{ number_format($item->obtenerConfig('precios.pescado_empleado_kg', 7000)) }}</strong></div>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <a href="{{ route('superadmin.fincas.edit', $item->id) }}"
                                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold text-xs transition border border-purple-200 shadow-2xs">
                                    <i class="fa-solid fa-sliders"></i>
                                    <span>Editar Módulos</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                No se encontraron fincas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
