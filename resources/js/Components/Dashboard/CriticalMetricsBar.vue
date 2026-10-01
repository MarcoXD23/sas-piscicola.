<script setup>
import { computed } from 'vue';

const props = defineProps({
  biomasaTotalKg: {
    type: Number,
    required: true
  },
  estanquesActivosCount: {
    type: Number,
    default: 0
  },
  tasaSupervivencia: {
    type: Number,
    default: 94.8
  },
  bajasAcumuladasCiclo: {
    type: Number,
    default: 0
  },
  alertasAguaCriticasCount: {
    type: Number,
    default: 0
  },
  oxigenoPromedioMgL: {
    type: Number,
    default: 5.6
  },
  diasAlimentoRestantes: {
    type: Number,
    required: true
  },
  stockAlimentoKg: {
    type: Number,
    default: 0
  },
  consumoDiarioKg: {
    type: Number,
    default: 0
  }
});

// Determinación contextual de estados (Normal, Preventivo, Crisis)
const estadoAgua = computed(() => {
  if (props.alertasAguaCriticasCount > 0) {
    return {
      severity: 'critical',
      label: 'CRISIS DETECTADA',
      badgeClass: 'bg-red-50 text-red-700 border-red-200',
      borderClass: 'border-l-4 border-l-red-600 border-slate-200'
    };
  }
  return {
    severity: 'optimal',
    label: 'PARÁMETROS ESTABLES',
    badgeClass: 'bg-emerald-50 text-emerald-800 border-emerald-200',
    borderClass: 'border-l-4 border-l-emerald-600 border-slate-200'
  };
});

const estadoAlimento = computed(() => {
  if (props.diasAlimentoRestantes <= 2) {
    return {
      severity: 'critical',
      label: 'DESABASTECIMIENTO INMINENTE',
      badgeClass: 'bg-red-50 text-red-700 border-red-200',
      borderClass: 'border-l-4 border-l-red-600 border-slate-200'
    };
  }
  if (props.diasAlimentoRestantes <= 5) {
    return {
      severity: 'warning',
      label: 'STOCK EN UMBRAL MÍNIMO',
      badgeClass: 'bg-amber-50 text-amber-800 border-amber-200',
      borderClass: 'border-l-4 border-l-amber-500 border-slate-200'
    };
  }
  return {
    severity: 'optimal',
    label: 'STOCK ABASTECIDO',
    badgeClass: 'bg-emerald-50 text-emerald-800 border-emerald-200',
    borderClass: 'border-l-4 border-l-aquatic-700 border-slate-200'
  };
});

const estadoSupervivencia = computed(() => {
  if (props.tasaSupervivencia < 85.0) {
    return {
      severity: 'critical',
      badgeClass: 'bg-red-50 text-red-700 border-red-200',
      borderClass: 'border-l-4 border-l-red-600 border-slate-200'
    };
  }
  if (props.tasaSupervivencia < 90.0) {
    return {
      severity: 'warning',
      badgeClass: 'bg-amber-50 text-amber-800 border-amber-200',
      borderClass: 'border-l-4 border-l-amber-500 border-slate-200'
    };
  }
  return {
    severity: 'optimal',
    badgeClass: 'bg-emerald-50 text-emerald-800 border-emerald-200',
    borderClass: 'border-l-4 border-l-aquatic-700 border-slate-200'
  };
});
</script>

<template>
  <!-- NIVEL 1: LAS 4 MÉTRICAS DE CRISIS OPERATIVA (ARRIBA, GRANDES, SIEMPRE VISIBLES) -->
  <section class="space-y-2.5">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="inline-block h-2 w-2 rounded-full bg-aquatic-700"></span>
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 font-mono">
          Nivel 1: Signos Vitales y Estado de Crisis de la Granja
        </h2>
      </div>
      <span class="text-[11px] text-slate-400 font-mono">Actualización en tiempo real</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
      
      <!-- Card 1: Biomasa Activa -->
      <article class="bg-white p-5 rounded-lg border border-slate-200 border-l-4 border-l-aquatic-800 shadow-level1-kpi flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between gap-2">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-tight">Biomasa Activa</span>
            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-slate-100 text-slate-700 border border-slate-200">
              {{ estanquesActivosCount }} estanques
            </span>
          </div>
          <!-- Pregunta Operativa Concreta -->
          <p class="text-xs text-slate-600 mt-1 italic font-medium">
            ¿Cuánta biomasa viva y en crecimiento estamos gestionando hoy?
          </p>
          <div class="mt-3 flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-slate-900 tracking-tight font-mono">
              {{ biomasaTotalKg.toLocaleString('es-CO', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) }}
            </span>
            <span class="text-sm font-semibold text-slate-500">kg</span>
          </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between">
          <span>Densidad media estimada:</span>
          <span class="font-mono font-semibold text-slate-700">2.8 kg/m³ (Óptimo &lt; 3.5)</span>
        </div>
      </article>

      <!-- Card 2: Supervivencia de Población -->
      <article :class="['bg-white p-5 rounded-lg border shadow-level1-kpi flex flex-col justify-between', estadoSupervivencia.borderClass]">
        <div>
          <div class="flex items-center justify-between gap-2">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-tight">Supervivencia del Ciclo</span>
            <span :class="['px-2 py-0.5 rounded text-[10px] font-mono font-semibold border', estadoSupervivencia.badgeClass]">
              Meta ≥ 90.0%
            </span>
          </div>
          <!-- Pregunta Operativa Concreta -->
          <p class="text-xs text-slate-600 mt-1 italic font-medium">
            ¿La tasa de supervivencia se mantiene dentro del umbral rentable?
          </p>
          <div class="mt-3 flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-slate-900 tracking-tight font-mono">
              {{ tasaSupervivencia.toFixed(1) }}%
            </span>
            <span class="text-xs font-semibold text-emerald-700 font-mono">Cumple objetivo</span>
          </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between">
          <span>Mortalidad acumulada ciclo:</span>
          <span class="font-mono font-semibold text-slate-700">{{ bajasAcumuladasCiclo }} peces</span>
        </div>
      </article>

      <!-- Card 3: Calidad de Agua & Oxígeno (Crisis Hypoxia) -->
      <article :class="['bg-white p-5 rounded-lg border shadow-level1-kpi flex flex-col justify-between', estadoAgua.borderClass]">
        <div>
          <div class="flex items-center justify-between gap-2">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-tight">Calidad de Agua & O₂</span>
            <span :class="['px-2 py-0.5 rounded text-[10px] font-mono font-bold border', estadoAgua.badgeClass]">
              {{ estadoAgua.label }}
            </span>
          </div>
          <!-- Pregunta Operativa Concreta -->
          <p class="text-xs text-slate-600 mt-1 italic font-medium">
            ¿Hay alguna crisis de oxígeno o boqueo en estanques en este momento?
          </p>
          <div class="mt-3 flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-slate-900 tracking-tight font-mono">
              {{ alertasAguaCriticasCount }}
            </span>
            <span class="text-sm font-semibold text-slate-500">alertas activas</span>
          </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between">
          <span>Oxígeno disuelto promedio:</span>
          <span class="font-mono font-semibold text-slate-800">{{ oxigenoPromedioMgL.toFixed(1) }} mg/L</span>
        </div>
      </article>

      <!-- Card 4: Autonomía de Alimento en Bodega -->
      <article :class="['bg-white p-5 rounded-lg border shadow-level1-kpi flex flex-col justify-between', estadoAlimento.borderClass]">
        <div>
          <div class="flex items-center justify-between gap-2">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-tight">Autonomía de Bodega</span>
            <span :class="['px-2 py-0.5 rounded text-[10px] font-mono font-bold border', estadoAlimento.badgeClass]">
              {{ diasAlimentoRestantes <= 5 ? 'ATENCIÓN REQUERIDA' : 'ABASTECIDO' }}
            </span>
          </div>
          <!-- Pregunta Operativa Concreta -->
          <p class="text-xs text-slate-600 mt-1 italic font-medium">
            ¿Para cuántos días de ración alcanza el stock antes del agotamiento?
          </p>
          <div class="mt-3 flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-slate-900 tracking-tight font-mono">
              {{ diasAlimentoRestantes > 90 ? '> 90' : diasAlimentoRestantes.toFixed(1) }}
            </span>
            <span class="text-sm font-semibold text-slate-500">días restantes</span>
          </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between">
          <span>Stock actual / Consumo diario:</span>
          <span class="font-mono font-semibold text-slate-800">{{ stockAlimentoKg }} kg / {{ consumoDiarioKg }} kg/d</span>
        </div>
      </article>

    </div>
  </section>
</template>
