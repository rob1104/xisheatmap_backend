<template>
    <Head title="Roles y Permisos" />

    <AdminLayout>
        <template #header>
            Roles de Sistema y Permisos
        </template>

        <div class="max-w-7xl mx-auto">
            <div class="sm:flex sm:items-center sm:justify-between mb-6 bg-slate-900 p-6 rounded-2xl shadow-lg border border-slate-800">
                <div>
                    <h3 class="text-lg leading-6 font-black text-slate-100">Catálogo de Roles (Spatie Permission)</h3>
                    <p class="mt-1 text-sm text-slate-400">
                        Administra los roles del sistema (<code class="text-indigo-400">guard: web</code>) y configura su matriz de permisos granulares.
                    </p>
                </div>
                <div class="mt-4 sm:mt-0 flex gap-3">
                    <button
                        v-if="permisos.crear"
                        @click="openModal()"
                        type="button"
                        class="inline-flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-indigo-500/20 text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                    >
                        <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Nuevo Rol Personalizado
                    </button>
                </div>
            </div>

            <!-- Alertas de Error -->
            <div v-if="$page.props.errors.message" class="mb-6 bg-red-500/10 border-l-4 border-red-500 p-4 rounded-r-xl shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-400 font-bold">{{ $page.props.errors.message }}</p>
                    </div>
                </div>
            </div>

            <!-- Buscador -->
            <div class="flex flex-col sm:flex-row gap-4 mb-6">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Buscar rol por nombre..."
                    class="w-full sm:w-1/3 bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none placeholder-slate-500 shadow-sm transition-colors"
                />
            </div>

            <!-- Tabla de Roles -->
            <div class="bg-slate-900 shadow-2xl rounded-2xl overflow-hidden border border-slate-800">
                <table class="min-w-full divide-y divide-slate-800">
                    <thead class="bg-slate-900/50">
                        <tr>
                            <th scope="col" class="py-4 pl-6 pr-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Rol</th>
                            <th scope="col" class="px-3 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Tipo</th>
                            <th scope="col" class="px-3 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Usuarios Asignados</th>
                            <th scope="col" class="px-3 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Permisos Asociados</th>
                            <th scope="col" class="relative py-4 pl-3 pr-6 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 bg-slate-900">
                        <tr v-if="roles.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500 font-bold">
                                No se encontraron roles registrados.
                            </td>
                        </tr>
                        <tr v-for="role in roles" :key="role.id" class="hover:bg-slate-800/50 transition-colors">
                            <td class="whitespace-nowrap py-4 pl-6 pr-3">
                                <div class="flex items-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold border uppercase tracking-wider" :class="getRoleBadge(role.name)">
                                        {{ role.name }}
                                    </span>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-4 text-center">
                                <span
                                    v-if="role.is_protected"
                                    class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20"
                                >
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                    Base Protegido
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20"
                                >
                                    Personalizado
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-3 py-4 text-center">
                                <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-black bg-slate-800 text-slate-200 border border-slate-700">
                                    {{ role.users_count }}
                                </span>
                            </td>

                            <td class="px-3 py-4 text-sm">
                                <div v-if="role.permissions && role.permissions.length > 0" class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="perm in role.permissions"
                                        :key="perm"
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-indigo-500/10 text-indigo-300 border border-indigo-500/20"
                                    >
                                        {{ perm }}
                                    </span>
                                </div>
                                <span v-else class="text-xs italic text-slate-500">Sin permisos administrativos asignados</span>
                            </td>

                            <td class="whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        v-if="permisos.editar"
                                        @click="openModal(role)"
                                        type="button"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 text-indigo-400 hover:bg-indigo-600 hover:text-white border border-slate-700 transition-colors"
                                    >
                                        Editar / Permisos
                                    </button>
                                    <button
                                        v-if="permisos.eliminar && !role.is_protected"
                                        @click="confirmarEliminacion(role)"
                                        :disabled="role.users_count > 0"
                                        :title="role.users_count > 0 ? 'No se puede eliminar un rol con usuarios asignados' : 'Eliminar rol'"
                                        type="button"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold border transition-colors"
                                        :class="role.users_count > 0 ? 'bg-slate-900 text-slate-600 border-slate-800 cursor-not-allowed' : 'bg-red-500/10 text-red-400 hover:bg-red-600 hover:text-white border-red-500/20'"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Crear / Editar Rol -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div @click="closeModal()" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="inline-block align-bottom bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-800">
                        <form @submit.prevent="submitForm">
                            <div class="bg-slate-900 px-8 pt-6 pb-6">
                                <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-6">
                                    <div>
                                        <h3 class="text-lg font-black text-slate-100" id="modal-title">
                                            {{ isEditing ? `Editar Rol: ${editingRole?.name}` : 'Crear Nuevo Rol Personalizado' }}
                                        </h3>
                                        <p class="text-xs text-slate-400 mt-1">
                                            Guard del sistema fijo en <code class="text-indigo-400">web</code>.
                                        </p>
                                    </div>
                                    <button @click="closeModal()" type="button" class="text-slate-400 hover:text-slate-200">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>

                                <div class="space-y-6">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">
                                            Nombre del Rol
                                        </label>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            :disabled="isEditing && editingRole?.is_protected"
                                            placeholder="Ej. Auditor territorial"
                                            class="block w-full bg-slate-950 border border-slate-700 rounded-xl shadow-inner text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-4 py-3 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                                        />
                                        <p v-if="isEditing && editingRole?.is_protected" class="text-xs text-amber-400 mt-1.5 font-medium">
                                            Este es uno de los 6 roles base protegidos del sistema; su nombre es inmutable, pero puedes configurar sus permisos.
                                        </p>
                                        <div v-if="form.errors.name" class="text-red-400 text-xs mt-1 font-bold">{{ form.errors.name }}</div>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-3">
                                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">
                                                Matriz de Permisos ({{ form.permissions.length }} seleccionados)
                                            </label>
                                            <div class="flex gap-2">
                                                <button @click="selectAllPermissions" type="button" class="text-xs font-bold text-indigo-400 hover:text-indigo-300">
                                                    Seleccionar todos
                                                </button>
                                                <span class="text-slate-600">|</span>
                                                <button @click="clearAllPermissions" type="button" class="text-xs font-bold text-slate-400 hover:text-slate-200">
                                                    Limpiar
                                                </button>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-72 overflow-y-auto p-3 bg-slate-950 rounded-xl border border-slate-800">
                                            <label
                                                v-for="perm in permissions"
                                                :key="perm.id"
                                                class="flex items-center space-x-3 p-2.5 rounded-lg border border-slate-800/80 hover:bg-slate-900 cursor-pointer transition-colors"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :value="perm.name"
                                                    v-model="form.permissions"
                                                    class="h-4 w-4 rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <span class="text-xs font-semibold text-slate-200">{{ perm.name }}</span>
                                            </label>
                                        </div>
                                        <div v-if="form.errors.permissions" class="text-red-400 text-xs mt-1 font-bold">{{ form.errors.permissions }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-slate-950 px-8 py-5 border-t border-slate-800 flex justify-end gap-3">
                                <button
                                    @click="closeModal()"
                                    type="button"
                                    class="inline-flex justify-center rounded-xl border border-transparent px-6 py-2.5 bg-slate-800 text-sm font-bold text-slate-300 hover:bg-slate-700 hover:text-white transition-colors"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex justify-center rounded-xl border border-transparent shadow-lg px-8 py-2.5 bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                >
                                    {{ isEditing ? 'Guardar Cambios' : 'Crear Rol' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { permisosRoles } from '@/Utils/permisosRoles.js';

const props = defineProps({
    roles: {
        type: Array,
        default: () => [],
    },
    permissions: {
        type: Array,
        default: () => [],
    },
    protectedRoles: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const permisos = computed(() => permisosRoles(page.props.auth));

const searchQuery = ref(props.filters?.search || '');
let searchTimeout;
watch(searchQuery, (newSearch) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('roles.index'), { search: newSearch }, { preserveState: true, replace: true, preserveScroll: true });
    }, 300);
});

const getRoleBadge = (roleName) => {
    switch (roleName) {
        case 'Administrador':
            return 'bg-purple-500/10 text-purple-400 border-purple-500/20';
        case 'Coordinador territorial':
            return 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
        case 'Gestor seccional':
            return 'bg-blue-500/10 text-blue-400 border-blue-500/20';
        case 'Presidente de comité':
            return 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20';
        case 'Integrante de comité':
            return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
        case 'Afines':
            return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
        default:
            return 'bg-slate-500/10 text-slate-300 border-slate-500/20';
    }
};

const showModal = ref(false);
const isEditing = ref(false);
const editingRole = ref(null);

const form = useForm({
    name: '',
    permissions: [],
});

const selectAllPermissions = () => {
    form.permissions = props.permissions.map((p) => p.name);
};

const clearAllPermissions = () => {
    form.permissions = [];
};

const openModal = (role = null) => {
    form.clearErrors();
    if (role) {
        isEditing.value = true;
        editingRole.value = role;
        form.name = role.name;
        form.permissions = Array.isArray(role.permissions) ? [...role.permissions] : [];
    } else {
        isEditing.value = false;
        editingRole.value = null;
        form.reset();
        form.permissions = [];
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingRole.value = null;
    form.reset();
};

const submitForm = () => {
    if (isEditing.value && editingRole.value) {
        form.put(route('roles.update', editingRole.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('roles.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const confirmarEliminacion = (role) => {
    if (role.is_protected) return;
    if (role.users_count > 0) return;

    if (confirm(`¿Estás seguro de que deseas eliminar el rol "${role.name}"?\nEsta acción no se puede deshacer.`)) {
        router.delete(route('roles.destroy', role.id), {
            preserveScroll: true,
        });
    }
};
</script>