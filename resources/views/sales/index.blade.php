@extends('layouts.app')

@section('title', 'Caja Diaria y Ventas de Pescado')
@section('page_title', 'Módulo de Ventas de Pescado y Caja Diaria')

@section('content')
@php
    $fincaActiva = auth()->user()->finca_segura ?? null;
    $precioVisitante = $fincaActiva ? $fincaActiva->obtenerConfig('precios.pescado_visitante_kg', 9000) : 9000;
    $precioEmpleado = $fincaActiva ? $fincaActiva->obtenerConfig('precios.pescado_empleado_kg', 7000) : 7000;
@endphp
<div class="space-y-6" x-data="salesBox({{ $precioVisitante }}, {{ $precioEmpleado }})">
    <!-- Componente reactivo: salesBox() -->

    <!-- Navegación Superior: Botón Volver al Dashboard -->
    <div class="flex items-center justify-between">
        <x-back-button />
        <span class="text-xs text-slate-500 font-medium hidden sm:inline">Ventas & Caja • Tarifas y Arqueo</span>
    </div>

    <!-- Selector de Fecha y Resumen -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-cash-register text-emerald-600"></i>
                Caja Diaria de Ventas con Tarifas Diferenciadas
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                Tarifa preferencial para <strong>Trabajador Interno (${{ number_format($precioEmpleado, 0, ',', '.') }}/kg)</strong> y tarifa comercial para <strong>Visitante Externo (${{ number_format($precioVisitante, 0, ',', '.') }}/kg)</strong>.
            </p>
        </div>

        <form method="GET" action="{{ route('ventas.index') }}" class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-600">Fecha de Caja:</label>
            <input type="date" name="date" value="{{ $selectedDate }}"
                   class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs px-3.5 py-1.5 rounded-xl transition duration-200 flex items-center gap-1.5 shadow-sm">
                <i class="fa-solid fa-calendar-day"></i> Ver Caja
            </button>
        </form>
    </div>

    <!-- KPI Cards de Balance del Día -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Kilos Vendidos -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Kilos Vendidos</p>
            <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalKilos, 1) }} <span class="text-sm font-medium text-slate-400">kg</span></h3>
            <span class="text-[11px] text-slate-400">Pescado fresco despachado</span>
        </div>

        <!-- 2. Total Dinero Recaudado -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Dinero Recaudado</p>
            <h3 class="text-2xl font-black text-emerald-600 mt-1">${{ number_format($totalCash, 0, ',', '.') }}</h3>
            <span class="text-[11px] text-slate-400">Efectivo, banco y nómina</span>
        </div>

        <!-- 3. Ventas Visitantes -->
        <div class="bg-white rounded-2xl p-4 border border-cyan-100 shadow-sm bg-cyan-50/20">
            <p class="text-xs font-semibold text-cyan-800 uppercase tracking-wider">Visitantes (${{ number_format($precioVisitante) }}/kg)</p>
            <h3 class="text-2xl font-black text-cyan-700 mt-1">${{ number_format($visitorTotal, 0, ',', '.') }}</h3>
            <span class="text-[11px] text-cyan-600">{{ number_format($visitorKilos, 1) }} kg vendidos a externos</span>
        </div>

        <!-- 4. Ventas Trabajadores -->
        <div class="bg-white rounded-2xl p-4 border border-indigo-100 shadow-sm bg-indigo-50/20">
            <p class="text-xs font-semibold text-indigo-800 uppercase tracking-wider">Trabajadores (${{ number_format($precioEmpleado) }}/kg)</p>
            <h3 class="text-2xl font-black text-indigo-700 mt-1">${{ number_format($workerTotal, 0, ',', '.') }}</h3>
            <span class="text-[11px] text-indigo-600">{{ number_format($workerKilos, 1) }} kg con tarifa interna</span>
        </div>
    </div>

    <!-- Grid: Formulario de Nueva Venta + Tabla de Transacciones -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Formulario de Registro de Venta (1 Columna) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-cart-plus text-emerald-600"></i> Registrar Venta en Caja
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Calcula automáticamente el total según el tipo de cliente.</p>
            </div>

            <form @submit.prevent="submitSale()" class="space-y-3.5">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tipo de Cliente *</label>
                    <select x-model="customerType" @change="updatePrice()"
                            class="w-full min-h-[44px] bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-semibold">
                        <option value="visitante">Visitante Externo (${{ number_format($precioVisitante, 0, ',', '.') }} / kg)</option>
                        <option value="trabajador">Trabajador Interno (${{ number_format($precioEmpleado, 0, ',', '.') }} / kg)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nombre del Comprador</label>
                    <input type="text" x-model="customerName" placeholder="Ej: Pedro Gómez o Doña María"
                           class="w-full min-h-[44px] bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kilos Vendidos *</label>
                        <input type="number" step="0.1" x-model.number="kilosSold" required placeholder="Ej: 3.5"
                               class="w-full min-h-[44px] bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Precio por Kilo</label>
                        <input type="number" x-model.number="pricePerKg" readonly
                               class="w-full min-h-[44px] bg-slate-100 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-600 font-bold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Método de Pago *</label>
                    <select x-model="paymentMethod"
                            class="w-full min-h-[44px] bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-semibold">
                        <option value="efectivo">Efectivo en Caja</option>
                        <option value="transferencia">Transferencia Bancaria (Nequi/Daviplata)</option>
                        <option value="descuento_nomina" x-show="customerType === 'trabajador'">Pescado Fiado (Descuento de Nómina Sábado)</option>
                    </select>
                </div>

                <!-- Total Calculado en Vivo -->
                <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-100 flex items-center justify-between">
                    <span class="text-xs font-bold text-emerald-800">Total a Cobrar:</span>
                    <span class="text-xl font-black text-emerald-600" x-text="'$' + calculatedTotal.toLocaleString('es-CO')">$0</span>
                </div>

                <button type="submit" :disabled="calculatedTotal <= 0"
                        class="w-full min-h-[44px] bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold text-xs py-3 rounded-xl transition duration-200 flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/20 active:scale-98">
                    <i class="fa-solid fa-check"></i> Registrar Venta en Caja
                </button>
            </form>
        </div>

        <!-- Tabla de Ventas Registradas (2 Columnas) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-cyan-600"></i> Detalle de Ventas del Día ({{ $selectedDate }})
                </h3>
                <span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-slate-100 text-slate-600">
                    {{ $sales->count() }} transacciones
                </span>
            </div>

            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-[10px] tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-3">Cliente</th>
                            <th class="py-3 px-3">Categoría</th>
                            <th class="py-3 px-3 text-right">Kilos</th>
                            <th class="py-3 px-3 text-right">Precio/kg</th>
                            <th class="py-3 px-3 text-right">Total</th>
                            <th class="py-3 px-3 text-center">Método Pago</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($sales as $sale)
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="py-2.5 px-3 font-semibold text-slate-800">{{ $sale->customer_name ?? 'Cliente Mostrador' }}</td>
                                <td class="py-2.5 px-3">
                                    @if($sale->customer_type === 'trabajador')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">Trabajador</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-700 border border-cyan-200">Visitante</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-bold text-slate-900">{{ number_format($sale->kilos_sold, 1) }} kg</td>
                                <td class="py-2.5 px-3 text-right text-slate-500">${{ number_format($sale->price_per_kg, 0, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-right font-bold text-emerald-600">${{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 capitalize">
                                        {{ str_replace('_', ' ', $sale->payment_method) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400">
                                    <i class="fa-solid fa-basket-shopping text-3xl mb-2 text-slate-300"></i>
                                    <p class="text-sm">No hay ventas registradas en la caja para esta fecha.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden space-y-2.5">
                @forelse($sales as $sale)
                    <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/60 shadow-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs text-slate-900">{{ $sale->customer_name ?? 'Cliente Mostrador' }}</span>
                            @if($sale->customer_type === 'trabajador')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">Trabajador</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-700 border border-cyan-200">Visitante</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between text-xs text-slate-600">
                            <span>{{ number_format($sale->kilos_sold, 1) }} kg × ${{ number_format($sale->price_per_kg, 0, ',', '.') }}</span>
                            <span class="font-black text-emerald-600 text-sm">${{ number_format($sale->total_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="text-[10px] text-slate-400 flex items-center justify-between pt-1 border-t border-slate-200/50">
                            <span>Pago: <strong class="text-slate-600 capitalize">{{ str_replace('_', ' ', $sale->payment_method) }}</strong></span>
                            <span>{{ $sale->created_at ? $sale->created_at->format('g:i A') : '' }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6 text-slate-400 text-xs">
                        No hay ventas registradas en la caja para esta fecha.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>

<script>
    function salesBox(defaultVisitorPrice = 9000, defaultWorkerPrice = 7000) {
        return {
            visitorPrice: defaultVisitorPrice,
            workerPrice: defaultWorkerPrice,
            customerType: 'visitante',
            customerName: '',
            kilosSold: 1.0,
            pricePerKg: defaultVisitorPrice,
            paymentMethod: 'efectivo',

            updatePrice() {
                this.pricePerKg = this.customerType === 'trabajador' ? this.workerPrice : this.visitorPrice;
                if (this.customerType !== 'trabajador' && this.paymentMethod === 'descuento_nomina') {
                    this.paymentMethod = 'efectivo';
                }
            },

            get calculatedTotal() {
                return (this.kilosSold || 0) * this.pricePerKg;
            },

            async submitSale() {
                try {
                    const res = await fetch('{{ route('web.ventas.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            customer_type: this.customerType,
                            customer_name: this.customerName || null,
                            kilos_sold: this.kilosSold,
                            price_per_kg: this.pricePerKg,
                            payment_method: this.paymentMethod
                        })
                    });

                    if (res.ok) {
                        alert('¡Venta de pescado registrada exitosamente en caja diaria!');
                        window.location.reload();
                    } else {
                        const data = await res.json();
                        alert(data.message || 'Error al registrar la venta.');
                    }
                } catch (e) {
                    alert('Error de conexión.');
                }
            }
        };
    }
</script>
@endsection
