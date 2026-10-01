<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentUrl = computed(() => page.url);

const props = defineProps({
  collapsed: {
    type: Boolean,
    default: false
  },
  userRole: {
    type: String,
    default: 'administrador'
  }
});

const navigationSections = [
  {
    category: 'OPERACIÓN DIARIA',
    description: 'Rutinas biológicas del día',
    items: [
      {
        name: 'Panel Operativo',
        route: '/admin/dashboard',
        icon: 'dashboard',
        match: '/admin/dashboard'
      },
      {
        name: 'Alimentación y Bodega',
        route: '/admin/bodega',
        icon: 'feed',
        match: '/admin/bodega'
      },
      {
        name: 'Biometrías y Muestreos',
        route: '/admin/lagos',
        icon: 'scale',
        match: '/admin/lagos'
      },
      {
        name: 'Reporte de Bajas / Mortalidad',
        route: '/trabajador/mortalidad',
        icon: 'mortality',
        match: '/mortalidad'
      }
    ]
  },
  {
    category: 'LAGOS Y BIOMASA',
    description: 'Inventario vivo y densidad',
    items: [
      {
        name: 'Estanques y Lagos',
        route: '/admin/lagos',
        icon: 'ponds',
        match: '/lagos'
      },
      {
        name: 'Lotes y Desdobles',
        route: '/traslados',
        icon: 'transfer',
        match: '/traslados'
      },
      {
        name: 'Telemetría IoT & Calidad Agua',
        route: '/celador',
        icon: 'sensor',
        match: '/celador'
      }
    ]
  },
  {
    category: 'CUMPLIMIENTO',
    description: 'Trazabilidad y normativa ICA',
    items: [
      {
        name: 'Sanidad y Retiro ICA',
        route: '/sanidad',
        icon: 'shield-ica',
        match: '/sanidad'
      },
      {
        name: 'Libro de Campo (BPAP)',
        route: '/ica/libro-campo',
        icon: 'book-ica',
        match: '/ica'
      }
    ]
  },
  {
    category: 'COMERCIAL',
    description: 'Despachos y comercialización',
    items: [
      {
        name: 'Ventas de Pescado',
        route: '/ventas',
        icon: 'cash',
        match: '/ventas'
      },
      {
        name: 'Liquidación de Cosechas',
        route: '/cosechas',
        icon: 'harvest',
        match: '/cosechas'
      }
    ]
  },
  {
    category: 'FINANZAS Y PERSONAL',
    description: 'Cierres de costos y cuadrilla',
    items: [
      {
        name: 'Cierre Financiero Mensual',
        route: '/jefe/reporte-mensual',
        icon: 'chart',
        match: '/reporte-mensual'
      },
      {
        name: 'Nómina Sabatina & Destajos',
        route: '/nomina',
        icon: 'payroll',
        match: '/nomina'
      }
    ]
  }
];

function isActive(itemMatch) {
  return currentUrl.value.includes(itemMatch);
}
</script>

<template>
  <aside 
    :class="[
      'bg-aquatic-950 text-slate-200 border-r border-aquatic-800/80 flex flex-col transition-all duration-200 select-none',
      collapsed ? 'w-20' : 'w-64'
    ]"
  >
    <!-- Brand / Tenant Header -->
    <div class="h-16 flex items-center px-4 border-b border-aquatic-900 bg-aquatic-950/80 gap-3">
      <div class="h-9 w-9 rounded-md bg-aquatic-900 border border-aquatic-700/60 flex items-center justify-center text-aquatic-300 shrink-0">
        <svg class="w-5 h-5 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
        </svg>
      </div>
      <div v-show="!collapsed" class="overflow-hidden">
        <span class="block text-xs font-bold uppercase tracking-wider text-white truncate">AquaSmart SaaS</span>
        <span class="block text-[10px] text-aquatic-400 font-mono">Piscícola San Jerónimo</span>
      </div>
    </div>

    <!-- Navigation Scroll Area -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
      <div v-for="section in navigationSections" :key="section.category" class="space-y-1">
        <!-- Category Header -->
        <div v-show="!collapsed" class="px-2.5 pb-1 flex items-center justify-between">
          <span class="text-[10px] font-bold tracking-wider text-aquatic-400/90 uppercase font-mono">
            {{ section.category }}
          </span>
        </div>

        <!-- Category Items -->
        <ul class="space-y-0.5">
          <li v-for="item in section.items" :key="item.name">
            <Link
              :href="item.route"
              :class="[
                'group flex items-center gap-3 px-2.5 py-2 rounded-md text-xs font-medium transition-colors',
                isActive(item.match)
                  ? 'bg-aquatic-800 text-white font-semibold shadow-xs border-l-2 border-aquatic-400'
                  : 'text-slate-400 hover:text-slate-100 hover:bg-aquatic-900/60'
              ]"
              :title="item.name"
            >
              <!-- Vector Icons -->
              <span class="shrink-0 text-slate-400 group-hover:text-slate-200">
                <svg v-if="item.icon === 'dashboard'" class="w-4 h-4 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                </svg>
                <svg v-else-if="item.icon === 'feed'" class="w-4 h-4 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
                <svg v-else-if="item.icon === 'scale'" class="w-4 h-4 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-16.5-.52c-.99.203-1.99.377-3 .52m19.5 0a2.25 2.25 0 0 1 1.75 2.19V11.25a2.25 2.25 0 0 1-2.25 2.25h-3a2.25 2.25 0 0 1-2.25-2.25V7.16a2.25 2.25 0 0 1 1.75-2.19m-11 0a2.25 2.25 0 0 0-1.75 2.19V11.25a2.25 2.25 0 0 0 2.25 2.25h3a2.25 2.25 0 0 0 2.25-2.25V7.16a2.25 2.25 0 0 0-1.75-2.19" />
                </svg>
                <svg v-else-if="item.icon === 'mortality'" class="w-4 h-4 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <svg v-else-if="item.icon === 'ponds'" class="w-4 h-4 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
                <svg v-else-if="item.icon === 'transfer'" class="w-4 h-4 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
                <svg v-else-if="item.icon === 'sensor'" class="w-4 h-4 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 0 1 0-5.304m5.304 0a3.75 3.75 0 0 1 0 5.304m-7.425 2.121a6.75 6.75 0 0 1 0-9.546m9.546 0a6.75 6.75 0 0 1 0 9.546M5.106 18.894c-3.808-3.808-3.808-9.98 0-13.789m13.788 0c3.808 3.808 3.808 9.981 0 13.79M12 12h.008v.007H12V12Z" />
                </svg>
                <svg v-else-if="item.icon === 'shield-ica'" class="w-4 h-4 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                <svg v-else-if="item.icon === 'book-ica'" class="w-4 h-4 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
                <svg v-else-if="item.icon === 'cash'" class="w-4 h-4 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v8.25m0 0a60.11 60.11 0 0 0 15.797 2.101c.727.198 1.453-.342 1.453-1.096V14.25m-17.25 0a2.25 2.25 0 0 1-2.25-2.25V6.75A2.25 2.25 0 0 1 3 4.5h18a2.25 2.25 0 0 1 2.25 2.25v5.25a2.25 2.25 0 0 1-2.25 2.25m-18 0V15a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 15v-.75m-6-3.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                <svg v-else-if="item.icon === 'harvest'" class="w-4 h-4 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25V3.75m0 3.75h3.75" />
                </svg>
                <svg v-else-if="item.icon === 'chart'" class="w-4 h-4 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
                <svg v-else-if="item.icon === 'payroll'" class="w-4 h-4 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
              </span>

              <span v-show="!collapsed" class="truncate">
                {{ item.name }}
              </span>
            </Link>
          </li>
        </ul>
      </div>
    </nav>
  </aside>
</template>
