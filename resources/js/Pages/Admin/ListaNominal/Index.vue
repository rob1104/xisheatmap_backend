<template>
    <Head title="Lista Nominal" />

    <AdminLayout>
        <template #header>
            Lista Nominal · Cortes
        </template>

        <div class="max-w-7xl mx-auto animate-fade-in-up space-y-6">

            <div v-if="usandoDatosDeEjemplo" class="flex items-start gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-300">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>
                    Datos de ejemplo solamente. 
                </span>
            </div>

            <!-- Corte activo -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-2xl">
                    <div class="flex items-center justify-between gap-4">
                        <div class="text-xs font-bold uppercase tracking-widest text-slate-500">Corte activo en el mapa</div>
                        <span v-if="corteActivo" class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-widest bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            Vigente
                        </span>
                    </div>
                    <template v-if="corteActivo">
                        <div class="mt-2 text-2xl font-black text-slate-100">{{ formatoFecha(corteActivo.fecha_corte) }}</div>
                        <div class="text-sm text-slate-400">{{ corteActivo.fuente }}</div>
                        <div v-if="corteActivo.descripcion" class="text-xs text-slate-500 italic mt-1">{{ corteActivo.descripcion }}</div>
                    </template>
                    <div v-else class="mt-2 text-sm text-amber-400">
                        No hay un corte activo. El mapa no puede calcular la cobertura hasta que actives uno.
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-2xl grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-widest text-slate-500">Secciones</div>
                        <div class="mt-2 text-2xl font-black text-slate-100 tabular-nums">{{ corteActivo ? formatoNumero(corteActivo.detalles_count) : '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-bold uppercase tracking-widest text-slate-500">Electores LN</div>
                        <div class="mt-2 text-2xl font-black text-indigo-400 tabular-nums">{{ corteActivo ? formatoNumero(corteActivo.total_lista_nominal) : '—' }}</div>
                    </div>
                </div>
            </div>

            <!-- Encabezado de la tabla -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-100">Historial de cortes</h3>
                    <p class="text-sm text-slate-500">Solo un corte alimenta el mapa a la vez. Los inactivos se toman como historicos.</p>
                </div>
                <button
                    v-if="permisos.crear"
                    @click="abrirModal"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 transition-colors text-white rounded-lg font-bold shadow-lg flex items-center gap-2 self-start"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Nuevo corte
                </button>
            </div>

            <!-- Tabla de cortes -->
            <div class="bg-slate-900 shadow-2xl rounded-2xl overflow-x-auto border border-slate-800">
                <table class="min-w-full divide-y divide-slate-800">
                    <thead class="bg-slate-900/50">
                        <tr>
                            <th class="px-4 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Fecha de corte</th>
                            <th class="px-4 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Fuente</th>
                            <th class="px-4 py-4 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Secciones</th>
                            <th class="px-4 py-4 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Lista Nominal</th>
                            <th class="px-4 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Estado</th>
                            <th class="px-4 py-4 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-slate-900 divide-y divide-slate-800">
                        <tr v-for="corte in cortesOrdenados" :key="corte.id" class="hover:bg-slate-800/50 transition-colors" :class="corte.is_active ? 'bg-emerald-500/[0.03]' : ''">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-slate-100">{{ formatoFecha(corte.fecha_corte) }}</div>
                                <div class="text-xs text-slate-500">Cargado {{ formatoFechaHora(corte.created_at) }}</div>
                            </td>
                            <td class="px-4 py-4 min-w-[14rem] max-w-sm">
                                <div class="text-sm text-slate-300">{{ corte.fuente }}</div>
                                <div v-if="corte.descripcion" class="text-xs text-slate-500 italic">{{ corte.descripcion }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm text-slate-300 tabular-nums">{{ formatoNumero(corte.detalles_count) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-semibold text-slate-200 tabular-nums">{{ formatoNumero(corte.total_lista_nominal) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span v-if="corte.is_active" class="px-3 py-1 rounded-md text-xs font-bold uppercase tracking-widest bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    ● Activo
                                </span>
                                <span v-else class="px-3 py-1 rounded-md text-xs font-bold uppercase tracking-widest bg-slate-800 text-slate-400 border border-slate-700">
                                    Histórico
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <button
                                    v-if="permisos.activar && !corte.is_active"
                                    @click="activarCorte(corte)"
                                    :disabled="procesandoId === corte.id || !corte.detalles_count"
                                    :title="corte.detalles_count ? 'Usar este corte en el mapa' : 'El corte no tiene secciones cargadas'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold border border-indigo-500/40 text-indigo-300 hover:bg-indigo-500/10 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                >
                                    {{ procesandoId === corte.id ? 'Activando…' : 'Activar' }}
                                </button>
                                <button
                                    v-if="permisos.eliminar"
                                    @click="eliminarCorte(corte)"
                                    :disabled="corte.is_active || procesandoId === corte.id"
                                    :title="corte.is_active ? 'No se puede eliminar el corte activo' : 'Eliminar corte'"
                                    class="text-slate-400 hover:text-red-400 disabled:opacity-30 disabled:cursor-not-allowed transition-colors align-middle"
                                >
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="cortesOrdenados.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                Aún no hay cortes de Lista Nominal. Crea el primero con «Nuevo corte».
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p v-if="mensajeError" class="text-sm text-red-400">{{ mensajeError }}</p>
        </div>

        <!-- Modal: nuevo corte + carga de archivo -->
        <div v-if="modalAbierto" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 backdrop-blur-sm p-4" @keydown.esc="cerrarModal">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-xl shadow-2xl max-h-[90vh] overflow-y-auto custom-scrollbar" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-corte">

                <!-- Paso 1: formulario -->
                <template v-if="!resumen">
                    <h3 id="titulo-modal-corte" class="text-xl font-bold mb-1 text-slate-100">Nuevo corte de Lista Nominal</h3>
                    <p class="text-sm text-slate-500 mb-6">Sube el archivo oficial por sección. Las secciones sin polígono en el mapa se reportan al terminar.</p>

                    <form @submit.prevent="guardarCorte" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="fecha_corte" class="block text-sm font-bold text-slate-400 mb-1">Fecha real del corte *</label>
                                <input id="fecha_corte" v-model="form.fecha_corte" type="date" :max="hoy" required class="w-full border-slate-700 bg-slate-950 text-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner [color-scheme:dark]">
                                <p v-if="errores.fecha_corte" class="mt-1 text-xs text-red-400">{{ errores.fecha_corte }}</p>
                            </div>
                            <div>
                                <label for="fuente" class="block text-sm font-bold text-slate-400 mb-1">Fuente *</label>
                                <input id="fuente" v-model="form.fuente" type="text" maxlength="150" required class="w-full border-slate-700 bg-slate-950 text-slate-200 placeholder-slate-500 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner">
                                <p v-if="errores.fuente" class="mt-1 text-xs text-red-400">{{ errores.fuente }}</p>
                            </div>
                        </div>

                        <div>
                            <label for="descripcion" class="block text-sm font-bold text-slate-400 mb-1">Descripción <span class="font-normal text-slate-600">(opcional)</span></label>
                            <input id="descripcion" v-model="form.descripcion" type="text" maxlength="255" placeholder="Ej. Corte definitivo Proceso Electoral 2027" class="w-full border-slate-700 bg-slate-950 text-slate-200 placeholder-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner">
                            <p v-if="errores.descripcion" class="mt-1 text-xs text-red-400">{{ errores.descripcion }}</p>
                        </div>

                        <div>
                            <span class="block text-sm font-bold text-slate-400 mb-1">Archivo de Lista Nominal *</span>
                            <label
                                for="archivo"
                                class="flex flex-col items-center justify-center gap-1 w-full rounded-xl border-2 border-dashed px-4 py-6 cursor-pointer transition-colors"
                                :class="arrastrando ? 'border-indigo-500 bg-indigo-500/10' : 'border-slate-700 bg-slate-950 hover:border-slate-500'"
                                @dragover.prevent="arrastrando = true"
                                @dragleave.prevent="arrastrando = false"
                                @drop.prevent="soltarArchivo"
                            >
                                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                <span v-if="form.archivo" class="text-sm font-semibold text-slate-200">{{ form.archivo.name }} <span class="text-slate-500 font-normal">({{ tamanoLegible(form.archivo.size) }})</span></span>
                                <span v-else class="text-sm text-slate-400">Arrastra el archivo o <span class="text-indigo-400 font-semibold">elígelo</span></span>
                                <span class="text-xs text-slate-600">CSV, TXT o XLSX · máx. 15 MB · columnas mínimas: seccion, total_lista_nominal</span>
                            </label>
                            <input id="archivo" ref="inputArchivo" type="file" accept=".csv,.txt,.xlsx" class="sr-only" @change="elegirArchivo">
                            <p v-if="errores.archivo" class="mt-1 text-xs text-red-400">{{ errores.archivo }}</p>
                        </div>

                        <label v-if="permisos.activar" class="flex items-center gap-2 text-sm text-slate-300">
                            <input v-model="form.activar" type="checkbox" class="rounded border-slate-600 bg-slate-950 text-indigo-600 focus:ring-indigo-500">
                            Activar este corte al terminar la carga
                        </label>

                        <div v-if="enviando" class="space-y-1">
                            <div class="flex justify-between text-xs text-slate-400">
                                <span>{{ etapa }}</span>
                                <span class="tabular-nums">{{ progreso }} %</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-800 overflow-hidden">
                                <div class="h-full bg-indigo-500 transition-all" :style="{ width: progreso + '%' }"></div>
                            </div>
                        </div>

                        <p v-if="errores.general" class="text-sm text-red-400">{{ errores.general }}</p>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="cerrarModal" :disabled="enviando" class="px-4 py-2 rounded-lg text-sm font-bold text-slate-300 bg-slate-800 hover:bg-slate-700 disabled:opacity-50">Cancelar</button>
                            <button type="submit" :disabled="enviando" class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50">
                                {{ enviando ? 'Procesando…' : 'Crear corte y cargar' }}
                            </button>
                        </div>
                    </form>
                </template>

                <!-- resumen de la carga -->
                <template v-else>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <h3 id="titulo-modal-corte" class="text-xl font-bold text-slate-100">Carga terminada</h3>
                            <p class="text-sm text-slate-500">{{ resumen.archivo }}</p>
                        </div>
                    </div>

                    <dl class="grid grid-cols-3 gap-3 text-center">
                        <div class="rounded-xl bg-slate-950 border border-slate-800 p-3">
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Procesadas</dt>
                            <dd class="text-2xl font-black text-slate-100 tabular-nums">{{ formatoNumero(resumen.total_procesadas) }}</dd>
                        </div>
                        <div class="rounded-xl bg-slate-950 border border-slate-800 p-3">
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Guardadas</dt>
                            <dd class="text-2xl font-black text-emerald-400 tabular-nums">{{ formatoNumero((resumen.insertadas || 0) + (resumen.actualizadas || 0)) }}</dd>
                        </div>
                        <div class="rounded-xl bg-slate-950 border border-slate-800 p-3">
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Sin polígono</dt>
                            <dd class="text-2xl font-black tabular-nums" :class="seccionesSinPoligono.length ? 'text-amber-400' : 'text-slate-100'">{{ formatoNumero(totalSinPoligono) }}</dd>
                        </div>
                    </dl>

                    <div v-if="seccionesSinPoligono.length" class="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-sm text-amber-300">
                        Estas secciones del archivo no existen en la cartografía y no se guardaron:
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <span v-for="s in seccionesSinPoligono" :key="s" class="px-2 py-0.5 rounded bg-amber-500/20 font-mono text-xs">{{ s }}</span>
                        </div>
                    </div>

                    <div class="flex justify-end pt-6">
                        <button type="button" @click="cerrarModal" class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-500">Listo</button>
                    </div>
                </template>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>

import { computed, reactive, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { formatoNumero } from '@/Utils/coberturaEscala.js'
import { cortesMock, mockActivo, resumenImportacionMock } from '@/Utils/listaNominalMock.js'

const props = defineProps({
    cortes: { type: Array, default: null },
    permisos: { type: Object, default: null },
})

const page = usePage()

// ---------- Datos ----------
const usandoDatosDeEjemplo = props.cortes === null && mockActivo()
const listaLocal = ref(usandoDatosDeEjemplo ? cortesMock() : [])
const listaCortes = computed(() => (usandoDatosDeEjemplo ? listaLocal.value : props.cortes || []))

const cortesOrdenados = computed(() =>
    [...listaCortes.value].sort((a, b) => String(b.fecha_corte).localeCompare(String(a.fecha_corte)) || b.id - a.id)
)
const corteActivo = computed(() => listaCortes.value.find((c) => c.is_active) || null)


const ACCIONES = ['ver', 'crear', 'activar', 'editar', 'eliminar']

const permisos = computed(() => {
    const auth = page.props.auth || {}

    const objeto = props.permisos || auth.permisos
    if (objeto && !Array.isArray(objeto)) return objeto

    const lista = auth.permissions || auth.user?.permissions || (Array.isArray(objeto) ? objeto : null)
    if (Array.isArray(lista)) {
        const nombres = lista.map((p) => (typeof p === 'string' ? p : p?.name))
        return Object.fromEntries(ACCIONES.map((a) => [a, nombres.includes(`lista-nominal.${a}`)]))
    }

    const esAdmin = auth.user?.role === 'Administrador'
    return { ver: true, crear: esAdmin, activar: esAdmin, editar: esAdmin, eliminar: esAdmin }
})

// ---------- Rutas ----------
const url = (nombre, parametros, respaldo) =>
    route().has(nombre) ? route(nombre, parametros) : respaldo

const rutas = {
    store: () => url('lista-nominal.cortes.store', undefined, '/admin/lista-nominal/cortes'),
    importar: (id) => url('lista-nominal.cortes.import', { corte: id }, `/admin/lista-nominal/cortes/${id}/import`),
    activar: (id) => url('lista-nominal.cortes.activar', { corte: id }, `/admin/lista-nominal/cortes/${id}/activar`),
    eliminar: (id) => url('lista-nominal.cortes.destroy', { corte: id }, `/admin/lista-nominal/cortes/${id}`),
}

const recargar = () => router.reload({ only: ['cortes'], preserveScroll: true })

// ---------- Activar / eliminar ----------
const procesandoId = ref(null)
const mensajeError = ref('')

const mensajeDeError = (error, porDefecto) =>
    error?.response?.data?.message || porDefecto

const activarCorte = async (corte) => {
    if (!confirm(`¿Usar el corte del ${formatoFecha(corte.fecha_corte)} para calcular la cobertura en el mapa?`)) return
    mensajeError.value = ''
    procesandoId.value = corte.id
    try {
        if (usandoDatosDeEjemplo) {
            listaLocal.value = listaLocal.value.map((c) => ({ ...c, is_active: c.id === corte.id }))
        } else {
            await axios.patch(rutas.activar(corte.id), {}, { headers: { Accept: 'application/json' } })
            recargar()
        }
    } catch (error) {
        mensajeError.value = mensajeDeError(error, 'No se pudo activar el corte.')
    } finally {
        procesandoId.value = null
    }
}

const eliminarCorte = async (corte) => {
    if (corte.is_active) return
    if (!confirm(`¿Eliminar el corte del ${formatoFecha(corte.fecha_corte)} y todas sus cifras por sección? Esta acción no se puede deshacer.`)) return
    mensajeError.value = ''
    procesandoId.value = corte.id
    try {
        if (usandoDatosDeEjemplo) {
            listaLocal.value = listaLocal.value.filter((c) => c.id !== corte.id)
        } else {
            await axios.delete(rutas.eliminar(corte.id), { headers: { Accept: 'application/json' } })
            recargar()
        }
    } catch (error) {
        mensajeError.value = mensajeDeError(error, 'No se pudo eliminar el corte.')
    } finally {
        procesandoId.value = null
    }
}

// ---------- Modal nuevo corte ----------
const hoy = new Date().toISOString().slice(0, 10)
const modalAbierto = ref(false)
const enviando = ref(false)
const progreso = ref(0)
const etapa = ref('')
const arrastrando = ref(false)
const inputArchivo = ref(null)
const resumen = ref(null)
const errores = reactive({})

const formularioVacio = () => ({
    fecha_corte: '',
    fuente: 'INE - Dirección Ejecutiva del Registro Federal de Electores',
    descripcion: '',
    archivo: null,
    activar: true,
})
const form = reactive(formularioVacio())

const limpiarErrores = () => Object.keys(errores).forEach((k) => delete errores[k])

const abrirModal = () => {
    Object.assign(form, formularioVacio())
    limpiarErrores()
    resumen.value = null
    progreso.value = 0
    modalAbierto.value = true
}

const cerrarModal = () => {
    if (enviando.value) return
    modalAbierto.value = false
    if (resumen.value && !usandoDatosDeEjemplo) recargar()
}

const EXTENSIONES = ['csv', 'txt', 'xlsx']
const TAMANO_MAXIMO = 15 * 1024 * 1024

const asignarArchivo = (archivo) => {
    delete errores.archivo
    if (!archivo) return
    const extension = archivo.name.split('.').pop().toLowerCase()
    if (!EXTENSIONES.includes(extension)) {
        errores.archivo = 'Formato no permitido. Usa CSV, TXT o XLSX.'
        return
    }
    if (archivo.size > TAMANO_MAXIMO) {
        errores.archivo = 'El archivo supera 15 MB.'
        return
    }
    form.archivo = archivo
}

const elegirArchivo = (e) => asignarArchivo(e.target.files?.[0])
const soltarArchivo = (e) => {
    arrastrando.value = false
    asignarArchivo(e.dataTransfer?.files?.[0])
}

const tamanoLegible = (bytes) =>
    bytes < 1024 * 1024 ? `${(bytes / 1024).toFixed(0)} KB` : `${(bytes / 1024 / 1024).toFixed(1)} MB`

// Convierte errores de Laravel al formulario
const mostrarErrores = (error) => {
    const lista = error?.response?.data?.errors
    if (error?.response?.status === 422 && lista) {
        Object.entries(lista).forEach(([campo, mensajes]) => {
            errores[campo] = Array.isArray(mensajes) ? mensajes[0] : mensajes
        })
    } else {
        errores.general = mensajeDeError(error, 'Ocurrió un error al procesar el corte. Intenta de nuevo.')
    }
}


const normalizarResumen = (datos, nombreArchivo) => {
    const r = datos?.data ?? datos ?? {}
    return { archivo: nombreArchivo, ...r }
}

const seccionesSinPoligono = computed(() => {
    const valor = resumen.value?.sin_poligono_geografico
    return Array.isArray(valor) ? valor : []
})
const totalSinPoligono = computed(() => {
    const valor = resumen.value?.sin_poligono_geografico
    return Array.isArray(valor) ? valor.length : Number(valor || 0)
})

const guardarCorte = async () => {
    limpiarErrores()
    if (!form.archivo) {
        errores.archivo = 'Selecciona el archivo de Lista Nominal.'
        return
    }

    enviando.value = true
    progreso.value = 0

    try {
        if (usandoDatosDeEjemplo) {
            etapa.value = 'Simulando carga (datos de ejemplo)…'
            for (const p of [25, 60, 100]) {
                progreso.value = p
                await new Promise((r) => setTimeout(r, 250))
            }
            const nuevoId = Math.max(0, ...listaLocal.value.map((c) => c.id)) + 1
            if (form.activar) listaLocal.value = listaLocal.value.map((c) => ({ ...c, is_active: false }))
            listaLocal.value.push({
                id: nuevoId,
                fecha_corte: form.fecha_corte,
                fuente: form.fuente,
                descripcion: form.descripcion || null,
                is_active: !!form.activar,
                detalles_count: 168,
                total_lista_nominal: 247300,
                created_at: new Date().toISOString(),
            })
            resumen.value = resumenImportacionMock(form.archivo.name)
            return
        }

        // Cabecera del corte
        etapa.value = 'Creando corte…'
        const { data: creado } = await axios.post(
            rutas.store(),
            { fecha_corte: form.fecha_corte, fuente: form.fuente, descripcion: form.descripcion || null },
            { headers: { Accept: 'application/json' } }
        )
        const corteId = creado?.corte?.id ?? creado?.id

        //  Subir el archivo 
        etapa.value = 'Subiendo y procesando archivo…'
        const datos = new FormData()
        datos.append('archivo', form.archivo)
        const { data: importado } = await axios.post(rutas.importar(corteId), datos, {
            headers: { Accept: 'application/json', 'Content-Type': 'multipart/form-data' },
            onUploadProgress: (e) => {
                if (e.total) progreso.value = Math.round((e.loaded / e.total) * 100)
            },
        })

        // Activarlo si se pidió
        if (form.activar && permisos.value.activar) {
            etapa.value = 'Activando corte…'
            await axios.patch(rutas.activar(corteId), {}, { headers: { Accept: 'application/json' } })
        }

        progreso.value = 100
        resumen.value = normalizarResumen(importado, form.archivo.name)
    } catch (error) {
        mostrarErrores(error)
    } finally {
        enviando.value = false
    }
}

// ---------- Formato ----------
const formatoFecha = (fecha) => {
    if (!fecha) return '—'
    const [a, m, d] = String(fecha).slice(0, 10).split('-').map(Number)
    return new Date(a, m - 1, d).toLocaleDateString('es-MX', { day: '2-digit', month: 'long', year: 'numeric' })
}


const formatoFechaHora = (fecha) => {
    if (!fecha) return '—'
    const f = new Date(fecha)
    return Number.isNaN(f.getTime())
        ? '—'
        : f.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>


