@extends('layouts.app')

@section('title', 'Informe Financiero Mensual de Rentabilidad')
@section('page_title', 'Informe Ejecutivo Mensual de Rentabilidad (Dueño / Jefe Mayor)')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Navegación y Encabezado con Botón Atrás (Oculto en Impresión) -->
    <div class="no-print space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <x-back-button />
                <div class="mt-3">
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                        <span class="p-2.5 rounded-2xl bg-amber-500/10 text-amber-600 border border-amber-500/20">
                            <i class="fa-solid fa-chart-line text-xl"></i>
                        </span>
                        <span>Cierre Financiero & Rentabilidad Mensual</span>
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Consolidado exclusivo para el Dueño / Jefe Mayor: ingresos, FCA, costos de concentrado, nómina, energía y utilidad neta real.
                    </p>
                </div>
            </div>

            <button onclick="window.print()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold shadow-lg shadow-amber-600/30 transition active:scale-95 self-start sm:self-auto cursor-pointer">
                <i class="fa-solid fa-print text-sm"></i>
                <span>Imprimir / Exportar PDF</span>
            </button>
        </div>

        <!-- Selector de Mes -->
        <form method="GET" action="{{ route('jefe.reporte_mensual') }}"
              class="bg-white p-4 rounded-3xl border border-slate-200/80 shadow-xs flex flex-wrap items-center gap-3">
            <label for="month" class="text-xs font-bold text-slate-700">Mes de Consulta:</label>
            <input type="month" name="month" id="month" value="{{ $monthStr }}"
                   class="rounded-xl border-slate-300 text-xs font-semibold py-1.5 px-3">
            <button type="submit"
                    class="px-4 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Generar Cierre Financiero
            </button>
        </form>
    </div>

    <!-- DOCUMENTO EJECUTIVO (PANTALLA E IMPRESIÓN) -->
    <div class="space-y-6 print:space-y-4">

        <!-- 1. Banner Principal de Utilidad Neta Real -->
        <div class="rounded-3xl p-6 sm:p-8 bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 text-white shadow-xl border border-slate-800">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        Cierre Económico {{ $startOfMonth->translatedFormat('F Y') }} • {{ $finca->nombre }}
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black tracking-tight text-white mt-2">
                        ${{ number_format($utilidadNetaReal, 0, ',', '.') }} COP
                    </h2>
                    <p class="text-xs text-slate-300 mt-1">
                        Utilidad Neta Real del mes • Margen de Rentabilidad Operativa:
                        <strong class="text-emerald-400 font-mono">{{ $margenRentabilidadPorcentaje }}%</strong>
                    </p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 border-t md:border-t-0 md:border-l border-slate-800 md:pl-6 pt-4 md:pt-0">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Ingreso Bruto:</span>
                        <strong class="text-lg font-black text-emerald-400">${{ number_format($ingresoBrutoTotal, 0, ',', '.') }}</strong>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Costos Totales:</span>
                        <strong class="text-lg font-black text-rose-400">${{ number_format($costosOperativos['total'], 0, ',', '.') }}</strong>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Costo x Kg Producido:</span>
                        <strong class="text-lg font-black text-amber-300">${{ number_format($costoPorKiloProducido, 0, ',', '.') }}/kg</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Grid de KPIs Técnicos y Financieros -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- FCA Global -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">FCA Promedio Global</span>
                <div class="flex items-center justify-between mt-1">
                    <h3 class="text-2xl font-black text-indigo-600">{{ number_format($fcaGlobal, 2) }}</h3>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $fcaGlobal <= 1.4 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                        {{ $fcaGlobal <= 1.4 ? 'Excelente' : 'Ajustar Ración' }}
                    </span>
                </div>
                <p class="text-[11px] text-slate-400 mt-2">Kg de concentrado por cada kg de carne</p>
            </div>

            <!-- Kilos Cosechados -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Kilos Cosechados (Báscula)</span>
                <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($kilosCosechadosTotal, 1) }} <span class="text-sm font-medium text-slate-400">kg</span></h3>
                <p class="text-[11px] text-slate-400 mt-2">Peso limpio neto pesado en el mes</p>
            </div>

            <!-- Kilos Vendidos -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Kilos Totales Vendidos</span>
                <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($kilosVendidosTotal, 1) }} <span class="text-sm font-medium text-slate-400">kg</span></h3>
                <p class="text-[11px] text-slate-400 mt-2">Despachos mayoristas y caja diaria</p>
            </div>

            <!-- Alimento Suministrado -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Alimento Consumido</span>
                <h3 class="text-2xl font-black text-cyan-600 mt-1">{{ number_format($kilosAlimentoTotal, 1) }} <span class="text-sm font-medium text-slate-400">kg</span></h3>
                <p class="text-[11px] text-slate-400 mt-2">{{ number_format($kilosAlimentoTotal / 40, 0) }} bultos de concentrado</p>
            </div>
        </div>

        <!-- 3. Desglose de Ingresos y Canales de Venta -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Card: Canales de Venta -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-cash-register text-emerald-600"></i> Desglose de Ingresos Comerciales
                    </h3>
                    <span class="text-xs font-bold text-emerald-600">${{ number_format($ingresoBrutoTotal, 0, ',', '.') }}</span>
                </div>

                <div class="space-y-3">
                    <div class="p-3.5 rounded-2xl bg-cyan-50/40 border border-cyan-100 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-900 text-xs block">Ventas a Visitantes (Portería / Caja Rápida)</span>
                            <span class="text-[11px] text-slate-500">{{ number_format($desgloseVentas['visitantes']['kilos'], 1) }} kg vendidos a $9.000/kg</span>
                        </div>
                        <span class="font-black text-slate-900 text-sm">${{ number_format($desgloseVentas['visitantes']['total'], 0, ',', '.') }}</span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-indigo-50/40 border border-indigo-100 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-900 text-xs block">Intermediarios / Mayoristas (Despachos en Camión)</span>
                            <span class="text-[11px] text-slate-500">{{ number_format($desgloseVentas['mayoristas']['kilos'], 1) }} kg despachados en báscula</span>
                        </div>
                        <span class="font-black text-slate-900 text-sm">${{ number_format($desgloseVentas['mayoristas']['total'], 0, ',', '.') }}</span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-amber-50/40 border border-amber-100 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-900 text-xs block">Descuento de Pescado a Trabajadores (Nómina)</span>
                            <span class="text-[11px] text-slate-500">{{ number_format($desgloseVentas['trabajadores']['kilos'], 1) }} kg fiados a $7.000/kg</span>
                        </div>
                        <span class="font-black text-slate-900 text-sm">${{ number_format($desgloseVentas['trabajadores']['total'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Card: Estructura de Costos Operativos Directos -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-receipt text-rose-600"></i> Estructura de Costos Directos del Mes
                    </h3>
                    <span class="text-xs font-bold text-rose-600">${{ number_format($costosOperativos['total'], 0, ',', '.') }}</span>
                </div>

                <div class="space-y-3">
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-900 text-xs block">1. Concentrado & Alimento Balanceado</span>
                            <span class="text-[11px] text-slate-500">{{ number_format($kilosAlimentoTotal, 1) }} kg @ $4.800/kg promedio</span>
                        </div>
                        <span class="font-black text-slate-900 text-sm">${{ number_format($costosOperativos['alimento'], 0, ',', '.') }}</span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-900 text-xs block">2. Mano de Obra (Jornales de Destajo y Cosechas)</span>
                            <span class="text-[11px] text-slate-500">Nómina semanal de sábados y labores operativas</span>
                        </div>
                        <span class="font-black text-slate-900 text-sm">${{ number_format($costosOperativos['nomina'], 0, ',', '.') }}</span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-900 text-xs block">3. Energía Eléctrica & Combustible de Aireadores</span>
                            <span class="text-[11px] text-slate-500">{{ number_format($horasAireadores, 1) }} horas de operación nocturna/auxiliar</span>
                        </div>
                        <span class="font-black text-slate-900 text-sm">${{ number_format($costosOperativos['energia'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Estilos para Impresión Limpia (PDF) -->
<style>
    @media print {
        body { background: white !important; font-size: 11px !important; }
        .no-print, header, aside, nav, footer, #gemini-chat-widget, button { display: none !important; }
        main { padding: 0 !important; margin: 0 !important; background: white !important; }
    }
</style>
@endsection
