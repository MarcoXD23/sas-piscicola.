<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cuenta Pendiente de Aprobación - El SAS Piscícola</title>

    <!-- PWA Manifest & Icons -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0C332F">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="AquaSmart">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">

    <!-- Tailwind CSS CDN Fallback -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="h-full bg-slate-100 font-sans text-slate-800 antialiased flex items-center justify-center p-4">

    <div class="w-full max-w-lg my-6">

        <!-- Tarjeta Informativa Central -->
        <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-7 sm:p-8">

            <!-- Icono y Encabezado de Estado -->
            <div class="text-center mb-6 pb-5 border-b border-slate-100">
                <div class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 border border-amber-200 text-amber-600 mb-3 shadow-xs">
                    <svg class="w-7 h-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                    Cuenta Pendiente de Activación
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    El Administrador de la finca debe asignar tu rol operativo
                </p>
            </div>

            <!-- Notificación Flash -->
            @if (session('status'))
                <div class="mb-5 rounded-md bg-emerald-50 border border-emerald-200 p-3.5 text-xs text-emerald-800 flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if (session('info'))
                <div class="mb-5 rounded-md bg-slate-50 border border-slate-200 p-3.5 text-xs text-slate-700 flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-info text-slate-500"></i>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            <!-- Explicación Técnica y Pasos Siguientes -->
            <div class="space-y-4 text-xs text-slate-600 leading-relaxed">
                <div class="rounded-md bg-slate-50 border border-slate-200 p-4">
                    <h2 class="font-semibold text-slate-900 text-sm mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Registro Recibido en el Sistema
                    </h2>
                    <p class="text-slate-600">
                        Tu información ha quedado guardada de forma segura en la base de datos de la finca. Por protocolo operativo, cada trabajador debe ser validado por la administración antes de acceder a los módulos de campo.
                    </p>
                </div>

                @if(isset($user) && $user)
                <!-- Resumen de Datos Registrados -->
                <div class="rounded-md border border-slate-200 divide-y divide-slate-100 text-xs">
                    <div class="flex justify-between px-3.5 py-2.5 bg-slate-50/50">
                        <span class="text-slate-500">Trabajador:</span>
                        <span class="font-semibold text-slate-800">{{ $user->name }}</span>
                    </div>
                    <div class="flex justify-between px-3.5 py-2.5">
                        <span class="text-slate-500">Cédula de Ciudadanía:</span>
                        <span class="font-mono font-medium text-slate-800">{{ $user->document_number ?? 'Pendiente' }}</span>
                    </div>
                    <div class="flex justify-between px-3.5 py-2.5">
                        <span class="text-slate-500">Correo Electrónico:</span>
                        <span class="font-medium text-slate-800">{{ $user->email }}</span>
                    </div>
                    <div class="flex justify-between px-3.5 py-2.5">
                        <span class="text-slate-500">Finca Asignada:</span>
                        <span class="font-medium text-slate-800">{{ $user->finca?->nombre ?? 'Finca Piscícola Principal' }}</span>
                    </div>
                    <div class="flex justify-between px-3.5 py-2.5 bg-slate-50/50">
                        <span class="text-slate-500">Estado Actual:</span>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            Pendiente de Asignación de Rol
                        </span>
                    </div>
                </div>
                @endif

                <!-- Aviso de Correo al Administrador -->
                <div class="rounded-md bg-cyan-50/80 border border-cyan-200 p-3.5 flex items-start gap-3">
                    <svg class="w-5 h-5 text-cyan-700 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    <div class="text-[11px] text-cyan-900 leading-relaxed">
                        <span class="font-semibold block text-cyan-950">Notificación Enviada</span>
                        El Administrador ya recibió un correo con tus datos para activar tu usuario y asignar si laboras como <strong class="font-semibold">Trabajador de Campo</strong>, <strong class="font-semibold">Celador Nocturno</strong> u otro cargo operativo.
                    </div>
                </div>
            </div>

            <!-- Acciones Disponibles -->
            <div class="mt-6 pt-5 border-t border-slate-100 flex flex-col sm:flex-row gap-2">
                <button type="button"
                        onclick="window.location.reload()"
                        class="flex-1 flex items-center justify-center gap-2 rounded-md bg-slate-900 hover:bg-slate-800 text-white font-medium py-2.5 px-4 text-xs transition shadow-xs">
                    <i class="fa-solid fa-arrows-rotate text-xs"></i>
                    <span>Comprobar Estado de Activación</span>
                </button>

                @auth
                <form method="POST" action="{{ route('logout') }}" class="sm:flex-shrink-0">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 rounded-md border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-medium py-2.5 px-4 text-xs transition">
                        <i class="fa-solid fa-arrow-right-from-bracket text-xs text-slate-400"></i>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
                @else
                <a href="{{ route('login') }}"
                   class="flex items-center justify-center gap-2 rounded-md border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-medium py-2.5 px-4 text-xs transition">
                    <i class="fa-solid fa-arrow-left text-xs text-slate-400"></i>
                    <span>Ir a Iniciar Sesión</span>
                </a>
                @endauth
            </div>

        </div>

        <!-- Pie Institucional -->
        <div class="mt-5 text-center text-xs text-slate-500 space-y-0.5">
            <p>El SAS Piscícola &copy; {{ date('Y') }} • ERP Acuícola Corporativo</p>
            <p class="text-[11px] text-slate-400">Colombia • Zona Horaria America/Bogota</p>
        </div>

    </div>

</body>
</html>
