<template>
    <Head title="Centro de Mando" />

    <AdminLayout>
        <template #header>
            Resumen Operativo
        </template>

        <div class="space-y-8 animate-fade-in-up">

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">

                <div class="bg-slate-900 overflow-hidden shadow-2xl rounded-2xl border border-slate-800 p-5 group hover:border-indigo-500/50 transition-all duration-300">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-indigo-500/10 rounded-xl p-3 group-hover:scale-110 transition-transform">
                            <svg class="h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-xs font-black text-slate-500 uppercase tracking-widest">Total INEs</dt>
                                <dd class="text-3xl font-black text-slate-100 leading-tight">{{ kpis.total_ines.toLocaleString() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900 overflow-hidden shadow-2xl rounded-2xl border border-slate-800 p-5 group hover:border-emerald-500/50 transition-all duration-300">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-emerald-500/10 rounded-xl p-3 group-hover:scale-110 transition-transform">
                            <svg class="h-8 w-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-xs font-black text-slate-500 uppercase tracking-widest">Capturas Hoy</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-3xl font-black text-slate-100 leading-tight">{{ kpis.capturas_hoy.toLocaleString() }}</div>
                                    <span v-if="kpis.capturas_hoy > 0" class="ml-2 text-xs font-bold text-emerald-400">Activo</span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900 overflow-hidden shadow-2xl rounded-2xl border border-slate-800 p-5 group hover:border-amber-500/50 transition-all duration-300">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-amber-500/10 rounded-xl p-3 group-hover:scale-110 transition-transform">
                            <svg class="h-8 w-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-xs font-black text-slate-500 uppercase tracking-widest">En Campo</dt>
                                <dd class="text-3xl font-black text-slate-100 leading-tight">{{ kpis.en_campo.toLocaleString() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900 overflow-hidden shadow-2xl rounded-2xl border border-slate-800 p-5 group hover:border-purple-500/50 transition-all duration-300">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-500/10 rounded-xl p-3 group-hover:scale-110 transition-transform">
                            <svg class="h-8 w-8 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-xs font-black text-slate-500 uppercase tracking-widest">Tarjetas</dt>
                                <dd class="text-3xl font-black text-slate-100 leading-tight">{{ kpis.tarjetas.toLocaleString() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

            </div>

            <div class="bg-slate-900 shadow-2xl rounded-2xl border border-slate-800 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
                    <h3 class="text-lg font-bold text-slate-100 flex items-center">
                        <span class="w-2 h-2 bg-indigo-500 rounded-full mr-2 animate-pulse"></span>
                        Últimas Sincronizaciones en Vivo
                    </h3>
                    <span class="text-xs font-bold text-slate-500 bg-slate-800 px-3 py-1 rounded-full uppercase tracking-tighter">
                        Actualizado ahora
                    </span>
                </div>

                <div class="p-12 text-center">
                    <div class="relative inline-block">
                        <svg class="mx-auto h-16 w-16 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <div class="absolute -top-1 -right-1 flex h-4 w-4">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-4 w-4 bg-indigo-500"></span>
                        </div>
                    </div>
                    <h3 class="mt-4 text-lg font-bold text-slate-200">Escaneando Red Operativa...</h3>
                    <p class="mt-2 text-sm text-slate-400 max-w-xs mx-auto">
                        Los datos de los brigadistas aparecerán aquí automáticamente conforme se sincronicen desde la App móvil.
                    </p>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

// Declaramos que este componente recibe los 'kpis' desde el controlador de Laravel
const props = defineProps({
    kpis: {
        type: Object,
        required: true,
    }
});
</script>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
