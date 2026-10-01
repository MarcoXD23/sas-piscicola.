<script setup>
defineProps({
  todayTasks: {
    type: Array,
    default: () => [
      { id: 101, title: 'Suministro de ración matutina (34% proteina)', pond: 'Estanques 01 a 04', assignedTo: 'Pedro Ruiz', done: true },
      { id: 102, title: 'Muestreo biométrico sabatino (50 peces por estanque)', pond: 'Estanque 03 - Cachama', assignedTo: 'Carlos Admin', done: false },
      { id: 103, title: 'Limpieza de rejillas de entrada y canal principal', pond: 'Bocatoma', assignedTo: 'Cuadrilla Campo', done: false },
      { id: 104, title: 'Calibración sonda multiparamétrica de pH y O₂', pond: 'Laboratorio / Campo', assignedTo: 'Marta Rivera', done: true },
    ]
  },
  growthBatches: {
    type: Array,
    default: () => [
      { pond: 'Estanque 01', species: 'Tilapia Roja', stockDate: '15/06/2026', days: 103, currentWeight: 380, targetWeight: 450, fcr: 1.35 },
      { pond: 'Estanque 02', species: 'Tilapia Roja', stockDate: '02/07/2026', days: 86, currentWeight: 295, targetWeight: 450, fcr: 1.41 },
      { pond: 'Estanque 03', species: 'Cachama Blanca', stockDate: '10/05/2026', days: 139, currentWeight: 720, targetWeight: 800, fcr: 1.28 },
      { pond: 'Estanque 04', species: 'Bocachico', stockDate: '20/04/2026', days: 159, currentWeight: 310, targetWeight: 350, fcr: 1.15 },
    ]
  },
  commercialSummary: {
    type: Object,
    default: () => ({
      salesTodayCop: 4850000,
      kilosSoldToday: 510.5,
      pendingHarvestKg: 1200.0,
      nextScheduledHarvestDate: 'Lunes 28 de Septiembre'
    })
  }
});
</script>

<template>
  <!-- NIVEL 3: OPERACIÓN NORMAL, CONSULTA Y SEGUIMIENTO DIARIO (NO ES DE ALARMA) -->
  <section class="space-y-4 pt-2">
    <div class="flex items-center justify-between border-b border-slate-200 pb-2">
      <div class="flex items-center gap-2">
        <span class="inline-block h-2 w-2 rounded-full bg-slate-400"></span>
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 font-mono">
          Nivel 3: Banco de Trabajo Operativo y Consulta Rutinaria
        </h2>
      </div>
      <span class="text-[11px] text-slate-400 font-mono">Información de gestión habitual</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

      <!-- Columna 1 (7 cols): Curva de Crecimiento & Estado Zootécnico de Lotes -->
      <div class="lg:col-span-7 space-y-4">
        <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-card-operational">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-sm font-bold text-slate-900">Estado de Lotes en Crecimiento</h3>
              <p class="text-xs text-slate-500">¿Cómo evoluciona el peso y la conversión alimenticia (FCR)?</p>
            </div>
            <a href="/admin/lagos" class="text-xs font-semibold text-aquatic-700 hover:text-aquatic-900 flex items-center gap-1">
              <span>Ver todos</span>
              <svg class="w-3.5 h-3.5 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
              </svg>
            </a>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-slate-500 font-mono uppercase tracking-wider text-[10px] border-y border-slate-100">
                <tr>
                  <th class="py-2.5 px-3">Estanque / Especie</th>
                  <th class="py-2.5 px-3 text-center">Días Cultivo</th>
                  <th class="py-2.5 px-3 text-right">Peso Actual</th>
                  <th class="py-2.5 px-3 text-right">Meta Cosecha</th>
                  <th class="py-2.5 px-3 text-center">FCR Est.</th>
                  <th class="py-2.5 px-3 text-center">Progreso</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="batch in growthBatches" :key="batch.pond" class="hover:bg-slate-50/60">
                  <td class="py-3 px-3">
                    <span class="font-bold text-slate-800 block">{{ batch.pond }}</span>
                    <span class="text-[10px] text-slate-500 font-mono">{{ batch.species }}</span>
                  </td>
                  <td class="py-3 px-3 text-center font-mono text-slate-600">
                    {{ batch.days }} d
                  </td>
                  <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">
                    {{ batch.currentWeight }} g
                  </td>
                  <td class="py-3 px-3 text-right font-mono text-slate-500">
                    {{ batch.targetWeight }} g
                  </td>
                  <td class="py-3 px-3 text-center">
                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold bg-slate-100 text-slate-700">
                      {{ batch.fcr }}
                    </span>
                  </td>
                  <td class="py-3 px-3">
                    <div class="w-20 mx-auto bg-slate-100 rounded-full h-1.5 overflow-hidden">
                      <div
                        class="bg-aquatic-700 h-1.5 rounded-full"
                        :style="{ width: Math.min(100, Math.round((batch.currentWeight / batch.targetWeight) * 100)) + '%' }"
                      ></div>
                    </div>
                    <span class="block text-[10px] text-center text-slate-400 font-mono mt-0.5">
                      {{ Math.round((batch.currentWeight / batch.targetWeight) * 100) }}%
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Columna 2 (5 cols): Tareas del Día y Despachos Comerciales -->
      <div class="lg:col-span-5 space-y-4">
        
        <!-- Tarjeta de Despachos Comerciales -->
        <div class="bg-earth-50/60 border border-earth-200 rounded-lg p-5 shadow-card-operational">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[10px] font-mono uppercase font-bold text-earth-800 tracking-wider">Comercialización Hoy</span>
            <span class="text-[10px] font-mono text-earth-600">{{ commercialSummary.nextScheduledHarvestDate }}</span>
          </div>
          <!-- Pregunta Operativa Concreta -->
          <p class="text-xs text-earth-900 font-medium italic mb-3">
            ¿Cuánto pescado y recaudo comercial llevamos consolidado hoy?
          </p>

          <div class="grid grid-cols-2 gap-3 pt-1">
            <div class="bg-white p-3 rounded border border-earth-200">
              <span class="block text-[10px] text-slate-500 font-medium">Recaudo Ventas</span>
              <span class="text-lg font-bold font-mono text-slate-900">
                ${{ commercialSummary.salesTodayCop.toLocaleString('es-CO') }}
              </span>
            </div>
            <div class="bg-white p-3 rounded border border-earth-200">
              <span class="block text-[10px] text-slate-500 font-medium">Kilos Vendidos</span>
              <span class="text-lg font-bold font-mono text-slate-900">
                {{ commercialSummary.kilosSoldToday }} kg
              </span>
            </div>
          </div>
        </div>

        <!-- Tarjeta de Tareas de Cuadrilla -->
        <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-card-operational">
          <div class="flex items-center justify-between mb-3">
            <div>
              <h3 class="text-sm font-bold text-slate-900">Rutas y Tareas de Cuadrilla</h3>
              <p class="text-xs text-slate-500">¿Qué actividades rutinarias faltan por ejecutar hoy?</p>
            </div>
            <span class="text-xs text-slate-500 font-mono">
              {{ todayTasks.filter(t => t.done).length }}/{{ todayTasks.length }}
            </span>
          </div>

          <ul class="space-y-2">
            <li
              v-for="task in todayTasks"
              :key="task.id"
              class="flex items-start gap-2.5 p-2 rounded hover:bg-slate-50 border border-transparent hover:border-slate-100 transition-colors"
            >
              <input
                type="checkbox"
                :checked="task.done"
                class="mt-0.5 rounded border-slate-300 text-aquatic-700 focus:ring-aquatic-700"
                disabled
              />
              <div class="flex-1 min-w-0">
                <p :class="['text-xs font-medium', task.done ? 'line-through text-slate-400' : 'text-slate-800']">
                  {{ task.title }}
                </p>
                <div class="flex items-center gap-2 text-[10px] text-slate-500 font-mono mt-0.5">
                  <span>{{ task.pond }}</span>
                  <span>•</span>
                  <span>{{ task.assignedTo }}</span>
                </div>
              </div>
            </li>
          </ul>
        </div>

      </div>

    </div>
  </section>
</template>
