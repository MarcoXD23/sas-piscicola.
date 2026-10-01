<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registro de Trabajadores - El SAS Piscícola</title>

    <!-- PWA Manifest & Icons -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0C332F">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="El SAS Piscícola">
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

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-slate-100 font-sans text-slate-800 antialiased flex items-center justify-center p-4">

    <div class="w-full max-w-lg my-6"
         x-data="{
             showPassword: false,
             showPasswordConfirm: false,
             isLoading: false
         }">

        <!-- Tarjeta Principal de Registro -->
        <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-7 sm:p-8">

            <!-- Encabezado Institucional -->
            <div class="text-center mb-6 pb-5 border-b border-slate-100">
                <div class="mx-auto inline-flex h-11 w-11 items-center justify-center rounded-md bg-slate-900 text-white mb-3 shadow-xs">
                    <svg class="w-5 h-5 text-slate-100" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                    </svg>
                </div>
                <h1 class="text-lg font-semibold text-slate-900 tracking-tight">
                    Registro de Personal de Finca
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    El SAS Piscícola • Incorporación de Operarios y Colaboradores
                </p>
            </div>

            <!-- Alerta Informativa del Flujo -->
            <div class="mb-5 rounded-md bg-cyan-50 border border-cyan-200 p-3.5 text-xs text-cyan-950 flex items-start gap-3">
                <svg class="w-5 h-5 text-cyan-700 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <div class="leading-relaxed">
                    <span class="font-semibold block text-cyan-900 mb-0.5">Proceso de Aprobación por la Administración</span>
                    Completa tus datos personales. Al registrarte, el Administrador recibirá una notificación por correo para validar tu ingreso y asignarte tu rol operativo correspondiente.
                </div>
            </div>

            <!-- Notificación de Errores de Validación -->
            @if ($errors->any())
                <div class="mb-5 rounded-md bg-rose-50 border border-rose-200 p-3.5 text-xs text-rose-800 space-y-1">
                    <div class="flex items-center gap-1.5 font-semibold text-rose-900">
                        <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                        <span>Revisa los siguientes campos requeridos:</span>
                    </div>
                    @foreach ($errors->all() as $error)
                        <p class="text-[11px] text-rose-700 pl-4">• {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- Formulario de Registro -->
            <form method="POST" action="{{ route('register') }}" @submit="isLoading = true" class="space-y-4">
                @csrf

                <!-- Campo 1: Nombre Completo -->
                <div>
                    <label for="name" class="block text-xs font-medium text-slate-700 mb-1">
                        Nombre Completo <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               required
                               autofocus
                               placeholder="ej. Carlos Arturo Rodríguez Pérez"
                               class="block w-full rounded-md border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 transition">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="fa-regular fa-user text-xs"></i>
                        </div>
                    </div>
                </div>

                <!-- Campo 2: Cédula de Ciudadanía -->
                <div>
                    <label for="document_number" class="block text-xs font-medium text-slate-700 mb-1">
                        Cédula de Ciudadanía / Documento de Identidad <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                               id="document_number"
                               name="document_number"
                               value="{{ old('document_number') }}"
                               required
                               placeholder="ej. 1070123456"
                               class="block w-full rounded-md border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 transition">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="fa-regular fa-id-card text-xs"></i>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">Con este documento podrás acceder rápidamente a tu panel de campo.</p>
                </div>

                <!-- Campo 3: Correo Electrónico -->
                <div>
                    <label for="email" class="block text-xs font-medium text-slate-700 mb-1">
                        Correo Electrónico <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               required
                               placeholder="ej. trabajador@correo.com"
                               class="block w-full rounded-md border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 transition">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="fa-regular fa-envelope text-xs"></i>
                        </div>
                    </div>
                </div>

                <!-- Campo 4: Finca de Trabajo -->
                @if(isset($fincas) && $fincas->count() > 1)
                    <div>
                        <label for="finca_id" class="block text-xs font-medium text-slate-700 mb-1">
                            Finca o Sede de Operaciones <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <select id="finca_id"
                                    name="finca_id"
                                    required
                                    class="block w-full rounded-md border border-slate-300 bg-white py-2 pl-9 pr-8 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 transition appearance-none cursor-pointer">
                                @foreach($fincas as $finca)
                                    <option value="{{ $finca->id }}" {{ old('finca_id') == $finca->id ? 'selected' : '' }}>
                                        {{ $finca->nombre }} ({{ $finca->codigo }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="fa-solid fa-water text-xs"></i>
                            </div>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                                <i class="fa-solid fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>
                @else
                    <input type="hidden" name="finca_id" value="{{ $fincas->first()?->id ?? 1 }}">
                @endif

                <!-- Campos de Contraseña -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <!-- Contraseña -->
                    <div>
                        <label for="password" class="block text-xs font-medium text-slate-700 mb-1">
                            Contraseña <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'"
                                   id="password"
                                   name="password"
                                   required
                                   placeholder="Mínimo 8 caracteres"
                                   class="block w-full rounded-md border border-slate-300 bg-white py-2 pl-3 pr-9 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 transition">
                            <button type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 transition"
                                    title="Mostrar / Ocultar">
                                <i class="fa-solid text-xs" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-medium text-slate-700 mb-1">
                            Confirmar Contraseña <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showPasswordConfirm ? 'text' : 'password'"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   required
                                   placeholder="Repite la contraseña"
                                   class="block w-full rounded-md border border-slate-300 bg-white py-2 pl-3 pr-9 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 transition">
                            <button type="button"
                                    @click="showPasswordConfirm = !showPasswordConfirm"
                                    class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 transition"
                                    title="Mostrar / Ocultar">
                                <i class="fa-solid text-xs" :class="showPasswordConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Botón de Envío -->
                <div class="pt-3">
                    <button type="submit"
                            :disabled="isLoading"
                            class="w-full flex items-center justify-center gap-2 rounded-md bg-slate-900 hover:bg-slate-800 text-white font-medium py-2.5 px-4 text-sm shadow-xs transition disabled:opacity-50">
                        <span x-show="isLoading" class="w-4 h-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
                        <svg x-show="!isLoading" class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                        <span x-text="isLoading ? 'Enviando Registro...' : 'Registrarme en la Finca'">Registrarme en la Finca</span>
                    </button>
                </div>
            </form>

            <!-- Enlace para Volver al Login -->
            <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-600">
                    ¿Ya tienes una cuenta activa en el sistema?
                </p>
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-1.5 mt-2 text-xs font-semibold text-slate-900 hover:text-slate-700 transition">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i>
                    <span>Volver a Iniciar Sesión</span>
                </a>
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
