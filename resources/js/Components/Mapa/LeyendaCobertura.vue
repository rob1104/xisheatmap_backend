<template>
    <div class="bg-white/95 backdrop-blur-sm rounded-xl shadow-lg border border-gray-200 p-3 w-64 text-slate-700">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                {{ modo === 'cobertura' ? 'Cobertura Lista Nominal' : 'Simpatizantes por sección' }}
            </span>
            
        </div>

        <!-- Escala de cobertura -->
        <template v-if="modo === 'cobertura'">
            <!-- misma opacidad que el relleno de las secciones en el mapa -->
            <div class="h-3 rounded-sm border border-slate-200" :style="{ background: degradado, opacity: 0.75 }"></div>
            <div class="relative h-4 mt-1 text-[11px] font-semibold text-slate-600 tabular-nums">
                <span class="absolute left-0">0 %</span>
                <span class="absolute left-1/2 -translate-x-1/2">{{ mitad }} %</span>
                <span class="absolute right-0">{{ tope }} %{{ tope < 100 ? '+' : '' }}</span>
            </div>
            <div class="flex items-center gap-2 mt-2 text-[11px] text-slate-600">
                <span class="w-4 h-3 rounded-sm border-2 border-slate-400 bg-transparent"></span>
                Sin Lista Nominal en el corte activo
            </div>
            <p class="mt-2 text-[10px] leading-snug text-slate-500">
                Simpatizantes ubicados por GPS ÷ Lista Nominal. Escala provisional hasta definir meta.
            </p>
            <p v-if="corteActivo" class="mt-2 pt-2 border-t border-slate-100 text-[10px] text-slate-500">
                Corte activo: <strong class="text-slate-700">{{ fechaCorte }}</strong>
            </p>
            <p v-else class="mt-2 pt-2 border-t border-slate-100 text-[10px] font-semibold text-amber-700">
                No hay un corte de Lista Nominal activo.
            </p>
        </template>

        <!-- Escala por conteo absoluto -->
        <template v-else>
            <ul class="space-y-1 text-[11px] text-slate-600">
                <li v-for="rango in rangosSimpatizantes" :key="rango.etiqueta" class="flex items-center gap-2">
                    <span class="w-4 h-3 rounded-sm border border-slate-200" :style="{ background: rango.color, opacity: 0.55 }"></span>
                    {{ rango.etiqueta }}
                </li>
            </ul>
        </template>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { degradadoCss } from '@/Utils/coberturaEscala.js'

const props = defineProps({
    modo: { type: String, default: 'cobertura' }, // 'cobertura' | 'simpatizantes'
    tope: { type: Number, default: 10 },
    corteActivo: { type: Object, default: null },
    datosDeEjemplo: { type: Boolean, default: false },
})

const degradado = degradadoCss()

const mitad = computed(() => {
    const m = props.tope / 2
    return Number.isInteger(m) ? m : m.toFixed(1)
})

const fechaCorte = computed(() => {
    const fecha = props.corteActivo?.fecha_corte
    if (!fecha) return ''
    const [a, m, d] = String(fecha).slice(0, 10).split('-').map(Number)
    return new Date(a, m - 1, d).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' })
})

// Mismos rangos que obtener color por metrica
const rangosSimpatizantes = [
    { etiqueta: '100 o más', color: '#1e3a8a' },             
    { etiqueta: '50 – 99', color: '#2563eb' },
    { etiqueta: '20 – 49', color: '#60a5fa' },
    { etiqueta: '1 – 19', color: '#93c5fd' },
    { etiqueta: 'Sin registros', color: '#e2e8f0' },
]
</script>
//const rangoSimpatizantes = [ 
    
