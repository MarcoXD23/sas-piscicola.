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

        {{-- Control de Alimentación (Propietario y Administrador) --}}
        @if(auth()->check() && (auth()->user()->isPropietario() || auth()->user()->isAdmin() || auth()->user()->hasRole(["propietario", "administrador", "admin", "jefe_mayor", "owner", "tecnico_acuicola"])))
        <a href="{{ route('admin.alimentacion.historial') }}"
           class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('admin.alimentacion.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
           title="Control de Alimentación">
            <i class="fa-solid fa-clipboard-list text-base w-5 text-center text-emerald-400 group-hover:scale-110 transition-transform"></i>
            <span :class="{ 'lg:hidden': sidebarCollapsed }">Control de Alimentación</span>
        </a>
        @endif

        {{-- Suministrar Alimento (Operario / Alimentador) --}}
        @if(auth()->check() && (auth()->user()->isWorker() || auth()->user()->hasRole(["operario_alimentador", "operario_campo", "trabajador", "worker"]) || auth()->user()->isPropietario() || auth()->user()->isAdmin()))
        <a href="{{ route('operaciones.alimentacion.index') }}"
           class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('operaciones.alimentacion.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
           title="Suministrar Alimento">
            <i class="fa-solid fa-bowl-food text-base w-5 text-center text-teal-400 group-hover:scale-110 transition-transform"></i>
            <span :class="{ 'lg:hidden': sidebarCollapsed }">Suministrar Alimento</span>
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

        {{-- Registro de Actividades de Trabajadores (Exclusivo Propietario y Administrador) --}}
        @if(auth()->check() && auth()->user()->hasRole(["administrador", "admin", "jefe_mayor", "owner", "jefe_finca", "jefe", "propietario"]))
        <a href="{{ route('admin.actividades.index') }}"
           class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 {{ request()->routeIs('admin.actividades.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
           title="Registro de Actividades">
            <i class="fa-solid fa-clock-rotate-left text-base w-5 text-center text-teal-400 group-hover:scale-110 transition-transform"></i>
            <span :class="{ 'lg:hidden': sidebarCollapsed }">Registro de Actividades</span>
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

        @if(in_array(auth()->user()->role ?? '', ['propietario', 'jefe_mayor', 'owner', 'jefe_finca', 'jefe', 'admin', 'administrador']) || (auth()->check() && auth()->user()->isPropietario()))
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
