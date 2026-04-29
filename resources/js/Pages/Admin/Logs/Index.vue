<template>
    <Head title="Auditoría del Sistema" />

    <AdminLayout>
        <template #header>
            Auditoría del Sistema
        </template>

        <div class="max-w-7xl mx-auto animate-fade-in-up">

            <div class="sm:flex sm:items-center sm:justify-between mb-8 gap-4">
                <div class="mb-4 sm:mb-0 flex items-center">
                    <div class="p-2 bg-indigo-500/10 rounded-xl mr-4 border border-indigo-500/20">
                        <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black text-slate-100 tracking-tight">Registro de Actividades</h1>
                        <p class="mt-1 text-sm text-slate-400 font-medium">Monitorea cambios, accesos y eventos de seguridad.</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 sm:mt-0 mt-4 flex-1 justify-end">
                    <div class="relative w-full sm:max-w-xs group">
                        <select v-model="evento" class="block w-full pl-3 pr-10 py-2 text-base border border-slate-700 bg-slate-950 text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-xl shadow-inner transition duration-200 custom-select appearance-none">
                            <option value="" class="bg-slate-900">Todos los Eventos</option>
                            <option value="login" class="bg-slate-900">Inicios de Sesión</option>
                            <option value="created" class="bg-slate-900">Creaciones</option>
                            <option value="updated" class="bg-slate-900">Modificaciones</option>
                            <option value="deleted" class="bg-slate-900">Eliminaciones</option>
                            <option value="alerta_seguridad" class="bg-slate-900 text-red-400 font-bold">Alertas de Seguridad</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 group-focus-within:text-indigo-500 transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>

                    <div class="relative w-full sm:max-w-xs group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-500 group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar usuario o acción..."
                            class="block w-full pl-10 pr-3 py-2 border border-slate-700 rounded-xl leading-5 bg-slate-950 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm shadow-inner transition duration-200"
                        >
                    </div>
                </div>
            </div>

            <div class="bg-slate-900 shadow-2xl rounded-2xl overflow-hidden border border-slate-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-800">
                        <thead class="bg-slate-900/50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Fecha</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Usuario</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Evento</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Descripción</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Detalles</th>
                        </tr>
                        </thead>
                        <tbody class="bg-slate-900 divide-y divide-slate-800">
                        <tr v-if="activities.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="text-slate-400 font-medium text-sm">No se encontraron registros que coincidan con la búsqueda.</p>
                            </td>
                        </tr>
                        <tr v-for="row in activities.data" :key="row.id" class="hover:bg-slate-800/50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-mono">#{{ row.id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-300 font-bold">{{ row.fecha_relativa }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-300 font-medium flex items-center">
                                <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                {{ row.usuario }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span :class="['px-3 py-1 inline-flex text-[10px] leading-5 font-black rounded-md uppercase tracking-widest', getBadgeClass(row.evento)]">
                                    {{ row.evento ? row.evento : 'GENERAL' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-400">{{ row.descripcion }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <button @click="verDetalles(row)" class="text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 p-2 rounded-lg transition-colors border border-transparent hover:border-indigo-500/30" title="Ver detalles profundos">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="activities.links && activities.data.length > 0" class="bg-slate-900/50 px-6 py-4 border-t border-slate-800 flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <Link v-if="activities.prev_page_url" :href="activities.prev_page_url" class="relative inline-flex items-center px-4 py-2 border border-slate-700 text-sm font-medium rounded-xl text-slate-300 bg-slate-800 hover:bg-slate-700">Anterior</Link>
                        <Link v-if="activities.next_page_url" :href="activities.next_page_url" class="ml-3 relative inline-flex items-center px-4 py-2 border border-slate-700 text-sm font-medium rounded-xl text-slate-300 bg-slate-800 hover:bg-slate-700">Siguiente</Link>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-slate-400">
                                Mostrando página <span class="font-bold text-slate-200">{{ activities.current_page }}</span> de <span class="font-bold text-slate-200">{{ activities.last_page }}</span>
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-xl shadow-sm -space-x-px overflow-hidden border border-slate-700" aria-label="Pagination">
                                <Link v-for="(link, i) in activities.links" :key="i" :href="link.url || '#'" v-html="link.label"
                                      :class="[
                                          link.active ? 'z-10 bg-indigo-500/20 border-x border-indigo-500/50 text-indigo-400 font-bold' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-slate-200 border-r border-slate-700 last:border-r-0',
                                          !link.url ? 'opacity-50 cursor-not-allowed hover:bg-slate-800 hover:text-slate-400' : '',
                                          'relative inline-flex items-center px-4 py-2 text-sm transition-colors'
                                      ]" />
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="modalDetalle" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

                    <div class="fixed inset-0 bg-slate-950/90 backdrop-blur-md transition-opacity" @click="cerrarModal" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="inline-block align-bottom bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-slate-800">
                        <div class="bg-slate-900/50 px-8 py-5 border-b border-slate-800 flex justify-between items-center">
                            <div>
                                <h3 class="text-xl font-black text-slate-100 tracking-widest uppercase" id="modal-title">Detalles del Registro</h3>
                                <p class="text-xs font-mono text-indigo-400 mt-1">LOG ID: #{{ logSeleccionado.id }}</p>
                            </div>
                            <button @click="cerrarModal" class="text-slate-500 hover:text-white bg-slate-800 p-2 rounded-full transition-colors focus:outline-none">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="px-8 py-6 max-h-[75vh] overflow-y-auto custom-scrollbar">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div class="bg-slate-950 rounded-2xl p-5 flex items-center border border-slate-800 shadow-inner">
                                    <div class="bg-indigo-500/10 p-3 rounded-xl mr-4 border border-indigo-500/20">
                                        <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Realizado por</p>
                                        <p class="text-lg font-black text-slate-200">{{ logSeleccionado.usuario }}</p>
                                    </div>
                                </div>
                                <div class="bg-slate-950 rounded-2xl p-5 flex items-center border border-slate-800 shadow-inner">
                                    <div class="bg-indigo-500/10 p-3 rounded-xl mr-4 border border-indigo-500/20">
                                        <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Fecha y Hora</p>
                                        <p class="text-lg font-black text-slate-200">{{ logSeleccionado.fecha }}</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="logSeleccionado.propiedades && logSeleccionado.propiedades.ip" class="mt-8">
                                <h4 class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-3 border-b border-slate-800 pb-2">Contexto de Red y Dispositivo</h4>
                                <div class="bg-slate-950 border border-slate-800 rounded-2xl overflow-hidden divide-y divide-slate-800">
                                    <div class="p-4 flex items-center">
                                        <span class="text-blue-400 mr-4 bg-blue-500/10 p-2 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg></span>
                                        <p class="text-sm text-slate-400 font-medium">Dirección IP: <strong class="text-slate-200 font-mono bg-slate-900 px-2 py-0.5 rounded">{{ logSeleccionado.propiedades.ip }}</strong></p>
                                    </div>
                                    <div class="p-4 flex items-center">
                                        <span class="text-orange-400 mr-4 bg-orange-500/10 p-2 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg></span>
                                        <p class="text-sm text-slate-400 font-medium">Tipo de Dispositivo: <strong class="text-slate-200 uppercase tracking-wider">{{ logSeleccionado.propiedades.dispositivo }}</strong></p>
                                    </div>

                                    <div v-if="logSeleccionado.propiedades.email_intentado" class="p-4 flex items-center bg-red-500/10 border-l-4 border-red-500">
                                        <span class="text-red-400 mr-4"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg></span>
                                        <p class="text-sm text-red-300">Intento fallido en email: <strong class="font-bold text-red-400">{{ logSeleccionado.propiedades.email_intentado }}</strong></p>
                                    </div>

                                    <div class="p-4 flex items-start">
                                        <span class="text-emerald-400 mr-4 bg-emerald-500/10 p-2 rounded-lg mt-0.5"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg></span>
                                        <div>
                                            <p class="text-sm font-bold text-slate-300">Navegador / Agente</p>
                                            <p class="text-xs text-slate-500 mt-1.5 break-all font-mono leading-relaxed">{{ logSeleccionado.propiedades.navegador }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="logSeleccionado.propiedades && logSeleccionado.propiedades.attributes" class="mt-8">
                                <h4 class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-3 border-b border-slate-800 pb-2">Cambios detectados en modelo <span class="text-slate-200">{{ logSeleccionado.sujeto }}</span></h4>
                                <div class="overflow-x-auto border border-slate-800 rounded-2xl shadow-inner bg-slate-950">
                                    <table class="min-w-full divide-y divide-slate-800">
                                        <thead class="bg-slate-900/50">
                                        <tr>
                                            <th scope="col" class="px-5 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">Atributo</th>
                                            <th scope="col" class="px-5 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">Valor Anterior</th>
                                            <th scope="col" class="px-5 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">Valor Nuevo</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-800">
                                        <tr v-for="(val, key) in logSeleccionado.propiedades.attributes" :key="key" class="hover:bg-slate-900/50">
                                            <td class="px-5 py-3 text-xs font-bold text-slate-300 uppercase tracking-wider">{{ key.replace('_', ' ') }}</td>
                                            <td class="px-5 py-3 text-xs text-red-400 bg-red-500/10 font-mono break-all border-l border-r border-slate-800">
                                                {{ logSeleccionado.propiedades.old && logSeleccionado.propiedades.old[key] ? logSeleccionado.propiedades.old[key] : '---' }}
                                            </td>
                                            <td class="px-5 py-3 text-xs text-emerald-400 bg-emerald-500/10 font-mono break-all">
                                                {{ val }}
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-950 px-8 py-5 flex justify-end border-t border-slate-800">
                            <button @click="cerrarModal" type="button" class="inline-flex justify-center rounded-xl border border-transparent shadow-sm px-6 py-2.5 bg-slate-800 text-sm font-bold text-slate-200 hover:bg-slate-700 hover:text-white focus:outline-none transition-colors">
                                Cerrar Registro
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from "@/Layouts/AdminLayout.vue"

const props = defineProps({
    activities: Object,
    filters: Object
})

const modalDetalle = ref(false)
const logSeleccionado = ref({})

const search = ref(props.filters?.search || '')
const evento = ref(props.filters?.evento || '')
let searchTimeout = null

watch([search, evento], () => {
    clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        router.get(route('logs.index'), {
            search: search.value,
            evento: evento.value
        }, {
            preserveState: true,
            replace: true
        })
    }, 400)
})

const getBadgeClass = (event) => {
    const classes = {
        created: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
        updated: 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
        deleted: 'bg-red-500/10 text-red-400 border border-red-500/20',
        login: 'bg-purple-500/10 text-purple-400 border border-purple-500/20',
        alerta_seguridad: 'bg-orange-500/10 text-orange-400 border border-orange-500/20'
    }
    return classes[event] || 'bg-slate-800 text-slate-400 border border-slate-700'
}

const verDetalles = (row) => {
    logSeleccionado.value = row
    modalDetalle.value = true
}

const cerrarModal = () => {
    modalDetalle.value = false
}
</script>

<style scoped>
/* Ocultar flechas del select nativo y estilar barra de scroll del modal */
.custom-select::-ms-expand {
    display: none;
}
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 10px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background: #475569;
}
</style>
