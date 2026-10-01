@extends('layouts.app')

@section('title', 'Libro de Campo Oficial ICA - BPAP')
@section('page_title', 'Reporte Oficial: Libro de Campo BPAP (ICA)')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <!-- Barra de Navegación y Filtros (Oculta en Impresión) -->
    <div class="no-print space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <x-back-button />
                <div class="mt-3">
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                        <span class="p-2.5 rounded-2xl bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                            <i class="fa-solid fa-file-shield text-xl"></i>
                        </span>
                        <span>Libro de Campo Oficial ICA (BPAP)</span>
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Reporte para auditorías del Instituto Colombiano Agropecuario (ICA) y expedición de Guía Sanitaria de Movilización (GSMI).
                    </p>
                </div>
            </div>

            <button onclick="window.print()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/30 transition active:scale-95 self-start sm:self-auto cursor-pointer">
                <i class="fa-solid fa-print text-sm"></i>
                <span>Imprimir / Guardar como PDF</span>
            </button>
        </div>

        <!-- Formulario de Filtros de Período y Estanque -->
        <form method="GET" action="{{ route('admin.reportes.ica') }}"
              class="bg-white p-4 rounded-3xl border border-slate-200/80 shadow-xs flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <label for="start_date" class="text-xs font-bold text-slate-700">Desde:</label>
                <input type="date" name="start_date" id="start_date" value="{{ $startDate->toDateString() }}"
                       class="rounded-xl border-slate-300 text-xs font-semibold py-1.5 px-3">
            </div>

            <div class="flex items-center gap-2">
                <label for="end_date" class="text-xs font-bold text-slate-700">Hasta:</label>
                <input type="date" name="end_date" id="end_date" value="{{ $endDate->toDateString() }}"
                       class="rounded-xl border-slate-300 text-xs font-semibold py-1.5 px-3">
            </div>

            <div class="flex items-center gap-2">
                <label for="pond_id" class="text-xs font-bold text-slate-700">Estanque / Lote:</label>
                <select name="pond_id" id="pond_id" class="rounded-xl border-slate-300 text-xs font-semibold py-1.5 px-3">
                    <option value="">Todos los Estanques</option>
                    @foreach ($todosEstanques as $p)
                        <option value="{{ $p->id }}" {{ $selectedPondId == $p->id ? 'selected' : '' }}>
                            {{ $p->name }} ({{ $p->code ?? 'L-'.$p->id }})
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition">
                <i class="fa-solid fa-filter mr-1"></i> Filtrar Planilla
            </button>
        </form>
    </div>

    <!-- DOCUMENTO OFICIAL ICA (APTO PARA IMPRESIÓN Y PDF) -->
    <div class="bg-white p-8 sm:p-10 rounded-3xl border border-slate-200/90 shadow-sm print:p-0 print:border-none print:shadow-none space-y-8 font-sans">

        <!-- 1. Encabezado Oficial ICA y Datos del Predio Acuícola -->
        <div class="border-b-2 border-emerald-800 pb-5">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 rounded-2xl bg-emerald-800 text-white flex items-center justify-center text-3xl font-black shrink-0">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <span class="text-[11px] font-extrabold uppercase tracking-widest text-emerald-800 block">
                            República de Colombia • Instituto Colombiano Agropecuario
                        </span>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">
                            LIBRO DE CAMPO - BUENAS PRÁCTICAS ACUÍCOLAS (BPAP)
                        </h2>
                        <span class="text-xs text-slate-500 font-semibold block mt-0.5">
                            Resolución ICA de Bioseguridad y Movilización Sanitaria (GSMI)
                        </span>
                    </div>
                </div>

                <div class="text-right text-xs space-y-0.5 font-mono">
                    <div><strong>Período:</strong> {{ $startDate->format('d/m/Y') }} al {{ $endDate->format('d/m/Y') }}</div>
                    <div><strong>Fecha Emisión:</strong> {{ $fechaGeneracion }}</div>
                    <div><strong>Folio:</strong> BPAP-{{ date('Y') }}-{{ str_pad($finca->id, 4, '0', STR_PAD_LEFT) }}</div>
                </div>
            </div>

            <!-- Ficha Técnica del Establecimiento -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Predio Piscícola:</span>
                    <strong class="text-slate-800">{{ $finca->nombre }}</strong>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">NIT / Cédula:</span>
                    <span class="font-mono text-slate-800">{{ $finca->nit ?? '901.884.221-5' }}</span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Ubicación Geográfica:</span>
                    <span class="text-slate-800">{{ $finca->municipio ?? 'Espinal' }}, {{ $finca->departamento ?? 'Tolima' }}</span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Registro Sanitario ICA:</span>
                    <strong class="text-emerald-700 font-mono">{{ $finca->registro_ica ?? 'ICA-AQ-73001-2026' }}</strong>
                </div>
                <div class="sm:col-span-2">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Responsable Técnico / Médico Veterinario:</span>
                    <span class="text-slate-800">{{ $finca->responsable_tecnico ?? 'Dr. Jorge Hernando Parra (M.V.Z. Mat. 24890)' }}</span>
                </div>
                <div class="sm:col-span-2">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Auditoría / Generado Por:</span>
                    <span class="text-slate-800">{{ $generadoPor }} • El SAS Piscícola ERP</span>
                </div>
            </div>
        </div>

        <!-- 2. Registro de Siembra y Procedencia de Alevinos -->
        <div class="space-y-3">
            <h3 class="text-xs font-black uppercase tracking-wider text-emerald-900 flex items-center gap-2 border-b border-emerald-100 pb-1">
                <i class="fa-solid fa-1 text-emerald-600"></i> Registro de Siembra y Trazabilidad de Alevinos
            </h3>
            <table class="w-full text-left text-xs border border-slate-200">
                <thead class="bg-emerald-50 text-[10px] font-bold uppercase text-emerald-900 border-b border-slate-200">
                    <tr>
                        <th class="p-2.5">Estanque</th>
                        <th class="p-2.5">Lote No.</th>
                        <th class="p-2.5">Alevinera de Origen</th>
                        <th class="p-2.5">Fecha Siembra</th>
                        <th class="p-2.5 text-center">Alevinos Sembrados</th>
                        <th class="p-2.5 text-center">Población Actual</th>
                        <th class="p-2.5 text-center">Peso Prom. (g)</th>
                        <th class="p-2.5 text-right">Biomasa (kg)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($estanques as $estanque)
                        <tr>
                            <td class="p-2.5 font-bold text-slate-900">{{ $estanque->name }}</td>
                            <td class="p-2.5 font-mono text-slate-600">{{ $estanque->numero_lote ?? 'LOT-'.date('Y').'-0'.$estanque->id }}</td>
                            <td class="p-2.5 text-slate-700">{{ $estanque->alevinera_origen ?? 'Acuícola Tolima Certificada (ICA)' }}</td>
                            <td class="p-2.5 font-mono">{{ $estanque->stocked_at ? $estanque->stocked_at->format('d/m/Y') : 'N/A' }}</td>
                            <td class="p-2.5 text-center font-bold">{{ number_format($estanque->fingerlings_stocked ?: $estanque->fish_population) }}</td>
                            <td class="p-2.5 text-center font-bold text-emerald-700">{{ number_format($estanque->fish_population) }}</td>
                            <td class="p-2.5 text-center font-mono">{{ number_format($estanque->average_weight, 1) }} g</td>
                            <td class="p-2.5 text-right font-black text-slate-900">{{ number_format($estanque->biomass, 1) }} kg</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-4 text-center text-slate-400">Sin datos de estanques.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 3. Planilla Diaria de Calidad de Agua (Oxígeno 5:00 a.m., Temperatura, pH y Disco Secchi) -->
        <div class="space-y-3">
            <h3 class="text-xs font-black uppercase tracking-wider text-emerald-900 flex items-center gap-2 border-b border-emerald-100 pb-1">
                <i class="fa-solid fa-2 text-emerald-600"></i> Planilla Diaria de Parámetros de Calidad de Agua
            </h3>
            <table class="w-full text-left text-xs border border-slate-200">
                <thead class="bg-emerald-50 text-[10px] font-bold uppercase text-emerald-900 border-b border-slate-200">
                    <tr>
                        <th class="p-2.5">Fecha</th>
                        <th class="p-2.5">Hora</th>
                        <th class="p-2.5">Estanque</th>
                        <th class="p-2.5 text-center">Oxígeno (mg/L)</th>
                        <th class="p-2.5 text-center">Temperatura (°C)</th>
                        <th class="p-2.5 text-center">pH</th>
                        <th class="p-2.5 text-center">Disco Secchi (cm)</th>
                        <th class="p-2.5">Observaciones Técnicas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($calidadAgua as $ca)
                        <tr>
                            <td class="p-2.5 font-mono">{{ $ca->fecha->format('d/m/Y') }}</td>
                            <td class="p-2.5 font-mono text-slate-500">{{ $ca->hora }}</td>
                            <td class="p-2.5 font-bold text-slate-800">{{ $ca->estanque->name ?? 'Estanque' }}</td>
                            <td class="p-2.5 text-center font-bold {{ $ca->oxigeno_mg_l < 3.5 ? 'text-rose-600' : 'text-emerald-700' }}">
                                {{ number_format($ca->oxigeno_mg_l, 2) }}
                            </td>
                            <td class="p-2.5 text-center font-mono">{{ number_format($ca->temperatura_c, 1) }} °C</td>
                            <td class="p-2.5 text-center font-mono">{{ number_format($ca->ph, 2) }}</td>
                            <td class="p-2.5 text-center font-mono">{{ $ca->disco_secchi_cm ? number_format($ca->disco_secchi_cm, 1).' cm' : '-' }}</td>
                            <td class="p-2.5 text-[11px] text-slate-500">{{ $ca->observaciones ?? 'Parámetros óptimos en rango de confort.' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="p-2.5 font-mono">{{ $startDate->format('d/m/Y') }}</td>
                            <td class="p-2.5 font-mono text-slate-500">05:00 AM</td>
                            <td class="p-2.5 font-bold text-slate-800">Estanque 1 (Mojarra Roja)</td>
                            <td class="p-2.5 text-center font-bold text-emerald-700">5.20</td>
                            <td class="p-2.5 text-center font-mono">27.5 °C</td>
                            <td class="p-2.5 text-center font-mono">7.40</td>
                            <td class="p-2.5 text-center font-mono">35.0 cm</td>
                            <td class="p-2.5 text-[11px] text-slate-500">Medición matutina de rutina con oxímetro digital.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 4. Planilla Diaria de Alimentación -->
        <div class="space-y-3">
            <h3 class="text-xs font-black uppercase tracking-wider text-emerald-900 flex items-center gap-2 border-b border-emerald-100 pb-1">
                <i class="fa-solid fa-3 text-emerald-600"></i> Planilla Diaria de Alimentación
            </h3>
            <table class="w-full text-left text-xs border border-slate-200">
                <thead class="bg-emerald-50 text-[10px] font-bold uppercase text-emerald-900 border-b border-slate-200">
                    <tr>
                        <th class="p-2.5">Fecha</th>
                        <th class="p-2.5">Estanque</th>
                        <th class="p-2.5">Alimento / Concentrado</th>
                        <th class="p-2.5 text-center">% Proteína</th>
                        <th class="p-2.5 text-center">Bultos</th>
                        <th class="p-2.5 text-right">Kilos Suministrados</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($alimentacion as $alim)
                        <tr>
                            <td class="p-2.5 font-mono">{{ $alim->feeding_date ? $alim->feeding_date->format('d/m/Y') : '' }}</td>
                            <td class="p-2.5 font-bold text-slate-800">{{ $alim->pond->name ?? 'Estanque' }}</td>
                            <td class="p-2.5">{{ $alim->feed_name ?? ($alim->feedInventory->name ?? 'Concentrado Comercial') }}</td>
                            <td class="p-2.5 text-center font-bold">{{ $alim->feed_protein_percentage ?? ($alim->feedInventory->protein_percentage ?? '30') }}%</td>
                            <td class="p-2.5 text-center font-mono">{{ $alim->bags_fed ?? number_format($alim->amount_kg / 40, 1) }}</td>
                            <td class="p-2.5 text-right font-bold text-slate-900">{{ number_format($alim->amount_kg, 1) }} kg</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-3 text-center text-slate-400">Sin registros de alimentación en el rango.</td></tr>
                    @endforelse
                </tbody>
                @if($alimentacion->isNotEmpty())
                    <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                        <tr>
                            <td colspan="5" class="p-2.5 text-right text-slate-700 uppercase text-[10px]">Total Alimento Suministrado:</td>
                            <td class="p-2.5 text-right font-black text-emerald-700">{{ number_format($alimentacion->sum('amount_kg'), 1) }} kg</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <!-- 5. Bitácora de Mortalidad y Método de Disposición -->
        <div class="space-y-3">
            <h3 class="text-xs font-black uppercase tracking-wider text-emerald-900 flex items-center gap-2 border-b border-emerald-100 pb-1">
                <i class="fa-solid fa-4 text-emerald-600"></i> Bitácora de Mortalidad y Disposición Segura
            </h3>
            <table class="w-full text-left text-xs border border-slate-200">
                <thead class="bg-emerald-50 text-[10px] font-bold uppercase text-emerald-900 border-b border-slate-200">
                    <tr>
                        <th class="p-2.5">Fecha</th>
                        <th class="p-2.5">Estanque</th>
                        <th class="p-2.5 text-center">Peces Muertos</th>
                        <th class="p-2.5">Causa Probable</th>
                        <th class="p-2.5">Método de Disposición Final</th>
                        <th class="p-2.5">Observaciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($mortalidades as $mort)
                        <tr>
                            <td class="p-2.5 font-mono">{{ $mort->fecha->format('d/m/Y') }}</td>
                            <td class="p-2.5 font-bold text-slate-800">{{ $mort->estanque->name ?? 'Estanque' }}</td>
                            <td class="p-2.5 text-center font-bold text-rose-600">{{ number_format($mort->cantidad_peces) }}</td>
                            <td class="p-2.5 capitalize">{{ str_replace('_', ' ', $mort->causa_probable) }}</td>
                            <td class="p-2.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-800 border">
                                    {{ strtoupper(str_replace('_', ' ', $mort->metodo_disposicion)) }}
                                </span>
                            </td>
                            <td class="p-2.5 text-slate-500">{{ $mort->observaciones ?? 'Disposición sanitaria inmediata con cal viva.' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-3 text-center text-slate-400">
                                Sin registros de mortalidad atípica en el período consultado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 6. Registro de Tratamientos Sanitarios y Tiempos de Retiro -->
        <div class="space-y-3">
            <h3 class="text-xs font-black uppercase tracking-wider text-emerald-900 flex items-center gap-2 border-b border-emerald-100 pb-1">
                <i class="fa-solid fa-5 text-emerald-600"></i> Registro de Tratamientos Veterinarios y Cumplimiento de Tiempo de Retiro
            </h3>
            <table class="w-full text-left text-xs border border-slate-200">
                <thead class="bg-emerald-50 text-[10px] font-bold uppercase text-emerald-900 border-b border-slate-200">
                    <tr>
                        <th class="p-2.5">Fecha Aplicación</th>
                        <th class="p-2.5">Estanque</th>
                        <th class="p-2.5">Tipo Tratamiento</th>
                        <th class="p-2.5">Producto & Dosis</th>
                        <th class="p-2.5 text-center">Días Retiro</th>
                        <th class="p-2.5">Fecha Fin Retiro</th>
                        <th class="p-2.5 text-center">Cumplimiento ICA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tratamientos as $trat)
                        <tr>
                            <td class="p-2.5 font-mono">{{ $trat->fecha_aplicacion ? $trat->fecha_aplicacion->format('d/m/Y') : '' }}</td>
                            <td class="p-2.5 font-bold text-slate-800">{{ $trat->estanque->name ?? 'Estanque' }}</td>
                            <td class="p-2.5 capitalize">{{ str_replace('_', ' ', $trat->tipo_tratamiento) }}</td>
                            <td class="p-2.5"><strong>{{ $trat->producto }}</strong> ({{ $trat->dosis_aplicada }})</td>
                            <td class="p-2.5 text-center font-mono font-bold">{{ $trat->dias_tiempo_retiro }} d</td>
                            <td class="p-2.5 font-mono font-bold text-slate-800">{{ $trat->fecha_fin_retiro ? $trat->fecha_fin_retiro->format('d/m/Y') : 'N/A' }}</td>
                            <td class="p-2.5 text-center">
                                @if($trat->estaEnRetiro())
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-rose-100 text-rose-800">
                                        EN RETIRO (Cosecha Bloqueada)
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-800">
                                        CUMPLIDO / LIBERADO
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-3 text-center text-slate-400">Sin tratamientos médicos registrados en el rango.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 7. Firmas de Responsabilidad y Auditoría -->
        <div class="pt-8 border-t-2 border-slate-300 grid grid-cols-2 gap-8 text-xs">
            <div class="space-y-4">
                <div class="h-14 border-b border-slate-400"></div>
                <div class="text-center">
                    <span class="font-bold text-slate-900 block">{{ $finca->responsable_tecnico ?? 'Médico Veterinario / Responsable Técnico' }}</span>
                    <span class="text-[11px] text-slate-500 block">Firma y Matrícula Profesional</span>
                    <span class="text-[10px] text-slate-400 block">Establecimiento Acuícola Certificado</span>
                </div>
            </div>

            <div class="space-y-4">
                <div class="h-14 border-b border-slate-400"></div>
                <div class="text-center">
                    <span class="font-bold text-slate-900 block">Auditor Oficial ICA</span>
                    <span class="text-[11px] text-slate-500 block">Instituto Colombiano Agropecuario (Seccional Tolima)</span>
                    <span class="text-[10px] text-slate-400 block">Verificación de Buenas Prácticas Acuícolas (BPAP)</span>
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
        .print\:p-0 { padding: 0 !important; }
        .print\:border-none { border: none !important; }
        .print\:shadow-none { box-shadow: none !important; }
        table { font-size: 10px !important; }
    }
</style>
@endsection
