<template>
    <Head title="Gestión de Red y Usuarios" />

    <AdminLayout>
        <template #header>
            Red Operativa y Usuarios
        </template>

        <div class="max-w-7xl mx-auto">

            <div class="sm:flex sm:items-center sm:justify-between mb-8">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Directorio del Equipo</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Gestiona administradores, supervisores y la red de capturistas en campo.
                    </p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <button @click="openModal()" type="button" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                        <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Nuevo Usuario
                    </button>
                </div>
            </div>

            <div v-if="$page.props.errors.message" class="mb-4 bg-red-50 border-l-4 border-red-400 p-4 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700 font-medium">{{ $page.props.errors.message }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm ring-1 ring-gray-900 ring-opacity-5 sm:rounded-xl overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Usuario</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nivel / Rol</th>
                        <th scope="col" class="px-3 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Estructura</th>
                        <th scope="col" class="px-3 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Capturas</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Acciones</span></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                    <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 transition-colors">

                        <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-r from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold shadow-sm">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="font-medium text-gray-900">{{ user.name }}</div>
                                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                <span v-if="user.role === 'Administrador'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    <svg class="mr-1.5 h-2 w-2 text-purple-400" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                    Administrador
                                </span>
                            <span v-else-if="user.role === 'Supervisor'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <svg class="mr-1.5 h-2 w-2 text-blue-400" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                    Supervisor
                                </span>
                            <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                    Capturista
                                </span>
                        </td>

                        <td class="whitespace-nowrap px-3 py-4 text-sm text-center">
                            <div class="flex flex-col items-center">
                                <div v-if="user.parent" class="text-xs text-gray-500 mb-1 flex items-center">
                                    <svg class="w-3 h-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                    {{ user.parent.name }}
                                </div>
                                <button
                                    v-if="user.children_count > 0"
                                    @click="abrirModalEquipo(user)"
                                    class="text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-100 px-2.5 py-1 rounded-md transition-colors flex items-center shadow-sm"
                                >
                                    <svg class="w-3 h-3 mr-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    Ver equipo ({{ user.children_count }})
                                </button>
                                <div v-else class="text-xs text-gray-400">Sin equipo</div>
                            </div>
                        </td>

                        <td class="whitespace-nowrap px-3 py-4 text-sm text-center font-semibold text-gray-900">
                            {{ user.ines_count }}
                        </td>

                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <div class="flex items-center justify-end space-x-3">
                                <button @click="openModal(user)" class="text-indigo-600 hover:text-indigo-900 p-1 rounded-full hover:bg-indigo-50 transition-colors" title="Editar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>

                                <div v-if="user.id === $page.props.auth.user.id"
                                     title="No puedes eliminar tu propia cuenta"
                                     class="p-1 cursor-not-allowed opacity-30">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                </div>

                                <button
                                    v-else-if="user.ines_count === 0 && user.children_count === 0"
                                    @click="confirmarEliminacion(user)"
                                    class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-50 transition-colors"
                                    title="Eliminar usuario"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>

                                <div v-else
                                     title="No se puede eliminar: Tiene capturas o equipo asignado"
                                     class="p-1 cursor-not-allowed opacity-30">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </div>

                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="showTeamModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showTeamModal = false"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex items-center justify-between mb-4 border-b pb-3">
                                <h3 class="text-lg font-bold text-gray-900">
                                    Equipo de {{ selectedLeader?.name }}
                                </h3>
                                <button @click="showTeamModal = false" class="text-gray-400 hover:text-gray-500">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>

                            <ul class="divide-y divide-gray-100 max-h-96 overflow-y-auto pr-2">
                                <li v-for="member in selectedLeader?.children" :key="member.id" class="py-3 flex justify-between items-center">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-bold text-xs">
                                            {{ member.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">{{ member.name }}</p>
                                            <p class="text-xs text-gray-500">{{ member.role }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                          :class="member.ines_count > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'">
                                        {{ member.ines_count }} capturas
                                    </span>
                                    </div>
                                </li>
                            </ul>

                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button @click="showTeamModal = false" type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:ml-3 sm:w-auto sm:text-sm">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal()"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <form @submit.prevent="submitForm">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <h3 class="text-lg font-bold text-gray-900 mb-6 border-b pb-2">
                                    {{ isEditing ? 'Editar Usuario' : 'Registrar Nuevo Integrante' }}
                                </h3>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                                        <input autofocus v-model="form.name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                                        <input v-model="form.email" type="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">
                                            Contraseña {{ isEditing ? '(Dejar en blanco para no cambiar)' : '' }}
                                        </label>
                                        <input v-model="form.password" type="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Rol del Sistema</label>
                                            <select v-model="form.role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                                <option value="Administrador">Administrador</option>
                                                <option value="Supervisor">Supervisor</option>
                                                <option value="Capturista">Capturista</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Jefe Directo</label>
                                            <select v-model="form.parent_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                                <option :value="null">Sin Jefe Directo</option>
                                                <option v-for="p in availableParents" :key="p.id" :value="p.id" v-show="p.id !== editingId">
                                                    {{ p.name }} ({{ p.role }})
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button type="submit" :disabled="form.processing" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                    {{ isEditing ? 'Guardar Cambios' : 'Crear Usuario' }}
                                </button>
                                <button @click="closeModal()" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
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

    // Función para confirmar antes de disparar el borrado
    const confirmarEliminacion = (user) => {
        if (confirm(`⚠️ ALERTA\n\n¿Estás seguro de que deseas eliminar a ${user.name}?\nEsta acción no se puede deshacer.`)) {
            router.delete(route('usuarios.destroy', user.id), {
                preserveScroll: true, // Evita que la página salte hacia arriba al recargar
            });
        }
    };
</script>
