@extends('layouts.app')

@section('title', 'Reporte de Bajas - SAS Piscícola')
@section('page_title', 'Reporte de Bajas Matutinas')

@section('content')
<div class="space-y-6">

    <!-- Barra Superior de Navegación -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <x-back-button :href="route('trabajador.dashboard')" label="Volver al Dashboard" />
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 bg-white/80 dark:bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
            <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
            <span>Control Zootécnico & Sanitario de Bajas</span>
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

        <!-- Formulario de Registro de Bajas -->
        <div class="lg:col-span-1 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-5">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-amber-600 dark:text-amber-400"></i>
                    Registrar Mortalidad
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Descuenta automáticamente la cantidad de peces de la población activa y recalcula la biomasa del lago.
                </p>
            </div>

            <form action="{{ route('trabajador.mortalidad') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Selector de Lago -->
                <div>
                    <label for="estanque_id" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Lago / Estanque <span class="text-rose-500">*</span>
                    </label>
                    <select name="estanque_id" id="estanque_id" required
                            class="w-full min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white px-3 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">
                        <option value="">-- Seleccione un lago --</option>
                        @foreach($estanques as $estanque)
                            <option value="{{ $estanque->id }}" {{ old('estanque_id') == $estanque->id ? 'selected' : '' }}>
                                {{ $estanque->name }} ({{ number_format($estanque->fish_population) }} peces)
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Cantidad de Peces -->
                <div>
                    <label for="cantidad_peces" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Cantidad de Peces Muertos <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="cantidad_peces" id="cantidad_peces" min="1" required
                           value="{{ old('cantidad_peces') }}"
                           placeholder="Ej. 15"
                           class="w-full min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white px-3 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">
                </div>

                <!-- Causa Probable -->
                <div>
                    <label for="causa" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Causa Probable / Motivo
                    </label>
                    <input type="text" name="causa" id="causa"
                           value="{{ old('causa') }}"
                           placeholder="Ej. Asfixia / Falta de oxígeno, Depredador, Hongos"
                           class="w-full min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white px-3 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">
                </div>

                <!-- Método de Disposición -->
                <div>
                    <label for="metodo_disposicion" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Método de Disposición
                    </label>
                    <select name="metodo_disposicion" id="metodo_disposicion"
                            class="w-full min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white px-3 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">
                        <option value="compostaje" {{ old('metodo_disposicion') == 'compostaje' ? 'selected' : '' }}>Compostaje Orgánico</option>
                        <option value="fosa" {{ old('metodo_disposicion') == 'fosa' ? 'selected' : '' }}>Fosa Séptica Sanitaria</option>
                        <option value="entierro_cal" {{ old('metodo_disposicion') == 'entierro_cal' ? 'selected' : '' }}>Entierro con Cal Viva</option>
                    </select>
                </div>

                <!-- Observaciones -->
                <div>
                    <label for="observaciones" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Observaciones Adicionales
                    </label>
                    <textarea name="observaciones" id="observaciones" rows="3"
                              placeholder="Detalles del hallazgo, orilla del estanque, estado de branquias..."
                              class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white p-3 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">{{ old('observaciones') }}</textarea>
                </div>

                <button type="submit"
                        class="w-full min-h-[44px] rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-cyan-600 dark:hover:bg-cyan-500 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-md transition cursor-pointer">
                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                    <span>Guardar Reporte de Bajas</span>
                </button>
            </form>
        </div>

        <!-- Historial de Bajas Registradas -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-cyan-600 dark:text-cyan-400"></i>
                        Historial de Bajas Registradas
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Registros recientes de mortalidad reportados en la granja.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-3">Fecha</th>
                            <th class="py-3 px-3">Lago</th>
                            <th class="py-3 px-3 text-right">Cantidad</th>
                            <th class="py-3 px-3">Causa</th>
                            <th class="py-3 px-3">Disposición</th>
                            <th class="py-3 px-3">Reportado Por</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($mortalidades as $registro)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                <td class="py-3 px-3 font-mono text-slate-500 dark:text-slate-400">
                                    {{ $registro->fecha ? $registro->fecha->format('d/m/Y') : $registro->created_at->format('d/m/Y') }}
                                </td>
                                <td class="py-3 px-3 font-bold text-slate-900 dark:text-white">
                                    {{ $registro->estanque?->name ?? 'Estanque N/A' }}
                                </td>
                                <td class="py-3 px-3 text-right font-black text-rose-600 dark:text-rose-400">
                                    {{ number_format($registro->cantidad_peces) }} peces
                                </td>
                                <td class="py-3 px-3 text-slate-600 dark:text-slate-300">
                                    {{ $registro->causa_probable ?? 'No especificada' }}
                                </td>
                                <td class="py-3 px-3">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ ucfirst($registro->metodo_disposicion ?? 'compostaje') }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-slate-500 dark:text-slate-400">
                                    {{ $registro->user?->name ?? 'Usuario' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-shield-halved text-2xl mb-2 text-slate-300 dark:text-slate-700 block"></i>
                                    No hay registros de mortalidad en el historial reciente.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($mortalidades->hasPages())
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $mortalidades->links() }}
                </div>
            @endif
        </div>

    </div>

</div>
@endsection
