<template>
    <div class="flex flex-col items-center">
        <!-- Tarjeta del usuario -->
        <div class="user-card bg-slate-800/95 backdrop-blur-md border border-slate-700/80 rounded-xl p-2 shadow-lg flex flex-col items-center text-center w-[110px] relative z-10 transition-all duration-300 hover:scale-105 hover:-translate-y-1 hover:shadow-indigo-500/30 hover:border-indigo-500/70 group cursor-default">
            
            <!-- Icono de Nivel / Badge flotante -->
            <div class="absolute -top-2 -right-2 h-6 w-6 rounded-full flex items-center justify-center shadow-lg border-2 border-slate-800 z-20" :class="roleBadgeClass">
                <!-- Admin -->
                <svg v-if="node.level === 0" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <!-- Coordinador -->
                <svg v-else-if="node.level === 1" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                <!-- Gestor -->
                <svg v-else-if="node.level === 2" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                <!-- Presidente -->
                <svg v-else-if="node.level === 3" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                <!-- Integrante -->
                <svg v-else-if="node.level === 4" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                <!-- Desdoble -->
                <svg v-else class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
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

const roleBadgeClass = computed(() => {
    switch (props.node.level) {
        case 0: return 'bg-purple-600'; // Administrador
        case 1: return 'bg-blue-600'; // Coordinador
        case 2: return 'bg-emerald-600'; // Gestor
        case 3: return 'bg-amber-500'; // Presidente
        case 4: return 'bg-orange-500'; // Integrante
        default: return 'bg-slate-500'; // Desdoble
    }
});

const avatarBorderClass = computed(() => {
    switch (props.node.level) {
        case 0: return 'border-purple-500/50'; 
        case 1: return 'border-blue-500/50'; 
        case 2: return 'border-emerald-500/50'; 
        case 3: return 'border-amber-500/50'; 
        case 4: return 'border-orange-500/50'; 
        default: return 'border-slate-600/50'; 
    }
});

const avatarBgGradient = computed(() => {
    switch (props.node.level) {
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
