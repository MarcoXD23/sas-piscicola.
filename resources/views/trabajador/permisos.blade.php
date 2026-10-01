@extends('layouts.app')

@section('title', 'Solicitud de Permisos - SAS Piscícola')
@section('page_title', 'Solicitud de Permisos Laborales')

@section('content')
<div class="space-y-6">

    <!-- Barra Superior de Navegación -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <x-back-button :href="route('trabajador.dashboard')" label="Volver al Dashboard" />
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 bg-white/80 dark:bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
            <i class="fa-solid fa-calendar-check text-cyan-600 dark:text-cyan-400"></i>
            <span>Gestión de Ausencias y Permisos Justificados</span>
        </div>
    </div>

    <!-- Alertas Flash -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-base text-emerald-600 dark:text-emerald-400"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs space-y-1">
            <div class="font-bold flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-base"></i>
                <span>Por favor corrige los siguientes errores:</span>
            </div>
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Formulario de Solicitud de Permiso -->
        <div class="lg:col-span-1 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-5">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane text-cyan-600 dark:text-cyan-400"></i>
                    Solicitar Permiso
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Radique con anticipación cualquier ausencia médica, calamidad o trámite personal.
                </p>
            </div>

            <form action="{{ route('trabajador.permisos.store') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Fecha de Inicio -->
                <div>
                    <label for="fecha_inicio" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Fecha de Inicio <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" required
                           value="{{ old('fecha_inicio', now()->toDateString()) }}"
                           class="w-full min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white px-3 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">
                </div>

                <!-- Fecha de Fin -->
                <div>
                    <label for="fecha_fin" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Fecha de Fin <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="fecha_fin" id="fecha_fin" required
                           value="{{ old('fecha_fin', now()->toDateString()) }}"
                           class="w-full min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white px-3 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">
                </div>

                <!-- Motivo Detallado -->
                <div>
                    <label for="motivo" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Motivo Detallado <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="motivo" id="motivo" rows="4" required
                              placeholder="Cita médica EPS, diligencia personal, calamidad doméstica..."
                              class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white p-3 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">{{ old('motivo') }}</textarea>
                </div>

                <button type="submit"
                        class="w-full min-h-[44px] rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-cyan-600 dark:hover:bg-cyan-500 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-md transition cursor-pointer">
                    <i class="fa-solid fa-envelope-open-text text-xs"></i>
                    <span>Radicar Solicitud de Permiso</span>
                </button>
            </form>
        </div>

        <!-- Historial de Solicitudes -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-cyan-600 dark:text-cyan-400"></i>
                    Historial de Mis Solicitudes
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Revise el estado de aprobación por parte del Administrador o Jefe Mayor.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-3">Período</th>
                            <th class="py-3 px-3">Motivo</th>
                            <th class="py-3 px-3">Estado</th>
                            <th class="py-3 px-3">Respuesta Administración</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($permisos as $permiso)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                <td class="py-3 px-3 font-mono text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ $permiso->start_date ? $permiso->start_date->format('d/m/Y') : '' }}
                                    @if($permiso->end_date && $permiso->end_date->ne($permiso->start_date))
                                        <br><span class="text-[10px] text-slate-400">al {{ $permiso->end_date->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-slate-800 dark:text-slate-200">
                                    {{ $permiso->reason }}
                                </td>
                                <td class="py-3 px-3">
                                    @if($permiso->status === 'aprobado')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            <i class="fa-solid fa-check text-[9px]"></i> Aprobado
                                        </span>
                                    @elseif($permiso->status === 'rechazado')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            <i class="fa-solid fa-xmark text-[9px]"></i> Rechazado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                            <i class="fa-regular fa-clock text-[9px]"></i> Pendiente
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-slate-500 dark:text-slate-400">
                                    @if($permiso->response_notes)
                                        <p class="text-xs italic text-slate-600 dark:text-slate-300">"{{ $permiso->response_notes }}"</p>
                                    @endif
                                    @if($permiso->reviewedBy)
                                        <span class="text-[10px]">Por: {{ $permiso->reviewedBy->name }}</span>
                                    @elseif($permiso->status === 'pendiente')
                                        <span class="text-[10px] text-slate-400 italic">En revisión</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-calendar-xmark text-2xl mb-2 text-slate-300 dark:text-slate-700 block"></i>
                                    No has registrado solicitudes de permiso.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($permisos->hasPages())
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $permisos->links() }}
                </div>
            @endif
        </div>

    </div>

</div>
@endsection
