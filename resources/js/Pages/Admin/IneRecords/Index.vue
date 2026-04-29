<template>
    <Head title="Expedientes Capturados" />

    <AdminLayout>
        <template #header>
            Control de Expedientes
        </template>

        <div class="max-w-7xl mx-auto animate-fade-in-up">

            <div class="sm:flex sm:items-center sm:justify-between mb-8 gap-4 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-xl">
                <div class="mb-4 sm:mb-0">
                    <h3 class="text-lg font-bold text-slate-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Registro de Capturas
                    </h3>
                    <p class="mt-1 text-sm text-slate-400">
                        Consulta, filtra y audita los expedientes enviados por el equipo en campo.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 sm:mt-0 mt-4 flex-1 justify-end">
                    <div class="relative w-full sm:max-w-xs group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-500 group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar por nombre o clave..."
                            class="block w-full pl-10 pr-3 py-2 border border-slate-700 rounded-xl leading-5 bg-slate-950 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-200 shadow-inner"
                        >
                    </div>

                    <div class="w-full sm:max-w-xs relative">
                        <select
                            v-model="userId"
                            class="block w-full pl-3 pr-10 py-2 border border-slate-700 rounded-xl leading-5 bg-slate-950 text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-200 shadow-inner appearance-none custom-select"
                        >
                            <option value="" class="bg-slate-900 text-slate-300">Todos los brigadistas</option>
                            <option v-for="user in users" :key="user.id" :value="user.id" class="bg-slate-900 text-slate-300">
                                {{ user.name }}
                            </option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900 shadow-2xl rounded-2xl border border-slate-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-800">
                        <thead class="bg-slate-900/50">
                        <tr>
                            <th scope="col" class="py-4 pl-6 pr-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Ciudadano</th>
                            <th scope="col" class="px-3 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Identificación</th>
                            <th scope="col" class="px-3 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Brigadista</th>
                            <th scope="col" class="px-3 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Evidencias</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Acciones</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 bg-slate-900">

                        <tr v-if="records.data.length === 0">
                            <td colspan="5" class="py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                <p class="text-slate-400 font-medium text-sm">No se encontraron expedientes con estos filtros.</p>
                            </td>
                        </tr>

                        <tr v-for="record in records.data" :key="record.id" class="hover:bg-slate-800/50 transition-colors group">

                            <td class="whitespace-nowrap py-4 pl-6 pr-3">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-blue-600 to-cyan-500 flex items-center justify-center text-white font-bold shadow-lg border border-slate-700 group-hover:scale-105 transition-transform">
                                            {{ record.nombre.charAt(0).toUpperCase() }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="font-bold text-slate-200">{{ record.nombre }}</div>
                                        <div class="text-sm text-slate-500">{{ record.apellido_paterno }} {{ record.apellido_materno }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-4">
                                <div class="text-xs font-mono text-slate-300 bg-slate-950 border border-slate-700 inline-block px-2.5 py-1 rounded-md shadow-inner">{{ record.clave_elector }}</div>
                                <div class="text-xs text-indigo-400 mt-1.5 font-bold flex items-center">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    Sección: {{ record.seccion }}
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-400">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    {{ record.user?.name || 'Usuario Eliminado' }}
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-4 text-sm text-center">
                                <div class="flex justify-center space-x-2">
                                    <button
                                        v-if="record.foto_frente_path"
                                        @click="openImageModal(record, 'frente', 'Frente - ' + record.clave_elector)"
                                        class="inline-flex items-center px-3 py-1.5 border border-blue-500/30 text-xs font-bold rounded-lg text-blue-400 bg-blue-500/10 hover:bg-blue-500/20 hover:border-blue-500/50 focus:outline-none transition-all shadow-sm"
                                    >
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Frente
                                    </button>

                                    <button
                                        v-if="record.foto_reverso_path"
                                        @click="openImageModal(record, 'reverso', 'Reverso - ' + record.clave_elector)"
                                        class="inline-flex items-center px-3 py-1.5 border border-purple-500/30 text-xs font-bold rounded-lg text-purple-400 bg-purple-500/10 hover:bg-purple-500/20 hover:border-purple-500/50 focus:outline-none transition-all shadow-sm"
                                    >
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Reverso
                                    </button>
                                    <span v-if="!record.foto_frente_path && !record.foto_reverso_path" class="text-xs font-medium text-slate-600 bg-slate-800 px-2.5 py-1 rounded-md">Sin fotos</span>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-400">
                                {{ new Date(record.created_at).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' }) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right space-x-3">
                                <button @click="verDetalles(record)" class="text-slate-400 hover:text-indigo-400 transition-colors" title="Ver Detalles">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="2"/></svg>
                                </button>

                                <button v-if="$page.props.auth.user.role === 'Administrador'"
                                        @click="confirmarBorrado(record)"
                                        class="text-slate-600 hover:text-red-500 transition-colors"
                                        title="Eliminar Expediente">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/></svg>
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-slate-900/50 px-6 py-4 border-t border-slate-800" v-if="records.links">
                    <div class="flex items-center justify-between">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-slate-400">
                                    Mostrando <span class="font-bold text-slate-200">{{ records.from || 0 }}</span> a <span class="font-bold text-slate-200">{{ records.to || 0 }}</span> de <span class="font-bold text-slate-200">{{ records.total }}</span> resultados
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-xl shadow-sm -space-x-px overflow-hidden border border-slate-700" aria-label="Pagination">
                                    <Link v-for="(link, k) in records.links" :key="k"
                                          :href="link.url || '#'"
                                          v-html="link.label"
                                          class="relative inline-flex items-center px-4 py-2 text-sm font-bold transition-colors"
                                          :class="[
                                              link.active ? 'z-10 bg-indigo-500/20 border-x border-indigo-500/50 text-indigo-400' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-slate-200',
                                              !link.url ? 'opacity-50 cursor-not-allowed hover:bg-slate-800 hover:text-slate-400' : 'border-r border-slate-700 last:border-r-0'
                                          ]"
                                    />
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="isImageModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                    <div class="fixed inset-0 bg-slate-950/90 backdrop-blur-sm transition-opacity" @click="isImageModalOpen = false"></div>

                    <div class="relative inline-block align-bottom bg-slate-900 text-left overflow-hidden rounded-2xl shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-slate-700">
                        <div class="flex justify-between items-center px-6 py-4 border-b border-slate-800 bg-slate-900/50">
                            <h3 class="text-lg font-bold text-slate-100">{{ currentImageTitle }}</h3>
                            <button @click="isImageModalOpen = false" class="text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-full p-1.5 transition-colors">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <div class="p-6 bg-slate-950 flex justify-center items-center">
                            <img :src="currentImageUrl" alt="Evidencia INE" class="max-w-full max-h-[70vh] object-contain rounded-xl ring-1 ring-slate-800 shadow-2xl" />
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="showDetalles" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/95 backdrop-blur-md">
                <div class="bg-slate-900 border border-slate-800 w-full max-w-4xl rounded-3xl shadow-2xl overflow-hidden animate-fade-in-up">

                    <div class="px-8 py-5 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
                        <div>
                            <h3 class="text-xl font-black text-white tracking-widest uppercase">Expediente Digital</h3>
                            <p class="text-xs text-indigo-400 font-bold tracking-tighter">DATOS EXTRAÍDOS DE IDENTIFICACIÓN OFICIAL</p>
                        </div>
                        <button @click="showDetalles = false" class="text-slate-500 hover:text-white bg-slate-800 p-2 rounded-full transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="3"/></svg>
                        </button>
                    </div>

                    <div class="p-8 overflow-y-auto max-h-[80vh] custom-scrollbar">

                        <div class="flex flex-col md:flex-row items-center gap-6 bg-slate-950 p-6 rounded-2xl border border-slate-800 mb-8">
                            <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-indigo-600 to-indigo-900 flex items-center justify-center text-4xl font-black text-white shadow-xl shadow-indigo-500/10">
                                {{ selectedRecord.nombre.charAt(0) }}
                            </div>
                            <div class="text-center md:text-left flex-1">
                                <div class="text-2xl font-black text-white tracking-tight">
                                    {{ selectedRecord.nombre }} {{ selectedRecord.apellido_paterno }} {{ selectedRecord.apellido_materno }}
                                </div>
                                <div class="flex flex-wrap justify-center md:justify-start gap-4 mt-2">
                        <span class="text-xs font-bold px-3 py-1 bg-slate-800 text-slate-300 rounded-full border border-slate-700 uppercase tracking-widest">
                            {{ selectedRecord.sexo === 'H' ? 'Hombre' : 'Mujer' }}
                        </span>
                                    <span class="text-xs font-bold px-3 py-1 bg-indigo-500/10 text-indigo-400 rounded-full border border-indigo-500/20 uppercase tracking-widest font-mono">
                            CURP: {{ selectedRecord.curp }}
                        </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                            <div class="space-y-6">
                                <h4 class="text-indigo-500 text-[10px] font-black uppercase tracking-[0.2em] border-b border-slate-800 pb-2">Identificación Electora</h4>

                                <div class="space-y-1">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Clave de Elector</p>
                                    <p class="text-slate-200 font-mono text-sm tracking-tight">{{ selectedRecord.clave_elector }}</p>
                                </div>

                                <div class="space-y-1">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Número OCR</p>
                                    <p class="text-slate-200 font-mono text-sm tracking-tight">{{ selectedRecord.ocr_numero || 'No disponible' }}</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Sección</p>
                                        <p class="text-slate-200 text-sm font-bold">{{ selectedRecord.seccion }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Vigencia</p>
                                        <p class="text-slate-200 text-sm font-bold">{{ selectedRecord.vigencia }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <h4 class="text-indigo-500 text-[10px] font-black uppercase tracking-[0.2em] border-b border-slate-800 pb-2">Domicilio Registrado</h4>

                                <div class="space-y-1">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Calle y Número</p>
                                    <p class="text-slate-200 text-sm italic">"{{ selectedRecord.calle_numero }}"</p>
                                </div>

                                <div class="space-y-1">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Colonia y C.P.</p>
                                    <p class="text-slate-200 text-sm">{{ selectedRecord.colonia }} - {{ selectedRecord.codigo_postal }}</p>
                                </div>

                                <div class="space-y-1">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Ubicación</p>
                                    <p class="text-slate-200 text-sm font-semibold">{{ selectedRecord.municipio }}, {{ selectedRecord.estado }}</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <h4 class="text-indigo-500 text-[10px] font-black uppercase tracking-[0.2em] border-b border-slate-800 pb-2">Contacto y Auditoría</h4>

                                <div class="space-y-1">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Teléfono</p>
                                    <p class="text-slate-200 text-sm">{{ selectedRecord.telefono || 'Sin registrar' }}</p>
                                </div>

                                <div class="space-y-1">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Correo Electrónico</p>
                                    <p class="text-indigo-400 text-sm truncate">{{ selectedRecord.correo || 'Sin registrar' }}</p>
                                </div>

                                <div class="space-y-1">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Capturado Por</p>
                                    <p class="text-slate-200 text-sm font-bold">{{ selectedRecord.user?.name }}</p>
                                </div>
                            </div>

                        </div>

                        <div class="mt-10 pt-6 border-t border-slate-800 flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="flex items-center gap-4">
                                <div class="bg-slate-800 p-3 rounded-xl border border-slate-700">
                                    <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" stroke-width="2"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-none mb-1">Coordenadas de Captura</p>
                                    <p class="text-slate-400 font-mono text-xs">{{ selectedRecord.latitud }}, {{ selectedRecord.longitud }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-none mb-1">Nacimiento</p>
                                    <p class="text-slate-300 font-bold text-xs uppercase">{{ formatearFechaSimple(selectedRecord.fecha_nacimiento) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-none mb-1">Sincronizado el</p>
                                    <p class="text-slate-300 font-bold text-xs uppercase">{{ formatearFechaSimple(selectedRecord.created_at) }}</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="px-8 py-5 bg-slate-950 border-t border-slate-800 flex justify-end gap-3">
                        <button @click="showDetalles = false" class="px-8 py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl font-black text-xs uppercase tracking-widest transition-all">Cerrar Ficha</button>
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

    const showDetalles = ref(false);
    const selectedRecord = ref(null);

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

    const verDetalles = (record) => {
        selectedRecord.value = record;
        showDetalles.value = true;
    };

    const confirmarBorrado = (record) => {
        if (confirm(`¿Estás seguro de borrar permanentemente el expediente de ${record.nombre}? Esta acción no se puede deshacer.`)) {
            router.delete(route('expedientes.destroy', record.id));
        }
    };

    const formatearFechaSimple = (fecha) => {
        return new Date(fecha).toLocaleDateString('es-MX', {
            day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
        });
    };

</script>

<style scoped>
/* Oculta la flecha por defecto del select en algunos navegadores para usar el icono SVG personalizado */
.custom-select::-ms-expand {
    display: none;
}
</style>
