<template>
    <Head title="Acceso al Panel de Administración" />

    <div class="flex min-h-screen bg-gray-50">
        <div class="flex flex-col justify-center flex-1 px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-white">
            <div class="w-full max-w-sm mx-auto lg:w-96">
                <div>
                    <div class="flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 mb-6">
                        <svg class="w-8 h-8 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-extrabold text-gray-900">Panel de Administración</h2>
                    <p class="mt-2 text-sm text-gray-600">Xis HeatMap Analytics</p>
                </div>

                <div class="mt-8">
                    <div v-if="form.errors.email" class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg">
                        {{ form.errors.email }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                            <div class="mt-1">
                                <input id="email" type="email" v-model="form.email" required autofocus class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                            <div class="mt-1">
                                <input id="password" type="password" v-model="form.password" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input id="remember" type="checkbox" v-model="form.remember" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
                                <label for="remember" class="ml-2 text-sm text-gray-900">Recordarme</label>
                            </div>
                        </div>

                        <div>
                            <button type="submit" :disabled="form.processing" class="flex justify-center w-full px-4 py-3 text-sm font-medium text-white transition duration-150 bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                                <span v-if="form.processing">Verificando credenciales...</span>
                                <span v-else>Ingresar al Panel</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="relative hidden w-0 flex-1 lg:block">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-900 to-purple-900"></div>
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>

            <div class="absolute inset-0 flex flex-col items-center justify-center p-12 text-center text-white">
                <h1 class="text-5xl font-bold tracking-tight mb-4">Control Operativo</h1>
                <p class="text-xl font-light text-indigo-200 max-w-lg">
                    Gestión de red, expedientes y análisis geoespacial en tiempo real para tu campaña.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    // Inertia maneja la petición y los errores automáticamente
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>
