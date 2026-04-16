<template>
    <div class="flex h-screen bg-gray-100 font-sans text-gray-900">

        <aside class="w-64 bg-indigo-900 text-white flex flex-col shadow-2xl z-20">
            <div class="h-16 flex items-center justify-center border-b border-indigo-800 px-4">
                <svg class="w-6 h-6 text-indigo-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span class="text-xl font-bold tracking-wider">HeMa-X</span>
            </div>

            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <Link :href="route('dashboard')"
                      :class="{'bg-indigo-800 text-white': route().current('dashboard'), 'text-indigo-100 hover:bg-indigo-800': !route().current('dashboard')}"
                      class="flex items-center px-3 py-2.5 rounded-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    Resumen
                </Link>

                <Link :href="route('expedientes.index')"
                      :class="{'bg-indigo-800 text-white': route().current('expedientes.index'), 'text-indigo-100 hover:bg-indigo-800': !route().current('expedientes.index')}"
                      class="flex items-center px-3 py-2.5 rounded-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Ines Capturadas
                </Link>

                <Link :href="route('usuarios.index')"
                      :class="{'bg-indigo-800 text-white': route().current('usuarios.index'), 'text-indigo-100 hover:bg-indigo-800': !route().current('usuarios.index')}"
                      class="flex items-center px-3 py-2.5 rounded-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Usuarios y Red
                </Link>

                <Link :href="route('heatmap.index')"
                      :class="{'bg-indigo-800 text-white': route().current('heatmap.index'), 'text-indigo-100 hover:bg-indigo-800': !route().current('heatmap.index')}"
                      class="flex items-center px-3 py-2.5 rounded-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                    Mapa de Calor
                </Link>

                <Link :href="route('logs.index')"
                      :class="{'bg-indigo-800 text-white': route().current('logs.index'), 'text-indigo-100 hover:bg-indigo-800': !route().current('logs.index')}"
                      class="flex items-center px-3 py-2.5 rounded-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Logs de Auditoria
                </Link>
            </nav>

            <div class="p-4 border-t border-indigo-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center font-bold text-white shadow-sm">
                            {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-white leading-none">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs font-medium text-indigo-300 mt-1">{{ $page.props.auth.user.role }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
                <h2 class="text-xl font-bold text-gray-800 tracking-tight">
                    <slot name="header" />
                </h2>

                <div class="flex items-center">
                    <Link :href="route('logout')" method="post" as="button" class="text-sm font-semibold text-red-600 hover:text-red-800 flex items-center transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Cerrar Sesión
                    </Link>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-8">
                <slot />
            </main>
        </div>

    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>
