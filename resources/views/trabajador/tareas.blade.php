@extends('layouts.app')

@section('title', 'Tareas Asignadas - SAS Piscícola')
@section('page_title', 'Muro de Instrucciones y Tareas de Campo')

@section('content')
<div class="space-y-6">

    <!-- Barra Superior de Navegación -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <x-back-button :href="route('trabajador.dashboard')" label="Volver al Dashboard" />
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 bg-white/80 dark:bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
            <i class="fa-solid fa-list-check text-cyan-600 dark:text-cyan-400"></i>
            <span>Instrucciones Operativas de Administración</span>
        </div>
    </div>

    <!-- Alertas Flash -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-base text-emerald-600 dark:text-emerald-400"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Lista de Tareas -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-6">
        <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-clipboard-list text-cyan-600 dark:text-cyan-400"></i>
                Tareas Asignadas para la Granja
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Revise las labores ordenadas por el Administrador. Al finalizar cada tarea, marque el botón de confirmación.
            </p>
        </div>

        <div class="space-y-3">
            @forelse($tareas as $tarea)
                <div class="p-4 sm:p-5 rounded-2xl border transition flex flex-col sm:flex-row sm:items-center justify-between gap-4 {{ $tarea->status === 'completada' ? 'bg-slate-50/60 dark:bg-slate-950/30 border-slate-200 dark:border-slate-800 opacity-75' : 'bg-white dark:bg-slate-800/70 border-slate-200 dark:border-slate-700 hover:border-cyan-500/50 shadow-sm' }}">
                    <div class="space-y-2 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- Badge Estado -->
                            @if($tarea->status === 'completada')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    <i class="fa-solid fa-check text-[9px]"></i> Realizada
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                    <i class="fa-regular fa-clock text-[9px]"></i> Pendiente
                                </span>
                            @endif

                            <!-- Badge Prioridad -->
                            @if($tarea->priority === 'urgente' || $tarea->priority === 'alta')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 uppercase">
                                    Prioridad {{ ucfirst($tarea->priority) }}
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                    Prioridad {{ ucfirst($tarea->priority ?? 'normal') }}
                                </span>
                            @endif

                            <!-- Fecha Límite / Vencimiento -->
                            @if($tarea->due_date)
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                                    <i class="fa-regular fa-calendar mr-1"></i>Vence: {{ $tarea->due_date->format('d/m/Y') }}
                                </span>
                            @endif
                        </div>

                        <h4 class="text-sm font-bold text-slate-900 dark:text-white {{ $tarea->status === 'completada' ? 'line-through text-slate-500 dark:text-slate-400' : '' }}">
                            {{ $tarea->title }}
                        </h4>

                        @if($tarea->description)
                            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                                {{ $tarea->description }}
                            </p>
                        @endif

                        <div class="text-[11px] text-slate-400 flex items-center gap-3">
                            <span>Asignado por: <strong>{{ $tarea->createdBy?->name ?? 'Administración' }}</strong></span>
                            @if($tarea->completed_at)
                                <span>• Completada el {{ $tarea->completed_at->format('d/m/Y H:i') }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Acción: Marcar como Realizada -->
                    <div class="sm:shrink-0 flex items-center">
                        @if($tarea->status !== 'completada')
                            <form action="{{ route('trabajador.tareas.completar', $tarea->id) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="min-h-[44px] px-4 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs flex items-center gap-2 shadow-md shadow-cyan-600/20 active:scale-95 transition cursor-pointer">
                                    <i class="fa-solid fa-check"></i>
                                    <span>Marcar como Realizada</span>
                                </button>
                            </form>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400 font-semibold px-3 py-2 bg-emerald-50 dark:bg-emerald-950/30 rounded-xl border border-emerald-200 dark:border-emerald-800">
                                <i class="fa-solid fa-circle-check"></i> Labor Finalizada
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-12 text-slate-400">
                    <i class="fa-solid fa-clipboard-check text-4xl mb-3 text-slate-300 dark:text-slate-700 block"></i>
                    <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300">No hay tareas pendientes asignadas</h4>
                    <p class="text-xs text-slate-500 mt-1">El tablón de instrucciones de la finca se encuentra al día.</p>
                </div>
            @endforelse
        </div>

        @if($tareas->hasPages())
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                {{ $tareas->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
