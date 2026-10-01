@extends('layouts.app')

@section('title', 'Mis Horas Extras - SAS Piscícola')
@section('page_title', 'Registro y Control de Horas Extras')

@section('content')
<div class="space-y-6">

    <!-- Barra Superior de Navegación -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <a href="{{ route('trabajador.dashboard') }}"
           class="inline-flex items-center gap-2 px-3.5 py-2 min-h-[44px] rounded-xl text-xs font-semibold text-slate-700 bg-white hover:bg-slate-900 hover:text-white border border-slate-200 hover:border-slate-900 shadow-sm hover:shadow-md transition-all duration-200 group dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 dark:hover:text-white">
            <svg class="w-4 h-4 text-cyan-600 group-hover:text-cyan-400 dark:text-cyan-400 transition-transform duration-200 group-hover:-translate-x-1 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            <span>&larr; Volver al Dashboard</span>
        </a>
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 bg-white dark:bg-slate-800 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
            <svg class="w-4 h-4 text-amber-500 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span>Módulo Exclusivo de Jornales & Turnos Suplementarios</span>
        </div>
    </div>

    <!-- Alertas Flash -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs space-y-1">
            <div class="font-bold flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 8.25h.008v.008H12v-.008Z" />
                </svg>
                <span>Por favor corrige los siguientes errores:</span>
            </div>
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Tarjeta Resumen con el Total de Horas Extras Acumuladas en el Mes -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Horas Extras Acumuladas en el Mes</span>
                <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($totalHorasMes, 1) }} hrs</p>
                <span class="text-xs text-slate-500">Horas suplementarias reportadas</span>
            </div>
            <div class="h-12 w-12 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-amber-600 dark:text-amber-400">
                <svg class="w-6 h-6 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Horas Aprobadas en el Mes</span>
                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($totalAprobadasMes, 1) }} hrs</p>
                <span class="text-xs text-slate-500">Listas para liquidación semanal/mensual</span>
            </div>
            <div class="h-12 w-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                <svg class="w-6 h-6 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Contenedor Principal: Formulario y Tabla de Historial -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Formulario Limpio y Compacto de Registro -->
        <div class="lg:col-span-1 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 space-y-5">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Nueva Labor Extra</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Registra la jornada o turno adicional para revisión y aprobación administrativa.
                </p>
            </div>

            <form action="{{ route('trabajador.horas-extras.store') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Fecha de la actividad -->
                <div>
                    <label for="fecha" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Fecha de la actividad <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="fecha" id="fecha" required
                           value="{{ old('fecha', now()->toDateString()) }}"
                           class="w-full min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white px-3 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">
                </div>

                <!-- Cantidad de horas -->
                <div>
                    <label for="horas" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Cantidad de horas <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" step="0.5" min="0.5" max="24" name="horas" id="horas" required
                           value="{{ old('horas') }}"
                           placeholder="Ej. 2.5"
                           class="w-full min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white px-3 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">
                </div>

                <!-- Motivo / Ocasión -->
                <div>
                    <label for="motivo" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Motivo / Ocasión <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="motivo" id="motivo" required
                           value="{{ old('motivo') }}"
                           placeholder="Ej. Apoyo en pesca nocturna"
                           class="w-full min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white px-3 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">
                </div>

                <!-- Detalles o Justificación -->
                <div>
                    <label for="justificacion" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Observaciones adicionales
                    </label>
                    <textarea name="justificacion" id="justificacion" rows="3"
                              placeholder="Detalles sobre supervisión, área o novedad..."
                              class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white p-3 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">{{ old('justificacion') }}</textarea>
                </div>

                <!-- Botón de envío -->
                <button type="submit"
                        class="w-full min-h-[44px] rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-cyan-600 dark:hover:bg-cyan-500 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-md transition cursor-pointer">
                    <svg class="w-4 h-4 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                    <span>Registrar Horas Extras</span>
                </button>
            </form>
        </div>

        <!-- Tabla con el Historial de Horas Extras del Trabajador -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm0 5.25h.007v.008H3.75V12Zm0 5.25h.007v.008H3.75v-.008Z" />
                    </svg>
                    <span>Historial de Horas Extras</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Registro histórico y estado de validación administrativa.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-3">Fecha</th>
                            <th class="py-3 px-3 text-right">Horas</th>
                            <th class="py-3 px-3">Motivo</th>
                            <th class="py-3 px-3 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($horas as $hora)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                <td class="py-3 px-3 font-mono text-slate-600 dark:text-slate-300">
                                    {{ $hora->record_date ? $hora->record_date->format('d/m/Y') : '' }}
                                </td>
                                <td class="py-3 px-3 text-right font-black text-slate-900 dark:text-white whitespace-nowrap">
                                    {{ number_format($hora->hours, 1) }} hrs
                                </td>
                                <td class="py-3 px-3 text-slate-700 dark:text-slate-200 font-medium">
                                    {{ $hora->occasion }}
                                    @if($hora->justification && $hora->justification !== $hora->occasion)
                                        <p class="text-[11px] text-slate-400 mt-0.5">{{ $hora->justification }}</p>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    @if(in_array($hora->status, ['aprobado', 'aprobada', 'approved']))
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            <svg class="w-3 h-3 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                            Aprobada
                                        </span>
                                    @elseif(in_array($hora->status, ['rechazado', 'rechazada', 'rejected']))
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            <svg class="w-3 h-3 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                            Rechazada
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                            <svg class="w-3 h-3 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                            Pendiente de revisión
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center text-slate-400">
                                    <svg class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <p>Aún no has registrado horas extras en el sistema.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($horas, 'hasPages') && $horas->hasPages())
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $horas->links() }}
                </div>
            @endif
        </div>

    </div>

</div>
@endsection
