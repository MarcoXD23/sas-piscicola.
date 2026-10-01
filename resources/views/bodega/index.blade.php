@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Inventario Físico de Concentrados en Bodega</h1>
            <p class="text-sm text-slate-500 mt-1">Control de bultos, pesaje en kilos y autonomía proyectada de alimentación.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="document.getElementById('modalEntrada').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-slate-900 hover:bg-slate-800 focus:outline-none">
                <svg class="w-4 h-4 mr-1.5 stroke-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Recepción de Camión (Entrada)
            </button>
        </div>
    </div>

    <!-- Métricas -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Bultos en Stock</span>
            <div class="text-3xl font-extrabold text-slate-900 mt-2">{{ number_format($totalBultos, 1) }} <span class="text-sm font-normal text-slate-500">bultos</span></div>
        </div>
        <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Masa Total Almacenada</span>
            <div class="text-3xl font-extrabold text-slate-900 mt-2">{{ number_format($totalKilos, 1) }} <span class="text-sm font-normal text-slate-500">kg</span></div>
        </div>
        <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Alertas de Reabastecimiento</span>
            <div class="text-3xl font-extrabold {{ $alertasStock > 0 ? 'text-amber-600' : 'text-slate-900' }} mt-2">{{ $alertasStock }} <span class="text-sm font-normal text-slate-500">críticas</span></div>
        </div>
    </div>

    <!-- Tabla de Alimentos en Bodega -->
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/75">
            <h2 class="text-base font-semibold text-slate-800">Lotes de Concentrado y Autonomía</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3 text-left">Concentrado</th>
                        <th class="px-5 py-3 text-center">Proteína</th>
                        <th class="px-5 py-3 text-center">Peso Bulto</th>
                        <th class="px-5 py-3 text-right">Stock (Bultos)</th>
                        <th class="px-5 py-3 text-right">Stock (Kilos)</th>
                        <th class="px-5 py-3 text-center">Autonomía</th>
                        <th class="px-5 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($alimentos as $item)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-3.5 font-medium text-slate-900">{{ $item->nombre_concentrado }}</td>
                            <td class="px-5 py-3.5 text-center text-slate-600">{{ $item->proteina_porcentaje }}%</td>
                            <td class="px-5 py-3.5 text-center text-slate-600">{{ number_format($item->peso_bulto_kg, 1) }} kg</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-slate-900">{{ number_format($item->stock_bultos, 1) }}</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-slate-900">{{ number_format($item->stock_kilos_actual, 1) }} kg</td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item->isBajoStock() ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-800' }}">
                                    {{ $item->diasAutonomia() > 90 ? '> 90 días' : round($item->diasAutonomia(), 1) . ' días' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <button type="button" onclick="abrirModalAjuste({{ $item->id }}, '{{ $item->nombre_concentrado }}', {{ $item->stock_bultos }})" class="text-xs text-slate-700 hover:text-slate-900 underline font-medium">Ajustar Físico</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-slate-500">No hay concentrados registrados en bodega.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Historial de Movimientos -->
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/75">
            <h2 class="text-base font-semibold text-slate-800">Últimos Movimientos de Bodega</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3 text-left">Fecha</th>
                        <th class="px-5 py-3 text-left">Tipo Movimiento</th>
                        <th class="px-5 py-3 text-left">Concentrado</th>
                        <th class="px-5 py-3 text-right">Bultos</th>
                        <th class="px-5 py-3 text-right">Kilos</th>
                        <th class="px-5 py-3 text-left">Detalle / Proveedor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($movimientos as $mov)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-3 text-slate-600">{{ $mov->fecha?->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $mov->tipo_movimiento === 'entrada_compra' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($mov->tipo_movimiento === 'salida_alimentacion' ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                    {{ str_replace('_', ' ', strtoupper($mov->tipo_movimiento)) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $mov->alimento?->nombre_concentrado }}</td>
                            <td class="px-5 py-3 text-right font-medium text-slate-900">{{ number_format($mov->cantidad_bultos, 1) }}</td>
                            <td class="px-5 py-3 text-right font-medium text-slate-900">{{ number_format($mov->cantidad_kilos, 1) }} kg</td>
                            <td class="px-5 py-3 text-slate-600 text-xs">{{ $mov->proveedor ?? $mov->observacion ?? $mov->observaciones ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-6 text-center text-slate-500">Sin movimientos recientes en bodega.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Entrada de Camión -->
<div id="modalEntrada" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-lg w-full p-6 shadow-xl border border-slate-200">
        <h3 class="text-lg font-bold text-slate-900 mb-4">Recepción de Camión / Compra</h3>
        <form method="POST" action="{{ route('admin.bodega.entrada') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Concentrado</label>
                <select name="alimento_id" required class="w-full border-slate-300 rounded-md shadow-sm text-sm focus:ring-slate-900 focus:border-slate-900">
                    @foreach($alimentos as $a)
                        <option value="{{ $a->id }}">{{ $a->nombre_concentrado }} (Actual: {{ $a->stock_bultos }} bultos)</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Cantidad Bultos</label>
                    <input type="number" step="0.5" min="0.5" name="cantidad_bultos" required class="w-full border-slate-300 rounded-md shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Costo Unitario Bulto</label>
                    <input type="number" step="100" min="0" name="costo_unitario_bulto" placeholder="125000" class="w-full border-slate-300 rounded-md shadow-sm text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Proveedor</label>
                <input type="text" name="proveedor" placeholder="Italcol, Solla, etc." class="w-full border-slate-300 rounded-md shadow-sm text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Observaciones</label>
                <textarea name="observaciones" rows="2" class="w-full border-slate-300 rounded-md shadow-sm text-sm"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="document.getElementById('modalEntrada').classList.add('hidden')" class="px-4 py-2 text-sm text-slate-700 hover:text-slate-900">Cancelar</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-md hover:bg-slate-800">Registrar Entrada</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ajuste Físico -->
<div id="modalAjuste" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-xl border border-slate-200">
        <h3 class="text-lg font-bold text-slate-900 mb-2">Ajuste de Conteo Físico</h3>
        <p id="ajusteNombreConcentrado" class="text-sm text-slate-500 mb-4"></p>
        <form id="formAjuste" method="POST" action="" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nuevo Stock Real (Bultos)</label>
                <input type="number" step="0.5" min="0" id="ajusteStockBultos" name="stock_bultos" required class="w-full border-slate-300 rounded-md shadow-sm text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Motivo del Ajuste (Merma / Conteo)</label>
                <textarea name="motivo" required placeholder="Bultos rotos, humedad, conteo físico" rows="3" class="w-full border-slate-300 rounded-md shadow-sm text-sm"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="document.getElementById('modalAjuste').classList.add('hidden')" class="px-4 py-2 text-sm text-slate-700 hover:text-slate-900">Cancelar</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-md hover:bg-slate-800">Guardar Ajuste</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalAjuste(id, nombre, stock) {
        document.getElementById('ajusteNombreConcentrado').textContent = nombre + ' (Stock registrado actual: ' + stock + ' bultos)';
        document.getElementById('ajusteStockBultos').value = stock;
        document.getElementById('formAjuste').action = '/admin/bodega/' + id + '/ajuste';
        document.getElementById('modalAjuste').classList.remove('hidden');
    }
</script>
@endsection
