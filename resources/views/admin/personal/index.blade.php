@extends('layouts.app')

@section('title', 'Gestión de Personal & Roles - El SAS Piscícola')

@section('content')
<div class="space-y-6">

    <!-- Encabezado de Página -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                <span>Personal & Nómina</span>
                <span>•</span>
                <span class="text-cyan-600">Gestión de Equipo</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Personal, Registro y Asignación de Roles
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
                Valida las solicitudes de ingreso de nuevos trabajadores y administra los roles operativos y modalidades de pago.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('nomina.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>Ir a Liquidación Sábado</span>
            </a>
        </div>
    </div>

    <!-- Notificaciones de Éxito / Error -->
    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 space-y-1">
            <div class="flex items-center gap-2 font-semibold text-rose-900">
                <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                <span>No se pudo procesar la asignación de rol:</span>
            </div>
            @foreach($errors->all() as $error)
                <p class="text-xs text-rose-700 pl-4">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <!-- Tarjetas de Resumen KPI -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Solicitudes Pendientes -->
        <div class="rounded-xl border {{ $totalPendientes > 0 ? 'border-amber-300 bg-amber-50/50' : 'border-slate-200 bg-white' }} p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold {{ $totalPendientes > 0 ? 'text-amber-800' : 'text-slate-500' }} uppercase tracking-wider">
                    Pendientes de Aprobación
                </span>
                <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $totalPendientes > 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold {{ $totalPendientes > 0 ? 'text-amber-900' : 'text-slate-900' }}">
                    {{ $totalPendientes }}
                </span>
                @if($totalPendientes > 0)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-200 text-amber-900">
                        Atención requerida
                    </span>
                @else
                    <span class="text-xs text-slate-500 font-medium">Al día</span>
                @endif
            </div>
        </div>

        <!-- 2. Trabajadores de Campo -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Trabajadores de Campo</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900">{{ $totalTrabajadores }}</span>
                <span class="text-xs text-slate-500 font-medium">Operarios activos</span>
            </div>
        </div>

        <!-- 3. Celadores Nocturnos -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Celadores Nocturnos</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900">{{ $totalCeladores }}</span>
                <span class="text-xs text-slate-500 font-medium">Turno de noche</span>
            </div>
        </div>

        <!-- 4. Administración & Jefatura -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Administración & Jefes</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-cyan-50 text-cyan-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900">{{ $totalAdministracion }}</span>
                <span class="text-xs text-slate-500 font-medium">Supervisión</span>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 1: TRABAJADORES PENDIENTES DE APROBACIÓN -->
    <div class="rounded-xl border border-slate-200 bg-white shadow-xs overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                    <span>Nuevas Solicitudes de Registro (Pendientes de Aprobación)</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Operarios que crearon su cuenta y están a la espera de que les definas su rol operativo para ingresar.
                </p>
            </div>
            @if($totalPendientes > 0)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                    {{ $totalPendientes }} {{ $totalPendientes === 1 ? 'aspirante por revisar' : 'aspirantes por revisar' }}
                </span>
            @endif
        </div>

        @if($pendingUsers->isEmpty())
            <div class="p-8 text-center">
                <div class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">No hay solicitudes pendientes de aprobación</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Cuando un nuevo trabajador se registre mediante el formulario institucional, aparecerá en esta sección y se te notificará por correo.
                </p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($pendingUsers as $pending)
                    <div class="p-5 hover:bg-slate-50/60 transition" x-data="{ openForm: true }">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <!-- Datos del Solicitante -->
                            <div class="flex items-start gap-3.5">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-900 text-white font-bold text-sm shadow-xs">
                                    {{ strtoupper(substr($pending->name, 0, 2)) }}
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-bold text-slate-900">{{ $pending->name }}</h3>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                            Pendiente
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                                        <span class="flex items-center gap-1.5 font-mono">
                                            <i class="fa-regular fa-id-card text-slate-400"></i>
                                            <strong>Cédula:</strong> {{ $pending->document_number ?? 'No registrada' }}
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <i class="fa-regular fa-envelope text-slate-400"></i>
                                            {{ $pending->email }}
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <i class="fa-regular fa-clock text-slate-400"></i>
                                            Registrado: {{ $pending->created_at ? $pending->created_at->timezone('America/Bogota')->format('d/m/Y h:i A') : 'Reciente' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Formulario de Aprobación y Asignación Directa -->
                            <form method="POST" action="{{ route('admin.personal.asignar-rol', $pending) }}" class="flex flex-col gap-3 bg-slate-100/70 p-3 rounded-lg border border-slate-200 w-full lg:w-auto">
                                @csrf

                                <!-- Checkboxes Multi-Rol de Campo -->
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1.5">Asignar Roles de Campo (Selección Múltiple):</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                        <label class="inline-flex items-center gap-2 p-2 rounded-md bg-white border border-slate-200 text-xs font-medium text-slate-800 hover:bg-slate-50 cursor-pointer shadow-2xs">
                                            <input type="checkbox" name="roles[]" value="operario_alimentador" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                            <span>Operario Alimentador</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2 p-2 rounded-md bg-white border border-slate-200 text-xs font-medium text-slate-800 hover:bg-slate-50 cursor-pointer shadow-2xs">
                                            <input type="checkbox" name="roles[]" value="celador" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                            <span>Celador</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2 p-2 rounded-md bg-white border border-slate-200 text-xs font-medium text-slate-800 hover:bg-slate-50 cursor-pointer shadow-2xs">
                                            <input type="checkbox" name="roles[]" value="tecnico_acuicola" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                                            <span>Técnico Acuícola</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                                    <!-- Selector de Contrato -->
                                    <div class="flex items-center gap-2">
                                        <label class="text-[11px] font-bold text-slate-600 uppercase">Modalidad:</label>
                                        <select name="employment_type" required class="rounded-md border border-slate-300 bg-white py-1 px-2.5 text-xs text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 font-medium">
                                            <option value="fijo">Fijo Mensual</option>
                                            <option value="destajo_semanal" selected>Destajo Semanal</option>
                                            <option value="temporal">Temporal</option>
                                        </select>
                                    </div>

                                    <!-- Botón Aprobar -->
                                    <button type="submit"
                                            class="inline-flex items-center justify-center gap-1.5 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-1.5 px-4 text-xs shadow-xs transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        <span>Aprobar y Asignar Rol</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- SECCIÓN 2: PERSONAL ACTIVO DE LA FINCA -->
    <div class="rounded-xl border border-slate-200 bg-white shadow-xs overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z" />
                    </svg>
                    <span>Equipo y Colaboradores Activos ({{ $activeUsers->count() }})</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Listado oficial de operarios, celadores y personal de supervisión autorizados para acceder a los módulos de la finca.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Colaborador / Documento</th>
                        <th class="px-5 py-3.5">Correo Electrónico</th>
                        <th class="px-5 py-3.5">Rol en Finca</th>
                        <th class="px-5 py-3.5">Modalidad Contrato</th>
                        <th class="px-5 py-3.5">Estado</th>
                        <th class="px-5 py-3.5 text-right">Modificar Rol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($activeUsers as $collaborator)
                        @php
                            $roleBadgeColor = match($collaborator->role) {
                                'jefe_mayor', 'owner', 'jefe_finca', 'jefe' => 'bg-purple-50 text-purple-700 border-purple-200',
                                'administrador', 'admin' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                'celador_nocturno', 'guard', 'celador' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                default => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            };
                            $roleLabel = match($collaborator->role) {
                                'jefe_mayor', 'owner', 'jefe_finca', 'jefe' => 'Jefe de Finca / Dueño',
                                'administrador', 'admin' => 'Administrador',
                                'celador_nocturno', 'guard', 'celador' => 'Celador Nocturno',
                                default => 'Trabajador de Campo',
                            };
                            $contractLabel = match($collaborator->employment_type) {
                                'fijo' => 'Fijo Mensual',
                                'destajo_semanal' => 'Destajo Semanal (Sabatino)',
                                default => 'Temporal',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition" x-data="{ editing: false }">
                            <td class="px-5 py-3.5">
                                <div class="font-bold text-slate-900">{{ $collaborator->name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono flex items-center gap-1 mt-0.5">
                                    <i class="fa-regular fa-id-card text-slate-400"></i>
                                    <span>C.C. {{ $collaborator->document_number ?? 'Sin documento' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-slate-700 font-medium">
                                {{ $collaborator->email }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if($collaborator->roles && $collaborator->roles->isNotEmpty())
                                        @foreach($collaborator->roles as $rol)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $rol->badge_color }}">
                                                {{ $rol->name }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $roleBadgeColor }}">
                                            {{ $roleLabel }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3.5 font-medium text-slate-700">
                                {{ $contractLabel }}
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center gap-1.5 text-emerald-700 font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Activo
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @if(! $collaborator->isPropietario())
                                    <button type="button"
                                            @click="editing = !editing"
                                            class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white hover:bg-slate-50 px-2.5 py-1 text-slate-700 font-medium shadow-2xs transition">
                                        <i class="fa-solid fa-pen text-[10px] text-slate-500"></i>
                                        <span x-text="editing ? 'Cancelar' : 'Reasignar'">Reasignar</span>
                                    </button>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        Propietario
                                    </span>
                                @endif
                            </td>

                            <!-- Fila Expandible para Reasignación de Rol / Contrato -->
                            <template x-if="editing">
                                <tr class="bg-slate-50 border-t border-b border-slate-200">
                                    <td colspan="6" class="px-5 py-3">
                                        <form method="POST" action="{{ route('admin.personal.asignar-rol', $collaborator) }}" class="flex flex-wrap items-center justify-end gap-3">
                                            @csrf
                                            <span class="text-xs font-semibold text-slate-700">Actualizar roles de {{ $collaborator->name }}:</span>

                                            <div class="flex flex-wrap items-center gap-2">
                                                <label class="inline-flex items-center gap-1 text-[11px] text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200">
                                                    <input type="checkbox" name="roles[]" value="operario_alimentador" {{ $collaborator->hasRole(['operario_alimentador', 'operario_campo', 'trabajador']) ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500">
                                                    <span>Operario Alimentador</span>
                                                </label>
                                                <label class="inline-flex items-center gap-1 text-[11px] text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200">
                                                    <input type="checkbox" name="roles[]" value="celador" {{ $collaborator->hasRole(['celador', 'celador_nocturno']) ? 'checked' : '' }} class="rounded text-indigo-600 focus:ring-indigo-500">
                                                    <span>Celador</span>
                                                </label>
                                                <label class="inline-flex items-center gap-1 text-[11px] text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200">
                                                    <input type="checkbox" name="roles[]" value="tecnico_acuicola" {{ $collaborator->hasRole('tecnico_acuicola') ? 'checked' : '' }} class="rounded text-cyan-600 focus:ring-cyan-500">
                                                    <span>Técnico Acuícola</span>
                                                </label>
                                            </div>

                                            <div class="flex items-center gap-1.5">
                                                <label class="text-[11px] font-bold text-slate-500">Contrato:</label>
                                                <select name="employment_type" required class="rounded-md border border-slate-300 bg-white py-1 px-2 text-xs text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                                                    <option value="fijo" {{ $collaborator->employment_type === 'fijo' ? 'selected' : '' }}>Fijo Mensual</option>
                                                    <option value="destajo_semanal" {{ $collaborator->employment_type === 'destajo_semanal' ? 'selected' : '' }}>Destajo Semanal</option>
                                                    <option value="temporal" {{ $collaborator->employment_type === 'temporal' ? 'selected' : '' }}>Temporal</option>
                                                </select>
                                            </div>

                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-md bg-slate-900 hover:bg-slate-800 text-white font-medium py-1 px-3 text-xs shadow-xs transition">
                                                <i class="fa-solid fa-check text-[10px]"></i>
                                                <span>Guardar Cambios</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            </template>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-6 text-center text-xs text-slate-500">
                                No se encontraron colaboradores registrados en la base de datos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
