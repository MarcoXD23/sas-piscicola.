@extends('layouts.app')

@section('title', 'Báscula y Cosechas')
@section('page_title', 'Módulo de Báscula Digital y Despacho')

@section('content')
@php
    $taraCanastilla = auth()->user()->finca_segura?->obtenerConfig('operacion.peso_tara_canastilla_kg', 2.0) ?? 2.0;
    $taraCanasta = auth()->user()->finca_segura?->obtenerConfig('operacion.peso_tara_canasta_kg', 1.8) ?? 1.8;
@endphp

<div class="space-y-6" x-data="weighingCalculator({{ $taraCanastilla }}, {{ $taraCanasta }})">
    <!-- Componente reactivo: weighingCalculator() -->

    <!-- Navegación Superior: Botón Volver al Dashboard -->
    <div class="flex items-center justify-between">
        <x-back-button />
        <span class="text-xs text-slate-500 font-medium hidden sm:inline">Operaciones & Cosecha • Báscula por Tandas y Despacho</span>
    </div>

    <!-- Header y Configuración de Tara por Tipo de Recipiente -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-scale-balanced text-teal-600"></i>
                Calculadora de Báscula: Bruto a Limpio por Canastillas y Canastas
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                Registra múltiples pesadas sucesivas con diferentes recipientes. Selecciona el tipo (Canastilla o Canasta) o escribe directamente la tara exacta con el teclado.
            </p>
        </div>

        <!-- Parámetros Rápidos de Tara -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                <span class="text-xs font-bold text-slate-700">Tara Canastilla:</span>
                <input type="number" step="0.05" x-model.number="defaultTaraCanastilla" @change="updateDefaults('canastilla')"
                       class="w-16 bg-white border border-slate-300 rounded px-1.5 py-0.5 text-xs font-bold text-slate-800 text-center focus:outline-none focus:ring-2 focus:ring-teal-500">
                <span class="text-xs font-bold text-slate-500">kg</span>
            </div>

            <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                <span class="text-xs font-bold text-slate-700">Tara Canasta:</span>
                <input type="number" step="0.05" x-model.number="defaultTaraCanasta" @change="updateDefaults('canasta')"
                       class="w-16 bg-white border border-slate-300 rounded px-1.5 py-0.5 text-xs font-bold text-slate-800 text-center focus:outline-none focus:ring-2 focus:ring-teal-500">
                <span class="text-xs font-bold text-slate-500">kg</span>
            </div>
        </div>
    </div>

    <!-- KPI Cards en Tiempo Real (Bruto, Unidades, Tara Descontada, Peso Limpio) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Kilos Brutos -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Kilos Brutos</p>
            <h3 class="text-2xl font-black text-slate-900 mt-1" x-text="totalGrossKg.toFixed(2) + ' kg'">0.00 kg</h3>
            <span class="text-[11px] text-slate-400">Sumatoria pesadas en báscula</span>
        </div>

        <!-- 2. Total Recipientes Utilizados -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Recipientes Utilizados</p>
            <h3 class="text-2xl font-black text-cyan-600 mt-1" x-text="totalBaskets + ' uds'">0 uds</h3>
            <span class="text-[11px] text-slate-500 truncate block" x-text="tareBreakdownText">0 canastillas</span>
        </div>

        <!-- 3. Descuento Tara Canastillas y Canastas (Rojo Semántico) -->
        <div class="bg-white rounded-2xl p-4 border border-rose-200 shadow-sm bg-rose-50/20">
            <p class="text-xs font-semibold text-rose-700 uppercase tracking-wider">Descuento Tara Canastillas</p>
            <h3 class="text-2xl font-black text-rose-600 mt-1" x-text="'-' + totalTareKg.toFixed(2) + ' kg'">-0.00 kg</h3>
            <span class="text-[11px] text-rose-600 font-semibold truncate block" x-text="tareBreakdownText">0 canastas descontadas</span>
        </div>

        <!-- 4. Peso Limpio Real (Verde Semántico) -->
        <div class="bg-white rounded-2xl p-4 border border-emerald-300 shadow-sm bg-emerald-50/30">
            <p class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">Peso Limpio Real a Despacho</p>
            <h3 class="text-2xl font-black text-emerald-600 mt-1" x-text="realCleanKg.toFixed(2) + ' kg'">0.00 kg</h3>
            <span class="text-[11px] text-emerald-600 font-semibold">Peso neto garantizado al comprador</span>
        </div>
    </div>

    <!-- Notificación Flash Toast de Despacho -->
    <div x-show="toastMessage"
         x-transition
         class="p-4 rounded-2xl text-xs font-bold border shadow-md flex items-center justify-between"
         :class="toastType === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'"
         style="display: none;">
        <span class="flex items-center gap-2">
            <i :class="toastType === 'success' ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-triangle-exclamation text-rose-600'"></i>
            <span x-text="toastMessage"></span>
        </span>
        <button type="button" @click="toastMessage = ''" class="text-slate-400 hover:text-slate-700">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Tabla Reactiva de Tandas de Pesadas -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 flex-wrap gap-2">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-list-ol text-cyan-600"></i>
                Sesión de Pesadas de Cosecha
            </h3>
            <div class="flex items-center gap-2">
                <button @click="resetBatches()" type="button" class="text-xs font-semibold text-slate-500 hover:text-slate-700 px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 transition">
                    <i class="fa-solid fa-arrow-rotate-left mr-1"></i> Reiniciar
                </button>
                <button @click="addBatch()" type="button" class="bg-teal-600 hover:bg-teal-500 text-white font-semibold text-xs px-3 py-1.5 rounded-xl transition duration-200 flex items-center gap-1.5 shadow-sm cursor-pointer">
                    <i class="fa-solid fa-plus"></i> Añadir Pesada / Tanda
                </button>
            </div>
        </div>

        <!-- Desktop Table (Pesadas) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-3"># Tanda</th>
                        <th class="py-3 px-3">Peso Bruto Báscula (kg)</th>
                        <th class="py-3 px-3">Tipo / Tara Unitaria (kg)</th>
                        <th class="py-3 px-3 text-center">Cantidad (uds)</th>
                        <th class="py-3 px-3 text-right">Tara Total (kg)</th>
                        <th class="py-3 px-3 text-right">Subtotal Limpio (kg)</th>
                        <th class="py-3 px-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="(batch, index) in batches" :key="index">
                        <tr class="hover:bg-slate-50/80 transition duration-150">
                            <td class="py-3 px-3 font-bold text-slate-900" x-text="'Pesada #' + (index + 1)"></td>

                            <!-- 1. Peso Bruto -->
                            <td class="py-3 px-3">
                                <div class="relative w-32">
                                    <input type="number" step="0.1" min="0" x-model.number="batch.gross_kg" placeholder="0.0"
                                           class="w-full bg-slate-50 border border-slate-300 rounded-lg pl-2.5 pr-7 py-1.5 text-xs text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-teal-500">
                                    <span class="absolute inset-y-0 right-2 flex items-center text-[10px] font-bold text-slate-400">kg</span>
                                </div>
                            </td>

                            <!-- 2. Tipo / Tara Unitaria (Dropdown + Campo Editable) -->
                            <td class="py-3 px-3">
                                <div class="flex items-center gap-1.5">
                                    <!-- Selector de Tipo -->
                                    <select x-model="batch.container_type" @change="onContainerTypeChange(batch)"
                                            class="bg-slate-50 border border-slate-300 rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-500 cursor-pointer">
                                        <option value="canastilla">Canastilla</option>
                                        <option value="canasta">Canasta</option>
                                        <option value="personalizado">Personalizada</option>
                                    </select>

                                    <!-- Campo numérico editable con teclado -->
                                    <div class="relative w-20">
                                        <input type="number" step="0.05" min="0" x-model.number="batch.unit_tare"
                                               title="Peso de tara unitario editable con teclado"
                                               class="w-full bg-white border border-slate-300 rounded-lg pl-2 pr-6 py-1.5 text-xs text-slate-900 font-mono font-bold text-center focus:outline-none focus:ring-2 focus:ring-teal-500">
                                        <span class="absolute inset-y-0 right-1.5 flex items-center text-[10px] font-bold text-slate-400">kg</span>
                                    </div>
                                </div>
                            </td>

                            <!-- 3. Cantidad de Unidades -->
                            <td class="py-3 px-3 text-center">
                                <input type="number" min="0" step="1" x-model.number="batch.baskets" placeholder="0"
                                       class="w-20 bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 font-bold text-center focus:outline-none focus:ring-2 focus:ring-teal-500">
                            </td>

                            <!-- 4. Tara Total Dinámica -->
                            <td class="py-3 px-3 text-right font-mono font-semibold text-rose-600"
                                x-text="((batch.baskets || 0) * (batch.unit_tare || 0)).toFixed(2) + ' kg'">
                            </td>

                            <!-- 5. Subtotal Limpio Dinámico -->
                            <td class="py-3 px-3 text-right font-mono font-black text-emerald-600 text-sm"
                                x-text="Math.max(0, (batch.gross_kg || 0) - ((batch.baskets || 0) * (batch.unit_tare || 0))).toFixed(2) + ' kg'">
                            </td>

                            <!-- Acciones -->
                            <td class="py-3 px-3 text-center">
                                <button @click="removeBatch(index)" type="button"
                                        class="text-rose-500 hover:text-rose-700 p-1.5 rounded-lg hover:bg-rose-50 transition" title="Eliminar tanda">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>

                <!-- Totales de la Tabla -->
                <tfoot class="bg-slate-50 font-bold border-t-2 border-slate-200">
                    <tr>
                        <td class="py-3 px-3 uppercase text-[10px] text-slate-500">Total Sesión:</td>
                        <td class="py-3 px-3 text-slate-900" x-text="totalGrossKg.toFixed(2) + ' kg'"></td>
                        <td class="py-3 px-3 text-xs text-slate-500 italic" x-text="tareBreakdownText"></td>
                        <td class="py-3 px-3 text-center text-cyan-700" x-text="totalBaskets + ' uds'"></td>
                        <td class="py-3 px-3 text-right text-rose-600" x-text="'-' + totalTareKg.toFixed(2) + ' kg'"></td>
                        <td class="py-3 px-3 text-right text-emerald-700 text-base font-black" x-text="realCleanKg.toFixed(2) + ' kg'"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Mobile Cards (Pesadas en Campo con Celular / Tablet) -->
        <div class="md:hidden space-y-3">
            <template x-for="(batch, index) in batches" :key="index">
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 shadow-xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs text-slate-900 flex items-center gap-1.5">
                            <i class="fa-solid fa-scale-balanced text-teal-600"></i>
                            <span x-text="'Pesada #' + (index + 1)"></span>
                        </span>
                        <button @click="removeBatch(index)" type="button"
                                class="min-h-[44px] min-w-[44px] text-rose-500 hover:text-rose-700 flex items-center justify-center p-2 rounded-lg hover:bg-rose-50 transition" title="Eliminar">
                            <i class="fa-regular fa-trash-can text-sm"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Bruto Báscula (kg)</label>
                            <input type="number" step="0.1" min="0" x-model.number="batch.gross_kg" placeholder="0.0"
                                   class="w-full min-h-[44px] bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Cantidad (uds)</label>
                            <input type="number" min="0" step="1" x-model.number="batch.baskets" placeholder="0"
                                   class="w-full min-h-[44px] bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-teal-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Tipo de Recipiente</label>
                            <select x-model="batch.container_type" @change="onContainerTypeChange(batch)"
                                    class="w-full min-h-[44px] bg-white border border-slate-300 rounded-xl px-2 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-500">
                                <option value="canastilla">Canastilla</option>
                                <option value="canasta">Canasta</option>
                                <option value="personalizado">Personalizada</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Tara Unit. (kg editable)</label>
                            <input type="number" step="0.05" min="0" x-model.number="batch.unit_tare"
                                   class="w-full min-h-[44px] bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 font-mono font-bold text-center focus:outline-none focus:ring-2 focus:ring-teal-500">
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1 border-t border-slate-200/60 text-xs">
                        <span class="text-rose-600 text-[11px]">
                            Tara: <strong x-text="((batch.baskets || 0) * (batch.unit_tare || 0)).toFixed(2) + ' kg'"></strong>
                        </span>
                        <span class="text-emerald-700 font-black text-sm">
                            Limpio: <span x-text="Math.max(0, (batch.gross_kg || 0) - ((batch.baskets || 0) * (batch.unit_tare || 0))).toFixed(2) + ' kg'"></span>
                        </span>
                    </div>
                </div>
            </template>
        </div>

        <!-- Botón de Abrir Modal de Despacho -->
        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <span class="text-xs text-slate-500 text-center sm:text-left">
                Una vez completadas las tandas de pesaje, confirma los datos para registrar el despacho en el historial.
            </span>
            <button @click="dispatchModalOpen = true" :disabled="realCleanKg <= 0"
                    class="w-full sm:w-auto min-h-[44px] bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold text-xs px-5 py-3 rounded-xl transition duration-200 flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/20 active:scale-98 cursor-pointer">
                <i class="fa-solid fa-truck-fast"></i> Registrar Despacho Final (<span x-text="realCleanKg.toFixed(1) + ' kg'"></span>)
            </button>
        </div>
    </div>

    <!-- ==================== MODAL DE DESPACHO (ALPINE.JS) ==================== -->
    <div x-show="dispatchModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
         x-cloak>

        <div @click.away="dispatchModalOpen = false"
             class="w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">

            <div class="bg-gradient-to-r from-slate-900 to-teal-900 p-4 text-white flex items-center justify-between">
                <h3 class="font-bold text-sm flex items-center gap-2">
                    <i class="fa-solid fa-truck-fast text-teal-400"></i> Despacho y Remisión de Pescado
                </h3>
                <button @click="dispatchModalOpen = false" class="text-slate-400 hover:text-white cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form @submit.prevent="submitDispatch()" class="p-5 space-y-4">
                <!-- Resumen de Cosecha & Tara -->
                <div class="grid grid-cols-3 gap-2 p-3 bg-emerald-50 rounded-xl border border-emerald-100 text-center">
                    <div>
                        <span class="text-[10px] text-emerald-800 font-bold uppercase block">Kilos Limpios</span>
                        <span class="text-base font-black text-emerald-600" x-text="realCleanKg.toFixed(2) + ' kg'"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-emerald-800 font-bold uppercase block">Total Recipientes</span>
                        <span class="text-base font-black text-cyan-700" x-text="totalBaskets + ' uds'"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-rose-800 font-bold uppercase block">Tara Descontada</span>
                        <span class="text-base font-black text-rose-600" x-text="'-' + totalTareKg.toFixed(2) + ' kg'"></span>
                    </div>
                </div>

                <!-- Detalle de recipientes -->
                <p class="text-[11px] text-slate-500 bg-slate-50 p-2 rounded-lg border border-slate-200">
                    <strong>Composición de recipientes:</strong> <span x-text="tareBreakdownText"></span>
                </p>

                <!-- Selección de Estanque -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Estanque Cosechado *</label>
                    <select x-model="dispatchForm.pond_id" required
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">-- Seleccionar Estanque --</option>
                        @foreach($ponds as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code ?? 'L-'.$p->id }}) • Biomasa: {{ number_format($p->biomass, 1) }} kg</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nombre del Conductor / Transportador *</label>
                    <input type="text" x-model="dispatchForm.driver_name" required placeholder="Ej: Don Humberto Camargo"
                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Cédula del Conductor</label>
                        <input type="text" x-model="dispatchForm.driver_id_card" placeholder="Ej: 93.456.789"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Placa del Vehículo *</label>
                        <input type="text" x-model="dispatchForm.vehicle_plate" required placeholder="Ej: TZR-890"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Rumbo / Destino *</label>
                        <input type="text" x-model="dispatchForm.destination" required placeholder="Ej: Corabastos Bogotá"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Comprador / Mayorista</label>
                        <input type="text" x-model="dispatchForm.buyer_name" placeholder="Ej: Pescadería El Dorado"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Observaciones</label>
                    <textarea x-model="dispatchForm.observations" rows="2" placeholder="Notas sobre calidad, hielo, temperatura..."
                              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="dispatchModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="isSubmitting"
                            class="bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition shadow flex items-center gap-1.5 cursor-pointer">
                        <i x-show="!isSubmitting" class="fa-solid fa-check"></i>
                        <i x-show="isSubmitting" class="fa-solid fa-circle-notch fa-spin"></i>
                        <span x-text="isSubmitting ? 'Guardando...' : 'Confirmar y Guardar Despacho'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== HISTORIAL DE ÓRDENES Y DESPACHOS ==================== -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 flex-wrap gap-2">
            <div>
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-clipboard-check text-cyan-600"></i>
                    Historial de Despachos y Pesajes Registrados
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Detalle de órdenes de cosecha, pesaje por báscula digital y remisiones de transporte emitidas.
                </p>
            </div>
            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-xl">
                {{ $harvestOrders->count() }} registros
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-3">Fecha / ID</th>
                        <th class="py-3 px-3">Estanque</th>
                        <th class="py-3 px-3 text-right">Peso Bruto</th>
                        <th class="py-3 px-3 text-center">Recipientes & Tara</th>
                        <th class="py-3 px-3 text-right">Peso Limpio Final</th>
                        <th class="py-3 px-3">Transporte / Destino</th>
                        <th class="py-3 px-3 text-center">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($harvestOrders as $order)
                        <tr class="hover:bg-slate-50/80 transition duration-150">
                            <td class="py-3 px-3">
                                <span class="font-bold text-slate-900 block font-mono">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</span>
                                <span class="text-[11px] text-slate-400">{{ $order->scheduled_date ? $order->scheduled_date->format('d/m/Y') : '-' }}</span>
                            </td>
                            <td class="py-3 px-3">
                                <span class="font-bold text-slate-800">{{ $order->pond->name ?? 'Estanque' }}</span>
                                <span class="block text-[10px] text-slate-400">{{ $order->buyer_name ?? 'Mayorista' }}</span>
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-semibold text-slate-900">
                                {{ $order->gross_weight_kg ? number_format($order->gross_weight_kg, 1, ',', '.') . ' kg' : '-' }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if($order->baskets_count)
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-700 border border-cyan-100">
                                        {{ $order->baskets_count }} uds
                                    </span>
                                    <span class="block text-[10px] font-semibold text-rose-600 mt-0.5">
                                        -{{ number_format($order->total_tare_kg ?? ($order->baskets_count * ($order->basket_tare_kg ?: 2.0)), 1) }} kg tara
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-black text-emerald-600 text-sm">
                                {{ $order->clean_weight_kg ? number_format($order->clean_weight_kg, 1, ',', '.') . ' kg' : ($order->net_weight_kg ? number_format($order->net_weight_kg, 1, ',', '.') . ' kg' : number_format($order->estimated_kg, 1, ',', '.') . ' kg') }}
                            </td>
                            <td class="py-3 px-3">
                                @if($order->driver_name)
                                    <span class="font-semibold text-slate-800 block">{{ $order->driver_name }}</span>
                                    <span class="text-[10px] text-slate-500 font-mono">{{ $order->driver_vehicle_plate }} • {{ $order->destination }}</span>
                                @else
                                    <span class="text-slate-400 italic">Sin despacho registrado</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                                    {{ $order->status === 'despachada' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                                       ($order->status === 'pesaje_completado' ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-400">
                                No hay órdenes de cosecha registradas actualmente.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Alpine Component Script -->
<script>
    function weighingCalculator(defaultCanastilla = 2.0, defaultCanasta = 1.8) {
        return {
            defaultTaraCanastilla: defaultCanastilla,
            defaultTaraCanasta: defaultCanasta,
            dispatchModalOpen: false,
            isSubmitting: false,
            toastMessage: '',
            toastType: 'success',

            // Tandas iniciales de pesada
            batches: [
                { gross_kg: 180.5, container_type: 'canastilla', unit_tare: defaultCanastilla, baskets: 5 },
                { gross_kg: 165.0, container_type: 'canasta', unit_tare: defaultCanasta, baskets: 4 },
                { gross_kg: 195.2, container_type: 'canastilla', unit_tare: defaultCanastilla, baskets: 5 }
            ],

            dispatchForm: {
                pond_id: '{{ $ponds->first()?->id ?? "" }}',
                driver_name: '',
                driver_id_card: '',
                vehicle_plate: '',
                destination: '',
                buyer_name: '',
                observations: ''
            },

            // 1. Total Kilos Brutos
            get totalGrossKg() {
                return this.batches.reduce((acc, b) => acc + (parseFloat(b.gross_kg) || 0), 0);
            },

            // 2. Total Unidades de Recipientes
            get totalBaskets() {
                return this.batches.reduce((acc, b) => acc + (parseInt(b.baskets) || 0), 0);
            },

            // 3. Total Tara Descontada (Dinámica por Fila)
            get totalTareKg() {
                return this.batches.reduce((acc, b) => {
                    const count = parseFloat(b.baskets) || 0;
                    const unit = parseFloat(b.unit_tare) || 0;
                    return acc + (count * unit);
                }, 0);
            },

            // 4. Peso Limpio Real a Despacho
            get realCleanKg() {
                return Math.max(0, this.totalGrossKg - this.totalTareKg);
            },

            // 5. Desglose detallado de recipientes (ej: "8 canastillas + 6 canastas")
            get tareBreakdownText() {
                let canastillas = 0;
                let canastas = 0;
                let personalizadas = 0;

                this.batches.forEach(b => {
                    const count = parseInt(b.baskets) || 0;
                    if (b.container_type === 'canastilla') {
                        canastillas += count;
                    } else if (b.container_type === 'canasta') {
                        canastas += count;
                    } else {
                        personalizadas += count;
                    }
                });

                const parts = [];
                if (canastillas > 0) parts.push(`${canastillas} canastillas`);
                if (canastas > 0) parts.push(`${canastas} canastas`);
                if (personalizadas > 0) parts.push(`${personalizadas} personalizadas`);

                return parts.length > 0 ? parts.join(' + ') : 'Sin recipientes';
            },

            onContainerTypeChange(batch) {
                if (batch.container_type === 'canastilla') {
                    batch.unit_tare = this.defaultTaraCanastilla;
                } else if (batch.container_type === 'canasta') {
                    batch.unit_tare = this.defaultTaraCanasta;
                }
            },

            updateDefaults(type) {
                this.batches.forEach(b => {
                    if (b.container_type === type) {
                        b.unit_tare = type === 'canastilla' ? this.defaultTaraCanastilla : this.defaultTaraCanasta;
                    }
                });
            },

            addBatch() {
                this.batches.push({
                    gross_kg: '',
                    container_type: 'canastilla',
                    unit_tare: this.defaultTaraCanastilla,
                    baskets: ''
                });
            },

            removeBatch(index) {
                if (this.batches.length > 1) {
                    this.batches.splice(index, 1);
                } else {
                    this.batches = [{
                        gross_kg: '',
                        container_type: 'canastilla',
                        unit_tare: this.defaultTaraCanastilla,
                        baskets: ''
                    }];
                }
            },

            resetBatches() {
                this.batches = [
                    { gross_kg: '', container_type: 'canastilla', unit_tare: this.defaultTaraCanastilla, baskets: '' }
                ];
            },

            async submitDispatch() {
                if (this.realCleanKg <= 0) {
                    alert('El peso limpio real debe ser mayor a 0 kg para registrar el despacho.');
                    return;
                }

                this.isSubmitting = true;

                // Preparar payload completo con desglose de tandas y tara por fila
                const formattedBatches = this.batches.map((b, idx) => ({
                    batch_number: idx + 1,
                    container_type: b.container_type,
                    unit_tare: parseFloat(b.unit_tare) || 0,
                    gross_kg: parseFloat(b.gross_kg) || 0,
                    baskets: parseInt(b.baskets) || 0,
                    total_tare: (parseInt(b.baskets) || 0) * (parseFloat(b.unit_tare) || 0),
                    subtotal_clean: Math.max(0, (parseFloat(b.gross_kg) || 0) - ((parseInt(b.baskets) || 0) * (parseFloat(b.unit_tare) || 0)))
                }));

                const payload = {
                    pond_id: this.dispatchForm.pond_id,
                    clean_weight_kg: parseFloat(this.realCleanKg.toFixed(2)),
                    gross_weight_kg: parseFloat(this.totalGrossKg.toFixed(2)),
                    total_tare_kg: parseFloat(this.totalTareKg.toFixed(2)),
                    baskets_count: this.totalBaskets,
                    driver_name: this.dispatchForm.driver_name,
                    driver_id_card: this.dispatchForm.driver_id_card,
                    driver_vehicle_plate: this.dispatchForm.vehicle_plate,
                    destination: this.dispatchForm.destination,
                    buyer_name: this.dispatchForm.buyer_name,
                    observations: this.dispatchForm.observations,
                    batches: formattedBatches
                };

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const response = await fetch('{{ route("web.cosechas.dispatch") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || ''
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (response.ok) {
                        this.toastMessage = `¡Despacho registrado con éxito! Kilos Limpios: ${this.realCleanKg.toFixed(2)} kg (${this.tareBreakdownText}).`;
                        this.toastType = 'success';
                        this.dispatchModalOpen = false;

                        // Recargar tras 1.5s para reflejar en el historial
                        setTimeout(() => {
                            window.location.reload();
                        }, 1400);
                    } else {
                        this.toastMessage = result.message || 'Error al registrar el despacho.';
                        this.toastType = 'error';
                        alert('Error al registrar: ' + (result.message || 'Verifique los campos requeridos.'));
                    }
                } catch (error) {
                    console.error('Error enviando despacho:', error);
                    this.toastMessage = 'Error de conexión con el servidor.';
                    this.toastType = 'error';
                } finally {
                    this.isSubmitting = false;
                }
            }
        };
    }
</script>
@endsection
