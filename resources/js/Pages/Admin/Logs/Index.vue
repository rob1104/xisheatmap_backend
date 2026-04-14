<template>
    <Head title="Auditoría del Sistema" />

    <AdminLayout>
        <template #header>
            Auditoría del Sistema
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 bg-gray-50 min-h-screen">

            <div class="sm:flex sm:items-center sm:justify-between mb-8 gap-4">
                <div class="mb-4 sm:mb-0 flex items-center">
                    <svg class="w-8 h-8 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <h1 class="text-2xl font-extrabold text-gray-900">Registro de Actividades</h1>
                        <p class="mt-1 text-sm text-gray-500">Monitorea cambios, accesos y eventos de seguridad.</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 sm:mt-0 mt-4 flex-1 justify-end">
                    <div class="relative w-full sm:max-w-xs">
                        <select v-model="evento" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                            <option value="">Todos los Eventos</option>
                            <option value="login">Inicios de Sesión</option>
                            <option value="created">Creaciones</option>
                            <option value="updated">Modificaciones</option>
                            <option value="deleted">Eliminaciones</option>
                            <option value="alerta_seguridad">Alertas de Seguridad</option>
                        </select>
                    </div>

                    <div class="relative w-full sm:max-w-xs">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar usuario o acción..."
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm shadow-sm"
                        >
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-md rounded-xl overflow-hidden border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-indigo-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-indigo-800 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-indigo-800 uppercase tracking-wider">Fecha</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-indigo-800 uppercase tracking-wider">Usuario</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-indigo-800 uppercase tracking-wider">Evento</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-indigo-800 uppercase tracking-wider">Descripción</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-indigo-800 uppercase tracking-wider">Detalles</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="activities.data.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">No se encontraron registros que coincidan con la búsqueda.</td>
                        </tr>
                        <tr v-for="row in activities.data" :key="row.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ row.id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">{{ row.fecha_relativa }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ row.usuario }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                  <span :class="['px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full', getBadgeClass(row.evento)]">
                    {{ row.evento ? row.evento.toUpperCase() : 'GENERAL' }}
                  </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ row.descripcion }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <button @click="verDetalles(row)" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 p-2 rounded-full transition-colors" title="Ver detalles profundos">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="activities.links && activities.data.length > 0" class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <Link v-if="activities.prev_page_url" :href="activities.prev_page_url" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Anterior</Link>
                        <Link v-if="activities.next_page_url" :href="activities.next_page_url" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Siguiente</Link>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Mostrando página <span class="font-medium">{{ activities.current_page }}</span> de <span class="font-medium">{{ activities.last_page }}</span>
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <Link v-for="(link, i) in activities.links" :key="i" :href="link.url || '#'" v-html="link.label"
                                      :class="[
                        link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                        !link.url ? 'opacity-50 cursor-not-allowed' : '',
                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                      ]" />
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="modalDetalle" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 backdrop-blur-sm transition-opacity" @click="cerrarModal" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                        <div class="bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-xl leading-6 font-bold text-indigo-700" id="modal-title">Detalles del Registro</h3>
                            <button @click="cerrarModal" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="px-6 py-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div class="bg-gray-50 rounded-lg p-4 flex items-center border border-gray-100">
                                    <div class="bg-indigo-100 p-2 rounded-full mr-3">
                                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-semibold uppercase">Realizado por</p>
                                        <p class="text-md font-bold text-gray-900">{{ logSeleccionado.usuario }}</p>
                                    </div>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4 flex items-center border border-gray-100">
                                    <div class="bg-indigo-100 p-2 rounded-full mr-3">
                                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-semibold uppercase">Fecha y Hora</p>
                                        <p class="text-md font-bold text-gray-900">{{ logSeleccionado.fecha }}</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="logSeleccionado.propiedades && logSeleccionado.propiedades.ip" class="mt-4">
                                <h4 class="text-sm font-bold text-gray-600 mb-3 border-b pb-2">Información del Dispositivo / Red</h4>
                                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden divide-y divide-gray-200">
                                    <div class="p-3 flex items-center">
                                        <span class="text-blue-500 mr-3"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg></span>
                                        <p class="text-sm text-gray-700">Dirección IP: <strong class="text-gray-900">{{ logSeleccionado.propiedades.ip }}</strong></p>
                                    </div>
                                    <div class="p-3 flex items-center">
                                        <span class="text-orange-500 mr-3"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg></span>
                                        <p class="text-sm text-gray-700">Tipo de Dispositivo: <strong class="text-gray-900">{{ logSeleccionado.propiedades.dispositivo }}</strong></p>
                                    </div>

                                    <div v-if="logSeleccionado.propiedades.email_intentado" class="p-3 flex items-center bg-red-50">
                                        <span class="text-red-500 mr-3"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg></span>
                                        <p class="text-sm text-red-700">Email intentado: <strong class="font-bold">{{ logSeleccionado.propiedades.email_intentado }}</strong></p>
                                    </div>

                                    <div class="p-3 flex items-start">
                                        <span class="text-green-500 mr-3 mt-0.5"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg></span>
                                        <div>
                                            <p class="text-sm text-gray-700">Navegador / Agente</p>
                                            <p class="text-xs text-gray-500 mt-1 break-all">{{ logSeleccionado.propiedades.navegador }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="logSeleccionado.propiedades && logSeleccionado.propiedades.attributes" class="mt-4">
                                <h4 class="text-sm font-bold text-gray-600 mb-3 border-b pb-2">Cambios detectados en {{ logSeleccionado.sujeto }}</h4>
                                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-indigo-600">
                                        <tr>
                                            <th scope="col" class="px-4 py-2 text-left text-xs font-bold text-white uppercase tracking-wider">Atributo</th>
                                            <th scope="col" class="px-4 py-2 text-left text-xs font-bold text-white uppercase tracking-wider">Valor Original</th>
                                            <th scope="col" class="px-4 py-2 text-left text-xs font-bold text-white uppercase tracking-wider">Valor Nuevo</th>
                                        </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(val, key) in logSeleccionado.propiedades.attributes" :key="key">
                                            <td class="px-4 py-2 text-xs font-bold text-gray-700 uppercase">{{ key.replace('_', ' ') }}</td>
                                            <td class="px-4 py-2 text-sm text-red-700 bg-red-50 font-mono break-all">
                                                {{ logSeleccionado.propiedades.old && logSeleccionado.propiedades.old[key] ? logSeleccionado.propiedades.old[key] : '---' }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-green-700 bg-green-50 font-mono break-all">
                                                {{ val }}
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-6 py-4 flex justify-end">
                            <button @click="cerrarModal" type="button" class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:text-sm transition-colors">
                                Cerrar
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

    // Variables reactivas inicializadas con los valores de la URL (si existen)
    const search = ref(props.filters?.search || '')
    const evento = ref(props.filters?.evento || '')
    let searchTimeout = null

    // Observador: Cuando el usuario teclea o cambia el select, recarga la tabla
    watch([search, evento], () => {
        clearTimeout(searchTimeout)

        // Espera 400ms después de escribir para no saturar el servidor
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
            created: 'bg-green-100 text-green-800',
            updated: 'bg-blue-100 text-blue-800',
            deleted: 'bg-red-100 text-red-800',
            login: 'bg-purple-100 text-purple-800',
            alerta_seguridad: 'bg-orange-100 text-orange-800'
        }
        return classes[event] || 'bg-gray-100 text-gray-800'
    }

    const verDetalles = (row) => {
        logSeleccionado.value = row
        modalDetalle.value = true
    }

    const cerrarModal = () => {
        modalDetalle.value = false
    }
</script>
