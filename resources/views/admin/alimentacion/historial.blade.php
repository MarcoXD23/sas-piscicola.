@extends('layouts.app')

@section('title', 'Control de Alimentación - Auditoría')

@section('content')
<div class="space-y-6">

    <!-- Encabezado de Página -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                <span>Operaciones</span>
                <span>•</span>
                <span class="text-cyan-600">Nutrición & Alimentación</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Control de Alimentación
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
                Auditoría histórica de raciones suministradas, estanques alimentados y trazabilidad por trabajador.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('operaciones.alimentacion.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                <i class="fa-solid fa-bowl-food text-emerald-600"></i>
                <span>Ir al Panel Operativo</span>
            </a>
            <a href="{{ route('admin.bodega.index') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-slate-800 transition">
                <i class="fa-solid fa-boxes-stacked text-amber-400"></i>
                <span>Bodega de Concentrado</span>
            </a>
        </div>
    </div>

    <!-- Métricas Rápidas -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Kilos Suministrados</span>
            <div class="text-3xl font-extrabold text-slate-900 mt-2">
                {{ number_format($totalKilos, 1, ',', '.') }} <span class="text-sm font-normal text-slate-500">kg</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Acumulado histórico registrado en la finca</p>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Raciones Registradas</span>
            <div class="text-3xl font-extrabold text-teal-700 mt-2">
                {{ $logs->total() }} <span class="text-sm font-normal text-slate-500">raciones</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Registros individuales de suministro</p>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Estanques en Cultivo</span>
            <div class="text-3xl font-extrabold text-cyan-700 mt-2">
                {{ $ponds->count() }} <span class="text-sm font-normal text-slate-500">estanques</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Infraestructura habilitada en el tenant</p>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('admin.alimentacion.historial') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <div>
                <label for="pond_id" class="block text-xs font-semibold text-slate-700 uppercase mb-1">Filtrar por Estanque / Lago</label>
                <select name="pond_id" id="pond_id" class="w-full border-slate-300 rounded-md text-xs py-2 shadow-xs focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="">-- Todos los Estanques --</option>
                    @foreach($ponds as $pond)
                        <option value="{{ $pond->id }}" {{ request('pond_id') == $pond->id ? 'selected' : '' }}>
                            {{ $pond->name }} ({{ $pond->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="fecha" class="block text-xs font-semibold text-slate-700 uppercase mb-1">Filtrar por Fecha</label>
                <input type="date" name="fecha" id="fecha" value="{{ request('fecha') }}" class="w-full border-slate-300 rounded-md text-xs py-2 shadow-xs focus:ring-cyan-500 focus:border-cyan-500">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="w-full inline-flex justify-center items-center gap-2 py-2 px-4 rounded-md bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition">
                    <i class="fa-solid fa-filter"></i>
                    <span>Aplicar Filtros</span>
                </button>
                @if(request()->hasAny(['pond_id', 'fecha']))
                    <a href="{{ route('admin.alimentacion.historial') }}" class="py-2 px-3 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs shadow-xs transition" title="Limpiar Filtros">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabla de Auditoría Histórica -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/75 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-cyan-600"></i>
                <span>Historial de Raciones Suministradas</span>
            </h2>
            <span class="text-xs text-slate-500 font-medium">Página {{ $logs->currentPage() }} de {{ $logs->lastPage() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5 text-left">Fecha</th>
                        <th class="px-5 py-3.5 text-left">Hora</th>
                        <th class="px-5 py-3.5 text-left">Estanque / Lago</th>
                        <th class="px-5 py-3.5 text-right">Kilos Suministrados</th>
                        <th class="px-5 py-3.5 text-left">Tipo de Concentrado</th>
                        <th class="px-5 py-3.5 text-left">Trabajador Responsable</th>
                        <th class="px-5 py-3.5 text-center">Nivel Apetito</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/50 transition">
                            <!-- Fecha -->
                            <td class="px-5 py-3.5 font-medium text-slate-900 whitespace-nowrap">
                                {{ $log->feeding_date ? $log->feeding_date->format('d/m/Y') : ($log->created_at ? $log->created_at->timezone('America/Bogota')->format('d/m/Y') : '-') }}
                            </td>

                            <!-- Hora -->
                            <td class="px-5 py-3.5 text-slate-600 whitespace-nowrap font-mono text-xs">
                                {{ $log->formatted_feeding_time ?? ($log->created_at ? $log->created_at->timezone('America/Bogota')->format('g:i A') : '-') }}
                            </td>

                            <!-- Estanque / Lago -->
                            <td class="px-5 py-3.5 font-semibold text-slate-900 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5">
                                    <i class="fa-solid fa-water text-cyan-500 text-xs"></i>
                                    {{ $log->pond?->name ?? 'Estanque #' . $log->pond_id }}
                                    @if($log->pond?->code)
                                        <span class="text-xs text-slate-400 font-normal font-mono">({{ $log->pond->code }})</span>
                                    @endif
                                </span>
                            </td>

                            <!-- Kilos suministrados -->
                            <td class="px-5 py-3.5 text-right font-extrabold text-slate-900 whitespace-nowrap">
                                {{ number_format($log->amount_kg, 2, ',', '.') }} kg
                            </td>

                            <!-- Tipo de concentrado -->
                            <td class="px-5 py-3.5 text-slate-700 whitespace-nowrap font-medium">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs bg-slate-100 text-slate-800 border border-slate-200">
                                    {{ $log->feed_name ?? $log->feed_type ?? 'Concentrado Estándar' }}
                                </span>
                            </td>

                            <!-- Trabajador responsable -->
                            <td class="px-5 py-3.5 text-slate-800 whitespace-nowrap font-medium">
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded-full bg-cyan-100 text-cyan-800 flex items-center justify-center text-[10px] font-bold">
                                        {{ strtoupper(substr($log->user?->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <span>{{ $log->user?->name ?? 'Operario No Registrado' }}</span>
                                </div>
                            </td>

                            <!-- Nivel apetito -->
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                @php
                                    $apetito = strtolower($log->appetite_level ?? 'bueno');
                                    $clases = match($apetito) {
                                        'excelente', 'bueno' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'regular' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'malo', 'bajo' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        default => 'bg-slate-50 text-slate-700 border-slate-200'
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $clases }}">
                                    {{ ucfirst($apetito) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                <i class="fa-solid fa-bowl-food text-4xl text-slate-300 mb-2"></i>
                                <p class="text-sm font-semibold text-slate-700">No hay registros de alimentación para los criterios seleccionados.</p>
                                <p class="text-xs text-slate-400 mt-1">Los registros suministrados por los operarios aparecerán en esta tabla.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-5 py-3 border-t border-slate-200 bg-slate-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
