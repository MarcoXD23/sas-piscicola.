@extends('layouts.app')

@section('title', 'Mi Saldo de Pescado - SAS Piscícola')
@section('page_title', 'Saldo de Pescado Llevado / Fiado')

@section('content')
<div class="space-y-6">

    <!-- Barra Superior de Navegación -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <x-back-button :href="route('trabajador.dashboard')" label="Volver al Dashboard" />
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 bg-white/80 dark:bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
            <i class="fa-solid fa-fish text-cyan-600 dark:text-cyan-400"></i>
            <span>Beneficio de Consumo Familiar de Pescado</span>
        </div>
    </div>

    <!-- Mensaje Informativo Institucional -->
    <div class="p-4 rounded-2xl bg-cyan-50 dark:bg-cyan-950/40 border border-cyan-200 dark:border-cyan-800 text-cyan-900 dark:text-cyan-200 text-xs flex items-center gap-3">
        <i class="fa-solid fa-circle-info text-base text-cyan-600 dark:text-cyan-400 shrink-0"></i>
        <p class="leading-relaxed">
            <strong>Información de Nómina:</strong> Este valor será descontado en su nómina del sábado (destajo) o en su corte mensual (trabajador fijo), a precio de costo institucional preferencial para empleados (${{ number_format($precioKg) }} COP/kg).
        </p>
    </div>

    <!-- Tarjetas Resumen -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Total Kilos Llevados -->
        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Kilos Llevados</span>
                <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($totalKilos, 2) }} kg</p>
                <span class="text-[11px] text-slate-500">Pescado fresco entregado</span>
            </div>
            <div class="h-12 w-12 rounded-2xl bg-cyan-50 dark:bg-cyan-950/40 border border-cyan-200 dark:border-cyan-800 flex items-center justify-center text-cyan-600 dark:text-cyan-400">
                <i class="fa-solid fa-weight-hanging text-lg"></i>
            </div>
        </div>

        <!-- Total Dinero Descontable -->
        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Total Descontable en Nómina</span>
                <p class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">${{ number_format($totalDinero, 0, ',', '.') }} COP</p>
                <span class="text-[11px] text-slate-500">Valor total a liquidar</span>
            </div>
            <div class="h-12 w-12 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 flex items-center justify-center text-rose-600 dark:text-rose-400">
                <i class="fa-solid fa-receipt text-lg"></i>
            </div>
        </div>

        <!-- Tarifa Especial Empleado -->
        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Tarifa Preferencial</span>
                <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">${{ number_format($precioKg, 0, ',', '.') }} / kg</p>
                <span class="text-[11px] text-slate-500">Subsidio interno de finca</span>
            </div>
            <div class="h-12 w-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                <i class="fa-solid fa-tag text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Tabla de Historial de Retiros -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-4">
        <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-cyan-600 dark:text-cyan-400"></i>
                Historial de Retiros de Pescado
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Detalle de entregas registradas en báscula o bodega para consumo doméstico.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-3">Fecha de Entrega</th>
                        <th class="py-3 px-3 text-right">Kilos Retirados</th>
                        <th class="py-3 px-3 text-right">Precio / Kg</th>
                        <th class="py-3 px-3 text-right">Total Liquidado</th>
                        <th class="py-3 px-3">Estado Nómina</th>
                        <th class="py-3 px-3">Autorizado Por</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($credits as $credit)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td class="py-3 px-3 font-mono text-slate-500 dark:text-slate-400">
                                {{ $credit->credit_date ? $credit->credit_date->format('d/m/Y') : $credit->created_at->format('d/m/Y') }}
                            </td>
                            <td class="py-3 px-3 text-right font-black text-slate-900 dark:text-white">
                                {{ number_format($credit->kilos, 2) }} kg
                            </td>
                            <td class="py-3 px-3 text-right font-mono text-slate-600 dark:text-slate-300">
                                ${{ number_format($credit->price_per_kg ?? $precioKg) }}
                            </td>
                            <td class="py-3 px-3 text-right font-black text-rose-600 dark:text-rose-400">
                                ${{ number_format($credit->total_amount ?? ($credit->kilos * $precioKg)) }}
                            </td>
                            <td class="py-3 px-3">
                                @if($credit->status === 'descontado_sabado' || $credit->status === 'pagado')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <i class="fa-solid fa-check text-[9px]"></i> Descontado
                                    </span>
                                @elseif($credit->status === 'acumulado_mensual')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                        <i class="fa-solid fa-calendar text-[9px]"></i> Corte Mensual
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        <i class="fa-regular fa-clock text-[9px]"></i> Pendiente Sábado
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-slate-500 dark:text-slate-400">
                                {{ $credit->registeredBy?->name ?? 'Administración' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-receipt text-2xl mb-2 text-slate-300 dark:text-slate-700 block"></i>
                                No registras retiros de pescado pendientes de descuento.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($credits->hasPages())
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                {{ $credits->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
