<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel de Control') - El SAS Piscícola ERP</title>

    <!-- PWA Manifest & Configuración Híbrida Móvil -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0C332F">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="AquaSmart">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">

    <!-- Vite Assets (Compilados para Producción y HMR en Desarrollo) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Tailwind CSS CDN Fallback & Configuración Dinámica -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        aqua: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                            950: '#042f2e',
                        },
                        brand: {
                            primary: '#0284c7', // Sky 600
                            dark: '#0369a1',
                            deep: '#0c4a6e',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }
        .pulse-live {
            animation: pulse-dot 2s infinite ease-in-out;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="h-full bg-slate-100 font-sans text-slate-800 antialiased"
      x-data="globalApp()"
      x-init="initApp()">

    <div class="flex h-full min-h-screen overflow-hidden">

        <!-- ==================== SIDEBAR (MENU LATERAL) ==================== -->
        <!-- Mobile Backdrop -->
        <div x-show="sidebarOpen"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden"
             x-cloak></div>

        <!-- Sidebar Container -->
        <aside :class="{
                   'translate-x-0': sidebarOpen,
                   '-translate-x-full': !sidebarOpen,
                   'lg:w-64': !sidebarCollapsed,
                   'lg:w-20': sidebarCollapsed
               }"
               class="fixed inset-y-0 left-0 z-50 flex flex-col bg-slate-950 text-white transition-all duration-300 ease-in-out lg:static lg:translate-x-0 shadow-2xl border-r border-slate-800">

            <!-- Logo & Brand Header -->
            <div class="flex h-16 items-center justify-between px-4 border-b border-slate-800/80 bg-slate-950">
                <a href="{{ route('dashboard.index') }}" class="flex items-center gap-3 overflow-hidden">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-cyan-600 to-teal-400 text-white shadow-lg shadow-cyan-500/20">
                        <i class="fa-solid fa-fish-fins text-lg"></i>
                    </div>
                    <div class="transition-opacity duration-200" :class="{ 'lg:hidden': sidebarCollapsed }">
                        <span class="block text-sm font-extrabold tracking-tight text-white leading-tight">El SAS Piscícola</span>
                        <span class="block text-[10px] font-semibold text-cyan-400 tracking-wider uppercase">ERP Acuícola</span>
                    </div>
                </a>

                <!-- Close button for mobile -->
                <button @click="sidebarOpen = false" class="text-slate-400 hover:text-white lg:hidden">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Role Badge Pill -->
            @php
                $badgeHoy = auth()->check() ? auth()->user()->badgeRolHoy() : ['label' => 'Invitado', 'sublabel' => '', 'color' => 'bg-slate-700 text-slate-300 border-slate-600'];
            @endphp
            <div class="px-4 py-3 border-b border-slate-800/50 bg-slate-900/40" :class="{ 'lg:hidden': sidebarCollapsed }">
                <div class="flex flex-col gap-1 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-medium text-[11px]">Rol Activo:</span>
                        <span class="px-2 py-0.5 rounded-full font-bold text-[10px] border {{ $badgeHoy['color'] }}">
                            {{ $badgeHoy['label'] }}
                        </span>
                    </div>
                    @if(!empty($badgeHoy['sublabel']))
                        <span class="text-[10px] text-slate-400 font-medium">{{ $badgeHoy['sublabel'] }}</span>
                    @endif
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 space-y-1.5 px-3 py-4 overflow-y-auto">
                <!-- 1. Dashboard Principal -->
                <a href="{{ route('dashboard.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('dashboard.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Dashboard Principal">
                    <i class="fa-solid fa-chart-pie text-base w-5 text-center text-cyan-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Dashboard Ejecutivo</span>
                </a>

                <!-- 2. Agenda y Calendario en Vivo -->
                <a href="{{ route('agenda.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('agenda.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Agenda & Calendario en Vivo">
                    <i class="fa-regular fa-calendar-days text-base w-5 text-center text-emerald-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Agenda Operativa</span>
                </a>

                <!-- Divider -->
                <div class="pt-3 pb-1" :class="{ 'lg:hidden': sidebarCollapsed }">
                    <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Operaciones & Cosecha</span>
                </div>

                {{-- Lagos y Muestreos (Exclusivo Administrador, Jefe Mayor, Técnico y Propietario) --}}
                @if(auth()->check() && (auth()->user()->hasRole(["administrador", "admin", "jefe_mayor", "owner", "tecnico_acuicola", "propietario"]) || auth()->user()->isPropietario()))
                <a href="{{ route('admin.lagos.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('admin.lagos.*') || request()->routeIs('admin.muestreos.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Lagos y Muestreos">
                    <i class="fa-solid fa-water text-base w-5 text-center text-sky-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Lagos y Muestreos</span>
                </a>
                @endif

                {{-- Alimentación Diaria (Operarios, Técnicos, Administradores y Propietario) --}}
                @if(auth()->check() && (auth()->user()->hasRole(["operario_campo", "trabajador", "worker", "jefe_mayor", "owner", "administrador", "tecnico_acuicola", "propietario"]) || auth()->user()->isPropietario()))
                <a href="{{ route('operaciones.alimentacion.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('operaciones.alimentacion.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Alimentación Diaria de Estanques">
                    <i class="fa-solid fa-bowl-food text-base w-5 text-center text-emerald-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Alimentación Diaria</span>
                </a>
                @endif

                {{-- Bodega y Control de Concentrados --}}
                @if(auth()->check() && (auth()->user()->hasRole(["jefe_mayor", "owner", "administrador", "admin", "tecnico_acuicola", "propietario"]) || auth()->user()->isPropietario()))
                <a href="{{ route('admin.bodega.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('admin.bodega.*') || request()->routeIs('bodega.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Bodega y Control de Concentrados">
                    <i class="fa-solid fa-boxes-stacked text-base w-5 text-center text-amber-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Bodega & Concentrados</span>
                </a>
                @endif

                {{-- Desdobles y Traslados de Peces --}}
                @if(auth()->check() && (auth()->user()->hasRole(["jefe_mayor", "owner", "administrador", "admin", "tecnico_acuicola", "propietario"]) || auth()->user()->isPropietario()))
                <a href="{{ route('traslados.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('traslados.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Desdobles y Traslados entre Estanques">
                    <i class="fa-solid fa-arrows-split-up-and-left text-base w-5 text-center text-cyan-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Desdobles & Traslados</span>
                </a>
                @endif

                {{-- Sanidad Acuícola y Tiempo de Retiro --}}
                @if(auth()->check() && (auth()->user()->hasRole(["jefe_mayor", "owner", "administrador", "admin", "tecnico_acuicola", "propietario"]) || auth()->user()->isPropietario()))
                <a href="{{ route('sanidad.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('sanidad.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Sanidad y Tiempos de Retiro ICA">
                    <i class="fa-solid fa-notes-medical text-base w-5 text-center text-rose-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Sanidad & Retiro ICA</span>
                </a>
                @endif

                {{-- Cosechas y Báscula Limpia (Exclusivo Propietario / Jefe Mayor) --}}
                @if(auth()->check() && (auth()->user()->hasRole(['propietario', 'jefe_mayor', 'owner', 'jefe_finca', 'jefe']) || auth()->user()->isPropietario()))
                <a href="{{ route('cosechas.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('cosechas.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Báscula y Cosechas">
                    <i class="fa-solid fa-scale-balanced text-base w-5 text-center text-teal-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Báscula & Cosechas</span>
                </a>
                @endif

                {{-- Ventas y Caja Diaria (Exclusivo Propietario / Jefe Mayor) --}}
                @if(auth()->check() && (auth()->user()->hasRole(['propietario', 'jefe_mayor', 'owner', 'jefe_finca', 'jefe']) || auth()->user()->isPropietario()))
                @modulo('ventas_visitantes')
                <a href="{{ route('ventas.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('ventas.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Caja Diaria y Ventas">
                    <i class="fa-solid fa-cash-register text-base w-5 text-center text-amber-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Ventas (Caja Diaria)</span>
                </a>
                @endmodulo
                @endif

                <!-- Divider Personal & Nómina -->
                <div class="pt-3 pb-1" :class="{ 'lg:hidden': sidebarCollapsed }">
                    <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Personal & Nómina</span>
                </div>

                {{-- Nómina y Liquidación Sábado (Exclusivo Propietario / Jefe Mayor) --}}
                @if(auth()->check() && (auth()->user()->hasRole(['propietario', 'jefe_mayor', 'owner', 'jefe_finca', 'jefe']) || auth()->user()->isPropietario()))
                <a href="{{ route('nomina.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('nomina.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Nómina y Liquidación">
                    <i class="fa-solid fa-file-invoice-dollar text-base w-5 text-center text-indigo-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Liquidación Sábado</span>
                </a>
                @endif

                {{-- Personal y Asignación de Roles (Administradores, Jefes y Propietario) --}}
                @if(auth()->check() && (auth()->user()->hasRole(["propietario", "administrador", "admin", "jefe_mayor", "owner", "jefe_finca", "jefe"]) || auth()->user()->isPropietario()))
                @php
                    $pendingWorkersBadge = \App\Models\User::where('role', \App\Models\User::ROLE_PENDIENTE)->count();
                @endphp
                <a href="{{ route('admin.personal.index') }}"
                   class="group flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('admin.personal.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Personal y Asignación de Roles">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-users-gear text-base w-5 text-center text-cyan-400 group-hover:scale-110 transition-transform"></i>
                        <span :class="{ 'lg:hidden': sidebarCollapsed }">Personal & Roles</span>
                    </div>
                    @if($pendingWorkersBadge > 0)
                        <span :class="{ 'lg:hidden': sidebarCollapsed }" class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-400 text-amber-950">
                            {{ $pendingWorkersBadge }}
                        </span>
                    @endif
                </a>
                @endif

                {{-- Bitácora de Actividades de Trabajadores --}}
                @if(auth()->check() && auth()->user()->hasRole(["administrador", "admin", "jefe_mayor", "owner", "jefe_finca", "jefe", "propietario", "tecnico_acuicola"]))
                <a href="{{ route('admin.actividades.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('admin.actividades.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Bitácora de Actividades de Trabajadores">
                    <i class="fa-solid fa-clock-rotate-left text-base w-5 text-center text-teal-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Bitácora Actividades</span>
                </a>
                @endif

                <!-- 6. Guardia Nocturna (Celador & Relevo) -->
                @modulo('celador_nocturno')
                <!-- Divider -->
                <div class="pt-3 pb-1" :class="{ 'lg:hidden': sidebarCollapsed }">
                    <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Seguridad & Noche</span>
                </div>

                <a href="{{ route('celador.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('celador.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Guardia Nocturna & Aireadores">
                    <i class="fa-solid fa-moon text-base w-5 text-center text-purple-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Guardia Nocturna</span>
                </a>
                @endmodulo

                <!-- 7. Guía de Especies Piscícolas de Colombia -->
                @modulo('policultivo_avanzado')
                <!-- Divider -->
                <div class="pt-3 pb-1" :class="{ 'lg:hidden': sidebarCollapsed }">
                    <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Enciclopedia Técnica</span>
                </div>

                <a href="{{ route('guia-peces.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('guia-peces.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Guía de Especies Piscícolas">
                    <i class="fa-solid fa-fish text-base w-5 text-center text-emerald-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Guía de Peces</span>
                </a>
                @endmodulo

                @if(in_array($user->role ?? '', ['propietario', 'jefe_mayor', 'owner', 'jefe_finca', 'jefe', 'admin', 'administrador']) || (auth()->check() && auth()->user()->isPropietario()))
                <!-- Divider Configuración Finca -->
                <div class="pt-3 pb-1" :class="{ 'lg:hidden': sidebarCollapsed }">
                    <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Reportes & Dirección</span>
                </div>

                <a href="{{ route('admin.reportes.ica') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('admin.reportes.ica*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Libro de Campo Oficial ICA">
                    <i class="fa-solid fa-file-shield text-base w-5 text-center text-emerald-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Libro de Campo ICA</span>
                </a>

                <a href="{{ route('jefe.reporte_mensual') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('jefe.reporte_mensual*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Rentabilidad Mensual para Dueño">
                    <i class="fa-solid fa-chart-line text-base w-5 text-center text-amber-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Cierre Financiero</span>
                </a>

                <a href="{{ route('admin.ajustes') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('admin.ajustes*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Ajustes de Finca & Precios">
                    <i class="fa-solid fa-sliders text-base w-5 text-center text-cyan-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Ajustes de Finca</span>
                </a>

                <a href="{{ route('superadmin.fincas.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('superadmin.fincas*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   title="Gestión de Módulos (SuperAdmin)">
                    <i class="fa-solid fa-building-flag text-base w-5 text-center text-purple-400 group-hover:scale-110 transition-transform"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Módulos & Fincas</span>
                </a>
                @endif

                <!-- Cerrar Sesión en Sidebar -->
                <div class="pt-3 mt-3 border-t border-slate-800/80">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-rose-300 hover:bg-rose-950/40 hover:text-rose-100 transition duration-150"
                                title="Cerrar sesión de forma segura">
                            <i class="fa-solid fa-arrow-right-from-bracket text-base w-5 text-center text-rose-400 group-hover:scale-110 transition-transform"></i>
                            <span :class="{ 'lg:hidden': sidebarCollapsed }">Cerrar Sesión</span>
                        </button>
                    </form>
                </div>
            </nav>

            <!-- Sidebar Footer (Collapse Toggle) -->
            <div class="hidden lg:flex items-center justify-between p-3 border-t border-slate-800 bg-slate-950">
                <button @click="sidebarCollapsed = !sidebarCollapsed"
                        class="flex w-full items-center justify-center gap-2 rounded-lg py-2 text-xs font-semibold text-slate-400 hover:bg-slate-800 hover:text-white transition">
                    <i class="fa-solid" :class="sidebarCollapsed ? 'fa-angles-right' : 'fa-angles-left'"></i>
                    <span :class="{ 'lg:hidden': sidebarCollapsed }">Colapsar Menú</span>
                </button>
            </div>
        </aside>

        <!-- ==================== MAIN CONTENT AREA ==================== -->
        <div class="flex flex-1 flex-col overflow-y-auto">

            <!-- Top Bar Header -->
            <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between border-b border-slate-200/80 bg-white/95 backdrop-blur px-4 sm:px-6 lg:px-8 shadow-sm">
                <!-- Left: Hamburger button & Title Context -->
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 lg:hidden">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>

                    <div class="hidden sm:block">
                        <h2 class="text-sm font-bold text-slate-800">@yield('page_title', 'Panel de Control')</h2>
                        <p class="text-xs text-slate-500">Finca Piscícola #{{ $user->finca_id ?? 1 }} • Tolima, Colombia</p>
                    </div>
                </div>

                <!-- Right: Reloj 12h AM/PM, Conectividad Rural, Notificaciones y Perfil -->
                <div class="flex items-center gap-3 sm:gap-5">

                    <!-- Reloj en Vivo (Estricto 12 Horas AM/PM - America/Bogota) -->
                    <div class="flex items-center gap-2 rounded-xl bg-slate-900 text-white px-3.5 py-1.5 shadow-sm border border-slate-800">
                        <i class="fa-regular fa-clock text-cyan-400 text-xs"></i>
                        <div class="text-right leading-none">
                            <div class="font-mono text-xs sm:text-sm font-bold tracking-tight text-white flex items-center gap-1.5">
                                <span x-text="clockTime">12:00:00</span>
                                <span class="rounded bg-cyan-500/20 px-1 py-0.5 text-[10px] font-sans font-bold text-cyan-300 border border-cyan-500/30" x-text="clockPeriod">PM</span>
                            </div>
                            <div class="text-[9px] text-slate-400 capitalize hidden md:block" x-text="clockDate">Cargando fecha...</div>
                        </div>
                    </div>

                    <!-- Indicador de Red Rural (Online / Offline) -->
                    <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold"
                         :class="isOnline ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'">
                        <span class="w-2 h-2 rounded-full pulse-live" :class="isOnline ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                        <span x-text="isOnline ? 'En Línea' : 'Offline Granja'" class="text-[11px]"></span>
                    </div>

                    <!-- Campana de Notificaciones -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="relative rounded-xl p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition">
                            <i class="fa-regular fa-bell text-base"></i>
                            <span class="absolute top-1.5 right-1.5 flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                            </span>
                        </button>

                        <!-- Dropdown Notificaciones -->
                        <div x-show="open"
                             @click.away="open = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-80 rounded-2xl bg-white p-3 shadow-2xl border border-slate-200 z-50"
                             x-cloak>
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-2">
                                <span class="text-xs font-bold text-slate-800">Alertas Operativas</span>
                                <span class="text-[10px] text-cyan-600 font-semibold">En tiempo real</span>
                            </div>
                            <div class="space-y-2 text-xs">
                                <div class="p-2 rounded-xl bg-slate-50 border border-slate-100 hover:bg-cyan-50/50 transition">
                                    <p class="font-semibold text-slate-800 text-[11px]">Cosecha Programada</p>
                                    <p class="text-slate-500 text-[10px] mt-0.5">Jefe de Finca agendó pesca en Estanque 1 para el sábado.</p>
                                    <span class="text-[9px] text-slate-400 mt-1 block">Hace 15 min</span>
                                </div>
                                <div class="p-2 rounded-xl bg-slate-50 border border-slate-100 hover:bg-cyan-50/50 transition">
                                    <p class="font-semibold text-slate-800 text-[11px]">Concentrado Bajo</p>
                                    <p class="text-slate-500 text-[10px] mt-0.5">Stock de Mojarra 38% por debajo de 500 kg.</p>
                                    <span class="text-[9px] text-slate-400 mt-1 block">Hace 1 hora</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Badge Dinámico del Rol / Turno de Hoy en Header -->
                    @if(!empty($badgeHoy['label']))
                    <div class="hidden sm:flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-sm {{ $badgeHoy['color'] }}">
                        <i class="fa-solid fa-id-badge text-[11px]"></i>
                        <span>{{ $badgeHoy['label'] }}</span>
                    </div>
                    @endif

                    <!-- Separador Vertical -->
                    <div class="h-6 w-px bg-slate-200"></div>

                    <!-- Perfil de Usuario Activo -->
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-cyan-600 to-teal-500 text-xs font-bold text-white shadow-md shadow-cyan-600/20 uppercase">
                            {{ substr($user->name ?? 'JP', 0, 2) }}
                        </div>
                        <div class="hidden text-left sm:block">
                            <span class="block text-xs font-bold text-slate-800">{{ $user->name ?? 'Usuario SAS' }}</span>
                            <span class="block text-[10px] text-slate-500 font-medium">{{ $badgeHoy['label'] }}</span>
                        </div>

                        <!-- Botón de Cerrar Sesión -->
                        <form method="POST" action="{{ route('logout') }}" class="inline ml-1">
                            @csrf
                            <button type="submit"
                                    class="rounded-xl p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition"
                                    title="Cerrar sesión">
                                <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                            </button>
                        </form>
                    </div>

                </div>
            </header>

            <!-- Banner de Alerta Offline en Estanques -->
            <div x-show="!isOnline" x-cloak
                 class="bg-amber-500 text-slate-950 font-bold text-xs px-4 py-2 text-center flex items-center justify-center gap-2 shadow-md sticky top-0 z-30 transition-all">
                <i class="fa-solid fa-triangle-exclamation animate-bounce"></i>
                <span>Modo de Campo Sin Conexión: Operando con almacenamiento local en estanques.</span>
            </div>

            <!-- Main Page Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8 bg-slate-100/80">
                @if (session('success'))
                    <div class="mb-5 rounded-2xl bg-emerald-50 border border-emerald-200/90 p-4 text-xs text-emerald-800 flex items-center justify-between shadow-2xs"
                         x-data="{ show: true }" x-show="show" x-transition>
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                            <span class="font-semibold">{{ session('success') }}</span>
                        </div>
                        <button @click="show = false" class="text-emerald-500 hover:text-emerald-800">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Footer sutil -->
            <footer class="border-t border-slate-200 bg-white px-6 py-3 text-center text-xs text-slate-400 flex items-center justify-between hidden md:flex">
                <span>El SAS Piscícola ERP &copy; {{ date('Y') }} • Gestión Inteligente de Acuicultura</span>
                <span class="text-[11px] text-slate-400">Colombia • Zona Horaria America/Bogota (12h)</span>
            </footer>
        </div>
    </div>

    <!-- ==================== MOBILE BOTTOM NAVIGATION BAR (FIXED TOUCH) ==================== -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-slate-950/95 backdrop-blur-lg border-t border-slate-800 shadow-2xl safe-area-pb">
        <div class="grid grid-cols-5 h-16 items-center px-1">
            <!-- 1. Inicio / Dashboard -->
            <a href="{{ route('dashboard.index') }}"
               class="flex flex-col items-center justify-center h-full min-h-[44px] min-w-[44px] {{ request()->routeIs('dashboard*') ? 'text-cyan-400 font-bold' : 'text-slate-400 hover:text-slate-200' }} transition">
                <i class="fa-solid fa-gauge-high text-lg"></i>
                <span class="text-[10px] mt-1 leading-none">Inicio</span>
            </a>

            <!-- 2. Báscula / Cosechas -->
            <a href="{{ route('cosechas.index') }}"
               class="flex flex-col items-center justify-center h-full min-h-[44px] min-w-[44px] {{ request()->routeIs('cosechas*') ? 'text-cyan-400 font-bold' : 'text-slate-400 hover:text-slate-200' }} transition">
                <i class="fa-solid fa-scale-balanced text-lg"></i>
                <span class="text-[10px] mt-1 leading-none">Báscula</span>
            </a>

            <!-- 3. Guardia Nocturna / Celador (Acceso Rápido Elevado) -->
            @modulo('celador_nocturno')
            <a href="{{ route('celador.index') }}"
               class="flex flex-col items-center justify-center -mt-3 relative group min-h-[44px] min-w-[44px]">
                <div class="h-12 w-12 rounded-2xl bg-gradient-to-tr from-cyan-600 to-teal-500 text-white flex items-center justify-center shadow-lg shadow-cyan-600/40 border-2 border-slate-950 group-active:scale-95 transition">
                    <i class="fa-solid fa-shield-halved text-base"></i>
                </div>
                <span class="text-[10px] mt-1 font-bold {{ request()->routeIs('celador*') ? 'text-cyan-400' : 'text-slate-300' }} leading-none">Guardia</span>
            </a>
            @endmodulo

            <!-- 4. Ventas / Caja -->
            @modulo('ventas_visitantes')
            <a href="{{ route('ventas.index') }}"
               class="flex flex-col items-center justify-center h-full min-h-[44px] min-w-[44px] {{ request()->routeIs('ventas*') ? 'text-cyan-400 font-bold' : 'text-slate-400 hover:text-slate-200' }} transition">
                <i class="fa-solid fa-cash-register text-lg"></i>
                <span class="text-[10px] mt-1 leading-none">Ventas</span>
            </a>
            @endmodulo

            <!-- 5. Menú Completo / Drawer Toggle -->
            <button type="button" @click="sidebarOpen = true"
                    class="flex flex-col items-center justify-center h-full min-h-[44px] min-w-[44px] text-slate-400 hover:text-slate-200 transition">
                <i class="fa-solid fa-bars text-lg"></i>
                <span class="text-[10px] mt-1 leading-none">Menú</span>
            </button>
        </div>
    </nav>

    <!-- ==================== BANNER / MODAL INSTALAR PWA (A2HS) ==================== -->
    <div x-show="showInstallPrompt"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed bottom-20 md:bottom-6 right-4 left-4 md:left-auto md:w-96 z-50 bg-slate-900/95 backdrop-blur-md border border-cyan-500/40 text-white rounded-2xl p-4 shadow-2xl flex items-center gap-3.5"
         x-cloak>
        <div class="h-12 w-12 rounded-xl bg-gradient-to-tr from-cyan-600 to-teal-400 flex items-center justify-center text-white shrink-0 shadow-lg shadow-cyan-500/30 text-xl">
            <i class="fa-solid fa-mobile-screen"></i>
        </div>
        <div class="flex-1 min-w-0">
            <h4 class="text-xs font-bold text-white flex items-center gap-1.5">
                <span>Instalar El SAS Piscícola</span>
                <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">PWA</span>
            </h4>
            <p class="text-[11px] text-slate-300 mt-0.5">Acceso a pantalla completa y soporte de trabajo offline en estanques.</p>
            <div class="mt-2.5 flex items-center gap-2">
                <button @click="installApp()" class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold rounded-xl shadow-md transition active:scale-95">
                    Instalar en Celular
                </button>
                <button @click="dismissInstall()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium rounded-xl transition">
                    Ahora no
                </button>
            </div>
        </div>
        <button @click="dismissInstall()" class="text-slate-400 hover:text-white p-1 self-start" title="Cerrar">
            <i class="fa-solid fa-xmark text-xs"></i>
        </button>
    </div>

    <!-- Asistente Técnico Acuícola (IA Zootécnica y Motor Local) - Widget Flotante -->
    @modulo('asistente_ia')
    <x-asistente-ia />
    @endmodulo

    <!-- Script Global de Alpine.js: Reloj 12h AM/PM, Conectividad y PWA -->
    <script>
        function globalApp() {
            return {
                sidebarOpen: false,
                sidebarCollapsed: false,
                clockTime: '12:00:00',
                clockPeriod: 'PM',
                clockDate: '',
                isOnline: navigator.onLine,
                serverOffset: 0,
                showInstallPrompt: false,
                deferredInstallPrompt: null,

                initApp() {
                    this.initClock();
                    this.watchConnectivity();
                    this.initPwa();
                },

                initClock() {
                    // Sincronizar reloj atómico con el servidor
                    fetch('/api/calendar-events/server-time')
                        .then(r => r.json())
                        .then(d => {
                            if (d.server_time) {
                                this.serverOffset = new Date(d.server_time).getTime() - Date.now();
                            }
                        })
                        .catch(() => { this.serverOffset = 0; });

                    const updateTick = () => {
                        const now = new Date(Date.now() + this.serverOffset);
                        let hours = now.getHours();
                        this.clockPeriod = hours >= 12 ? 'PM' : 'AM';
                        hours = hours % 12 || 12; // Formato 12 horas estricto

                        const hStr = String(hours).padStart(2, '0');
                        const mStr = String(now.getMinutes()).padStart(2, '0');
                        const sStr = String(now.getSeconds()).padStart(2, '0');
                        this.clockTime = `${hStr}:${mStr}:${sStr}`;

                        const opts = { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric', timeZone: 'America/Bogota' };
                        this.clockDate = now.toLocaleDateString('es-CO', opts);
                    };

                    updateTick();
                    setInterval(updateTick, 1000);
                },

                watchConnectivity() {
                    window.addEventListener('online', () => { this.isOnline = true; });
                    window.addEventListener('offline', () => { this.isOnline = false; });
                },

                initPwa() {
                    // 1. Registro de Service Worker
                    if ('serviceWorker' in navigator) {
                        navigator.serviceWorker.register('/service-worker.js')
                            .then(reg => {
                                console.log('[PWA] Service Worker registrado en scope:', reg.scope);
                            })
                            .catch(err => {
                                console.warn('[PWA] Error registrando Service Worker:', err);
                            });
                    }

                    // 2. Capturar evento de instalación A2HS
                    window.addEventListener('beforeinstallprompt', (e) => {
                        e.preventDefault();
                        this.deferredInstallPrompt = e;
                        const dismissed = localStorage.getItem('sas_pwa_dismissed');
                        if (!dismissed) {
                            setTimeout(() => {
                                this.showInstallPrompt = true;
                            }, 2500);
                        }
                    });

                    // 3. Confirmación de app instalada
                    window.addEventListener('appinstalled', () => {
                        this.showInstallPrompt = false;
                        this.deferredInstallPrompt = null;
                        console.log('[PWA] Aplicación instalada con éxito en dispositivo');
                    });
                },

                installApp() {
                    if (this.deferredInstallPrompt) {
                        this.deferredInstallPrompt.prompt();
                        this.deferredInstallPrompt.userChoice.then((choiceResult) => {
                            if (choiceResult.outcome === 'accepted') {
                                console.log('[PWA] Usuario aceptó instalación');
                            }
                            this.deferredInstallPrompt = null;
                            this.showInstallPrompt = false;
                        });
                    }
                },

                dismissInstall() {
                    this.showInstallPrompt = false;
                    localStorage.setItem('sas_pwa_dismissed', 'true');
                }
            }
        }
    </script>
</body>
</html>
