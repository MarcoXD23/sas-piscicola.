<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar Sesión - Sistema de Gestión Acuícola - El SAS Piscícola</title>

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0C332F">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="AquaSmart">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

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

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-slate-100 font-sans text-slate-800 antialiased flex items-center justify-center p-4">

    <div class="w-full max-w-md"
         x-data="{
             selectedRole: '{{ old('role', 'propietario') }}',
             showPassword: false,
             loginValue: '{{ old('login', old('email', '')) }}',
             passwordValue: '',
             isLoading: false,

             setDemo(userLogin, pass, role) {
                 this.loginValue = userLogin;
                 this.passwordValue = pass;
                 this.selectedRole = role;
             }
         }">

        <!-- Tarjeta Central Corporativa -->
        <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-7 sm:p-8">

            <!-- Encabezado Institucional -->
            <div class="text-center mb-6 pb-5 border-b border-slate-100">
                <div class="mx-auto inline-flex h-11 w-11 items-center justify-center rounded-md bg-slate-900 text-white mb-3 shadow-xs">
                    <svg class="w-5 h-5 text-slate-100" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </div>
                <h1 class="text-lg font-semibold text-slate-900 tracking-tight">
                    Sistema de Gestión Acuícola
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    El SAS Piscícola • Portal Institucional
                </p>
            </div>

            <!-- Notificaciones y Mensajes de Estado / Flash -->
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-800 flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if (session('info'))
                <div class="mb-4 rounded-md bg-slate-50 border border-slate-200 p-3 text-xs text-slate-700 flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-slate-500"></i>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800 space-y-1">
                    <div class="flex items-center gap-1.5 font-semibold text-rose-900">
                        <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                        <span>Credenciales inválidas</span>
                    </div>
                    @foreach ($errors->all() as $error)
                        <p class="text-[11px] text-rose-700 pl-4">• {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- Formulario de Acceso -->
            <form method="POST" action="{{ route('login') }}" @submit="isLoading = true" class="space-y-4">
                @csrf

                <!-- Campo 1: Identificador / Correo -->
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">
                        Identificador / Correo Electrónico
                    </label>
                    <input type="text"
                           name="login"
                           x-model="loginValue"
                           required
                           autofocus
                           placeholder="ej. admin@finca.com o operario1@finca.com"
                           class="block w-full rounded-md border border-slate-300 bg-white py-2 px-3 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 transition">
                </div>

                <!-- Campo 2: Selector Desplegable de Rol -->
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">
                        Rol Institucional
                    </label>
                    <div class="relative">
                        <select name="role"
                                x-model="selectedRole"
                                required
                                class="block w-full rounded-md border border-slate-300 bg-white py-2 px-3 pr-8 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 transition appearance-none cursor-pointer">
                            <option value="" disabled>Selecciona tu Rol</option>
                            <option value="propietario">Propietario / Gerente General (Jefe Mayor)</option>
                            <option value="administrador">Administrador / Técnico Acuícola</option>
                            <option value="trabajador">Trabajador de Campo / Operario</option>
                            <option value="celador_nocturno">Celador Nocturno</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>

                <!-- Campo 3: Contraseña -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-medium text-slate-700">
                            Contraseña
                        </label>
                    </div>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'"
                               name="password"
                               x-model="passwordValue"
                               required
                               placeholder="Ingresa tu contraseña"
                               class="block w-full rounded-md border border-slate-300 bg-white py-2 px-3 pr-10 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 transition">
                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 transition"
                                title="Mostrar / Ocultar">
                            <i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Recordar Sesión -->
                <div class="flex items-center justify-between pt-0.5">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               name="remember"
                               class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                        <span class="text-xs text-slate-600">Mantener sesión activa</span>
                    </label>
                </div>

                <!-- Botón de Envío -->
                <div class="pt-2">
                    <button type="submit"
                            :disabled="isLoading"
                            class="w-full flex items-center justify-center gap-2 rounded-md bg-slate-900 hover:bg-slate-800 text-white font-medium py-2.5 px-4 text-sm shadow-xs transition disabled:opacity-50">
                        <span x-show="isLoading" class="w-4 h-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
                        <span x-text="isLoading ? 'Autenticando...' : 'Iniciar Sesión'">Iniciar Sesión</span>
                    </button>
                </div>
            </form>

            <!-- Registro de Nuevos Trabajadores -->
            <div class="mt-4 pt-4 border-t border-slate-100 text-center">
                <span class="text-xs text-slate-500">¿Eres nuevo operario o colaborador de la finca?</span>
                <div class="mt-1">
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-900 hover:text-cyan-700 transition">
                        <i class="fa-solid fa-user-plus text-[11px] text-cyan-600"></i>
                        <span>Regístrate aquí para solicitar activación de rol</span>
                    </a>
                </div>
            </div>

            <!-- Accesos Rápidos de Prueba / Demostración -->
            <div class="mt-5 pt-4 border-t border-slate-100">
                <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wider block mb-2 text-center">
                    Cuentas de Acceso Rápido
                </span>
                <div class="grid grid-cols-2 gap-2 text-left">
                    <button type="button"
                            @click="setDemo('propietario@finca.com', 'password123', 'propietario')"
                            class="p-2 rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 hover:border-slate-300 text-slate-700 text-xs transition">
                        <span class="block font-semibold text-slate-900 text-xs">Propietario / Gerente</span>
                        <span class="text-[11px] text-slate-500 block truncate font-mono">propietario@finca.com</span>
                    </button>

                    <button type="button"
                            @click="setDemo('admin@finca.com', 'password123', 'administrador')"
                            class="p-2 rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 hover:border-slate-300 text-slate-700 text-xs transition">
                        <span class="block font-semibold text-slate-900 text-xs">Administrador</span>
                        <span class="text-[11px] text-slate-500 block truncate font-mono">admin@finca.com</span>
                    </button>

                    <button type="button"
                            @click="setDemo('trabajador@finca.com', 'password123', 'trabajador')"
                            class="p-2 rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 hover:border-slate-300 text-slate-700 text-xs transition">
                        <span class="block font-semibold text-slate-900 text-xs">Operario de Campo</span>
                        <span class="text-[11px] text-slate-500 block truncate font-mono">trabajador@finca.com</span>
                    </button>

                    <button type="button"
                            @click="setDemo('celador@finca.com', 'password123', 'celador_nocturno')"
                            class="p-2 rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 hover:border-slate-300 text-slate-700 text-xs transition">
                        <span class="block font-semibold text-slate-900 text-xs">Celador Nocturno</span>
                        <span class="text-[11px] text-slate-500 block truncate font-mono">celador@finca.com</span>
                    </button>
                </div>
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
