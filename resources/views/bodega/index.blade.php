@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Inventario Físico de Concentrados en Bodega</h1>
            <p class="text-sm text-slate-500 mt-1">Control de bultos, pesaje en kilos y autonomía proyectada de alimentación.</p>
        </div>
        @if(auth()->user()?->isPropietario() || auth()->user()?->isOwner() || auth()->user()?->hasRole(['propietario', 'owner', 'jefe_mayor']))
        <div class="flex items-center gap-3">
            <button type="button" onclick="document.getElementById('modalDespacho').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-semibold rounded-md shadow-sm text-white bg-cyan-700 hover:bg-cyan-800 focus:outline-none">
                <i class="fa-solid fa-truck-ramp-box mr-2"></i>
                Despacho de Alimento a Finca
            </button>
            <button type="button" onclick="document.getElementById('modalEntrada').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 border border-slate-300 text-sm font-medium rounded-md shadow-sm text-slate-700 bg-white hover:bg-slate-50 focus:outline-none">
                <i class="fa-solid fa-plus mr-1.5 text-slate-400"></i>
                Recepción Inmediata
            </button>
        </div>
        @endif
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

    <!-- Despachos en Tránsito / Pendientes de Recepción (Flujo de Dos Pasos) -->
    @if(isset($despachosPendientes) && $despachosPendientes->isNotEmpty())
    <div class="bg-amber-50/60 border border-amber-300 rounded-lg shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-amber-200 bg-amber-100/70 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-truck-fast text-amber-700 text-lg"></i>
                <div>
                    <h2 class="text-base font-bold text-amber-900">Despachos en Tránsito (Pendientes de Confirmación Física en Bodega)</h2>
                    <p class="text-xs text-amber-700">El alimento no estará disponible para suministrar a estanques hasta que el Administrador cuente los bultos y confirme.</p>
                </div>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-extrabold bg-amber-200 text-amber-900 border border-amber-300">
                {{ $despachosPendientes->count() }} por recibir
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-amber-200 text-sm">
                <thead class="bg-amber-50/90 text-amber-900 text-xs font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3 text-left">Fecha Despacho</th>
                        <th class="px-5 py-3 text-left">Proveedor</th>
                        <th class="px-5 py-3 text-left">Concentrado</th>
                        <th class="px-5 py-3 text-right">Bultos Despachados</th>
                        <th class="px-5 py-3 text-right">Kilos en Tránsito</th>
                        <th class="px-5 py-3 text-center">Estado</th>
                        <th class="px-5 py-3 text-center">Acción (Administrador)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-amber-200/80 bg-white">
                    @foreach($despachosPendientes as $despacho)
                        <tr class="hover:bg-amber-50/30">
                            <td class="px-5 py-3.5 text-slate-800 font-medium whitespace-nowrap">
                                {{ $despacho->fecha?->format('d/m/Y') ?? now()->format('d/m/Y') }}
                            </td>
                            <td class="px-5 py-3.5 text-slate-700 whitespace-nowrap">
                                {{ $despacho->proveedor ?? 'Proveedor Central' }}
                            </td>
                            <td class="px-5 py-3.5 font-bold text-slate-900 whitespace-nowrap">
                                {{ $despacho->alimento?->nombre_concentrado ?? 'Concentrado' }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-extrabold text-slate-900 whitespace-nowrap">
                                {{ number_format($despacho->cantidad_bultos, 1) }} bultos
                            </td>
                            <td class="px-5 py-3.5 text-right font-extrabold text-slate-900 whitespace-nowrap">
                                {{ number_format($despacho->cantidad_kilos, 1) }} kg
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                    <i class="fa-solid fa-clock-rotate-left mr-1.5 text-amber-600"></i>
                                    En Tránsito / Pendiente
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                @if(auth()->user()?->isAdmin() || auth()->user()?->isPropietario() || auth()->user()?->hasRole(['administrador', 'admin', 'propietario', 'owner', 'jefe_mayor']))
                                <form method="POST" action="{{ route('admin.bodega.despachos.confirmar', $despacho->id) }}" onsubmit="return confirm('¿Confirmas que contaste físicamente {{ $despacho->cantidad_bultos }} bultos y autorizas el ingreso a bodega?');" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-xs font-bold shadow-xs transition">
                                        <i class="fa-solid fa-check mr-1.5"></i>
                                        Confirmar Recepción en Bodega
                                    </button>
                                </form>
                                @else
                                <span class="text-xs text-slate-400 italic">Esperando confirmación del Administrador</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

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

<!-- Modal Despacho de Alimento a Finca (Exclusivo Propietario) -->
<div id="modalDespacho" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-lg w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Despacho de Alimento a Finca</h3>
                <p class="text-xs text-slate-500">Registro de bultos despachados. Queda en tránsito hasta ser confirmado físicamente en bodega.</p>
            </div>
            <button type="button" onclick="document.getElementById('modalDespacho').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.bodega.despacho') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Fecha Despacho</label>
                    <input type="date" name="fecha_despacho" value="{{ date('Y-m-d') }}" required class="w-full border-slate-300 rounded-md shadow-sm text-sm focus:ring-slate-900 focus:border-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Proveedor / Fábrica</label>
                    <input type="text" name="proveedor" required placeholder="Italcol, Solla, etc." class="w-full border-slate-300 rounded-md shadow-sm text-sm focus:ring-slate-900 focus:border-slate-900">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Tipo de Concentrado (% Proteína / Etapa)</label>
                <input type="text" name="tipo_concentrado" list="listaConcentradosDespacho" required placeholder="Ej: Iniciación 45%, Levante 34%, Engorde 30%" class="w-full border-slate-300 rounded-md shadow-sm text-sm focus:ring-slate-900 focus:border-slate-900">
                <datalist id="listaConcentradosDespacho">
                    @foreach($alimentos as $a)
                        <option value="{{ $a->nombre_concentrado }}">
                    @endforeach
                    <option value="Iniciación 45%">
                    <option value="Levante 34%">
                    <option value="Engorde 32%">
                    <option value="Engorde 30%">
                </datalist>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Cantidad de Bultos</label>
                    <input type="number" step="0.5" min="0.5" name="cantidad_bultos" required placeholder="Ej: 30" class="w-full border-slate-300 rounded-md shadow-sm text-sm focus:ring-slate-900 focus:border-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Peso por Bulto (kg)</label>
                    <input type="number" step="0.1" min="1" name="peso_bulto_kg" value="40.0" required class="w-full border-slate-300 rounded-md shadow-sm text-sm focus:ring-slate-900 focus:border-slate-900">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Número de Lote de Fábrica</label>
                <input type="text" name="lote_fabrica" placeholder="Ej: LOT-ITAL-2026-99" class="w-full border-slate-300 rounded-md shadow-sm text-sm focus:ring-slate-900 focus:border-slate-900">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Observaciones</label>
                <input type="text" name="observaciones" placeholder="Ej: Remisión despacho camión #1" class="w-full border-slate-300 rounded-md shadow-sm text-sm focus:ring-slate-900 focus:border-slate-900">
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="document.getElementById('modalDespacho').classList.add('hidden')" class="px-4 py-2 text-sm text-slate-700 hover:text-slate-900">Cancelar</button>
                <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-cyan-700 rounded-md hover:bg-cyan-800 shadow-sm">Registrar Despacho en Tránsito</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Entrada de Concentrado Físico (Exclusivo Propietario) -->
<div id="modalEntrada" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-lg w-full p-6 shadow-xl border border-slate-200">
        <h3 class="text-lg font-bold text-slate-900 mb-1">Recepción de Alimento en Bodega</h3>
        <p class="text-xs text-slate-500 mb-4">Registro estrictamente físico y logístico de bultos y pesaje. Sin costos ni valores monetarios.</p>
        <form method="POST" action="{{ route('admin.inventario-alimento.ingresar') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Fecha Recepción</label>
                    <input type="date" name="fecha_recepcion" value="{{ date('Y-m-d') }}" required class="w-full border-slate-300 rounded-md shadow-sm text-sm focus:ring-slate-900 focus:border-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Proveedor / Fábrica</label>
                    <input type="text" name="proveedor" required placeholder="Italcol, Solla, etc." class="w-full border-slate-300 rounded-md shadow-sm text-sm focus:ring-slate-900 focus:border-slate-900">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Tipo de Concentrado (% Proteína / Etapa)</label>
                <input type="text" name="tipo_concentrado" list="listaConcentrados" required placeholder="Ej: Iniciación 45%, Levante 34%, Engorde 30%" class="w-full border-slate-300 rounded-md shadow-sm text-sm focus:ring-slate-900 focus:border-slate-900">
                <datalist id="listaConcentrados">
                    @foreach($alimentos as $a)
                        <option value="{{ $a->nombre_concentrado }}">
                    @endforeach
                    <option value="Iniciación 45%">
                    <option value="Levante 34%">
                    <option value="Engorde 32%">
                    <option value="Engorde 30%">
                </datalist>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Bultos Recibidos</label>
                    <input type="number" step="0.5" min="0.5" name="bultos_recibidos" required placeholder="Ej: 25" class="w-full border-slate-300 rounded-md shadow-sm text-sm focus:ring-slate-900 focus:border-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Peso por Bulto (kg)</label>
                    <input type="number" step="0.1" min="1" name="peso_bulto_kg" value="40.0" required class="w-full border-slate-300 rounded-md shadow-sm text-sm focus:ring-slate-900 focus:border-slate-900">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Número de Lote de Fábrica</label>
                <input type="text" name="lote_fabrica" placeholder="Ej: LOT-ITAL-2026-88" class="w-full border-slate-300 rounded-md shadow-sm text-sm focus:ring-slate-900 focus:border-slate-900">
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="document.getElementById('modalEntrada').classList.add('hidden')" class="px-4 py-2 text-sm text-slate-700 hover:text-slate-900">Cancelar</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-md hover:bg-slate-800">Registrar Llegada</button>
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
