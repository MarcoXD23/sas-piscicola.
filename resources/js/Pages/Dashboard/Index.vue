<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import SidebarNavigation from '@/Components/Navigation/SidebarNavigation.vue';
import CriticalMetricsBar from '@/Components/Dashboard/CriticalMetricsBar.vue';
import ActiveAlertsBanner from '@/Components/Dashboard/ActiveAlertsBanner.vue';
import OperationalWorkbench from '@/Components/Dashboard/OperationalWorkbench.vue';

const props = defineProps({
  biomasaTotalKg: { type: Number, default: 28450.0 },
  estanquesActivosCount: { type: Number, default: 8 },
  tasaSupervivencia: { type: Number, default: 94.8 },
  bajasAcumuladasCiclo: { type: Number, default: 520 },
  alertasAguaCriticasCount: { type: Number, default: 1 },
  oxigenoPromedioMgL: { type: Number, default: 5.4 },
  diasAlimentoRestantes: { type: Number, default: 18.5 },
  stockAlimentoKg: { type: Number, default: 740.0 },
  consumoDiarioKg: { type: Number, default: 40.0 },
  userRole: { type: String, default: 'administrador' }
});

const sidebarCollapsed = ref(false);
</script>

<template>
  <Head title="Tablero de Mando Operativo - El SAS Piscícola" />

  <div class="flex h-screen bg-slate-50 font-sans text-slate-800 antialiased overflow-hidden">
    <!-- Sidebar con 5 Categorías -->
    <SidebarNavigation 
      :collapsed="sidebarCollapsed"
      :user-role="userRole"
    />

    <!-- Área Principal de Contenido -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Topbar Institucional -->
      <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-4">
          <button 
            type="button" 
            @click="sidebarCollapsed = !sidebarCollapsed"
            class="text-slate-500 hover:text-slate-800 p-1.5 rounded-md hover:bg-slate-100"
            title="Colapsar barra lateral"
          >
            <svg class="w-5 h-5 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
          </button>
          <div>
            <h1 class="text-base font-bold text-slate-900 tracking-tight leading-tight">
              Tablero de Mando Operativo
            </h1>
            <p class="text-xs text-slate-500 font-mono">
              Finca Piscícola San Jerónimo • Espinal, Tolima
            </p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-mono font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
            Operación Activa
          </span>
        </div>
      </header>

      <!-- Main Canvas con 3 Niveles Explícitos de Jerarquía Visual -->
      <main class="flex-1 overflow-y-auto p-6 space-y-6 max-w-7xl w-full mx-auto">
        <!-- Nivel 1: Métricas de Crisis (Arriba, Más grande, Siempre Visible) -->
        <CriticalMetricsBar
          :biomasa-total-kg="biomasaTotalKg"
          :estanques-activos-count="estanquesActivosCount"
          :tasa-supervivencia="tasaSupervivencia"
          :bajas-acumuladas-ciclo="bajasAcumuladasCiclo"
          :alertas-agua-criticas-count="alertasAguaCriticasCount"
          :oxigeno-promedio-mg-l="oxigenoPromedioMgL"
          :dias-alimento-restantes="diasAlimentoRestantes"
          :stock-alimento-kg="stockAlimentoKg"
          :consumo-diario-kg="consumoDiarioKg"
        />

        <!-- Nivel 2: Lista Aparte de Alertas Activas (Urgencia Biológica / Sanitaria) -->
        <ActiveAlertsBanner />

        <!-- Nivel 3: Operación Normal, Consulta y Tareas Diarias -->
        <OperationalWorkbench />
      </main>
    </div>
  </div>
</template>
