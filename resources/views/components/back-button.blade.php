@props([
    'href' => null,
    'label' => 'Volver al Dashboard',
])

@php
    $user = auth()->user();
    $defaultHref = $user ? $user->dashboardRoute() : route('dashboard.index');
    $targetHref = $href ?? $defaultHref;
@endphp

<a href="{{ $targetHref }}"
   onclick="if (window.history.length > 1 && document.referrer && document.referrer.indexOf(window.location.host) !== -1) { window.history.back(); return false; }"
   {{ $attributes->merge([
       'class' => 'inline-flex items-center gap-2 px-3.5 py-2 min-h-[44px] rounded-xl text-xs font-semibold text-slate-700 bg-white/95 hover:bg-slate-900 hover:text-white border border-slate-200/90 hover:border-slate-900 shadow-sm hover:shadow-md transition-all duration-200 group backdrop-blur-sm dark:bg-slate-800/90 dark:text-slate-300 dark:border-slate-700/80 dark:hover:bg-slate-700 dark:hover:text-white cursor-pointer'
   ]) }}>
    <!-- Heroicons: arrow-left (con animación suave hacia la izquierda en hover) -->
    <svg xmlns="http://www.w3.org/2000/svg"
         fill="none"
         viewBox="0 0 24 24"
         stroke-width="2.2"
         stroke="currentColor"
         class="w-4 h-4 text-cyan-600 group-hover:text-cyan-400 dark:text-cyan-400 transition-transform duration-200 ease-out group-hover:-translate-x-1">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
    </svg>
    <span class="tracking-tight">{{ $label }}</span>
</a>
