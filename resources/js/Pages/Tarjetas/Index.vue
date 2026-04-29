<template>
    <Head title="Historial de Tarjetas" />

    <AdminLayout>
        <template #header>
            Historial de Tarjetas de Descuento
        </template>

        <div class="max-w-7xl mx-auto animate-fade-in-up">

            <div class="mb-6 flex justify-between items-center">
                <div class="relative w-full max-w-md group">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-500 group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                    </span>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por nombre, CURP o correo..."
                        class="block w-full pl-10 pr-3 py-2 border border-slate-700 rounded-xl leading-5 bg-slate-950 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-200 shadow-inner"
                        @keyup.enter="doSearch"
                    >
                </div>
            </div>

            <div class="bg-slate-900 shadow-2xl rounded-2xl overflow-hidden border border-slate-800">
                <table class="min-w-full divide-y divide-slate-800">
                    <thead class="bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Folio / Curp</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Simpatizante</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Correo de Envío</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Fechas</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                    </thead>
                    <tbody class="bg-slate-900 divide-y divide-slate-800">
                    <tr v-for="tarjeta in tarjetas.data" :key="tarjeta.id" class="hover:bg-slate-800/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-indigo-400">{{ tarjeta.folio }}</div>
                            <div class="text-xs text-slate-500 font-mono">{{ tarjeta.curp }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-slate-200">{{ tarjeta.ine_record.nombre }} {{ tarjeta.ine_record.apellido_paterno }}</div>
                            <div class="text-xs text-slate-400 italic">Brigadista: {{ tarjeta.brigadista.name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-300">
                            {{ tarjeta.email_envio }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-xs text-slate-500 mb-1">
                                <span class="font-bold text-slate-500 uppercase tracking-wider mr-1">Alta:</span>
                                <span class="text-slate-300">{{ formatearFecha(tarjeta.created_at) }}</span>
                            </div>
                            <div class="text-xs text-slate-500">
                                <span class="font-bold text-slate-500 uppercase tracking-wider mr-1">Vence:</span>
                                <span class="text-indigo-400 font-bold">{{ formatearFecha(tarjeta.fecha_validez_fin) }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span :class="tarjeta.enviado_por_correo ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20'" class="px-3 py-1 rounded-md text-xs font-bold uppercase tracking-widest">
                                    {{ tarjeta.enviado_por_correo ? 'ENVIADO' : 'PENDIENTE' }}
                                </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                            <button @click="openEditModal(tarjeta)" class="text-slate-400 hover:text-indigo-400 transition-colors" title="Editar Correo">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                            </button>
                            <button @click="reenviar(tarjeta.id)" class="text-slate-400 hover:text-emerald-400 transition-colors" title="Reenviar Correo">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                            </button>
                            <a :href="'/storage/' + tarjeta.image_path" target="_blank" class="text-slate-400 hover:text-red-400 transition-colors" title="Ver Tarjeta">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                            </a>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <dialog v-if="showModal" class="modal-open fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 backdrop-blur-sm">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md shadow-2xl">
                <h3 class="text-lg font-bold mb-4 text-slate-100">Actualizar Correo de Envío</h3>
                <input v-model="form.email_envio" type="email" class="w-full border-slate-700 bg-slate-950 text-slate-200 placeholder-slate-500 rounded-xl mb-6 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner" placeholder="nuevo@correo.com">
                <div class="flex justify-end gap-3">
                    <button @click="showModal = false" class="px-4 py-2 text-slate-400 hover:text-slate-200 transition-colors font-medium">Cancelar</button>
                    <button @click="saveEmail" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 transition-colors text-white rounded-lg font-bold shadow-lg">Guardar Cambios</button>
                </div>
            </div>
        </dialog>

    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    tarjetas: Object,
    filters: Object
})

const search = ref(props.filters.search || '')
const showModal = ref(false)
const selectedTarjeta = ref(null)

const form = useForm({
    email_envio: ''
})

const doSearch = () => {
    router.get(route('tarjetas.index'), { search: search.value }, { preserveState: true })
}

const openEditModal = (tarjeta) => {
    selectedTarjeta.value = tarjeta
    form.email_envio = tarjeta.email_envio
    showModal.value = true
}

const saveEmail = () => {
    form.put(route('tarjetas.updateEmail', selectedTarjeta.value.id), {
        onSuccess: () => showModal.value = false
    })
}

const reenviar = (id) => {
    if(confirm('¿Estás seguro de reenviar esta tarjeta?')) {
        router.post(route('tarjetas.reenviar', id))
    }
}

const formatearFecha = (fechaCruda) => {
    if (!fechaCruda) return 'N/A';

    const fecha = new Date(fechaCruda);
    return fecha.toLocaleDateString('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};
</script>
