<template>
    <div class="flex flex-col items-center">
        <!-- Tarjeta del usuario -->
        <div class="user-card bg-slate-800/95 backdrop-blur-md border border-slate-700/80 rounded-xl p-2 shadow-lg flex flex-col items-center text-center w-[110px] relative z-10 transition-all duration-300 hover:scale-105 hover:-translate-y-1 hover:shadow-indigo-500/30 hover:border-indigo-500/70 group cursor-default">
            
            <!-- Nivel en el árbol (profundidad por parent_id, no por rol) / Badge flotante -->
            <div class="absolute -top-2 -right-2 h-6 w-6 rounded-full flex items-center justify-center shadow-lg border-2 border-slate-800 z-20" :class="depthBadgeClass">
                <span class="text-[9px] font-black text-white leading-none" :title="`Nivel ${node.depth + 1} en la jerarquía`">{{ node.depth + 1 }}</span>
            </div>

            <!-- Avatar CSS puro sin imágenes externas para compatibilidad con html2canvas -->
            <div class="h-8 w-8 rounded-full border-2 shadow-inner mb-1.5 relative overflow-hidden flex items-center justify-center text-white font-bold text-xs" :class="[avatarBorderClass, avatarBgGradient]">
                {{ node.name.charAt(0).toUpperCase() }}
            </div>

            <h4 class="font-extrabold text-slate-100 text-[10px] leading-tight break-words w-full" :title="node.name">{{ node.name }}</h4>
            <p class="text-[8px] text-slate-400 mt-0.5 break-words w-full" :title="node.role">{{ node.role }}</p>
            
            <!-- Barra de estado/capturas -->
            <div class="mt-1.5 w-full bg-slate-900/80 rounded px-1.5 py-0.5 border border-slate-700/50 flex items-center justify-between">
                <span class="text-[8px] font-bold text-slate-500 uppercase tracking-widest flex items-center">
                    Capturas
                </span>
                <span class="text-[9px] font-black" :class="node.ines_count > 0 ? 'text-emerald-400' : 'text-slate-400'">{{ node.ines_count }}</span>
            </div>
        </div>

        <!-- Contenedor de Hijos y Conectores -->
        <div v-if="node.children && node.children.length > 0" class="relative flex justify-center w-full">
            
            <!-- Línea vertical principal (Padre -> Centro de la línea horizontal) -->
            <div class="absolute top-0 left-1/2 w-[2px] h-3 bg-slate-500/70 -translate-x-1/2"></div>
            
            <!-- Iteración de Hijos -->
            <div v-for="(child, i) in node.children" :key="child.id" class="relative flex flex-col items-center px-1 w-auto">
                
                <!-- Línea horizontal (Conecta hermanos entre sí, a la altura de 12px (top-3)) -->
                <div v-if="node.children.length > 1" 
                     class="absolute top-3 h-[2px] bg-slate-500/70"
                     :class="{
                         'left-1/2 w-1/2': i === 0,
                         'right-1/2 w-1/2 left-auto': i === node.children.length - 1,
                         'w-full left-0': i > 0 && i < node.children.length - 1
                     }">
                </div>

                <!-- Línea vertical individual para cada hijo (Línea horizontal -> Tarjeta hijo) -->
                <div class="absolute top-3 left-1/2 w-[2px] h-3 bg-slate-500/70 -translate-x-1/2"></div>
                
                <!-- Spacer para que la tarjeta del hijo se renderice 24px por debajo (h-3 + h-3 = mt-6) -->
                <div class="mt-6">
                    <OrgNode :node="child" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    node: Object
});

const depthBadgeClass = computed(() => {
    switch (props.node.depth) {
        case 0: return 'bg-purple-600'; // Raíz
        case 1: return 'bg-blue-600';
        case 2: return 'bg-emerald-600';
        case 3: return 'bg-amber-500';
        case 4: return 'bg-orange-500';
        default: return 'bg-slate-500'; // Nivel 6 o más
    }
});

const avatarBorderClass = computed(() => {
    switch (props.node.depth) {
        case 0: return 'border-purple-500/50'; 
        case 1: return 'border-blue-500/50'; 
        case 2: return 'border-emerald-500/50'; 
        case 3: return 'border-amber-500/50'; 
        case 4: return 'border-orange-500/50'; 
        default: return 'border-slate-600/50'; 
    }
});

const avatarBgGradient = computed(() => {
    switch (props.node.depth) {
        case 0: return 'bg-gradient-to-tr from-purple-700 to-purple-500'; 
        case 1: return 'bg-gradient-to-tr from-blue-700 to-blue-500'; 
        case 2: return 'bg-gradient-to-tr from-emerald-700 to-emerald-500'; 
        case 3: return 'bg-gradient-to-tr from-amber-600 to-amber-400'; 
        case 4: return 'bg-gradient-to-tr from-orange-600 to-orange-400'; 
        default: return 'bg-gradient-to-tr from-slate-600 to-slate-400'; 
    }
});
</script>

<style scoped>
/* Sin estilos CSS extra, toda la lógica de conectores ahora es 100% Tailwind garantizando alineación pixel-perfect */
</style>
