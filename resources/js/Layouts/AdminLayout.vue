<template>
    <div class="flex h-screen bg-slate-950 font-sans text-slate-200 selection:bg-indigo-500 selection:text-white">

        <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col shadow-2xl z-20 transition-all duration-300">
            <div class="h-16 flex items-center justify-center bg-slate-950/50 border-b border-slate-800 px-4">
                <svg class="w-7 h-7 text-indigo-500 mr-2 drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span class="text-2xl font-black tracking-widest text-transparent bg-clip-text bg-gradient-to-r from-white to-indigo-200">
                    HeMa-X
                </span>
            </div>

            <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-2 custom-scrollbar">

                <Link :href="route('dashboard')"
                      :class="route().current('dashboard') ? 'bg-indigo-600/10 text-indigo-400 border-l-4 border-indigo-500' : 'text-slate-400 border-l-4 border-transparent hover:bg-slate-800 hover:text-white hover:border-slate-600'"
                      class="flex items-center px-4 py-3 rounded-r-xl transition-all duration-300 ease-in-out group hover:translate-x-1">
                    <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span class="font-medium">Resumen</span>
                </Link>

                <Link :href="route('expedientes.index')"
                      :class="route().current('expedientes.index') ? 'bg-indigo-600/10 text-indigo-400 border-l-4 border-indigo-500' : 'text-slate-400 border-l-4 border-transparent hover:bg-slate-800 hover:text-white hover:border-slate-600'"
                      class="flex items-center px-4 py-3 rounded-r-xl transition-all duration-300 ease-in-out group hover:translate-x-1">
                    <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span class="font-medium">Ines Capturadas</span>
                </Link>

                <Link :href="route('tarjetas.index')"
                      :class="route().current('tarjetas.index') ? 'bg-indigo-600/10 text-indigo-400 border-l-4 border-indigo-500' : 'text-slate-400 border-l-4 border-transparent hover:bg-slate-800 hover:text-white hover:border-slate-600'"
                      class="flex items-center px-4 py-3 rounded-r-xl transition-all duration-300 ease-in-out group hover:translate-x-1">
                    <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    <span class="font-medium">Tarjetas de Beneficios</span>
                </Link>

                <Link :href="route('usuarios.index')"
                      :class="route().current('usuarios.index') ? 'bg-indigo-600/10 text-indigo-400 border-l-4 border-indigo-500' : 'text-slate-400 border-l-4 border-transparent hover:bg-slate-800 hover:text-white hover:border-slate-600'"
                      class="flex items-center px-4 py-3 rounded-r-xl transition-all duration-300 ease-in-out group hover:translate-x-1">
                    <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span class="font-medium">Usuarios y Red</span>
                </Link>

                <Link :href="route('heatmap.index')"
                      :class="route().current('heatmap.index') ? 'bg-indigo-600/10 text-indigo-400 border-l-4 border-indigo-500' : 'text-slate-400 border-l-4 border-transparent hover:bg-slate-800 hover:text-white hover:border-slate-600'"
                      class="flex items-center px-4 py-3 rounded-r-xl transition-all duration-300 ease-in-out group hover:translate-x-1">
                    <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                    <span class="font-medium">Mapa de Calor</span>
                </Link>

                <Link :href="route('logs.index')"
                      :class="route().current('logs.index') ? 'bg-indigo-600/10 text-indigo-400 border-l-4 border-indigo-500' : 'text-slate-400 border-l-4 border-transparent hover:bg-slate-800 hover:text-white hover:border-slate-600'"
                      class="flex items-center px-4 py-3 rounded-r-xl transition-all duration-300 ease-in-out group hover:translate-x-1">
                    <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <span class="font-medium">Logs de Auditoria</span>
                </Link>
            </nav>

            <div class="p-4 bg-slate-950/30 border-t border-slate-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center font-bold text-white shadow-lg border border-slate-700">
                            {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                        </div>
                    </div>
                    <div class="ml-3 truncate">
                        <p class="text-sm font-bold text-white leading-none truncate">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs font-medium text-indigo-400 mt-1 uppercase tracking-wider">{{ $page.props.auth.user.role }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">

            <header class="h-16 bg-slate-900/80 backdrop-blur-md shadow-sm border-b border-slate-800 flex items-center justify-between px-6 z-10 relative">
                <h2 class="text-xl font-bold text-slate-100 tracking-tight flex items-center">
                    <slot name="header" />
                </h2>

                <div class="flex items-center gap-4">
                    <Link :href="route('logout')" method="post" as="button" class="group flex items-center px-4 py-2 text-sm font-bold text-slate-400 bg-slate-800 hover:bg-red-900/50 hover:text-red-400 rounded-lg transition-all duration-300">
                        <svg class="w-4 h-4 mr-2 transition-transform duration-300 group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Cerrar Sesión
                    </Link>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-950 p-6 md:p-8">
                <div class="animate-fade-in-up">
                    <slot />
                </div>
            </main>
        </div>

    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<style>
/* Pequeñas utilidades visuales */
.animate-fade-in-up {
    animation: fadeInUp 0.4s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Scrollbar estilizado para el sidebar */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 4px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background: #475569;
}
</style>
