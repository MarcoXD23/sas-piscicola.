@extends('layouts.app')

@section('title', 'Bitácora de Actividades de Trabajadores - El SAS Piscícola')

@section('content')
<div class="space-y-6">

    <!-- Encabezado de Página -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                <span>Personal & Operaciones</span>
                <span>•</span>
                <span class="text-cyan-600">Auditoría Operativa</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Bitácora de Actividades de Trabajadores
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
                Historial cronológico de labores de campo, alimentación, mortalidades, traslados y rondas nocturnas con fecha y hora exacta.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.personal.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                <span>Gestión de Personal</span>
            </a>
        </div>
    </div>

    <!-- Filtros de Búsqueda -->
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.actividades.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <!-- Filtro Trabajador -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Trabajador:</label>
                <select name="user_id" class="w-full rounded-md border border-slate-300 bg-white py-1.5 px-2.5 text-xs text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    <option value="">Todos los trabajadores</option>
                    @foreach($trabajadores as $trabajador)
                        <option value="{{ $trabajador->id }}" {{ request('user_id') == $trabajador->id ? 'selected' : '' }}>
                            {{ $trabajador->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro Rol -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Rol al Momento:</label>
                <select name="rol" class="w-full rounded-md border border-slate-300 bg-white py-1.5 px-2.5 text-xs text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    <option value="">Todos los roles</option>
                    @foreach($roles as $rolOption)
                        <option value="{{ $rolOption }}" {{ request('rol') == $rolOption ? 'selected' : '' }}>
                            {{ $rolOption }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro Fecha -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Fecha:</label>
                <input type="date" name="fecha" value="{{ request('fecha') }}"
                       class="w-full rounded-md border border-slate-300 bg-white py-1.5 px-2.5 text-xs text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
            </div>

            <!-- Filtro Estanque -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Estanque / Lago:</label>
                <select name="estanque_id" class="w-full rounded-md border border-slate-300 bg-white py-1.5 px-2.5 text-xs text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    <option value="">Todos los estanques</option>
                    @foreach($estanques as $estanque)
                        <option value="{{ $estanque->id }}" {{ request('estanque_id') == $estanque->id ? 'selected' : '' }}>
                            {{ $estanque->name }} ({{ $estanque->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Botones Filtrar y Limpiar -->
            <div class="flex items-center gap-2">
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1 rounded-md bg-slate-900 hover:bg-slate-800 text-white font-semibold py-1.5 px-3 text-xs shadow-xs transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <span>Filtrar</span>
                </button>
                <a href="{{ route('admin.actividades.index') }}"
                   class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-slate-100 hover:bg-slate-200 text-slate-700 py-1.5 px-2.5 text-xs font-medium transition" title="Limpiar filtros">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Tabla Histórica de Actividades -->
    <div class="rounded-xl border border-slate-200 bg-white shadow-xs overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>Registro de Eventos y Trazabilidad ({{ $actividades->total() }})</span>
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Fecha y Hora Exacta</th>
                        <th class="px-5 py-3.5">Trabajador</th>
                        <th class="px-5 py-3.5">Rol en ese Momento</th>
                        <th class="px-5 py-3.5">Acción</th>
                        <th class="px-5 py-3.5">Estanque</th>
                        <th class="px-5 py-3.5">Descripción de la Actividad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($actividades as $actividad)
                        @php
                            $badgeAccion = match($actividad->tipo_accion) {
                                'alimentacion' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'mortalidad' => 'bg-rose-50 text-rose-700 border-rose-200',
                                'traslado_peces' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'ronda_nocturna' => 'bg-purple-50 text-purple-700 border-purple-200',
                                'ingreso_alimento' => 'bg-amber-50 text-amber-700 border-amber-200',
                                default => 'bg-slate-50 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3.5 font-mono text-slate-900 whitespace-nowrap">
                                {{ $actividad->created_at ? $actividad->created_at->timezone('America/Bogota')->format('d/m/Y h:i:s A') : '-' }}
                            </td>
                            <td class="px-5 py-3.5 font-bold text-slate-900">
                                {{ $actividad->user?->name ?? 'Usuario eliminado' }}
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $actividad->rol_momento }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $badgeAccion }} uppercase">
                                    {{ str_replace('_', ' ', $actividad->tipo_accion) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 font-medium text-slate-800">
                                {{ $actividad->estanque ? $actividad->estanque->name : 'N/A General' }}
                            </td>
                            <td class="px-5 py-3.5 text-slate-700 max-w-md">
                                {{ $actividad->descripcion }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-xs text-slate-500">
                                No se encontraron actividades registradas con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($actividades->hasPages())
            <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50">
                {{ $actividades->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
