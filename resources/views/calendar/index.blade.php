<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Agenda Operativa y Calendario en Vivo - El SAS Piscícola</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        @keyframes pulse-subtle {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        .live-pulse {
            animation: pulse-subtle 2s infinite ease-in-out;
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen">
    <!-- Barra Superior: Encabezado y Reloj en Vivo -->
    <header class="bg-slate-800 border-b border-slate-700 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-3 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <x-back-button />
                <div class="bg-emerald-500/20 text-emerald-400 p-2.5 rounded-xl border border-emerald-500/30">
                    <i class="fa-solid fa-fish text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-white flex items-center gap-2">
                        El SAS Piscícola
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/30">Agenda Jefe de Finca</span>
                    </h1>
                    <p class="text-xs text-slate-400">Control de Operaciones, Cosechas y Logística</p>
                </div>
            </div>

            <!-- Widget Reloj y Conectividad en Vivo -->
            <div class="flex items-center gap-4">
                <!-- Estado Conexión Rural (Online/Offline) -->
                <div id="connection-status-badge" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 live-pulse"></span>
                    <span id="connection-text">En Línea</span>
                </div>

                <!-- Reloj Digital en Vivo -->
                <div class="bg-slate-950 px-4 py-1.5 rounded-xl border border-slate-700 text-right">
                    <div id="live-clock" class="text-lg font-mono font-bold text-emerald-400 tracking-wider">00:00:00</div>
                    <div id="live-date" class="text-[11px] text-slate-400 capitalize">Cargando fecha...</div>
                </div>

                <!-- Botón de Cerrar Sesión -->
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="px-3 py-2 rounded-xl bg-slate-950 hover:bg-rose-950/60 text-slate-300 hover:text-rose-300 border border-slate-700 transition text-xs font-semibold flex items-center gap-1.5 min-h-[38px]"
                            title="Cerrar sesión del sistema">
                        <i class="fa-solid fa-arrow-right-from-bracket text-rose-400"></i>
                        <span class="hidden sm:inline">Salir</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Alerta de Sincronización Offline -->
    <div id="offline-sync-banner" class="hidden bg-amber-500/15 border-b border-amber-500/30 px-4 py-2 text-xs text-amber-300 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-cloud-arrow-up text-amber-400"></i>
            <span>Tienes <strong id="offline-queue-count">0</strong> eventos guardados localmente en modo sin conexión.</span>
        </div>
        <button onclick="syncOfflineQueue()" class="bg-amber-600 hover:bg-amber-500 text-white font-medium px-3 py-1 rounded transition text-xs flex items-center gap-1.5">
            <i class="fa-solid fa-arrows-rotate"></i> Sincronizar Ahora
        </button>
    </div>

    <div x-data="{
        activeTab: 'turnos',
        modalSemanal: false,
        modalFinde: false,
        fechaSemana: '{{ $semanaActualLunes ?? now()->startOfWeek()->toDateString() }}'
    }" class="pb-12">

        <!-- Notificaciones de Sesión y Validaciones -->
        @if(session('status'))
            <div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8">
                <div class="p-4 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 text-sm flex items-center justify-between shadow-lg">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-check text-emerald-400 text-lg"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                    <button @click="$el.parentElement.parentElement.remove()" class="text-emerald-400 hover:text-white p-1">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8">
                <div class="p-4 rounded-2xl bg-rose-500/20 border border-rose-500/40 text-rose-200 text-sm shadow-lg">
                    <div class="font-bold flex items-center gap-2 mb-1.5 text-rose-300">
                        <i class="fa-solid fa-triangle-exclamation text-rose-400"></i> No se pudo procesar la solicitud:
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Pestañas de Navegación Operativa -->
        <div class="max-w-7xl mx-auto px-4 pt-6 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-700/80 pb-4">
                <div class="flex items-center gap-2 bg-slate-950 p-1.5 rounded-2xl border border-slate-800">
                    <button type="button" @click="activeTab = 'turnos'"
                            :class="activeTab === 'turnos' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'"
                            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition flex items-center gap-2">
                        <i class="fa-solid fa-user-clock text-cyan-300"></i>
                        <span>Turnos Operativos & Guardia</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-900 text-slate-200 border border-slate-700">
                            {{ isset($turnos) ? $turnos->count() : 0 }}
                        </span>
                    </button>

                    <button type="button" @click="activeTab = 'eventos'"
                            :class="activeTab === 'eventos' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'"
                            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition flex items-center gap-2">
                        <i class="fa-regular fa-calendar-days text-emerald-300"></i>
                        <span>Eventos & Bitácora de Campo</span>
                        <span id="tab-events-badge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-900 text-slate-200 border border-slate-700">
                            0
                        </span>
                    </button>
                </div>

                @if(auth()->check() && auth()->user()->hasRole(['jefe_mayor', 'owner', 'administrador', 'admin']))
                <div x-show="activeTab === 'turnos'" class="flex items-center gap-2" x-cloak>
                    <button type="button" @click="modalSemanal = true"
                            class="px-3.5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold transition shadow-lg shadow-cyan-600/20 flex items-center gap-1.5">
                        <i class="fa-solid fa-calendar-plus"></i>
                        <span>Programar Turno Semanal</span>
                    </button>
                    <button type="button" @click="modalFinde = true"
                            class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700 transition flex items-center gap-1.5">
                        <i class="fa-solid fa-calendar-day text-amber-400"></i>
                        <span>Turno Fin de Semana</span>
                    </button>
                </div>
                @endif
            </div>
        </div>

        <!-- ==================== VISTA TAB 1: TURNOS OPERATIVOS ==================== -->
        <div x-show="activeTab === 'turnos'" class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-6" x-cloak>
            <!-- Banner Explicativo de la Regla de Encadenamiento -->
            <div class="p-4 rounded-2xl bg-cyan-950/40 border border-cyan-800/50 flex items-start gap-3.5 shadow-sm">
                <div class="h-9 w-9 rounded-xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center shrink-0 border border-cyan-500/30">
                    <i class="fa-solid fa-link"></i>
                </div>
                <div class="text-xs text-slate-300 space-y-1">
                    <h3 class="font-bold text-cyan-300 text-sm">Regla Estricta de Encadenamiento Nocturno</h3>
                    <p>Al programar a un operario como <strong>Alimentador</strong> para la semana (Lunes a Viernes), el sistema le asigna automáticamente la <strong>Guardia Nocturna (Seguridad & Aireadores)</strong> del Domingo inmediatamente anterior (06:00 PM a 06:00 AM). Los turnos de fin de semana (Sábados y Domingos diurnos) son independientes.</p>
                </div>
            </div>

            <!-- Grilla de Turnos Programados -->
            @if(isset($turnos) && $turnos->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($turnos as $turno)
                        @php
                            $fechaCarbon = \Carbon\Carbon::parse($turno->fecha);
                            $isDomingoNoche = $turno->rol_asignado === \App\Models\AgendaTurno::ROL_SEGURIDAD_NOCHE;
                            $isAlimentador = $turno->rol_asignado === \App\Models\AgendaTurno::ROL_ALIMENTADOR;
                            $isHoy = $fechaCarbon->isToday();
                        @endphp
                        <div class="p-4 rounded-2xl border transition duration-150 {{ $isHoy ? 'bg-slate-800 border-cyan-500 ring-2 ring-cyan-500/30 shadow-lg' : 'bg-slate-800/80 border-slate-700/80 hover:border-slate-600' }}">
                            <div class="flex items-center justify-between mb-3 border-b border-slate-700/60 pb-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold capitalize text-white flex items-center gap-1.5">
                                        <i class="fa-regular fa-calendar text-slate-400"></i>
                                        {{ $fechaCarbon->locale('es')->isoFormat('dddd D [de] MMMM') }}
                                    </span>
                                    @if($isHoy)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-cyan-500/20 text-cyan-300 border border-cyan-500/40 animate-pulse">
                                            HOY
                                        </span>
                                    @endif
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $turno->estado === 'activo' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-slate-700 text-slate-300' }}">
                                    {{ ucfirst($turno->estado) }}
                                </span>
                            </div>

                            <!-- Rol Badge -->
                            <div class="mb-3">
                                @if($isAlimentador)
                                    <div class="p-2.5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center gap-2.5">
                                        <div class="h-8 w-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                                            <i class="fa-solid fa-bowl-food text-sm"></i>
                                        </div>
                                        <div>
                                            <span class="block text-xs font-bold text-emerald-300">Alimentador de Campo</span>
                                            <span class="block text-[10px] text-slate-400">06:00 AM - 05:00 PM (Raciones Diarias)</span>
                                        </div>
                                    </div>
                                @elseif($isDomingoNoche)
                                    <div class="p-2.5 rounded-xl bg-indigo-500/15 border border-indigo-500/30 flex items-center gap-2.5">
                                        <div class="h-8 w-8 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0">
                                            <i class="fa-solid fa-moon text-sm"></i>
                                        </div>
                                        <div>
                                            <span class="block text-xs font-bold text-indigo-300">Seguridad & Noche</span>
                                            <span class="block text-[10px] text-slate-400">06:00 PM - 06:00 AM (Aireadores & Ronda)</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Operario Asignado -->
                            <div class="flex items-center gap-3 pt-1">
                                <div class="h-9 w-9 rounded-xl bg-slate-700 border border-slate-600 text-slate-200 flex items-center justify-center text-xs font-bold uppercase">
                                    {{ substr($turno->user->name ?? 'OP', 0, 2) }}
                                </div>
                                <div class="overflow-hidden">
                                    <h4 class="text-xs font-bold text-white truncate">{{ $turno->user->name ?? 'Operario no especificado' }}</h4>
                                    <p class="text-[11px] text-slate-400 truncate">C.C. {{ $turno->user->document_number ?? 'Sin documento' }}</p>
                                </div>
                            </div>

                            @if($turno->observaciones)
                                <div class="mt-3 p-2 rounded-lg bg-slate-900/60 border border-slate-700/50 text-[11px] text-slate-400 flex items-start gap-1.5">
                                    <i class="fa-regular fa-comment text-slate-500 mt-0.5"></i>
                                    <span class="italic">{{ $turno->observaciones }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-12 text-center rounded-2xl bg-slate-800/40 border border-slate-700/60">
                    <div class="h-12 w-12 mx-auto rounded-2xl bg-slate-800 text-slate-400 flex items-center justify-center mb-3 text-xl">
                        <i class="fa-solid fa-calendar-xmark"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-200 mb-1">No hay turnos programados en este período</h3>
                    <p class="text-xs text-slate-400 max-w-md mx-auto mb-4">Haz clic en "Programar Turno Semanal" para asignar la semana de alimentación (con guardia dominical encadenada).</p>
                    @if(auth()->check() && auth()->user()->hasRole(['jefe_mayor', 'owner', 'administrador', 'admin']))
                        <button type="button" @click="modalSemanal = true" class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold shadow-lg transition">
                            <i class="fa-solid fa-calendar-plus mr-1"></i> Programar Primer Turno Semanal
                        </button>
                    @endif
                </div>
            @endif
        </div>

        <!-- ==================== VISTA TAB 2: EVENTOS Y BITÁCORA ==================== -->
        <div x-show="activeTab === 'eventos'" class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-6" x-cloak>
            <!-- Columna Izquierda: Formulario de Programación del Jefe de Finca -->
            <section class="lg:col-span-1 bg-slate-800 rounded-2xl border border-slate-700 p-5 shadow-xl">
                <div class="flex items-center justify-between mb-4 border-b border-slate-700 pb-3">
                    <h2 class="text-base font-semibold text-white flex items-center gap-2">
                        <i class="fa-regular fa-calendar-plus text-emerald-400"></i>
                        Programar Evento
                    </h2>
                    <span class="text-xs text-slate-400">Jefe de Finca</span>
                </div>

                <form id="calendar-event-form" onsubmit="handleFormSubmit(event)" class="space-y-4">
                    <!-- Título del Evento -->
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Título de la Actividad *</label>
                        <input type="text" id="title" required placeholder="Ej: Pesca de prueba o llegada concentrado" 
                            class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <!-- Tipo de Evento -->
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Tipo de Evento *</label>
                        <select id="event_type" onchange="toggleEventFields()" required
                            class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="pesca_cosecha">Pesca / Cosecha</option>
                            <option value="llegada_alevinos">Llegada de Alevinos</option>
                            <option value="llegada_alimento">Llegada de Alimento</option>
                            <option value="visita_general">Visita o Asunto General</option>
                        </select>
                    </div>

                    <!-- Fecha y Hora -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Fecha Programada *</label>
                            <input type="date" id="event_date" required
                                class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Hora Estimada</label>
                            <input type="time" id="event_time"
                                class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>

                    <!-- Campos Dinámicos 1: Pesca / Cosecha -->
                    <div id="fields-pesca" class="space-y-3 p-3.5 bg-slate-900/60 rounded-xl border border-slate-700/60">
                        <p class="text-xs font-semibold text-emerald-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-water"></i> Parámetros de Pesca / Cosecha
                        </p>
                        <div>
                            <label class="block text-xs text-slate-300 mb-1">Estanque / Lago</label>
                            <select id="pond_id" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white">
                                <option value="">Seleccione Estanque...</option>
                                @isset($ponds)
                                    @foreach($ponds as $pond)
                                        <option value="{{ $pond->id }}">{{ $pond->name }}</option>
                                    @endforeach
                                @endisset
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-300 mb-1">Kilos Estimados a Cosechar</label>
                            <input type="number" step="0.01" id="estimated_kg" placeholder="Ej: 850.5"
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white">
                        </div>
                    </div>

                    <!-- Campos Dinámicos 2: Llegada de Alevinos -->
                    <div id="fields-alevinos" class="hidden space-y-3 p-3.5 bg-slate-900/60 rounded-xl border border-slate-700/60">
                        <p class="text-xs font-semibold text-cyan-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-seedling"></i> Parámetros de Siembra / Alevinos
                        </p>
                        <div>
                            <label class="block text-xs text-slate-300 mb-1">Cantidad de Alevinos (Unidades)</label>
                            <input type="number" id="fingerlings_quantity" placeholder="Ej: 15000"
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-300 mb-1">Etapa de Desarrollo</label>
                            <input type="text" id="stage" placeholder="Ej: Reversión, 1 gramo, Alevinaje"
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white">
                        </div>
                    </div>

                    <!-- Campos Dinámicos 3: Llegada de Alimento -->
                    <div id="fields-alimento" class="hidden space-y-3 p-3.5 bg-slate-900/60 rounded-xl border border-slate-700/60">
                        <p class="text-xs font-semibold text-amber-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-boxes-stacked"></i> Parámetros de Concentrado
                        </p>
                        <div>
                            <label class="block text-xs text-slate-300 mb-1">Tipo / Porcentaje de Proteína</label>
                            <input type="text" id="feed_type" placeholder="Ej: Mojarra 38% o Crecimiento 32%"
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs text-slate-300 mb-1">Bultos (40kg)</label>
                                <input type="number" id="feed_bags_count" placeholder="Ej: 50"
                                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white">
                            </div>
                            <div>
                                <label class="block text-xs text-slate-300 mb-1">Total Kilos</label>
                                <input type="number" step="0.01" id="feed_weight_kg" placeholder="Ej: 2000"
                                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white">
                            </div>
                        </div>
                    </div>

                    <!-- Campos Dinámicos 4: Visita General -->
                    <div id="fields-visita" class="hidden space-y-3 p-3.5 bg-slate-900/60 rounded-xl border border-slate-700/60">
                        <p class="text-xs font-semibold text-purple-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-clipboard-check"></i> Notas de Inspección del Jefe
                        </p>
                        <textarea id="inspection_notes" rows="3" placeholder="Puntos a revisar, auditoría o visita de veterinario..."
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg p-2 text-xs text-white"></textarea>
                    </div>

                    <!-- Notas / Observaciones Generales -->
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Notas u Observaciones</label>
                        <textarea id="notes" rows="2" placeholder="Instrucciones para el Administrador..."
                            class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
                    </div>

                    <!-- Botón de Envío -->
                    <button type="submit" id="btn-submit"
                        class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-2.5 px-4 rounded-xl shadow transition duration-150 flex items-center justify-center gap-2 text-sm">
                        <i class="fa-regular fa-bell"></i> Agendar y Notificar al Admin
                    </button>
                </form>
            </section>

            <!-- Columna Derecha: Vista de Agenda Operativa en Vivo -->
            <section class="lg:col-span-2 space-y-4">
                <!-- Barra de Filtros y Estado -->
                <div class="bg-slate-800 rounded-2xl border border-slate-700 p-4 shadow-xl flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-white">Eventos Agendados</span>
                        <span id="events-count-badge" class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-700 text-slate-300">0</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <button onclick="fetchEvents()" class="bg-slate-700 hover:bg-slate-600 text-slate-200 px-3 py-1.5 rounded-lg transition flex items-center gap-1.5">
                            <i class="fa-solid fa-rotate"></i> Actualizar
                        </button>
                    </div>
                </div>

                <!-- Lista de Tarjetas de Eventos -->
                <div id="events-container" class="space-y-3">
                    <div class="text-center py-12 text-slate-500 bg-slate-800/40 rounded-2xl border border-slate-700/50">
                        <i class="fa-solid fa-spinner fa-spin text-2xl mb-2 text-emerald-400"></i>
                        <p class="text-sm">Cargando eventos de la agenda...</p>
                    </div>
                </div>
            </section>
        </div>

        <!-- ==================== MODALES ==================== -->
        <!-- Modal: Programar Turno Semanal (Lunes a Viernes + Domingo Noche Anterior) -->
        <div x-show="modalSemanal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
             x-cloak>
            <div @click.away="modalSemanal = false"
                 class="relative w-full max-w-lg rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-2xl text-left">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-cyan-500/20 text-cyan-400 border border-cyan-500/30">
                            <i class="fa-solid fa-calendar-week"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">Programar Turno Semanal</h3>
                            <p class="text-[11px] text-slate-400">Lunes a Viernes + Guardia Dominical previa obligatoria</p>
                        </div>
                    </div>
                    <button type="button" @click="modalSemanal = false" class="text-slate-400 hover:text-white text-lg p-1">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('agenda.turnos.semanal') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Operario de Campo *</label>
                        <select name="user_id" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                            <option value="">Seleccione el operario...</option>
                            @isset($operarios)
                                @foreach($operarios as $op)
                                    <option value="{{ $op->id }}">{{ $op->name }} (C.C. {{ $op->document_number ?? 'S/D' }})</option>
                                @endforeach
                            @endisset
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Lunes de Inicio de Semana *</label>
                        <input type="date" name="fecha_lunes" value="{{ $semanaActualLunes ?? now()->startOfWeek()->toDateString() }}" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <p class="text-[10px] text-slate-400 mt-1">El sistema tomará automáticamente el lunes correspondiente a la semana de la fecha seleccionada.</p>
                    </div>

                    <div class="p-3 rounded-xl bg-cyan-950/50 border border-cyan-800/40 text-[11px] text-cyan-300 space-y-1">
                        <div class="font-bold flex items-center gap-1.5 text-cyan-200">
                            <i class="fa-solid fa-circle-info"></i> Encadenamiento de Seguridad Automático
                        </div>
                        <p>Al guardar, el operario seleccionado quedará programado para:</p>
                        <ul class="list-disc list-inside space-y-0.5 text-slate-300">
                            <li><strong>Domingo anterior (06:00 PM a 06:00 AM):</strong> Guardia Nocturna & Aireadores.</li>
                            <li><strong>Lunes a Viernes (06:00 AM a 05:00 PM):</strong> Alimentador Diario.</li>
                        </ul>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Observaciones (Opcional)</label>
                        <textarea name="observaciones" rows="2" placeholder="Instrucciones especiales para el operario..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-cyan-500 focus:outline-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="modalSemanal = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold shadow-lg shadow-cyan-600/30 transition flex items-center gap-1.5">
                            <i class="fa-solid fa-check"></i> Asignar Semana Completa
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Programar Turno Fin de Semana (Sábados y Domingos independientes) -->
        <div x-show="modalFinde"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
             x-cloak>
            <div @click.away="modalFinde = false"
                 class="relative w-full max-w-lg rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-2xl text-left">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30">
                            <i class="fa-solid fa-calendar-day"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">Turno de Fin de Semana</h3>
                            <p class="text-[11px] text-slate-400">Asignación independiente de Sábado o Domingo</p>
                        </div>
                    </div>
                    <button type="button" @click="modalFinde = false" class="text-slate-400 hover:text-white text-lg p-1">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('agenda.turnos.findesemana') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Trabajador / Operario *</label>
                        <select name="user_id" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <option value="">Seleccione al trabajador...</option>
                            @isset($operarios)
                                @foreach($operarios as $op)
                                    <option value="{{ $op->id }}">{{ $op->name }} (C.C. {{ $op->document_number ?? 'S/D' }})</option>
                                @endforeach
                            @endisset
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Fecha del Turno *</label>
                            <input type="date" name="fecha" value="{{ now()->isWeekend() ? now()->toDateString() : now()->next(\Carbon\Carbon::SATURDAY)->toDateString() }}" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Labor Asignada *</label>
                            <select name="rol_asignado" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <option value="alimentador">Alimentador Diurno</option>
                                <option value="seguridad_noche">Seguridad Nocturna</option>
                            </select>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-800/80 border border-slate-700/80 text-[11px] text-slate-300">
                        <i class="fa-solid fa-shield-halved text-amber-400 mr-1"></i>
                        Este turno es rotativo e independiente y <strong>no arrastra</strong> turnos nocturnos previos.
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Observaciones (Opcional)</label>
                        <textarea name="observaciones" rows="2" placeholder="Instrucciones para el fin de semana..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-amber-500 focus:outline-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="modalFinde = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold shadow-lg shadow-amber-600/30 transition flex items-center gap-1.5">
                            <i class="fa-solid fa-check"></i> Asignar Turno Fin de Semana
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Notificación Toast Flotante -->
    <div id="toast" class="fixed bottom-5 right-5 z-50 transform transition-all duration-300 translate-y-20 opacity-0 bg-slate-800 border border-slate-700 text-white px-4 py-3 rounded-xl shadow-2xl flex items-center gap-3">
        <div id="toast-icon" class="text-emerald-400 text-lg"></div>
        <div id="toast-message" class="text-xs font-medium"></div>
    </div>

    <!-- Script de Lógica en Vivo, Sincronización y Almacenamiento Offline -->
    <script>
        const STORAGE_KEY = 'sas_offline_agenda_events';
        let serverTimeOffset = 0; // Diferencia de tiempo con el servidor en ms

        // 1. Inicialización
        document.addEventListener('DOMContentLoaded', () => {
            initLiveClock();
            setupConnectivityWatchers();
            setDefaultDate();
            checkOfflineQueue();
            fetchEvents();
        });

        // 2. Reloj en Vivo sincronizado (12 horas AM/PM - America/Bogota)
        function initLiveClock() {
            // Obtener hora precisa del servidor bajo zona horaria America/Bogota
            fetch('/api/calendar-events/server-time')
                .then(res => res.json())
                .then(data => {
                    const serverDate = new Date(data.server_time);
                    serverTimeOffset = serverDate.getTime() - Date.now();
                })
                .catch(() => {
                    serverTimeOffset = 0; // Modo offline: usar hora local del dispositivo
                });

            setInterval(() => {
                const now = new Date(Date.now() + serverTimeOffset);
                let rawHours = now.getHours();
                const ampm = rawHours >= 12 ? 'PM' : 'AM';
                const hours12 = rawHours % 12 || 12;
                const hoursStr = String(hours12).padStart(2, '0');
                const minutesStr = String(now.getMinutes()).padStart(2, '0');
                const secondsStr = String(now.getSeconds()).padStart(2, '0');

                document.getElementById('live-clock').innerHTML = `${hoursStr}:${minutesStr}:${secondsStr} <span class="text-xs font-semibold px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">${ampm}</span>`;

                const options = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric', timeZone: 'America/Bogota' };
                document.getElementById('live-date').innerText = now.toLocaleDateString('es-CO', options) + ' • Colombia';
            }, 1000);
        }

        // Conversor estricto de hora militar a formato 12 horas AM/PM
        function formatTimeTo12h(timeStr) {
            if (!timeStr) return '';
            if (timeStr.toUpperCase().includes('AM') || timeStr.toUpperCase().includes('PM')) {
                return timeStr;
            }
            const parts = timeStr.split(':');
            if (parts.length >= 2) {
                let hour = parseInt(parts[0], 10);
                const minute = parts[1];
                const ampm = hour >= 12 ? 'PM' : 'AM';
                hour = hour % 12 || 12;
                return `${String(hour).padStart(2, '0')}:${minute} ${ampm}`;
            }
            return timeStr;
        }

        // 3. Monitoreo de Conectividad (Online / Offline Rural)
        function setupConnectivityWatchers() {
            const updateStatus = () => {
                const isOnline = navigator.onLine;
                const badge = document.getElementById('connection-status-badge');
                const text = document.getElementById('connection-text');

                if (isOnline) {
                    badge.className = "flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30";
                    text.innerText = "En Línea";
                    // Al volver en línea, intentar sincronizar automáticamente
                    syncOfflineQueue();
                } else {
                    badge.className = "flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/30";
                    text.innerText = "Modo Fuera de Línea (Granja)";
                    showToast("Sin conexión de internet. Los eventos se guardarán localmente.", "warning");
                }
            };

            window.addEventListener('online', updateStatus);
            window.addEventListener('offline', updateStatus);
            updateStatus();
        }

        function setDefaultDate() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('event_date').value = today;
        }

        // 4. Conmutar campos según Tipo de Evento
        function toggleEventFields() {
            const type = document.getElementById('event_type').value;
            document.getElementById('fields-pesca').classList.toggle('hidden', type !== 'pesca_cosecha');
            document.getElementById('fields-alevinos').classList.toggle('hidden', type !== 'llegada_alevinos');
            document.getElementById('fields-alimento').classList.toggle('hidden', type !== 'llegada_alimento');
            document.getElementById('fields-visita').classList.toggle('hidden', type !== 'visita_general');
        }

        // 5. Envío del Formulario (con soporte Online y Offline)
        async function handleFormSubmit(event) {
            event.preventDefault();

            const clientUuid = 'offline_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            let eventTimeValue = document.getElementById('event_time').value || null;
            if (eventTimeValue) {
                eventTimeValue = formatTimeTo12h(eventTimeValue);
            }

            const titleVal = document.getElementById('title').value;
            const eventTypeVal = document.getElementById('event_type').value;
            const eventDateVal = document.getElementById('event_date').value;
            const notesVal = document.getElementById('notes').value || null;

            const payload = {
                client_uuid: clientUuid,
                title: titleVal,
                titulo: titleVal,
                event_type: eventTypeVal,
                tipo_evento: eventTypeVal,
                event_date: eventDateVal,
                fecha_programada: eventDateVal,
                event_time: eventTimeValue,
                hora_programada: eventTimeValue,
                notes: notesVal,
                notas: notesVal,
            };

            // Parámetros específicos según tipo de evento
            if (payload.event_type === 'pesca_cosecha') {
                const pondIdVal = document.getElementById('pond_id').value || null;
                const estKgVal = document.getElementById('estimated_kg').value ? parseFloat(document.getElementById('estimated_kg').value) : null;
                payload.pond_id = pondIdVal;
                payload.lago_id = pondIdVal;
                payload.estimated_kg = estKgVal;
                payload.kilos_estimados = estKgVal;
            } else if (payload.event_type === 'llegada_alevinos') {
                payload.fingerlings_quantity = document.getElementById('fingerlings_quantity').value ? parseInt(document.getElementById('fingerlings_quantity').value) : null;
                payload.stage = document.getElementById('stage').value || null;
            } else if (payload.event_type === 'llegada_alimento') {
                payload.feed_type = document.getElementById('feed_type').value || null;
                payload.feed_bags_count = document.getElementById('feed_bags_count').value ? parseInt(document.getElementById('feed_bags_count').value) : null;
                payload.feed_weight_kg = document.getElementById('feed_weight_kg').value ? parseFloat(document.getElementById('feed_weight_kg').value) : null;
            } else if (payload.event_type === 'visita_general') {
                payload.inspection_notes = document.getElementById('inspection_notes').value || null;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            if (!navigator.onLine) {
                // Guardar en almacenamiento local offline
                saveOfflineEvent(payload);
                showToast("Evento guardado localmente (Modo Offline). Se sincronizará al recuperar señal.", "info");
                resetForm();
                renderEventList();
                return;
            }

            try {
                const res = await fetch('/agenda/guardar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message || `Evento programado exitosamente. Se notificó al Administrador.`, "success");
                    resetForm();
                    // Si había cola pendiente offline, intentar sincronizarla
                    if (getOfflineQueue().length > 0) {
                        syncOfflineQueue();
                    }
                    fetchEvents();
                } else if (res.status === 422) {
                    showToast(data.message || "Por favor verifica los datos ingresados.", "danger");
                } else {
                    // Si el servidor falla, almacenar offline
                    saveOfflineEvent(payload);
                    showToast("No se pudo conectar con el servidor central. Guardado localmente.", "warning");
                    resetForm();
                }
            } catch (err) {
                // Fallo de red: guardar offline
                saveOfflineEvent(payload);
                showToast("Falla de red detectada. Guardado localmente para sincronización posterior.", "warning");
                resetForm();
            }
        }

        function resetForm() {
            document.getElementById('calendar-event-form').reset();
            setDefaultDate();
            toggleEventFields();
        }

        // 6. Almacenamiento Local Offline
        function getOfflineQueue() {
            try {
                return JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
            } catch (e) {
                return [];
            }
        }

        function saveOfflineEvent(eventPayload) {
            const queue = getOfflineQueue();
            eventPayload.is_offline_pending = true;
            queue.push(eventPayload);
            localStorage.setItem(STORAGE_KEY, JSON.stringify(queue));
            checkOfflineQueue();
            renderEventList();
        }

        function checkOfflineQueue() {
            const queue = getOfflineQueue();
            const banner = document.getElementById('offline-sync-banner');
            const count = document.getElementById('offline-queue-count');

            if (queue.length > 0) {
                banner.classList.remove('hidden');
                count.innerText = queue.length;
            } else {
                banner.classList.add('hidden');
            }
        }

        async function syncOfflineQueue() {
            const queue = getOfflineQueue();
            if (queue.length === 0 || !navigator.onLine) return;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch('/agenda/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ events: queue })
                });

                if (res.ok) {
                    localStorage.removeItem(STORAGE_KEY);
                    checkOfflineQueue();
                    showToast(`Sincronización completada: ${queue.length} evento(s) consolidados y confirmados.`, "success");
                    fetchEvents();
                }
            } catch (e) {
                console.error("Error al sincronizar cola offline", e);
            }
        }

        // 7. Cargar Eventos desde API o Almacenamiento Local
        let cachedServerEvents = [];
        async function fetchEvents() {
            try {
                const res = await fetch('/agenda/eventos', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (res.ok) {
                    const data = await res.json();
                    cachedServerEvents = data.data || [];
                    renderEventList();
                } else {
                    renderEventList();
                }
            } catch (e) {
                renderEventList();
            }
        }

        function renderEventList() {
            const offlineEvents = getOfflineQueue();
            const allEvents = [...offlineEvents, ...cachedServerEvents];
            const container = document.getElementById('events-container');
            if (document.getElementById('events-count-badge')) {
                document.getElementById('events-count-badge').innerText = allEvents.length;
            }
            if (document.getElementById('tab-events-badge')) {
                document.getElementById('tab-events-badge').innerText = allEvents.length;
            }

            if (allEvents.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-12 text-slate-500 bg-slate-800 rounded-2xl border border-slate-700">
                        <i class="fa-regular fa-calendar-xmark text-3xl mb-2 text-slate-600"></i>
                        <p class="text-sm">No hay eventos agendados actualmente.</p>
                        <p class="text-xs text-slate-500 mt-1">Usa el formulario para programar la primera actividad.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = allEvents.map(evt => {
                const isOffline = evt.is_offline_pending;
                const typeIcon = getTypeIcon(evt.event_type);
                const typeColor = getTypeColor(evt.event_type);
                const detailText = getEventDetail(evt);

                return `
                    <div class="bg-slate-800 rounded-xl p-4 border ${isOffline ? 'border-amber-500/40 bg-amber-950/10' : 'border-slate-700'} hover:border-slate-600 transition shadow">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <div class="${typeColor} p-2.5 rounded-lg border text-sm mt-0.5">
                                    <i class="${typeIcon}"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="font-bold text-sm text-white">${evt.title}</h3>
                                        ${isOffline 
                                            ? '<span class="text-[10px] font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30 px-1.5 py-0.5 rounded">Pendiente Offline</span>' 
                                            : '<span class="text-[10px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-1.5 py-0.5 rounded">Guardado / Confirmado</span>'
                                        }
                                        <span class="text-[10px] font-medium bg-slate-700 text-slate-300 px-2 py-0.5 rounded-full capitalize">${evt.status || 'programado'}</span>
                                    </div>
                                    <div class="text-xs text-emerald-400 mt-1 flex items-center gap-3">
                                        <span><i class="fa-regular fa-calendar mr-1"></i> ${evt.event_date}</span>
                                        ${evt.formatted_event_time ? `<span><i class="fa-regular fa-clock mr-1"></i> ${evt.formatted_event_time}</span>` : (evt.event_time ? `<span><i class="fa-regular fa-clock mr-1"></i> ${formatTimeTo12h(evt.event_time)}</span>` : '')}
                                    </div>
                                    <p class="text-xs text-slate-300 mt-2 bg-slate-900/60 p-2 rounded-lg border border-slate-700/50">${detailText}</p>
                                    ${evt.notes ? `<p class="text-[11px] text-slate-400 mt-1.5 italic"><i class="fa-solid fa-comment mr-1"></i> ${evt.notes}</p>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function getTypeIcon(type) {
            switch(type) {
                case 'pesca_cosecha': return 'fa-solid fa-fish';
                case 'llegada_alevinos': return 'fa-solid fa-seedling';
                case 'llegada_alimento': return 'fa-solid fa-boxes-stacked';
                case 'visita_general': return 'fa-solid fa-clipboard-check';
                default: return 'fa-regular fa-calendar';
            }
        }

        function getTypeColor(type) {
            switch(type) {
                case 'pesca_cosecha': return 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30';
                case 'llegada_alevinos': return 'bg-cyan-500/20 text-cyan-400 border-cyan-500/30';
                case 'llegada_alimento': return 'bg-amber-500/20 text-amber-400 border-amber-500/30';
                case 'visita_general': return 'bg-purple-500/20 text-purple-400 border-purple-500/30';
                default: return 'bg-slate-700 text-slate-300 border-slate-600';
            }
        }

        function getEventDetail(evt) {
            switch(evt.event_type) {
                case 'pesca_cosecha':
                    return `Pesca en Estanque ${evt.pond?.name || (evt.pond_id ? 'ID #' + evt.pond_id : 'General')} • Estimado: ${evt.estimated_kg || 0} kg`;
                case 'llegada_alevinos':
                    return `Siembra de ${evt.fingerlings_quantity || 0} alevinos • Etapa: ${evt.stage || 'General'}`;
                case 'llegada_alimento':
                    return `Concentrado: ${evt.feed_type || 'Alimento'} • ${evt.feed_bags_count || 0} bultos (${evt.feed_weight_kg || 0} kg)`;
                case 'visita_general':
                    return `Inspección: ${evt.inspection_notes || 'Revisión general del Jefe de Finca'}`;
                default:
                    return evt.notes || 'Actividad programada';
            }
        }

        // 8. Mensaje Toast
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toast-icon');
            const msg = document.getElementById('toast-message');

            msg.innerText = message;
            if (type === 'success') {
                icon.className = 'fa-solid fa-circle-check text-emerald-400 text-lg';
            } else if (type === 'warning') {
                icon.className = 'fa-solid fa-triangle-exclamation text-amber-400 text-lg';
            } else {
                icon.className = 'fa-solid fa-circle-info text-blue-400 text-lg';
            }

            toast.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('translate-y-20', 'opacity-0');
            }, 4000);
        }
    </script>
</body>
</html>
