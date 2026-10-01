@extends('layouts.app')

@section('title', 'Liquidación de Sábados y Nómina')
@section('page_title', 'Módulo de Nómina Semanal y Liquidación de Sábado')

@section('content')
<div class="space-y-6" x-data="{ settleModalOpen: false, notes: '' }">

    <!-- Navegación Superior: Botón Volver al Dashboard -->
    <div class="flex items-center justify-between">
        <x-back-button />
        <span class="text-xs text-slate-500 font-medium hidden sm:inline">Personal & Nómina • Liquidación Semanal</span>
    </div>

    <!-- Selector de Fecha de Corte de Sábado -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-invoice-dollar text-indigo-600"></i>
                Liquidación Semanal de Personal de Apoyo (Corte Sábados)
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                Semana operativa: del <strong>{{ $weekStartDate }}</strong> al sábado de corte <strong>{{ $cutoffDateStr }}</strong>.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('nomina.index') }}" class="flex items-center gap-2">
                <label class="text-xs font-semibold text-slate-600">Fecha de Corte:</label>
                <input type="date" name="cutoff_date" value="{{ $cutoffDateStr }}"
                       class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs px-3.5 py-2 rounded-xl transition duration-200 flex items-center gap-1.5 shadow-sm min-h-[38px]">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
            </form>
            @modulo('exportacion_facturacion')
            <a href="{{ route('nomina.export_csv', ['cutoff_date' => $cutoffDateStr]) }}"
               class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs px-3.5 py-2 rounded-xl transition duration-200 flex items-center gap-1.5 shadow-sm min-h-[38px]"
               title="Descargar liquidación en formato CSV / Excel">
                <i class="fa-solid fa-file-excel"></i> Descargar Excel
            </a>
            @endmodulo
        </div>
    </div>

    <!-- KPI Cards de Liquidación (Bruto, Deducción Pescado, Total Neto) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Jornaleros Temporales -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Personal Temporal a Liquidar</p>
            <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $temporalesSummary->count() }} <span class="text-sm font-medium text-slate-400">jornaleros</span></h3>
            <span class="text-[11px] text-slate-400">Excluye fijos con salario mensual</span>
        </div>

        <!-- 2. Total Bruto de Jornales -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Bruto de Jornales</p>
            <h3 class="text-2xl font-black text-indigo-600 mt-1">${{ number_format($totalGross, 0, ',', '.') }}</h3>
            <span class="text-[11px] text-slate-400">Total devengado por labores</span>
        </div>

        <!-- 3. Descuento Pescado Fiado (Rojo Semántico) -->
        <div class="bg-white rounded-2xl p-4 border border-rose-200 shadow-sm bg-rose-50/20">
            <p class="text-xs font-semibold text-rose-700 uppercase tracking-wider">Deducción Pescado Fiado</p>
            <h3 class="text-2xl font-black text-rose-600 mt-1">-${{ number_format($totalDeductions, 0, ',', '.') }}</h3>
            <span class="text-[11px] text-rose-500">Tarifa interna preferencial $7.000 / kg</span>
        </div>

        <!-- 4. Total Neto a Pagar el Sábado (Verde Semántico) -->
        <div class="bg-white rounded-2xl p-4 border border-emerald-300 shadow-sm bg-emerald-50/30">
            <p class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">Total Neto a Desembolsar</p>
            <h3 class="text-2xl font-black text-emerald-600 mt-1">${{ number_format($totalNet, 0, ',', '.') }}</h3>
            <span class="text-[11px] text-emerald-600 font-semibold">Neto tras deducción de pescado</span>
        </div>
    </div>

    <!-- Tabla 1: Personal Temporal / Jornaleros (Sujetos a Liquidación) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-users-gear text-indigo-600"></i>
                    Planilla de Personal Temporal / Jornaleros (Sábado de Corte)
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Calcula automáticamente el jornal y descuenta el pescado fiado a $7.000 el kilo.</p>
            </div>

            @if($temporalesSummary->isNotEmpty())
                <button @click="settleModalOpen = true" type="button"
                        class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs px-4 py-2 rounded-xl transition duration-200 flex items-center gap-2 shadow-lg shadow-indigo-600/20">
                    <i class="fa-solid fa-cash-register"></i> Liquidar y Cerrar Nómina de Sábado
                </button>
            @endif
        </div>

        <!-- Desktop Table (Pantallas Medianas y Grandes) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Trabajador de Apoyo</th>
                        <th class="py-3 px-4">Cédula</th>
                        <th class="py-3 px-4 text-center">Días Trab.</th>
                        <th class="py-3 px-4">Labores Realizadas</th>
                        <th class="py-3 px-4 text-right">Acumulado Bruto</th>
                        <th class="py-3 px-4 text-center">Pescado Fiado</th>
                        @php
                            $deduccionPrecioKg = auth()->user()->finca_segura?->obtenerConfig('precios.pescado_empleado_kg', 7000) ?? 7000;
                        @endphp
                        <th class="py-3 px-4 text-right">Deducción (${{ number_format($deduccionPrecioKg) }}/kg)</th>
                        <th class="py-3 px-4 text-right font-black text-emerald-700">Total Neto a Pagar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($temporalesSummary as $worker)
                        <tr class="hover:bg-slate-50/80 transition duration-150">
                            <td class="py-3 px-4 font-bold text-slate-900 flex items-center gap-2">
                                <span class="h-7 w-7 rounded-lg bg-indigo-50 text-indigo-600 font-bold text-[10px] flex items-center justify-center">
                                    {{ substr($worker['worker_name'], 0, 2) }}
                                </span>
                                {{ $worker['worker_name'] }}
                            </td>
                            <td class="py-3 px-4 text-slate-500 font-mono">{{ $worker['worker_id_card'] ?? 'No registrada' }}</td>
                            <td class="py-3 px-4 text-center font-bold text-slate-800">{{ $worker['dias_trabajados'] }} d</td>
                            <td class="py-3 px-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($worker['labores'] as $labType => $count)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 capitalize">
                                            {{ str_replace('_', ' ', $labType) }} ({{ $count }})
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-slate-800">${{ number_format($worker['acumulado_jornales'], 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-center font-semibold {{ $worker['kilos_pescado_fiado'] > 0 ? 'text-rose-600 font-bold' : 'text-slate-400' }}">
                                {{ $worker['kilos_pescado_fiado'] > 0 ? $worker['kilos_pescado_fiado'] . ' kg' : '0 kg' }}
                            </td>
                            <td class="py-3 px-4 text-right font-bold {{ $worker['descuento_pescado'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                {{ $worker['descuento_pescado'] > 0 ? '-$' . number_format($worker['descuento_pescado'], 0, ',', '.') : '$0' }}
                            </td>
                            <td class="py-3 px-4 text-right font-black text-emerald-600 text-sm">
                                ${{ number_format($worker['total_neto'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                <i class="fa-regular fa-clipboard text-3xl mb-2 text-slate-300"></i>
                                <p class="text-sm">No existen jornales pendientes para liquidar en la semana con corte al {{ $cutoffDateStr }}.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($temporalesSummary->isNotEmpty())
                    <tfoot class="bg-slate-50 font-bold border-t-2 border-slate-200">
                        <tr>
                            <td colspan="4" class="py-3 px-4 text-right uppercase text-[10px] text-slate-500">Totales de Liquidación:</td>
                            <td class="py-3 px-4 text-right text-indigo-700">${{ number_format($totalGross, 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-center text-rose-700">{{ $temporalesSummary->sum('kilos_pescado_fiado') }} kg</td>
                            <td class="py-3 px-4 text-right text-rose-700">-${{ number_format($totalDeductions, 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-right text-emerald-600 text-sm">${{ number_format($totalNet, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <!-- Mobile Cards (Celulares / Dispositivos Táctiles) -->
        <div class="md:hidden space-y-3">
            @forelse($temporalesSummary as $worker)
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="h-9 w-9 rounded-xl bg-indigo-100 text-indigo-700 font-black text-xs flex items-center justify-center">
                                {{ substr($worker['worker_name'], 0, 2) }}
                            </span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 leading-tight">{{ $worker['worker_name'] }}</h4>
                                <p class="text-[10px] text-slate-500 font-mono">CC: {{ $worker['worker_id_card'] ?? 'Sin cédula' }}</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 rounded-lg text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">
                            {{ $worker['dias_trabajados'] }} días trab.
                        </span>
                    </div>

                    <!-- Labores -->
                    <div class="flex flex-wrap gap-1">
                        @foreach($worker['labores'] as $labType => $count)
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-white text-slate-600 border border-slate-200 capitalize">
                                {{ str_replace('_', ' ', $labType) }} ({{ $count }})
                            </span>
                        @endforeach
                    </div>

                    <!-- Desglose Económico -->
                    <div class="grid grid-cols-2 gap-2 text-xs pt-1 border-t border-slate-200/60">
                        <div>
                            <span class="text-[10px] text-slate-400 block font-medium">Acumulado Bruto</span>
                            <span class="font-bold text-slate-800">${{ number_format($worker['acumulado_jornales'], 0, ',', '.') }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-rose-500 block font-medium">
                                Pescado Fiado ({{ $worker['kilos_pescado_fiado'] }} kg)
                            </span>
                            <span class="font-bold {{ $worker['descuento_pescado'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                {{ $worker['descuento_pescado'] > 0 ? '-$' . number_format($worker['descuento_pescado'], 0, ',', '.') : '$0' }}
                            </span>
                        </div>
                    </div>

                    <!-- Total Neto Destacado -->
                    <div class="p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-800">Neto a Liquidar:</span>
                        <span class="text-base font-black text-emerald-600">${{ number_format($worker['total_neto'], 0, ',', '.') }}</span>
                    </div>
                </div>
            @empty
                <div class="text-center py-6 text-slate-400 text-xs">
                    No existen jornales pendientes para liquidar en la semana con corte al {{ $cutoffDateStr }}.
                </div>
            @endforelse

            @if($temporalesSummary->isNotEmpty())
                <div class="p-3.5 rounded-xl bg-slate-900 text-white space-y-1.5 shadow-md">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Desembolso Semanal:</span>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-300">Bruto Jornales:</span>
                        <span class="font-bold">${{ number_format($totalGross, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-rose-400">Deducción Pescado:</span>
                        <span class="font-bold text-rose-400">-${{ number_format($totalDeductions, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm font-black text-emerald-400 pt-1.5 border-t border-slate-800">
                        <span>Total Neto a Pagar:</span>
                        <span>${{ number_format($totalNet, 0, ',', '.') }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Tabla 2: Trabajadores Fijos Excluidos (Informativa) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-user-lock text-slate-500"></i>
                    Trabajadores Fijos (Excluidos de la Paga de Sábado)
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Participan en labores de cosecha (ej. rayadores de mesa), pero perciben un salario fijo independiente.
                </p>
            </div>
            <span class="text-xs px-2.5 py-1 rounded-full font-bold bg-slate-100 text-slate-600 border border-slate-200">
                {{ $fijosSummary->count() }} Empleados Fijos
            </span>
        </div>

        <!-- Desktop Table Fijos -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-2.5 px-4">Empleado Fijo</th>
                        <th class="py-2.5 px-4">Cédula</th>
                        <th class="py-2.5 px-4 text-center">Días de Apoyo</th>
                        <th class="py-2.5 px-4">Labores Realizadas</th>
                        <th class="py-2.5 px-4 text-center">Paga de Sábado</th>
                        <th class="py-2.5 px-4">Motivo de Exclusión</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($fijosSummary as $fijo)
                        <tr class="hover:bg-slate-50/80 transition duration-150">
                            <td class="py-2.5 px-4 font-bold text-slate-800">{{ $fijo['worker_name'] }}</td>
                            <td class="py-2.5 px-4 text-slate-500 font-mono">{{ $fijo['worker_id_card'] ?? 'No registrada' }}</td>
                            <td class="py-2.5 px-4 text-center font-semibold">{{ $fijo['dias_trabajados'] }} d</td>
                            <td class="py-2.5 px-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($fijo['labores'] as $labType => $count)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 capitalize">
                                            {{ str_replace('_', ' ', $labType) }} ({{ $count }})
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-2.5 px-4 text-center font-bold text-slate-400">$0 (Excluido)</td>
                            <td class="py-2.5 px-4 text-slate-500 text-[11px]">
                                <span class="inline-flex items-center gap-1 text-purple-700 bg-purple-50 px-2 py-0.5 rounded-full font-semibold border border-purple-200">
                                    <i class="fa-solid fa-circle-info"></i> Salario Fijo Mensual Independiente
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-slate-400 text-xs">
                                No hubo trabajadores fijos participando en jornales de apoyo en este periodo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards Fijos -->
        <div class="md:hidden space-y-2.5">
            @forelse($fijosSummary as $fijo)
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs text-slate-800">{{ $fijo['worker_name'] }}</span>
                        <span class="text-[10px] font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-full border border-purple-200">
                            Fijo Mensual
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                        <span>CC: {{ $fijo['worker_id_card'] ?? 'N/A' }}</span>
                        <span>{{ $fijo['dias_trabajados'] }} días de apoyo</span>
                    </div>
                    <div class="text-[10px] text-slate-400 flex items-center justify-between pt-1 border-t border-slate-200/50">
                        <span>Paga sábado: $0</span>
                        <span>Excluido de destajo semanal</span>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-slate-400 text-xs">
                    No hubo trabajadores fijos participando en jornales de apoyo.
                </div>
            @endforelse
        </div>
    </div>

    <!-- ==================== MODAL DE CONFIRMACIÓN DE LIQUIDACIÓN ==================== -->
    <div x-show="settleModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
         x-cloak>

        <div @click.away="settleModalOpen = false"
             class="w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">

            <div class="bg-gradient-to-r from-slate-900 to-indigo-950 p-4 text-white flex items-center justify-between">
                <h3 class="font-bold text-sm flex items-center gap-2">
                    <i class="fa-solid fa-cash-register text-indigo-400"></i> Confirmar Cierre y Liquidación
                </h3>
                <button @click="settleModalOpen = false" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div class="bg-indigo-50/60 p-3.5 rounded-xl border border-indigo-100 text-xs text-indigo-900 space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Fecha de corte:</span>
                        <strong class="font-bold text-slate-800">{{ $cutoffDateStr }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Jornaleros a liquidar:</span>
                        <strong class="font-bold text-slate-800">{{ $temporalesSummary->count() }} trabajadores</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Total deducciones pescado:</span>
                        <strong class="font-bold text-rose-600">-${{ number_format($totalDeductions, 0, ',', '.') }}</strong>
                    </div>
                    <div class="flex justify-between border-t border-indigo-200 pt-1.5 text-sm">
                        <span class="font-bold text-slate-800">Total Neto a Desembolsar:</span>
                        <strong class="font-black text-emerald-600">${{ number_format($totalNet, 0, ',', '.') }}</strong>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Notas u Observaciones del Cierre</label>
                    <textarea x-model="notes" rows="2" placeholder="Ej: Liquidación semanal por cosecha y empaque estanque 1..."
                              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="settleModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                        Cancelar
                    </button>
                    <button type="button" @click="executeSettlement()" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs px-4 py-2 rounded-xl transition shadow">
                        Liquidar y Emitir Comprobante
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    async function executeSettlement() {
        try {
            const res = await fetch('{{ route('web.nomina.settle') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    cutoff_date: '{{ $cutoffDateStr }}',
                    notes: document.querySelector('textarea')?.value || null
                })
            });

            if (res.ok) {
                alert('¡Nómina semanal del sábado liquidada y cerrada exitosamente!');
                window.location.reload();
            } else {
                const data = await res.json();
                alert(data.message || 'Error al procesar la liquidación.');
            }
        } catch (e) {
            alert('Error de conexión al procesar la liquidación.');
        }
    }
</script>
@endsection
