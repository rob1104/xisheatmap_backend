<template>
    <Head title="Acceso al Panel de Administración" />

    <div class="flex min-h-screen bg-slate-950 font-sans text-slate-200 selection:bg-indigo-500 selection:text-white">

        <div class="flex flex-col justify-center flex-1 px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-slate-900 border-r border-slate-800 shadow-2xl z-10 relative">
            <div class="w-full max-w-sm mx-auto lg:w-96 animate-fade-in-up">
                <div>
                    <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 mb-8 shadow-lg shadow-indigo-500/10">
                        <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-black text-slate-100 tracking-tight">Panel de Administración</h2>
                    <p class="mt-2 text-sm text-slate-400 font-medium">Xis HeatMap Analytics</p>
                </div>

                <div class="mt-10">
                    <div v-if="form.errors.email" class="p-4 mb-6 text-sm text-red-400 font-bold bg-red-500/10 border-l-4 border-red-500 rounded-r-xl shadow-sm">
                        {{ form.errors.email }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label for="email" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Correo Electrónico</label>
                            <div class="mt-1">
                                <input id="email" type="email" v-model="form.email" required autofocus class="block w-full px-4 py-3 bg-slate-950 border border-slate-700 rounded-xl shadow-inner text-slate-200 placeholder-slate-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors" placeholder="usuario@xis.mx" />
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Contraseña</label>
                            <div class="mt-1">
                                <input id="password" type="password" v-model="form.password" required class="block w-full px-4 py-3 bg-slate-950 border border-slate-700 rounded-xl shadow-inner text-slate-200 placeholder-slate-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors" placeholder="••••••••" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input id="remember" type="checkbox" v-model="form.remember" class="w-4 h-4 text-indigo-500 bg-slate-950 border-slate-700 rounded focus:ring-indigo-500 focus:ring-offset-slate-900 transition-colors cursor-pointer" />
                                <label for="remember" class="ml-3 text-sm font-medium text-slate-300 cursor-pointer hover:text-slate-200 transition-colors">Mantener sesión activa</label>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" :disabled="form.processing" class="flex justify-center w-full px-4 py-3 text-sm font-bold text-white transition-all duration-200 bg-indigo-600 border border-transparent rounded-xl shadow-lg shadow-indigo-500/20 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span v-if="form.processing" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Verificando credenciales...
                                </span>
                                <span v-else>Ingresar al Panel</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="relative hidden w-0 flex-1 lg:block overflow-hidden bg-slate-950">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-950 to-indigo-950"></div>
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10 mix-blend-overlay"></div>

            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl animate-pulse" style="animation-duration: 4s;"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl animate-pulse" style="animation-duration: 6s;"></div>

            <div class="absolute inset-0 flex flex-col items-center justify-center p-12 text-center z-10">
                <div class="mb-10 p-5 bg-white/5 backdrop-blur-md border border-white/10 rounded-3xl shadow-2xl">
                    <svg class="w-16 h-16 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                </div>
                <h1 class="text-5xl font-black tracking-tight mb-6 text-transparent bg-clip-text bg-gradient-to-r from-white to-indigo-200">
                    Control Operativo
                </h1>
                <p class="text-xl font-medium text-slate-400 max-w-lg leading-relaxed">
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

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
