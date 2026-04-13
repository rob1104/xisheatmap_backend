<template>
    <Head title="Expedientes Capturados" />

    <AdminLayout>
        <template #header>
            Control de Expedientes
        </template>

        <div class="max-w-7xl mx-auto">

            <div class="sm:flex sm:items-center sm:justify-between mb-8 gap-4">
                <div class="mb-4 sm:mb-0">
                    <h3 class="text-lg font-bold text-gray-900">Registro de Capturas</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Consulta, filtra y audita los expedientes enviados por el equipo en campo.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 sm:mt-0 mt-4 flex-1 justify-end">
                    <div class="relative w-full sm:max-w-xs">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar por nombre o clave..."
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-150 ease-in-out"
                        >
                    </div>

                    <div class="w-full sm:max-w-xs">
                        <select
                            v-model="userId"
                            class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                        >
                            <option value="">Todos los brigadistas</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm ring-1 ring-gray-900 ring-opacity-5 sm:rounded-xl overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ciudadano</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Identificación</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Brigadista</th>
                        <th scope="col" class="px-3 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Evidencias</th>
                        <th scope="col" class="px-3 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Fecha</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                    <tr v-if="records.data.length === 0">
                        <td colspan="5" class="py-8 text-center text-gray-500">No se encontraron expedientes.</td>
                    </tr>
                    <tr v-for="record in records.data" :key="record.id" class="hover:bg-gray-50 transition-colors">

                        <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-cyan-600 flex items-center justify-center text-white font-bold shadow-sm">
                                        {{ record.nombre.charAt(0).toUpperCase() }}
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="font-medium text-gray-900">{{ record.nombre }}</div>
                                    <div class="text-sm text-gray-500">{{ record.apellido_paterno }} {{ record.apellido_materno }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="whitespace-nowrap px-3 py-4">
                            <div class="text-sm font-mono text-gray-900 bg-gray-100 inline-block px-2 py-0.5 rounded">{{ record.clave_elector }}</div>
                            <div class="text-xs text-indigo-600 mt-1 font-medium">Sección: {{ record.seccion }}</div>
                        </td>

                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                {{ record.user?.name || 'Usuario Eliminado' }}
                            </div>
                        </td>

                        <td class="whitespace-nowrap px-3 py-4 text-sm text-center">
                            <div class="flex justify-center space-x-2">
                                <button
                                    v-if="record.foto_frente_path"
                                    @click="openImageModal(record, 'frente', 'Frente - ' + record.clave_elector)"
                                    class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none transition-colors"
                                >
                                    Frente
                                </button>

                                <button
                                    v-if="record.foto_reverso_path"
                                    @click="openImageModal(record, 'reverso', 'Reverso - ' + record.clave_elector)"
                                    class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-purple-700 bg-purple-100 hover:bg-purple-200 focus:outline-none transition-colors"
                                >
                                    Reverso
                                </button>
                                <span v-if="!record.foto_frente_path && !record.foto_reverso_path" class="text-xs text-gray-400">Sin fotos</span>
                            </div>
                        </td>

                        <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-gray-500">
                            {{ new Date(record.created_at).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' }) }}
                        </td>
                    </tr>
                    </tbody>
                </table>

                <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6" v-if="records.links">
                    <div class="flex items-center justify-between">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Mostrando <span class="font-medium">{{ records.from || 0 }}</span> a <span class="font-medium">{{ records.to || 0 }}</span> de <span class="font-medium">{{ records.total }}</span> resultados
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <Link v-for="(link, k) in records.links" :key="k"
                                          :href="link.url || '#'"
                                          v-html="link.label"
                                          class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                          :class="[
                                              link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                              !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                          ]"
                                    />
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="isImageModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-900 bg-opacity-90 transition-opacity" @click="isImageModalOpen = false"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="inline-block align-bottom bg-transparent text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-white">{{ currentImageTitle }}</h3>
                            <button @click="isImageModalOpen = false" class="text-gray-300 hover:text-white bg-gray-800 rounded-full p-1">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <div class="bg-white rounded-lg overflow-hidden shadow-2xl flex justify-center items-center p-2">
                            <img :src="currentImageUrl" alt="Evidencia INE" class="max-w-full max-h-[80vh] object-contain rounded" />
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    records: Object,
    filters: Object,
    users: Array
});

// --- LÓGICA DE FILTROS EN TIEMPO REAL ---
const search = ref(props.filters?.search || '');
const userId = ref(props.filters?.user_id || '');
let searchTimeout = null;

// Observador: Cuando el usuario teclea o cambia el select, recarga la tabla
watch([search, userId], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('expedientes.index'), {
            search: search.value,
            user_id: userId.value
        }, {
            preserveState: true, // Mantiene el foco en el input
            replace: true        // No satura el historial del navegador
        });
    }, 400); // Espera 400ms después de que el usuario deja de escribir
});


// --- LÓGICA DEL VISOR DE IMÁGENES ---
const isImageModalOpen = ref(false);
const currentImageUrl = ref('');
const currentImageTitle = ref('');

const openImageModal = (record, tipo, title) => {
    // Usamos la ruta segura de Laravel (cadenero)
    currentImageUrl.value = route('expedientes.foto', { id: record.id, tipo: tipo });
    currentImageTitle.value = title;
    isImageModalOpen.value = true;
};
</script>
