@extends('layouts.app')

@section('title', 'Control Nocturno - Celador')
@section('page_title', 'Módulo Operativo de Guardia Nocturna')

@section('content')
<!-- Contenedor general en MODO OSCURO profundo (#0B1120) para no deslumbrar en la noche -->
<div class="min-h-screen -mx-4 -mt-6 -mb-6 px-4 py-6 text-slate-100 font-sans" style="background-color: #0B1120;" x-data="celadorNocturnoApp()">

    <!-- 0. Barra de Navegación & Botón Atrás -->
    <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
        <x-back-button />
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 shadow-inner">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                Guardia Nocturna Activa (6:00 PM - 6:00 AM)
            </span>
        </div>
    </div>

    <!-- 1. BOTÓN DE PÁNICO Y EMERGENCIA (CABECERA) - ROJO GIGANTE VISIBLE -->
    <div class="mb-6">
        <button type="button"
                @click="abrirModalPanico()"
                class="w-full py-5 px-6 rounded-3xl bg-gradient-to-r from-red-600 via-rose-600 to-red-700 hover:from-red-500 hover:to-rose-600 text-white font-black text-lg sm:text-xl tracking-wide shadow-2xl shadow-rose-950/80 active:scale-[0.98] transition flex items-center justify-center gap-3 border-2 border-rose-400/40 cursor-pointer">
            <i class="fa-solid fa-triangle-exclamation text-2xl sm:text-3xl"></i>
            <span>ALERTA CRÍTICA: BOQUEO / FALTA DE OXÍGENO</span>
        </button>
    </div>

    <!-- Mensaje de Notificación Flash -->
    <div x-show="toastMensaje"
         x-transition
         class="mb-4 p-4 rounded-2xl text-sm font-bold border shadow-lg flex items-center justify-between"
         :class="toastTipo === 'exito' ? 'bg-emerald-950/90 text-emerald-300 border-emerald-600/60' : 'bg-rose-950/90 text-rose-300 border-rose-600/60'"
         x-text="toastMensaje"
         style="display: none;"></div>

    <!-- 2. Registro de Turno (Entrada / Salida) y Pescado Llevado -->
    <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Turno Actual -->
        <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-md">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Turno de Noche</span>
                <span class="text-xs text-cyan-400 font-semibold">Horario: 6:00 PM - 6:00 AM</span>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-bold text-slate-200">{{ $user->name }}</h4>
                    <p class="text-xs text-slate-400">
                        {{ $turno_activo ? 'Entrada: ' . $turno_activo->hora_entrada->timezone('America/Bogota')->format('g:i A') : 'Sin entrada registrada hoy' }}
                    </p>
                </div>
                <div>
                    @if(!$turno_activo)
                        <button type="button" @click="registrarEntrada()"
                                class="px-4 py-2.5 rounded-xl font-black text-xs bg-cyan-600 hover:bg-cyan-500 text-white shadow-lg active:scale-95 transition">
                            <i class="fa-solid fa-right-to-bracket mr-1"></i>Marcar Entrada
                        </button>
                    @else
                        <button type="button" @click="abrirModalSalida()"
                                class="px-4 py-2.5 rounded-xl font-black text-xs bg-amber-600 hover:bg-amber-500 text-white shadow-lg active:scale-95 transition">
                            <i class="fa-solid fa-right-from-bracket mr-1"></i>Finalizar Guardia
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pescado Fiado Celador ($7.000/kg) -->
        <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-md flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pescado Retirado</span>
                <h3 class="text-xl font-black text-slate-100 mt-1">
                    {{ $pescado_fiado_kilos }} <span class="text-sm font-medium text-slate-400">kg</span>
                </h3>
                <p class="text-xs text-amber-400 font-semibold mt-0.5">
                    Saldo: ${{ number_format($pescado_fiado_total, 0, ',', '.') }} COP ($7.000/kg)
                </p>
            </div>
            <div class="h-12 w-12 rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center text-xl">
                <i class="fa-solid fa-fish"></i>
            </div>
        </div>
    </div>

    <!-- 3. CUADRÍCULA COMPLETA DE AIREADORES (TODOS LOS ESTANQUES DE LA FINCA) -->
    <div class="mb-6 p-5 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-xl">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
            <div>
                <h3 class="text-base font-black text-white flex items-center gap-2">
                    <i class="fa-solid fa-fan text-cyan-400 animate-spin" style="animation-duration: 8s;"></i>
                    <span>Control de Aireadores por Estanque</span>
                </h3>
                <p class="text-xs text-slate-400">Interruptor de un toque. Soporte para Red Eléctrica y Planta Diésel con control de combustible.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-xl text-xs font-bold bg-cyan-950 text-cyan-300 border border-cyan-800">
                    {{ $aireadores_activos->count() }} Encendidos / {{ $estanques->count() }} Estanques
                </span>
            </div>
        </div>

        <!-- Cuadrícula Responsiva: 1 col móvil, 2 tablet, 3-4 PC -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($estanques as $estanque)
                @php
                    $activo = $estanque->aireadorActivo;
                    $esDiesel = $activo && $activo->fuente_energia === 'planta_emergencia';
                @endphp
                <div class="p-4 rounded-2xl border transition flex flex-col justify-between {{ $activo ? ($esDiesel ? 'bg-slate-950 border-amber-500/60 shadow-lg shadow-amber-950/40' : 'bg-slate-950 border-cyan-500/60 shadow-lg shadow-cyan-950/40') : 'bg-slate-950/60 border-slate-800/80 hover:border-slate-700' }}">
                    <div>
                        <!-- Cabecera del Estanque con Especie -->
                        <div class="flex items-center justify-between mb-3 gap-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <img src="{{ $estanque->foto_especie }}"
                                     alt="{{ $estanque->name }}"
                                     class="h-10 w-10 rounded-xl object-cover border border-slate-700 shrink-0">
                                <div class="min-w-0">
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-800 text-cyan-300 mr-1">
                                        {{ $estanque->code ?? 'L-'.$estanque->id }}
                                    </span>
                                    <h4 class="text-xs sm:text-sm font-bold text-white truncate" title="{{ $estanque->name }}">
                                        {{ $estanque->name }}
                                    </h4>
                                    <span class="text-[10px] text-slate-400 block truncate">{{ $estanque->especie_nombre }}</span>
                                </div>
                            </div>

                            <!-- Badge Grande de Estado -->
                            <div class="shrink-0">
                                @if($activo)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                        </span>
                                        ENCENDIDO
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-400 border border-slate-700">
                                        <span class="h-2 w-2 rounded-full bg-slate-600"></span>
                                        APAGADO
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Información de Operación Activa y Combustible Diésel -->
                        @if($activo)
                            <div class="mb-3 p-3 rounded-xl bg-slate-900 border text-xs space-y-1.5 {{ $esDiesel ? 'border-amber-500/40' : 'border-slate-800' }}">
                                <div class="flex justify-between text-slate-300">
                                    <span>Encendido a las:</span>
                                    <span class="font-bold text-white">{{ $activo->hora_encendido->timezone('America/Bogota')->format('g:i A') }}</span>
                                </div>
                                <div class="flex justify-between text-slate-300">
                                    <span>Fuente:</span>
                                    <span class="font-bold {{ $esDiesel ? 'text-amber-400 flex items-center gap-1' : 'text-cyan-400 flex items-center gap-1' }}">
                                        @if($esDiesel)
                                            <i class="fa-solid fa-gas-pump"></i> Planta Diésel
                                        @else
                                            <i class="fa-solid fa-bolt"></i> Red Eléctrica
                                        @endif
                                    </span>
                                </div>
                                @if($esDiesel)
                                    <!-- Contabilización de tiempo y control de combustible -->
                                    <div class="pt-1.5 mt-1 border-t border-slate-800/80 flex items-center justify-between text-amber-300 font-bold">
                                        <span class="flex items-center gap-1">
                                            <i class="fa-regular fa-clock"></i> {{ $activo->tiempo_operacion_texto }}
                                        </span>
                                        <span class="text-[11px] bg-amber-950/60 px-2 py-0.5 rounded border border-amber-800/60">
                                            ~{{ $activo->consumo_diesel_estimado_galones }} gal diésel
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="mb-3 p-2.5 rounded-xl bg-slate-900/50 border border-slate-900 text-xs text-slate-500 text-center">
                                Aireador listo para arranque nocturno.
                            </div>
                        @endif
                    </div>

                    <!-- Botones de Control de 1 Toque -->
                    <div>
                        @if($activo)
                            <button type="button"
                                    @click="apagarAireador({{ $activo->id }})"
                                    class="w-full py-3 rounded-xl font-bold text-xs bg-rose-600 hover:bg-rose-500 text-white shadow-md active:scale-95 transition flex items-center justify-center gap-2 cursor-pointer">
                                <i class="fa-solid fa-power-off"></i>
                                <span>Apagar Aireador</span>
                            </button>
                        @else
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button"
                                        @click="encenderAireador({{ $estanque->id }}, 'red_electrica')"
                                        class="py-2.5 px-2 rounded-xl font-bold text-xs bg-emerald-600 hover:bg-emerald-500 text-white shadow-md active:scale-95 transition flex items-center justify-center gap-1.5 cursor-pointer">
                                    <i class="fa-solid fa-bolt"></i>
                                    <span>Red Eléctrica</span>
                                </button>
                                <button type="button"
                                        @click="encenderAireador({{ $estanque->id }}, 'planta_emergencia')"
                                        class="py-2.5 px-2 rounded-xl font-bold text-xs bg-amber-600 hover:bg-amber-500 text-white shadow-md active:scale-95 transition flex items-center justify-center gap-1.5 cursor-pointer">
                                    <i class="fa-solid fa-gas-pump"></i>
                                    <span>Planta Diésel</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- 4. BITÁCORA DE RONDAS NOCTURNAS CON CHECKPOINTS HORARIOS -->
    <div class="mb-6 p-5 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-xl">
        <h3 class="text-base font-black text-white flex items-center gap-2 mb-2">
            <i class="fa-solid fa-clipboard-check text-emerald-400"></i>
            <span>Registrar Ronda Nocturna</span>
        </h3>
        <p class="text-xs text-slate-400 mb-4">Selecciona el checkpoint horario y verifica monjes, aliviaderos y mallas perimetrales.</p>

        <form @submit.prevent="guardarRonda()" class="space-y-4">
            <!-- Checkpoints Rápidos por Horas (10:00 PM, 1:00 AM, 3:30 AM, 5:00 AM) -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-2">Hora de Ronda (Checkpoints) *</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach($checkpoints as $cp)
                        <button type="button"
                                @click="rondaForm.hora_ronda = '{{ $cp }}'"
                                :class="rondaForm.hora_ronda === '{{ $cp }}' ? 'bg-cyan-600 text-white border-cyan-400 ring-2 ring-cyan-500/50' : 'bg-slate-950 text-slate-300 border-slate-800 hover:bg-slate-800'"
                                class="py-3 px-2 rounded-xl text-xs font-bold border transition text-center cursor-pointer">
                            <i class="fa-regular fa-clock mr-1"></i>{{ $cp }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Estanque inspeccionado -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Estanque / Sector Inspeccionado</label>
                <select x-model="rondaForm.estanque_id" class="w-full min-h-[44px] text-sm bg-slate-950 border border-slate-800 rounded-xl text-white px-3 py-2.5 focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="">Recorrido General de Granja / Todos los Lagos</option>
                    @foreach($estanques as $est)
                        <option value="{{ $est->id }}">{{ $est->name }} ({{ $est->code ?? 'L-'.$est->id }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Estado de Monjes y Aliviaderos -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Condición General *</label>
                    <select x-model="rondaForm.estado" required class="w-full min-h-[44px] text-sm bg-slate-950 border border-slate-800 rounded-xl text-white px-3 py-2.5 focus:ring-cyan-500 focus:border-cyan-500 font-bold">
                        <option value="normal">Normal (Sin novedades)</option>
                        <option value="anomalia">Anomalía Detectada</option>
                        <option value="fuga_monje">Fuga en Monje / Aliviadero</option>
                        <option value="depredador">Depredador / Intruso</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nivel Monje / Aliviadero *</label>
                    <select x-model="rondaForm.nivel_agua_monje" required class="w-full min-h-[44px] text-sm bg-slate-950 border border-slate-800 rounded-xl text-white px-3 py-2.5 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="optimo">Normal (Nivel Óptimo)</option>
                        <option value="fuga">Fuga de Agua</option>
                        <option value="rebose">Rebose Excesivo</option>
                        <option value="bajo">Nivel Bajo</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Estado de Mallas *</label>
                    <select x-model="rondaForm.estado_mallas" required class="w-full min-h-[44px] text-sm bg-slate-950 border border-slate-800 rounded-xl text-white px-3 py-2.5 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="bueno">Mallas en Buen Estado</option>
                        <option value="danada">Malla Rota / Dañada</option>
                        <option value="ajustada">Malla Reajustada</option>
                    </select>
                </div>
            </div>

            <!-- Novedades de Seguridad -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Novedades de Seguridad (Ruidos, Depredadores, Lluvia Fuerte) *</label>
                <input type="text" x-model="rondaForm.observaciones" placeholder="Ej: Ruidos en sector perimetral este, lluvia fuerte con viento, garzas espantadas..."
                       class="w-full min-h-[44px] text-sm bg-slate-950 border border-slate-800 rounded-xl text-white px-3 py-2.5 focus:ring-cyan-500 focus:border-cyan-500">
            </div>

            <button type="submit"
                    class="w-full min-h-[48px] py-3.5 rounded-2xl font-black text-sm bg-cyan-600 hover:bg-cyan-500 text-white shadow-lg shadow-cyan-900/40 active:scale-98 transition flex items-center justify-center gap-2 cursor-pointer">
                <i class="fa-solid fa-floppy-disk mr-1"></i>Guardar Ronda Nocturna
            </button>
        </form>
    </div>

    <!-- 5. HISTORIAL DE NOVEDADES NOCTURNAS (TABLA COMPACTA) -->
    <div class="p-5 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
            <h3 class="text-sm font-black text-white flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-slate-400"></i>
                <span>Historial de Novedades Nocturnas de esta Guardia</span>
            </h3>
            <span class="text-xs text-slate-400 font-mono">{{ $rondas_recientes->count() }} eventos</span>
        </div>

        @if($rondas_recientes->isEmpty())
            <p class="text-xs text-slate-500 text-center py-6">Aún no hay rondas ni alertas registradas para esta jornada.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider text-[10px]">
                            <th class="py-2.5 px-3">Hora Ronda</th>
                            <th class="py-2.5 px-3">Estanque / Sector</th>
                            <th class="py-2.5 px-3">Condición</th>
                            <th class="py-2.5 px-3">Monje / Aliviadero</th>
                            <th class="py-2.5 px-3">Novedades / Observación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($rondas_recientes as $ronda)
                            <tr class="hover:bg-slate-950/40 transition">
                                <td class="py-2.5 px-3 font-mono font-bold text-cyan-400">
                                    {{ $ronda->hora_ronda }}
                                </td>
                                <td class="py-2.5 px-3 font-semibold text-slate-200">
                                    {{ $ronda->estanque->name ?? 'Recorrido General' }}
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase
                                        @if($ronda->estado === 'normal') bg-emerald-950 text-emerald-400 border border-emerald-800
                                        @elseif($ronda->estado === 'anomalia') bg-amber-950 text-amber-400 border border-amber-800
                                        @else bg-rose-950 text-rose-400 border border-rose-800 @endif">
                                        {{ str_replace('_', ' ', $ronda->estado) }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-slate-300">
                                    {{ ucfirst($ronda->nivel_agua_monje ?? 'Óptimo') }}
                                </td>
                                <td class="py-2.5 px-3 text-slate-400 italic max-w-xs truncate">
                                    {{ $ronda->observaciones ? '"'.$ronda->observaciones.'"' : 'Sin novedades registradas' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- MODAL 1: BOTÓN DE PÁNICO (ALERTA DE BOQUEO) -->
    <div x-show="modalPanicoAbierto"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         x-cloak>
        <div class="w-full max-w-md p-6 rounded-3xl bg-slate-900 border-2 border-rose-600 text-white shadow-2xl space-y-4"
             @click.away="modalPanicoAbierto = false">
            <div class="flex items-center gap-3 text-rose-500">
                <i class="fa-solid fa-triangle-exclamation text-3xl animate-bounce"></i>
                <div>
                    <h3 class="text-lg font-black text-white">CONFIRMAR ALERTA DE BOQUEO</h3>
                    <p class="text-xs text-rose-300">Se notificará de inmediato al Administrador y Jefe Mayor.</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Estanque en Emergencia *</label>
                <select x-model="panicoForm.estanque_id" class="w-full text-sm bg-slate-950 border-rose-800 rounded-xl text-white focus:ring-rose-500 focus:border-rose-500">
                    <option value="">Seleccione el estanque afectado...</option>
                    @foreach($estanques as $est)
                        <option value="{{ $est->id }}">{{ $est->name }} ({{ $est->code ?? 'L-'.$est->id }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Detalle de la Emergencia</label>
                <textarea x-model="panicoForm.observaciones" rows="2" placeholder="Ej: Peces en superficie en la orilla sur, boqueo masivo visible con linterna..."
                          class="w-full text-sm bg-slate-950 border-rose-800 rounded-xl text-white focus:ring-rose-500 focus:border-rose-500"></textarea>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="button" @click="modalPanicoAbierto = false"
                        class="flex-1 py-3 rounded-xl font-bold text-xs bg-slate-800 text-slate-300 hover:bg-slate-700 transition">
                    Cancelar
                </button>
                <button type="button" @click="dispararAlertaPanico()"
                        class="flex-1 py-3 rounded-xl font-black text-xs bg-rose-600 hover:bg-rose-500 text-white shadow-lg shadow-rose-900/50 transition">
                    DISPARAR ALERTA
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: SALIDA DE GUARDIA Y REGISTRO DE PESCADO FIADO -->
    <div x-show="modalSalidaAbierto"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         x-cloak>
        <div class="w-full max-w-md p-6 rounded-3xl bg-slate-900 border border-slate-700 text-white shadow-2xl space-y-4"
             @click.away="modalSalidaAbierto = false">
            <h3 class="text-base font-black text-white flex items-center gap-2">
                <i class="fa-solid fa-person-walking-arrow-right text-cyan-400"></i>
                <span>Finalizar Guardia Nocturna</span>
            </h3>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">¿Llevas pescado hoy? (Kilos)</label>
                <input type="number" step="0.1" min="0" x-model="salidaForm.pescado_kilos_llevados" placeholder="0.0"
                       class="w-full text-sm bg-slate-950 border-slate-800 rounded-xl text-white focus:ring-cyan-500 focus:border-cyan-500">
                <p class="text-[11px] text-slate-400 mt-1">Tarifa de trabajador/celador: $7.000 COP/kg</p>
            </div>

            <div x-show="salidaForm.pescado_kilos_llevados > 0" class="p-3 bg-slate-950 rounded-xl border border-amber-500/30 text-xs">
                <span class="text-slate-400">Descuento a nómina: </span>
                <span class="font-black text-amber-400" x-text="'$' + (salidaForm.pescado_kilos_llevados * 7000).toLocaleString() + ' COP'"></span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Novedad / Observación de Salida</label>
                <input type="text" x-model="salidaForm.observaciones" placeholder="Entrega de llaves sin novedad a relevo..."
                       class="w-full text-sm bg-slate-950 border-slate-800 rounded-xl text-white focus:ring-cyan-500 focus:border-cyan-500">
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="button" @click="modalSalidaAbierto = false"
                        class="flex-1 py-3 rounded-xl font-bold text-xs bg-slate-800 text-slate-300 hover:bg-slate-700 transition">
                    Cancelar
                </button>
                <button type="button" @click="confirmarSalida()"
                        class="flex-1 py-3 rounded-xl font-black text-xs bg-cyan-600 hover:bg-cyan-500 text-white shadow-lg transition">
                    Confirmar Salida
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function celadorNocturnoApp() {
    return {
        toastMensaje: '',
        toastTipo: 'exito',
        modalPanicoAbierto: false,
        modalSalidaAbierto: false,

        rondaForm: {
            hora_ronda: '10:00 PM',
            estanque_id: '',
            estado: 'normal',
            nivel_agua_monje: 'optimo',
            estado_mallas: 'bueno',
            observaciones: ''
        },

        panicoForm: {
            estanque_id: '{{ $estanques->first()?->id }}',
            observaciones: 'Boqueo de peces detectado en recorrido nocturno'
        },

        salidaForm: {
            pescado_kilos_llevados: 0,
            observaciones: ''
        },

        mostrarToast(msg, tipo = 'exito') {
            this.toastMensaje = msg;
            this.toastTipo = tipo;
            setTimeout(() => { this.toastMensaje = ''; }, 4500);
        },

        guardarRonda() {
            fetch('/api/celador/rondas', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.rondaForm)
            })
            .then(res => res.json())
            .then(data => {
                this.mostrarToast(data.message);
                this.rondaForm.observaciones = '';
                setTimeout(() => location.reload(), 1200);
            })
            .catch(err => {
                this.mostrarToast('Error al registrar la ronda.', 'error');
            });
        },

        encenderAireador(estanqueId, fuente) {
            fetch('/api/celador/aireadores/encender', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    estanque_id: estanqueId,
                    fuente_energia: fuente,
                    corte_luz: fuente === 'planta_emergencia'
                })
            })
            .then(res => res.json())
            .then(data => {
                this.mostrarToast(data.message);
                setTimeout(() => location.reload(), 1200);
            })
            .catch(err => {
                this.mostrarToast('Error al encender aireador.', 'error');
            });
        },

        apagarAireador(controlId) {
            fetch(`/api/celador/aireadores/${controlId}/apagar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({})
            })
            .then(res => res.json())
            .then(data => {
                this.mostrarToast(data.message);
                setTimeout(() => location.reload(), 1200);
            })
            .catch(err => {
                this.mostrarToast('Error al apagar aireador.', 'error');
            });
        },

        abrirModalPanico() {
            this.modalPanicoAbierto = true;
        },

        dispararAlertaPanico() {
            if (!this.panicoForm.estanque_id) {
                alert('Selecciona un estanque.');
                return;
            }

            fetch('/api/celador/alerta-boqueo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.panicoForm)
            })
            .then(res => res.json())
            .then(data => {
                this.modalPanicoAbierto = false;
                this.mostrarToast(data.message, 'error');
                setTimeout(() => location.reload(), 1800);
            })
            .catch(err => {
                this.mostrarToast('Error al transmitir la alerta de pánico.', 'error');
            });
        },

        registrarEntrada() {
            fetch('/api/celador/entrada', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({})
            })
            .then(res => res.json())
            .then(data => {
                this.mostrarToast(data.message);
                setTimeout(() => location.reload(), 1200);
            });
        },

        abrirModalSalida() {
            this.modalSalidaAbierto = true;
        },

        confirmarSalida() {
            fetch('/api/celador/salida', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.salidaForm)
            })
            .then(res => res.json())
            .then(data => {
                this.modalSalidaAbierto = false;
                this.mostrarToast(data.message);
                setTimeout(() => location.reload(), 1500);
            });
        }
    }
}
</script>
@endsection
