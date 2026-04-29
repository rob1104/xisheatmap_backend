<template>
    <Head title="Gestión de Red y Usuarios" />

    <AdminLayout>
        <template #header>
            Red Operativa y Usuarios
        </template>

        <div class="max-w-7xl mx-auto animate-fade-in-up">

            <div class="sm:flex sm:items-center sm:justify-between mb-8">
                <div>
                    <h3 class="text-lg font-bold text-slate-100">Directorio del Equipo</h3>
                    <p class="mt-1 text-sm text-slate-400">
                        Gestiona administradores, supervisores y la red de capturistas en campo.
                    </p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <button @click="openModal()" type="button" class="inline-flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-indigo-500/20 text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all">
                        <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Nuevo Usuario
                    </button>
                </div>
            </div>

            <div v-if="$page.props.errors.message" class="mb-6 bg-red-500/10 border-l-4 border-red-500 p-4 rounded-r-xl shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-400 font-bold">{{ $page.props.errors.message }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900 shadow-2xl rounded-2xl overflow-hidden border border-slate-800">
                <table class="min-w-full divide-y divide-slate-800">
                    <thead class="bg-slate-900/50">
                    <tr>
                        <th scope="col" class="py-4 pl-6 pr-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Usuario</th>
                        <th scope="col" class="px-3 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Nivel / Rol</th>
                        <th scope="col" class="px-3 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Estructura</th>
                        <th scope="col" class="px-3 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Capturas</th>
                        <th scope="col" class="relative py-4 pl-3 pr-4 sm:pr-6"><span class="sr-only">Acciones</span></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 bg-slate-900">
                    <tr v-for="user in users" :key="user.id" class="hover:bg-slate-800/50 transition-colors group">

                        <td class="whitespace-nowrap py-4 pl-6 pr-3">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white font-bold shadow-sm border border-slate-700">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="font-bold text-slate-200">{{ user.name }}</div>
                                    <div class="text-xs text-slate-500">{{ user.email }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <span v-if="user.role === 'Administrador'" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-purple-500/10 text-purple-400 border border-purple-500/20 uppercase tracking-wider">
                                <svg class="mr-1.5 h-2 w-2 text-purple-400" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                Administrador
                            </span>
                            <span v-else-if="user.role === 'Supervisor'" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20 uppercase tracking-wider">
                                <svg class="mr-1.5 h-2 w-2 text-blue-400" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                Supervisor
                            </span>
                            <span v-else class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 uppercase tracking-wider">
                                <svg class="mr-1.5 h-2 w-2 text-emerald-400" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                Capturista
                            </span>
                        </td>

                        <td class="whitespace-nowrap px-3 py-4 text-sm text-center">
                            <div class="flex flex-col items-center">
                                <div v-if="user.parent" class="text-xs text-slate-400 mb-1.5 flex items-center bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                                    <svg class="w-3 h-3 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                    {{ user.parent.name }}
                                </div>
                                <button
                                    v-if="user.children_count > 0"
                                    @click="abrirModalEquipo(user)"
                                    class="text-xs font-bold text-indigo-400 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/30 px-3 py-1.5 rounded-lg transition-colors flex items-center shadow-sm"
                                >
                                    <svg class="w-3.5 h-3.5 mr-1 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    Ver equipo ({{ user.children_count }})
                                </button>
                                <div v-else class="text-xs font-medium text-slate-600 bg-slate-800 px-2 py-1 rounded-md">Sin equipo</div>
                            </div>
                        </td>

                        <td class="whitespace-nowrap px-3 py-4 text-center font-black text-slate-200">
                            {{ user.ines_count }}
                        </td>

                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <div class="flex items-center justify-end space-x-3">
                                <button @click="openModal(user)" class="text-slate-400 hover:text-indigo-400 p-1.5 rounded-lg hover:bg-slate-800 transition-colors" title="Editar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>

                                <div v-if="user.id === $page.props.auth.user.id"
                                     title="No puedes eliminar tu propia cuenta"
                                     class="p-1.5 cursor-not-allowed opacity-30">
                                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                </div>

                                <button
                                    v-else-if="user.ines_count === 0 && user.children_count === 0"
                                    @click="confirmarEliminacion(user)"
                                    class="text-slate-400 hover:text-red-500 p-1.5 rounded-lg hover:bg-slate-800 transition-colors"
                                    title="Eliminar usuario"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>

                                <div v-else
                                     title="No se puede eliminar: Tiene capturas o equipo asignado"
                                     class="p-1.5 cursor-not-allowed opacity-30">
                                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </div>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>

    <Teleport to="body">
        <div v-if="showTeamModal" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-slate-950/90 backdrop-blur-sm transition-opacity" @click="showTeamModal = false"></div>

                <div class="relative inline-block bg-slate-900 border border-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <div class="px-6 py-5">
                        <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-4">
                            <h3 class="text-lg font-bold text-slate-100">
                                Equipo de {{ selectedLeader?.name }}
                            </h3>
                            <button @click="showTeamModal = false" class="text-slate-500 hover:text-slate-300 transition-colors">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <ul class="divide-y divide-slate-800 max-h-96 overflow-y-auto custom-scrollbar pr-2">
                            <li v-for="member in selectedLeader?.children" :key="member.id" class="py-3 flex justify-between items-center group">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-300 font-bold text-xs">
                                        {{ member.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-bold text-slate-200">{{ member.name }}</p>
                                        <p class="text-[10px] text-slate-500 uppercase tracking-widest">{{ member.role }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-bold border"
                                          :class="member.ines_count > 0 ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-slate-800 text-slate-500 border-slate-700'">
                                        {{ member.ines_count }} capturas
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="bg-slate-950 px-6 py-4 border-t border-slate-800 sm:flex sm:flex-row-reverse">
                        <button @click="showTeamModal = false" type="button" class="w-full inline-flex justify-center rounded-xl border border-transparent px-5 py-2 bg-slate-800 text-sm font-bold text-slate-200 hover:bg-slate-700 transition-colors sm:ml-3 sm:w-auto">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-slate-950/90 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>

                <div class="relative inline-block bg-slate-900 border border-slate-800 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form @submit.prevent="submitForm">

                        <div class="bg-slate-900/50 px-8 py-6 border-b border-slate-800 flex justify-between items-center">
                            <h3 class="text-xl font-black text-slate-100 tracking-widest uppercase">
                                {{ isEditing ? 'Editar Usuario' : 'Registrar Nuevo Integrante' }}
                            </h3>
                            <button type="button" @click="closeModal()" class="text-slate-500 hover:text-white bg-slate-800 p-2 rounded-full transition-colors focus:outline-none">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="px-8 py-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-6">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Nombre Completo</label>
                                        <input autofocus v-model="form.name" type="text" class="block w-full bg-slate-950 border border-slate-700 rounded-xl shadow-inner text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-4 py-3 transition-colors">
                                        <div v-if="form.errors.name" class="text-red-400 text-xs mt-1 font-bold">{{ form.errors.name }}</div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">
                                            Contraseña <span class="text-slate-500 lowercase normal-case tracking-normal">{{ isEditing ? '(Opcional)' : '' }}</span>
                                        </label>
                                        <input v-model="form.password" type="password" class="block w-full bg-slate-950 border border-slate-700 rounded-xl shadow-inner text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-4 py-3 transition-colors">
                                        <div v-if="form.errors.password" class="text-red-400 text-xs mt-1 font-bold">{{ form.errors.password }}</div>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Correo Electrónico</label>
                                        <input v-model="form.email" type="email" class="block w-full bg-slate-950 border border-slate-700 rounded-xl shadow-inner text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-4 py-3 transition-colors">
                                        <div v-if="form.errors.email" class="text-red-400 text-xs mt-1 font-bold">{{ form.errors.email }}</div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Rol del Sistema</label>
                                        <select v-model="form.role" class="block w-full bg-slate-950 border border-slate-700 rounded-xl shadow-inner text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-4 py-3 transition-colors custom-select appearance-none">
                                            <option value="Administrador" class="bg-slate-900">Administrador</option>
                                            <option value="Supervisor" class="bg-slate-900">Supervisor</option>
                                            <option value="Capturista" class="bg-slate-900">Capturista</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="md:col-span-2 mt-2">
                                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Jefe Directo Asignado</label>
                                    <select v-model="form.parent_id" class="block w-full bg-slate-950 border border-slate-700 rounded-xl shadow-inner text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-4 py-3 transition-colors custom-select appearance-none">
                                        <option :value="null" class="bg-slate-900 text-slate-400">Sin Jefe Directo / Operación Independiente</option>
                                        <option v-for="p in availableParents" :key="p.id" :value="p.id" v-show="p.id !== editingId" class="bg-slate-900">
                                            {{ p.name }} (Nivel: {{ p.role }})
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-950 px-8 py-5 border-t border-slate-800 flex justify-end gap-3">
                            <button @click="closeModal()" type="button" class="inline-flex justify-center rounded-xl border border-transparent px-6 py-2.5 bg-slate-800 text-sm font-bold text-slate-300 hover:bg-slate-700 hover:text-white transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="form.processing" class="inline-flex justify-center rounded-xl border border-transparent shadow-lg px-8 py-2.5 bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all">
                                {{ isEditing ? 'Guardar Cambios' : 'Registrar Usuario' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue'

defineProps({
    users: Array,
    availableParents: Array
});

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const showTeamModal = ref(false);
const selectedLeader = ref(null);

const abrirModalEquipo = (user) => {
    selectedLeader.value = user;
    showTeamModal.value = true;
};

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'Capturista',
    parent_id: null
});

const openModal = (user = null) => {
    form.clearErrors();
    if (user) {
        isEditing.value = true;
        editingId.value = user.id;
        form.name = user.name;
        form.email = user.email;
        form.role = user.role;
        form.parent_id = user.parent_id;
        form.password = ''; // Siempre vacío al editar por seguridad
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('usuarios.update', editingId.value), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('usuarios.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const confirmarEliminacion = (user) => {
    if (confirm(`⚠️ ALERTA\n\n¿Estás seguro de que deseas eliminar a ${user.name}?\nEsta acción no se puede deshacer.`)) {
        router.delete(route('usuarios.destroy', user.id), {
            preserveScroll: true,
        });
    }
};
</script>

<style scoped>
.custom-select::-ms-expand {
    display: none;
}
</style>
