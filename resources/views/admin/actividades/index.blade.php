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

    <!-- Filtros de Búsqueda Combinables -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
        <form method="GET" action="{{ route('admin.actividades.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 items-end">
            <!-- Filtro Trabajador -->
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Trabajador:</label>
                <select name="user_id" class="w-full rounded-xl border border-slate-300 bg-white py-2 px-3 text-xs text-slate-900 focus:border-cyan-600 focus:ring-1 focus:ring-cyan-600">
                    <option value="">Todos los colaboradores</option>
                    @foreach($trabajadores as $trabajador)
                        <option value="{{ $trabajador->id }}" {{ (request('user_id') == $trabajador->id || request('trabajador_id') == $trabajador->id) ? 'selected' : '' }}>
                            {{ $trabajador->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro Rol al Momento -->
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Rol Desempeñado:</label>
                <select name="rol" class="w-full rounded-xl border border-slate-300 bg-white py-2 px-3 text-xs text-slate-900 focus:border-cyan-600 focus:ring-1 focus:ring-cyan-600">
                    <option value="">Todos los roles</option>
                    <option value="alimentador" {{ strtolower(request('rol', '')) === 'alimentador' ? 'selected' : '' }}>Alimentador</option>
                    <option value="seguridad_noche" {{ strtolower(request('rol', '')) === 'seguridad_noche' ? 'selected' : '' }}>Seguridad Noche</option>
                    <option value="tecnico_acuicola" {{ strtolower(request('rol', '')) === 'tecnico_acuicola' ? 'selected' : '' }}>Técnico Acuícola</option>
                    @foreach($roles as $rolOption)
                        @if(!in_array(strtolower($rolOption), ['alimentador', 'seguridad_noche', 'tecnico_acuicola', 'operario']))
                            <option value="{{ $rolOption }}" {{ strtolower(request('rol', '')) === strtolower($rolOption) ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $rolOption)) }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            <!-- Filtro Tipo de Acción -->
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Tipo de Acción:</label>
                <select name="tipo_accion" class="w-full rounded-xl border border-slate-300 bg-white py-2 px-3 text-xs text-slate-900 focus:border-cyan-600 focus:ring-1 focus:ring-cyan-600">
                    <option value="">Todas las acciones</option>
                    <option value="suministro_alimento" {{ in_array(request('tipo_accion', request('accion')), ['suministro_alimento', 'alimentacion']) ? 'selected' : '' }}>Suministro Alimento</option>
                    <option value="traslado_peces" {{ in_array(request('tipo_accion', request('accion')), ['traslado_peces', 'traslado']) ? 'selected' : '' }}>Traslado / Desdoble</option>
                    <option value="reporte_mortalidad" {{ in_array(request('tipo_accion', request('accion')), ['reporte_mortalidad', 'mortalidad']) ? 'selected' : '' }}>Reporte Mortalidad</option>
                    <option value="ronda_seguridad" {{ in_array(request('tipo_accion', request('accion')), ['ronda_seguridad', 'ronda_nocturna']) ? 'selected' : '' }}>Ronda de Seguridad</option>
                    <option value="ingreso_alimento" {{ request('tipo_accion', request('accion')) === 'ingreso_alimento' ? 'selected' : '' }}>Ingreso a Bodega</option>
                    <option value="tarea_completada" {{ request('tipo_accion', request('accion')) === 'tarea_completada' ? 'selected' : '' }}>Tarea Completada</option>
                </select>
            </div>

            <!-- Filtro Estanque / Lago -->
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Estanque / Lago:</label>
                <select name="estanque_id" class="w-full rounded-xl border border-slate-300 bg-white py-2 px-3 text-xs text-slate-900 focus:border-cyan-600 focus:ring-1 focus:ring-cyan-600">
                    <option value="">Todos los estanques</option>
                    @foreach($estanques as $estanque)
                        <option value="{{ $estanque->id }}" {{ (request('estanque_id') == $estanque->id || request('lago_id') == $estanque->id) ? 'selected' : '' }}>
                            {{ $estanque->name }} ({{ $estanque->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro Rango de Fechas -->
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Fecha Inicio:</label>
                <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio', request('fecha')) }}"
                       class="w-full rounded-xl border border-slate-300 bg-white py-2 px-3 text-xs text-slate-900 focus:border-cyan-600 focus:ring-1 focus:ring-cyan-600">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Fecha Fin:</label>
                <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}"
                       class="w-full rounded-xl border border-slate-300 bg-white py-2 px-3 text-xs text-slate-900 focus:border-cyan-600 focus:ring-1 focus:ring-cyan-600">
            </div>

            <!-- Botones Filtrar y Limpiar -->
            <div class="sm:col-span-2 md:col-span-3 lg:col-span-6 flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <a href="{{ route('admin.actividades.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 py-2 px-4 text-xs font-bold transition shadow-2xs" title="Limpiar filtros">
                    <i class="fa-solid fa-rotate-left text-slate-400"></i>
                    <span>Limpiar Filtros</span>
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-500 hover:to-teal-500 text-white font-bold py-2 px-5 text-xs shadow-md shadow-cyan-600/20 transition">
                    <i class="fa-solid fa-filter"></i>
                    <span>Aplicar Filtros</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla Histórica de Actividades -->
    <div class="rounded-3xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-50/75 px-6 py-4 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <span class="p-1.5 rounded-lg bg-cyan-50 text-cyan-600">
                    <i class="fa-solid fa-clipboard-list"></i>
                </span>
                <span>Registro de Eventos y Auditoría Operativa ({{ $actividades->total() }})</span>
            </h2>
            <span class="text-xs text-slate-500 font-medium">Zona horaria: America/Bogota (12h)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Fecha y Hora</th>
                        <th class="px-6 py-4">Colaborador</th>
                        <th class="px-6 py-4">Acción Realizada</th>
                        <th class="px-6 py-4">Detalle de la Operación</th>
                        <th class="px-6 py-4">Estanque Involucrado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($actividades as $actividad)
                        @php
                            $tipo = strtolower($actividad->tipo_accion);
                            $badgeAccion = match(true) {
                                in_array($tipo, ['alimentacion', 'suministro_alimento']) => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                in_array($tipo, ['traslado_peces', 'traslado', 'desdoble']) => 'bg-blue-50 text-blue-700 border-blue-200',
                                in_array($tipo, ['mortalidad', 'reporte_mortalidad']) => 'bg-rose-50 text-rose-700 border-rose-200',
                                in_array($tipo, ['ronda_nocturna', 'ronda_seguridad', 'seguridad']) => 'bg-purple-50 text-purple-700 border-purple-200',
                                in_array($tipo, ['ingreso_alimento', 'bodega']) => 'bg-amber-50 text-amber-700 border-amber-200',
                                default => 'bg-slate-50 text-slate-700 border-slate-200',
                            };
                            $labelAccion = match(true) {
                                in_array($tipo, ['alimentacion', 'suministro_alimento']) => 'Suministro Alimento',
                                in_array($tipo, ['traslado_peces', 'traslado', 'desdoble']) => 'Traslado Peces',
                                in_array($tipo, ['mortalidad', 'reporte_mortalidad']) => 'Reporte Mortalidad',
                                in_array($tipo, ['ronda_nocturna', 'ronda_seguridad', 'seguridad']) => 'Ronda Seguridad',
                                in_array($tipo, ['ingreso_alimento', 'bodega']) => 'Ingreso Bodega',
                                default => ucwords(str_replace('_', ' ', $actividad->tipo_accion)),
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-mono font-semibold text-slate-900 whitespace-nowrap">
                                {{ $actividad->created_at ? $actividad->created_at->timezone('America/Bogota')->format('d/m/Y h:i:s A') : '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">{{ $actividad->user?->name ?? 'Usuario no disponible' }}</div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200 mt-0.5">
                                    {{ $actividad->rol_momento }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-[10px] font-bold border {{ $badgeAccion }} uppercase tracking-wide">
                                    {{ $labelAccion }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-700 max-w-md font-medium leading-relaxed">
                                {{ $actividad->descripcion }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($actividad->estanque)
                                    <span class="font-bold text-slate-900">{{ $actividad->estanque->name }}</span>
                                    <span class="text-[11px] text-slate-400 block font-mono">{{ $actividad->estanque->code }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-600">
                                        General / Bodega
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="h-12 w-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-xl mb-3">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-700">No se encontraron registros de actividades para los criterios seleccionados</p>
                                    <p class="text-xs text-slate-400 mt-1">Prueba modificando los filtros de colaborador, rol, acción o rango de fechas.</p>
                                    <a href="{{ route('admin.actividades.index') }}" class="mt-4 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                                        Restablecer Filtros
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($actividades->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                {{ $actividades->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
