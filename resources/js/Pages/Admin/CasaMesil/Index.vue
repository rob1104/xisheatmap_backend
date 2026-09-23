<template>
    <Head title="Historial de Casas Mesil" />

    <AdminLayout>
        <template #header>
            Historial de Casas Mesil
        </template>

        <div class="max-w-7xl mx-auto animate-fade-in-up">

            <div class="mb-6 flex justify-between items-center gap-4">
                <div class="relative w-full max-w-md group">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-500 group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                    </span>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por nombre, teléfono o colonia..."
                        class="block w-full pl-10 pr-3 py-2 border border-slate-700 rounded-xl leading-5 bg-slate-950 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-200 shadow-inner"
                        @keyup.enter="doSearch"
                    >
                </div>
                <div>
                    <button @click="openCreateModal" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 transition-colors text-white rounded-lg font-bold shadow-lg flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Crear Casa Mesil
                    </button>
                </div>
            </div>

            <div class="bg-slate-900 shadow-2xl rounded-2xl overflow-hidden border border-slate-800">
                <table class="min-w-full divide-y divide-slate-800">
                    <thead class="bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Beneficiario</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Ubicación</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Casa</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Estatus</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                    </thead>
                    <tbody class="bg-slate-900 divide-y divide-slate-800">
                    <tr v-for="casa in casas.data" :key="casa.id" class="hover:bg-slate-800/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-indigo-400">{{ casa.nombre }}</div>
                            <div class="text-xs text-slate-500">{{ casa.telefono || 'Sin teléfono' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-slate-200">{{ casa.colonia }}</div>
                            <div class="text-xs text-slate-400 italic">{{ casa.calle_y_numero }}</div>
                            <div class="text-[10px] text-slate-600 font-mono mt-1">Lat: {{ casa.latitud }}, Lng: {{ casa.longitud }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-300">
                            {{ casa.casa }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span :class="casa.estatus_de_casa === 'Atendido' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20'" class="px-3 py-1 rounded-md text-xs font-bold uppercase tracking-widest">
                                {{ casa.estatus_de_casa }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                            <button @click="openEditModal(casa)" class="text-slate-400 hover:text-indigo-400 transition-colors" title="Editar Casa Mesil">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                            </button>
                            <button @click="deleteCasa(casa.id)" class="text-slate-400 hover:text-red-400 transition-colors" title="Eliminar Casa">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="casas.data.length === 0">
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            No hay casas registrados.
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex justify-between items-center" v-if="casas.links.length > 3">
                <div class="text-sm text-slate-400">
                    Mostrando {{ casas.from }} a {{ casas.to }} de {{ casas.total }} resultados
                </div>
                <div class="flex gap-1">
                    <template v-for="(link, p) in casas.links" :key="p">
                        <div v-if="link.url === null" class="px-3 py-1 text-sm bg-slate-900 text-slate-600 rounded-md border border-slate-800" v-html="link.label"></div>
                        <Link v-else :href="link.url" :class="link.active ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-slate-900 text-slate-300 border-slate-800 hover:bg-slate-800'" class="px-3 py-1 text-sm rounded-md border transition-colors" v-html="link.label" preserve-scroll />
                    </template>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <dialog v-if="showModal" class="modal-open fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 backdrop-blur-sm p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-y-auto custom-scrollbar">
                <h3 class="text-xl font-bold mb-6 text-slate-100">{{ isEditing ? 'Editar Casa Mesil' : 'Registrar Nueva Casa Mesil' }}</h3>
                
                <form @submit.prevent="saveCasa" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-400 mb-1">Nombre Completo</label>
                            <input v-model="form.nombre" type="text" required class="w-full border-slate-700 bg-slate-950 text-slate-200 placeholder-slate-500 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-400 mb-1">Teléfono</label>
                            <input v-model="form.telefono" type="text" class="w-full border-slate-700 bg-slate-950 text-slate-200 placeholder-slate-500 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-400 mb-1">Colonia</label>
                            <input v-model="form.colonia" type="text" required class="w-full border-slate-700 bg-slate-950 text-slate-200 placeholder-slate-500 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-400 mb-1">Calle y Número</label>
                            <input v-model="form.calle_y_numero" type="text" required class="w-full border-slate-700 bg-slate-950 text-slate-200 placeholder-slate-500 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-400 mb-1">Latitud</label>
                            <input v-model="form.latitud" type="number" step="any" required class="w-full border-slate-700 bg-slate-950 text-slate-200 placeholder-slate-500 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-400 mb-1">Longitud</label>
                            <input v-model="form.longitud" type="number" step="any" required class="w-full border-slate-700 bg-slate-950 text-slate-200 placeholder-slate-500 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-400 mb-1">Casa</label>
                            <input v-model="form.casa" type="text" required class="w-full border-slate-700 bg-slate-950 text-slate-200 placeholder-slate-500 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner" placeholder="Ej. Lotería, Despensa...">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-400 mb-1">Estatus de Casa</label>
                            <select v-model="form.estatus_de_casa" required class="w-full border-slate-700 bg-slate-950 text-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner">
                                <option value="Pendiente">Pendiente</option>
                                <option value="Atendido">Atendido</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-slate-800">
                        <button type="button" @click="closeModal" class="px-4 py-2 text-slate-400 hover:text-slate-200 transition-colors font-medium">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 transition-colors text-white rounded-lg font-bold shadow-lg disabled:opacity-50">
                            {{ isEditing ? 'Guardar Cambios' : 'Crear Casa Mesil' }}
                        </button>
                    </div>
                </form>
            </div>
        </dialog>

    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router, useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    casas: Object,
    filters: Object
})

const search = ref(props.filters.search || '')
const showModal = ref(false)
const isEditing = ref(false)
const selectedCasa = ref(null)

const form = useForm({
    nombre: '',
    telefono: '',
    curp: '',
    direccion: '',
    latitud: '',
    longitud: ''
})

const doSearch = () => {
    router.get(route('casas-mesil.index'), { search: search.value }, { preserveState: true })
}

const openCreateModal = () => {
    isEditing.value = false
    selectedCasa.value = null
    form.reset()
    showModal.value = true
}

const openEditModal = (casa) => {
    isEditing.value = true
    selectedCasa.value = casa
    
    form.nombre = casa.nombre
    form.telefono = casa.telefono
    form.curp = casa.curp
    form.direccion = casa.direccion
    form.latitud = casa.latitud
    form.longitud = casa.longitud
    
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    form.reset()
}

const saveCasa = () => {
    if (isEditing.value) {
        form.put(route('casas-mesil.update', selectedCasa.value.id), {
            onSuccess: () => closeModal(),
            preserveScroll: true
        })
    } else {
        form.post(route('casas-mesil.store'), {
            onSuccess: () => closeModal(),
            preserveScroll: true
        })
    }
}

const deleteCasa = (id) => {
    if(confirm('¿Estás seguro de eliminar este registro de casa?')) {
        router.delete(route('casas-mesil.destroy', id), {
            preserveScroll: true
        })
    }
}
</script>
