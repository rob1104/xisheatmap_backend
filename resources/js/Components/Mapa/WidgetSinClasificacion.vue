<template>
    <div
        v-if="simpatizantes"
        class="bg-white/95 backdrop-blur-sm rounded-xl shadow-lg border border-gray-200 w-72 text-slate-700 overflow-hidden"
    >
        <button
            type="button"
            class="w-full flex items-center gap-3 p-3 text-left hover:bg-slate-50 transition-colors"
            @click="abierto = !abierto"
            :aria-expanded="abierto"
        >
            <div class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center"
                 :class="sinClasificar > 0 ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600'">
                <svg v-if="sinClasificar > 0" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Sin clasificación territorial</div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xl font-extrabold tabular-nums text-slate-800">{{ formatoNumero(sinClasificar) }}</span>
                    <span class="text-xs text-slate-500">simpatizantes ({{ formatoPorcentaje(porcentajeSinClasificar) }})</span>
                </div>
            </div>
            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="abierto ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div v-show="abierto" class="px-3 pb-3 text-xs text-slate-600 space-y-2">
            <p class="leading-snug">
                Registros sin coordenadas GPS o cuya ubicación cae fuera de las secciones del municipio.
            </p>
            <dl class="grid grid-cols-2 gap-x-3 gap-y-1 bg-slate-50 rounded-lg p-2 tabular-nums">
                <dt class="text-slate-500">Clasificados en secciones</dt>
                <dd class="text-right font-semibold text-slate-700">{{ formatoNumero(clasificados) }}</dd>
                <dt class="text-slate-500">Sin clasificación</dt>
                <dd class="text-right font-semibold text-slate-700">{{ formatoNumero(sinClasificar) }}</dd>
                <dt class="text-slate-500 border-t border-slate-200 pt-1">Total general</dt>
                <dd class="text-right font-bold text-slate-800 border-t border-slate-200 pt-1">{{ formatoNumero(total) }}</dd>
            </dl>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { formatoNumero, formatoPorcentaje } from '@/Utils/coberturaEscala.js'

const props = defineProps({
    // summary.simpatizantes del endpoint secciones-geojson
    simpatizantes: { type: Object, default: null },
})

const abierto = ref(false)

const clasificados = computed(() => Number(props.simpatizantes?.clasificados_en_secciones ?? 0))
const sinClasificar = computed(() => Number(props.simpatizantes?.sin_clasificacion_territorial ?? 0))
const total = computed(() => Number(props.simpatizantes?.total_general ?? clasificados.value + sinClasificar.value))
const porcentajeSinClasificar = computed(() => (total.value ? (sinClasificar.value / total.value) * 100 : 0))
</script>
