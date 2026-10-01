<script setup>
defineProps({
  alerts: {
    type: Array,
    default: () => [
      {
        id: 1,
        severity: 'critical', // 'critical' = rojo, 'warning' = ámbar
        module: 'CALIDAD DE AGUA',
        location: 'Estanque 02 (Tilapia Roja)',
        timestamp: '05:30 AM',
        title: 'Hipoxia Crítica: Oxígeno disuelto en 2.4 mg/L',
        detail: 'Nivel por debajo del umbral vital de 3.0 mg/L. Encendido forzado de aireadores y recirculación mandatoria.',
        actionLabel: 'Activar Aireador',
        actionUrl: '/celador'
      },
      {
        id: 2,
        severity: 'critical',
        module: 'SANIDAD & RETIRO ICA',
        location: 'Estanque 04 (Cachama Blanca)',
        timestamp: 'Vigente hasta 05/10/2026',
        title: 'Bloqueo Sanitario Activo: Tiempo de retiro en curso',
        detail: 'Tratamiento con Oxitetraciclina 20%. Prohibida su cosecha y comercialización por resolución ICA 20186.',
        actionLabel: 'Ver Registro ICA',
        actionUrl: '/sanidad'
      },
      {
        id: 3,
        severity: 'warning',
        module: 'BODEGA & ALIMENTACIÓN',
        location: 'Bodega Principal',
        timestamp: 'Cálculo de hoy',
        title: 'Stock Crítico: Iniciación 45% con 3.5 días de autonomía',
        detail: 'Quedan 140 kg en bodega. Se proyecta agotamiento el próximo martes según ración actual de alevinaje.',
        actionLabel: 'Registrar Entrada Camión',
        actionUrl: '/admin/bodega'
      }
    ]
  }
});
</script>

<template>
  <!-- NIVEL 2: ALERTAS ACTIVAS BIOLÓGICAS Y SANITARIAS (LISTA APARTE CON COLORES DE URGENCIA RESERVADOS) -->
  <section class="space-y-3">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="inline-block h-2 w-2 rounded-full bg-red-600 animate-pulse"></span>
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 font-mono">
          Nivel 2: Alertas Operativas y Sanitarias Activas ({{ alerts.length }})
        </h2>
      </div>
      <span class="text-xs text-slate-500 font-medium">Exclusivo para eventos que amenazan biomasa o cumplimiento</span>
    </div>

    <!-- Lista Aparte de Alertas -->
    <div class="space-y-2.5">
      <div
        v-for="alert in alerts"
        :key="alert.id"
        :class="[
          'rounded-md p-4 border flex flex-col md:flex-row md:items-center justify-between gap-4 transition-all',
          alert.severity === 'critical'
            ? 'bg-red-50/70 border-red-200 text-red-950 border-l-4 border-l-red-600'
            : 'bg-amber-50/70 border-amber-200 text-amber-950 border-l-4 border-l-amber-500'
        ]"
      >
        <div class="flex items-start gap-3.5">
          <!-- Icono SVG sin emojis -->
          <div
            :class="[
              'h-9 w-9 rounded-md flex items-center justify-center shrink-0 mt-0.5 border',
              alert.severity === 'critical'
                ? 'bg-red-100 text-red-700 border-red-300'
                : 'bg-amber-100 text-amber-700 border-amber-300'
            ]"
          >
            <svg v-if="alert.severity === 'critical'" class="w-5 h-5 stroke-[1.75]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <svg v-else class="w-5 h-5 stroke-[1.75]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.875A8.625 8.625 0 1 0 20.625 12 8.625 8.625 0 0 0 12 1.875Zm0 14.25h.008v.008H12v-.008Z" />
            </svg>
          </div>

          <div>
            <div class="flex flex-wrap items-center gap-2">
              <span
                :class="[
                  'px-2 py-0.5 text-[10px] font-mono font-bold tracking-wider rounded uppercase border',
                  alert.severity === 'critical'
                    ? 'bg-red-200/60 text-red-900 border-red-300'
                    : 'bg-amber-200/60 text-amber-900 border-amber-300'
                ]"
              >
                {{ alert.module }}
              </span>
              <span class="text-xs font-semibold text-slate-700">{{ alert.location }}</span>
              <span class="text-[11px] text-slate-500 font-mono">• {{ alert.timestamp }}</span>
            </div>

            <h3 class="text-sm font-bold text-slate-900 mt-1">
              {{ alert.title }}
            </h3>
            <p class="text-xs text-slate-700 mt-0.5 leading-relaxed">
              {{ alert.detail }}
            </p>
          </div>
        </div>

        <!-- Botón de acción sobrio (Colores neutros institucionales, reservando rojo/ámbar para la alerta en sí) -->
        <div class="shrink-0 flex items-center justify-end">
          <a
            :href="alert.actionUrl"
            class="inline-flex items-center px-3.5 py-1.5 rounded text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-xs"
          >
            <span>{{ alert.actionLabel }}</span>
            <svg class="w-3.5 h-3.5 ml-1.5 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
          </a>
        </div>
      </div>
    </div>
  </section>
</template>
