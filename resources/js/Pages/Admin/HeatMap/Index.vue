<template>
    <Head title="Mapa de Calor" />

    <AdminLayout>
        <template #header>
            Mapa de Calor de Simpatizantes
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 bg-gray-50 min-h-screen flex flex-col">

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">

                <div class="flex flex-col sm:flex-row items-center gap-6 w-full md:w-auto">
                    <div class="flex items-center">
                        <div class="bg-red-100 p-3 rounded-full mr-4">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Concentración Territorial</h2>
                            <p class="text-sm text-gray-500">Total capturado: <span class="font-bold text-gray-700">{{ coordenadas.length }}</span></p>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 px-4 py-2 rounded-lg flex items-center shadow-sm w-full sm:w-auto justify-center gap-4">
                        <span class="text-xs text-gray-800 font-bold uppercase tracking-wider">En esta vista:</span>
                        
                        <div v-if="viendoSimpatizantes" class="flex items-baseline bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">
                            <span class="text-xl font-extrabold text-indigo-600">{{ puntosVisibles }}</span>
                            <span class="text-xs text-indigo-600 ml-1 font-semibold">simpatizantes</span>
                        </div>

                        <div v-if="viendoApoyos" class="flex items-baseline bg-amber-50 px-2 py-0.5 rounded border border-amber-100">
                            <span class="text-xl font-extrabold text-amber-600">{{ apoyosVisibles }}</span>
                            <span class="text-xs text-amber-600 ml-1 font-semibold">apoyos</span>
                        </div>
                        <div v-if="viendoCasas" class="flex items-baseline bg-blue-50 px-2 py-0.5 rounded border border-blue-100">
                            <span class="text-xl font-extrabold text-blue-600">{{ casasVisibles }}</span>
                            <span class="text-xs text-blue-600 ml-1 font-semibold">casas</span>
                        </div>

                    </div>
                </div>

                <div class="flex gap-2 w-full md:w-auto justify-end">
                    <button @click="toggleHeatmap" :class="viendoSimpatizantes ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-white border-gray-300 text-gray-700'" class="px-4 py-2 border rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                        <div :class="viendoSimpatizantes ? 'bg-indigo-500 animate-pulse' : 'bg-gray-300'" class="w-2 h-2 rounded-full"></div>
                        Simpatizantes
                    </button>

                    <button @click="toggleBrigadistas" :class="viendoBrigadistas ? 'bg-green-50 text-green-700 border-green-200' : 'bg-white border-gray-300 text-gray-700'" class="px-4 py-2 border rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                        <div :class="viendoBrigadistas ? 'bg-green-500 animate-pulse' : 'bg-gray-300'" class="w-2 h-2 rounded-full"></div>
                        Brigadistas
                    </button>

                    <button @click="toggleApoyos" :class="viendoApoyos ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-white border-gray-300 text-gray-700'" class="px-4 py-2 border rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                        <div :class="viendoApoyos ? 'bg-amber-500 animate-pulse' : 'bg-gray-300'" class="w-2 h-2 rounded-full"></div>
                        Apoyos
                    </button>
                    <button @click="toggleCasas" :class="viendoCasas ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-white border-gray-300 text-gray-700'" class="px-4 py-2 border rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                        <div :class="viendoCasas ? 'bg-blue-500 animate-pulse' : 'bg-gray-300'" class="w-2 h-2 rounded-full"></div>
                        Casas Mesil
                    </button>


                    <button @click="changeRadius" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-sm font-semibold transition-colors">
                        Radio
                    </button>

                    <!-- Botón Capa Secciones Electorales -->
                    <button
                        @click="toggleSecciones"
                        :class="viendoSecciones ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-white border-gray-300 text-gray-700'"
                        class="px-4 py-2 border rounded-lg text-sm font-semibold transition-colors flex items-center gap-2"
                    >
                        <div :class="viendoSecciones ? 'bg-blue-500 animate-pulse' : 'bg-gray-300'" class="w-2 h-2 rounded-full"></div>
                        <span v-if="cargandoSecciones" class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Cargando...
                        </span>
                        <span v-else>Secciones</span>
                    </button>
                </div>
            </div>

            <div class="flex-1 bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden relative min-h-[600px]">
                <div ref="mapContainer" class="absolute inset-0 w-full h-full"></div>

                <div v-if="cargando" class="absolute inset-0 bg-white bg-opacity-75 backdrop-blur-sm flex flex-col items-center justify-center z-10">
                    <svg class="animate-spin h-10 w-10 text-indigo-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span class="text-indigo-900 font-semibold">Cargando motor geográfico...</span>
                </div>
            </div>

        </div>

        <Modal :show="mostrarModalApoyo" @close="cerrarModalApoyo">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Registrar Nuevo Apoyo
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="nombre" value="Nombre del Solicitante" />
                        <TextInput id="nombre" ref="nombreInput" v-model="formApoyo.nombre" type="text" class="mt-1 block w-full" />
                        <InputError :message="formApoyo.errors.nombre ? formApoyo.errors.nombre[0] : ''" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="telefono" value="Teléfono" />
                        <TextInput id="telefono" v-model="formApoyo.telefono" type="text" class="mt-1 block w-full" />
                        <InputError :message="formApoyo.errors.telefono ? formApoyo.errors.telefono[0] : ''" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="colonia" value="Colonia" />
                        <TextInput id="colonia" v-model="formApoyo.colonia" type="text" class="mt-1 block w-full" />
                        <InputError :message="formApoyo.errors.colonia ? formApoyo.errors.colonia[0] : ''" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="calle" value="Calle y Número" />
                        <TextInput id="calle" v-model="formApoyo.calle_y_numero" type="text" class="mt-1 block w-full" />
                        <InputError :message="formApoyo.errors.calle_y_numero ? formApoyo.errors.calle_y_numero[0] : ''" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <InputLabel for="apoyo" value="Apoyo Solicitado" />
                        <TextInput id="apoyo" v-model="formApoyo.apoyo" type="text" class="mt-1 block w-full" />
                        <InputError :message="formApoyo.errors.apoyo ? formApoyo.errors.apoyo[0] : ''" class="mt-2" />
                    </div>
                    
                    <div>
                        <InputLabel for="estatus" value="Estatus" />
                        <select id="estatus" v-model="formApoyo.estatus_de_apoyo" class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full">
                            <option value="Pendiente">Pendiente</option>
                            <option value="Atendido">Atendido</option>
                        </select>
                        <InputError :message="formApoyo.errors.estatus_de_apoyo ? formApoyo.errors.estatus_de_apoyo[0] : ''" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="cerrarModalApoyo">
                        Cancelar
                    </SecondaryButton>

                    <PrimaryButton class="ml-3" :class="{ 'opacity-25': formApoyo.processing }" :disabled="formApoyo.processing" @click="guardarApoyo">
                        Guardar Apoyo
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="mostrarModalCasa" @close="cerrarModalCasa">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Registrar Nueva Casa Mesil
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="nombre_casa" value="Nombre" />
                        <TextInput id="nombre_casa" v-model="formCasa.nombre" type="text" class="mt-1 block w-full" />
                        <InputError :message="formCasa.errors.nombre ? formCasa.errors.nombre[0] : ''" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="telefono_casa" value="Teléfono" />
                        <TextInput id="telefono_casa" v-model="formCasa.telefono" type="text" class="mt-1 block w-full" />
                        <InputError :message="formCasa.errors.telefono ? formCasa.errors.telefono[0] : ''" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="direccion_casa" value="Dirección" />
                        <TextInput id="direccion_casa" v-model="formCasa.direccion" type="text" class="mt-1 block w-full" />
                        <InputError :message="formCasa.errors.direccion ? formCasa.errors.direccion[0] : ''" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="cerrarModalCasa">
                        Cancelar
                    </SecondaryButton>

                    <PrimaryButton class="ml-3" :class="{ 'opacity-25': formCasa.processing }" :disabled="formCasa.processing" @click="guardarCasa">
                        Guardar Casa Mesil
                    </PrimaryButton>
                </div>
            </div>
        </Modal>


    </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, nextTick } from 'vue'
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import axios from 'axios'

const props = defineProps({
    coordenadas: Array,
    apoyos: Array,
    casas: Array,
    googleApiKey: String
})

const mapContainer = ref(null)
const cargando = ref(true)

// Variables reactivas
const puntosVisibles = ref(props.coordenadas.length)
const apoyosVisibles = ref(props.apoyos ? props.apoyos.length : 0)
const casasVisibles = ref(props.casas ? props.casas.length : 0)
const viendoSimpatizantes = ref(true)
const viendoBrigadistas = ref(false)
const viendoApoyos = ref(false)
const viendoCasas = ref(false)

let map = null
let heatmap = null
let heatMapData = []

// Variables para brigadistas y apoyos
let marcadoresBrigadistas = {}
let marcadoresApoyos = []
let marcadoresCasas = []
let intervaloRastreo = null
let infoWindow = null
let contextMenu = null
let geocoder = null

const mostrarModalApoyo = ref(false)
const nombreInput = ref(null)
const formApoyo = reactive({
    nombre: '',
    telefono: '',
    colonia: '',
    calle_y_numero: '',
    latitud: '',
    longitud: '',
    apoyo: '',
    estatus_de_apoyo: 'Pendiente',
    processing: false,
    errors: {}
})

const mostrarModalCasa = ref(false)
const formCasa = reactive({
    nombre: '',
    telefono: '',
    direccion: '',
    latitud: '',
    longitud: '',
    processing: false,
    errors: {}
})

const cerrarModalCasa = () => {
    mostrarModalCasa.value = false;
    formCasa.errors = {};
}

const guardarCasa = async () => {
    formCasa.processing = true;
    try {
        const res = await axios.post('/casas-mesil', formCasa, {
            headers: { 'Accept': 'application/json' }
        });
        if (res.data.success) {
            cerrarModalCasa();
            const nuevaCasa = res.data.casa;
            if (props.casas) {
                props.casas.push(nuevaCasa);
            }
            if (viendoCasas.value) {
                agregarUnMarcadorCasa(nuevaCasa);
                if (map) window.google.maps.event.trigger(map, 'idle');
            }
        }
    } catch (error) {
        if (error.response && error.response.status === 422) {
            formCasa.errors = error.response.data.errors;
        } else {
            console.error(error);
        }
    } finally {
        formCasa.processing = false;
    }
}


const cerrarModalApoyo = () => {
    mostrarModalApoyo.value = false;
    formApoyo.errors = {};
}

const guardarApoyo = async () => {
    formApoyo.processing = true;
    formApoyo.errors = {};
    
    try {
        const res = await axios.post('/apoyos', formApoyo, {
            headers: { 'Accept': 'application/json' }
        });
        
        if (res.data.success) {
            const nuevoApoyo = res.data.apoyo;
            props.apoyos.push(nuevoApoyo);
            
            if (viendoApoyos.value) {
                agregarUnMarcador(nuevoApoyo);
                if (map) window.google.maps.event.trigger(map, 'idle');
            }
            cerrarModalApoyo();
        }
    } catch (error) {
        if (error.response && error.response.status === 422) {
            formApoyo.errors = error.response.data.errors;
        }
        console.error(error);
    } finally {
        formApoyo.processing = false;
    }
}

const initMap = () => {
    let centerLat = 23.7369
    let centerLng = -99.1411
    
    geocoder = new window.google.maps.Geocoder();

    if (props.coordenadas && props.coordenadas.length > 0) {
        centerLat = parseFloat(props.coordenadas[0].latitud)
        centerLng = parseFloat(props.coordenadas[0].longitud)
    }

    map = new window.google.maps.Map(mapContainer.value, {
        zoom: 13,
        center: { lat: centerLat, lng: centerLng },
        mapTypeId: 'roadmap',
        mapId: 'DEMO_MAP_ID' // Requerido para Advanced Markers
    })

    heatMapData = props.coordenadas.map(coord => {
        return new window.google.maps.LatLng(
            parseFloat(coord.latitud),
            parseFloat(coord.longitud)
        )
    })

    try {
        if (window.google.maps.visualization && window.google.maps.visualization.HeatmapLayer) {
            heatmap = new window.google.maps.visualization.HeatmapLayer({
                data: heatMapData,
                map: map,
                radius: 25,
                opacity: 0.8
            })
        }
    } catch (err) {
        console.warn("HeatmapLayer no está disponible en esta versión de Google Maps (v3.65+):", err)
        heatmap = null
        viendoSimpatizantes.value = false
    }

    if (props.coordenadas.length > 1) {
        const bounds = new window.google.maps.LatLngBounds()
        heatMapData.forEach(point => bounds.extend(point))
        map.fitBounds(bounds)
    }

    map.addListener('idle', () => {
        const bounds = map.getBounds()
        if (!bounds) return
        
        // Conteo de Simpatizantes
        let countSimpatizantes = 0
        if (viendoSimpatizantes.value) {
            heatMapData.forEach(point => {
                if (bounds.contains(point)) countSimpatizantes++
            })
        }
        puntosVisibles.value = countSimpatizantes

        // Conteo de Apoyos
        let countApoyos = 0
        if (viendoApoyos.value) {
            marcadoresApoyos.forEach(marker => {
                if (bounds.contains(marker.position)) countApoyos++
            })
        }
        apoyosVisibles.value = countApoyos

        let countCasas = 0
        if (viendoCasas.value) {
            marcadoresCasas.forEach(marker => {
                if (bounds.contains(marker.position)) countCasas++
            })
        }
        casasVisibles.value = countCasas

    })

    contextMenu = new window.google.maps.InfoWindow();

    map.addListener('contextmenu', (e) => {
        if (!viendoApoyos.value && !viendoCasas.value) return;

        const lat = e.latLng.lat();
        const lng = e.latLng.lng();

        const contentString = `
            <div class="p-1 space-y-2">
                <button id="btn-add-context" class="w-full text-white text-xs px-4 py-2 rounded font-semibold shadow-sm transition-colors bg-indigo-600 hover:bg-indigo-700 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Registrar Apoyo Aquí
                </button>
                <button id="btn-add-context-casa" class="w-full text-white text-xs px-4 py-2 rounded font-semibold shadow-sm transition-colors bg-blue-600 hover:bg-blue-700 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Registrar Casa Mesil Aquí
                </button>
            </div>
        `;

        contextMenu.setContent(contentString);
        contextMenu.setPosition(e.latLng);
        contextMenu.open(map);

        setTimeout(() => {
            const btn = document.getElementById('btn-add-context');
            if (btn) {
                btn.addEventListener('click', async () => {
                    contextMenu.close();
                    
                    formApoyo.nombre = '';
                    formApoyo.telefono = '';
                    formApoyo.colonia = 'Buscando...';
                    formApoyo.calle_y_numero = 'Buscando...';
                    formApoyo.apoyo = '';
                    formApoyo.latitud = lat;
                    formApoyo.longitud = lng;
                    formApoyo.estatus_de_apoyo = 'Pendiente';
                    
                    mostrarModalApoyo.value = true;
                    
                    // Geocoding reverso
                    try {
                        const response = await geocoder.geocode({ location: { lat, lng } });
                        if (response.results && response.results.length > 0) {
                            const result = response.results[0];
                            let route = '';
                            let streetNumber = '';
                            let neighborhood = '';

                            result.address_components.forEach(component => {
                                if (component.types.includes('route')) {
                                    route = component.long_name;
                                }
                                if (component.types.includes('street_number')) {
                                    streetNumber = component.long_name;
                                }
                                if (component.types.includes('sublocality') || component.types.includes('sublocality_level_1') || component.types.includes('neighborhood')) {
                                    neighborhood = component.long_name;
                                }
                            });

                            if (route || streetNumber) {
                                formApoyo.calle_y_numero = route + (streetNumber ? ' ' + streetNumber : '');
                            } else {
                                formApoyo.calle_y_numero = '';
                            }
                            
                            if (neighborhood) {
                                formApoyo.colonia = neighborhood;
                            } else {
                                formApoyo.colonia = '';
                            }
                        } else {
                            formApoyo.calle_y_numero = '';
                            formApoyo.colonia = '';
                        }
                    } catch (error) {
                        console.warn("Error en geocodificación inversa", error);
                        formApoyo.calle_y_numero = '';
                        formApoyo.colonia = '';
                    }
                    
                    nextTick(() => {
                        if (nombreInput.value) {
                            nombreInput.value.focus();
                        }
                    });
                });
            }
        }, 100);
    });

    cargando.value = false
}

const obtenerBrigadistas = async () => {
    if (!viendoBrigadistas.value) return;

    try {
        const response = await axios.get(route('rastreo-brigadistas'));
        const activosEnCalle = response.data;

        activosEnCalle.forEach(brigadista => {
            const posicion = new window.google.maps.LatLng(brigadista.lat, brigadista.lng);

            if (marcadoresBrigadistas[brigadista.id]) {
                // Actualizamos posición usando la nueva API
                marcadoresBrigadistas[brigadista.id].position = posicion;
            } else {
                // Creamos el marcador avanzado con HTML y Tailwind
                const pinElement = document.createElement('div');
                pinElement.className = 'relative flex flex-col items-center justify-center cursor-pointer';
                pinElement.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#10B981" stroke="#064E3B" stroke-width="1" class="w-10 h-10 drop-shadow-lg">
                        <path d="M12 2.016q2.906 0 4.945 2.039t2.039 4.945q0 1.453-0.727 3.328t-1.758 3.516-2.039 3.070-1.711 2.273l-0.75 0.797q-0.281-0.328-0.75-0.867t-1.688-2.156-2.133-3.141-1.664-3.445-0.75-3.375q0-2.906 2.039-4.945t4.945-2.039zM10.453 14.016l6.563-6.609-1.406-1.406-5.156 5.203-2.063-2.109-1.406 1.406z"/>
                    </svg>
                    <div class="absolute top-10 bg-white px-2 py-1 rounded shadow-md border border-gray-200 text-sm font-bold text-emerald-900 whitespace-nowrap z-10">
                        ${brigadista.name}
                    </div>
                `;

                const marker = new window.google.maps.marker.AdvancedMarkerElement({
                    position: posicion,
                    map: map,
                    content: pinElement,
                    title: `Última conexión: ${brigadista.ultima_conexion}`
                });

                marcadoresBrigadistas[brigadista.id] = marker;
            }
        });

        // Limpiar desconectados
        const idsActivos = activosEnCalle.map(b => b.id);
        Object.keys(marcadoresBrigadistas).forEach(id => {
            if (!idsActivos.includes(parseInt(id))) {
                marcadoresBrigadistas[id].map = null; // En AdvancedMarkers se ocultan así
                delete marcadoresBrigadistas[id];
            }
        });

    } catch (error) {
        console.error("Error al obtener la ubicación de brigadistas:", error);
    }
}

const toggleBrigadistas = () => {
    viendoBrigadistas.value = !viendoBrigadistas.value;

    if (viendoBrigadistas.value) {
        obtenerBrigadistas();
        intervaloRastreo = setInterval(obtenerBrigadistas, 10000);
    } else {
        clearInterval(intervaloRastreo);
        Object.values(marcadoresBrigadistas).forEach(marker => marker.map = null);
        marcadoresBrigadistas = {};
    }
}


const toggleCasas = () => {
    viendoCasas.value = !viendoCasas.value;
    
    if (viendoCasas.value) {
        renderizarCasas();
    } else {
        marcadoresCasas.forEach(marker => marker.map = null);
        marcadoresCasas = [];
    }
    if (map) window.google.maps.event.trigger(map, 'idle');
}

const toggleApoyos = () => {
    viendoApoyos.value = !viendoApoyos.value;
    
    if (viendoApoyos.value) {
        renderizarApoyos();
    } else {
        marcadoresApoyos.forEach(marker => marker.map = null);
        marcadoresApoyos = [];
    }
    // Forzar actualización de conteo
    if (map) window.google.maps.event.trigger(map, 'idle')
}

const buildPinHtml = (apoyo) => {
    const isAtendido = apoyo.estatus_de_apoyo === 'Atendido';
    let checkmarkSvg = '';
    if (isAtendido) {
        checkmarkSvg = `
            <div class="absolute -top-2 -right-2 bg-emerald-500 text-white rounded-full p-0.5 border-2 border-white shadow-md z-20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </div>
        `;
    }

    return `
        ${checkmarkSvg}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#F59E0B" stroke="#78350F" stroke-width="1.5" class="w-10 h-10 drop-shadow-lg relative z-10 transition-transform duration-200 hover:scale-110">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
        </svg>
        <div class="absolute top-11 bg-slate-900 text-white px-3 py-1.5 rounded-lg shadow-xl border border-slate-700 text-xs font-bold whitespace-nowrap z-30 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none mt-1">
            ${apoyo.nombre} <br/> <span class="text-amber-400 font-medium">${apoyo.apoyo}</span>
        </div>
    `;
};

const agregarUnMarcador = (apoyo) => {
    if (!infoWindow) {
        infoWindow = new window.google.maps.InfoWindow();
    }
    
    const posicion = new window.google.maps.LatLng(parseFloat(apoyo.latitud), parseFloat(apoyo.longitud));
    
    const pinElement = document.createElement('div');
    pinElement.className = 'relative flex flex-col items-center justify-center cursor-pointer group';
    pinElement.innerHTML = buildPinHtml(apoyo);

    const marker = new window.google.maps.marker.AdvancedMarkerElement({
        position: posicion,
        map: map,
        content: pinElement,
        title: ''
    });

    marker.addListener('click', () => {
        const isAtendido = apoyo.estatus_de_apoyo === 'Atendido';
        const nextStatus = isAtendido ? 'Pendiente' : 'Atendido';
        const btnColor = isAtendido ? 'bg-red-500 hover:bg-red-600' : 'bg-emerald-500 hover:bg-emerald-600';
        const btnText = isAtendido ? 'Marcar como Pendiente' : 'Marcar como Atendido';
        
        const contentString = `
            <div class="p-2 text-slate-800" style="min-width: 150px;">
                <h3 class="font-bold text-sm mb-1 text-indigo-900">${apoyo.nombre}</h3>
                <p class="text-xs mb-3 text-slate-600">Estatus: <span class="font-bold ${isAtendido ? 'text-emerald-600' : 'text-amber-600'}">${apoyo.estatus_de_apoyo}</span></p>
                <button id="btn-toggle-${apoyo.id}" class="w-full text-white text-xs px-3 py-2 rounded font-semibold shadow-sm transition-colors cursor-pointer ${btnColor}">
                    ${btnText}
                </button>
            </div>
        `;
        
        infoWindow.setContent(contentString);
        infoWindow.open({
            anchor: marker,
            map,
        });

        setTimeout(() => {
            const btn = document.getElementById(`btn-toggle-${apoyo.id}`);
            if (btn) {
                btn.addEventListener('click', async () => {
                    btn.disabled = true;
                    btn.innerText = 'Actualizando...';
                    try {
                        const res = await axios.post(`/apoyos/${apoyo.id}/toggle-status`, {
                            estatus_de_apoyo: nextStatus
                        }, {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (res.data.success) {
                            apoyo.estatus_de_apoyo = nextStatus;
                            pinElement.innerHTML = buildPinHtml(apoyo);
                            infoWindow.close();
                        }
                    } catch (err) {
                        console.error(err);
                        btn.disabled = false;
                        btn.innerText = 'Error';
                    }
                });
            }
        }, 100);
    });

    pinElement.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const contentString = `
            <div class="p-1">
                <button id="btn-delete-${apoyo.id}" class="w-full text-white text-xs px-4 py-2 rounded font-semibold shadow-sm transition-colors bg-red-600 hover:bg-red-700 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Eliminar Apoyo
                </button>
            </div>
        `;
        
        contextMenu.setContent(contentString);
        contextMenu.open({
            anchor: marker,
            map: map
        });

        setTimeout(() => {
            const btn = document.getElementById(`btn-delete-${apoyo.id}`);
            if (btn) {
                btn.addEventListener('click', async () => {
                    if (!confirm('¿Estás seguro de eliminar este apoyo?')) {
                        contextMenu.close();
                        return;
                    }
                    
                    btn.disabled = true;
                    btn.innerText = 'Eliminando...';
                    try {
                        const res = await axios.delete(`/apoyos/${apoyo.id}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (res.data.success) {
                            marker.map = null;
                            marcadoresApoyos = marcadoresApoyos.filter(m => m !== marker);
                            const index = props.apoyos.findIndex(a => a.id === apoyo.id);
                            if (index !== -1) {
                                props.apoyos.splice(index, 1);
                            }
                            contextMenu.close();
                            if (map) window.google.maps.event.trigger(map, 'idle');
                        }
                    } catch (err) {
                        console.error(err);
                        btn.disabled = false;
                        btn.innerText = 'Error';
                    }
                });
            }
        }, 100);
    });

    marcadoresApoyos.push(marker);
};


const buildCasaHtml = (casa) => {
    return `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#3B82F6" stroke="#1E3A8A" stroke-width="1.5" class="w-10 h-10 drop-shadow-lg relative z-10 transition-transform duration-200 hover:scale-110">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
        </svg>
        <div class="absolute top-11 bg-slate-900 text-white px-3 py-1.5 rounded-lg shadow-xl border border-slate-700 text-xs font-bold whitespace-nowrap z-30 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none mt-1">
            ${casa.nombre || 'Casa Mesil'}
        </div>
    `;
};

const agregarUnMarcadorCasa = (casa) => {
    if (!infoWindow) {
        infoWindow = new window.google.maps.InfoWindow();
    }
    
    const posicion = new window.google.maps.LatLng(parseFloat(casa.latitud), parseFloat(casa.longitud));
    
    const pinElement = document.createElement('div');
    pinElement.className = 'relative flex flex-col items-center justify-center cursor-pointer group';
    pinElement.innerHTML = buildCasaHtml(casa);

    const marker = new window.google.maps.marker.AdvancedMarkerElement({
        position: posicion,
        map: map,
        content: pinElement,
        title: ''
    });

    pinElement.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const contentString = `
            <div class="p-1">
                <button id="btn-delete-casa-${casa.id}" class="w-full text-white text-xs px-4 py-2 rounded font-semibold shadow-sm transition-colors bg-red-600 hover:bg-red-700 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Eliminar Casa Mesil
                </button>
            </div>
        `;
        
        contextMenu.setContent(contentString);
        contextMenu.open({
            anchor: marker,
            map: map
        });

        setTimeout(() => {
            const btn = document.getElementById(`btn-delete-casa-${casa.id}`);
            if (btn) {
                btn.addEventListener('click', async () => {
                    if (!confirm('¿Estás seguro de eliminar esta casa mesil?')) {
                        contextMenu.close();
                        return;
                    }
                    
                    btn.disabled = true;
                    btn.innerText = 'Eliminando...';
                    try {
                        const res = await axios.delete(`/casas-mesil/${casa.id}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (res.data.success) {
                            marker.map = null;
                            marcadoresCasas = marcadoresCasas.filter(m => m !== marker);
                            const index = props.casas.findIndex(a => a.id === casa.id);
                            if (index !== -1) {
                                props.casas.splice(index, 1);
                            }
                            contextMenu.close();
                            if (map) window.google.maps.event.trigger(map, 'idle');
                        }
                    } catch (err) {
                        console.error(err);
                        btn.disabled = false;
                        btn.innerText = 'Error';
                    }
                });
            }
        }, 100);
    });

    marcadoresCasas.push(marker);
};

const renderizarCasas = () => {
    if (!props.casas) return;
    props.casas.forEach(casa => {
        agregarUnMarcadorCasa(casa);
    });
}

const renderizarApoyos = () => {
    if (!props.apoyos) {
        console.warn("No hay apoyos en props");
        return;
    }
    
    console.log("Renderizando " + props.apoyos.length + " apoyos");

    props.apoyos.forEach(apoyo => {
        agregarUnMarcador(apoyo);
    });
}

const toggleHeatmap = () => {
    if (heatmap) {
        heatmap.setMap(heatmap.getMap() ? null : map)
        viendoSimpatizantes.value = heatmap.getMap() !== null
        // Forzar actualización de conteo
        if (map) window.google.maps.event.trigger(map, 'idle')
    }
}

const changeRadius = () => {
    if (heatmap) {
        const currentRadius = heatmap.get('radius') || 25
        heatmap.set('radius', currentRadius === 25 ? 50 : 25)
    }
}

/** 
 * Data Layer
 * 
 * Aqui van todos los Layers del mapa
 */

// Estados reactivos y referencias para la capa de secciones
const viendoSecciones = ref(false)
const cargandoSecciones = ref(false)
let seccionesLayer = null
let geoJsonSeccionesCache = null
let seccionInfoWindow = null

// Función de color temático (Coropletas)
const obtenerColorPorMetrica = (simpatizantes) => {
    if (simpatizantes >= 100) return "#1e3a8a"; // Azul marino (muy alta densidad)
    if (simpatizantes >= 50)  return '#2563eb'; // Azul intenso
    if (simpatizantes >= 20)  return '#60a5fa'; // Azul medio
    if (simpatizantes > 0)   return '#93c5fd'; // Azul claro
    return '#e2e8f0';                          // Gris claro neutro (sin registros)
}

const initSeccionesLayer = () => {
    // Instanciar capa independiente y ventana de información
    seccionesLayer = new window.google.maps.Data({ map: null })
    seccionInfoWindow = new window.google.maps.InfoWindow()

    // Estilo de polígono (basado en sus propiedades GeoJSON)
    seccionesLayer.setStyle((feature) => {
        const simpatizantes = feature.getProperty('total_simpatizantes') || 0

        return {
            fillColor: obtenerColorPorMetrica(simpatizantes),
            fillOpacity: 0.35,
            strokeColor: '#1e40af',
            strokeWeight: 1.2,
            strokeOpacity: 0.8,
            cursor: 'pointer'
        }
    })

    // Hover: Resalta los polígonos al pasar el cursor y restaura al salir
    seccionesLayer.addListener('mouseover', (event) => {
        seccionesLayer.overrideStyle(event.feature, {
            fillOpacity: 0.65,
            strokeWeight: 2.5,
            strokeColor: '#0f172a'
        })
    })

    seccionesLayer.addListener('mouseout', () => {
        seccionesLayer.revertStyle()
    })

    // Mostrar popup InfoWindow con el desglose de métricas
    seccionesLayer.addListener('click', (event) => {
        const seccion = event.feature.getProperty('seccion')
        const df = event.feature.getProperty('distrito_federal')
        const dl = event.feature.getProperty('distrito_local')
        const tipo = event.feature.getProperty('tipo') === 1 ? 'Urbana' : 'Rural'
        const simpatizantes = event.feature.getProperty('total_simpatizantes') || 0
        const apoyos = event.feature.getProperty('total_apoyos') || 0
        const porcentaje = event.feature.getProperty('porcentaje') ?? event.feature.getProperty('porcentaje_simpatizantes') ?? 0

        const contenido = `
            <div class="p-3 font-sans text-slate-800" style="min-width: 200px;">
                <div class="flex items-center justify-between pb-2 mb-2 border-b border-gray-200">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Sección Electoral</span>
                    <span class="text-base font-extrabold text-gray-900">${seccion}</span>
                </div>
                <div class="space-y-1 text-xs text-gray-600 mb-3">
                    <div class="flex justify-between"><span>Distrito Federal:</span><strong class="text-gray-800">${df}</strong></div>
                    <div class="flex justify-between"><span>Distrito Local:</span><strong class="text-gray-800">${dl}</strong></div>
                    <div class="flex justify-between"><span>Tipo:</span> <strong class="text-gray-800">${tipo}</strong></div>
                </div>
                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-100 text-center">
                    <div class="bg-indigo-50 p-2 rounded border border-indigo-100">
                        <div class="text-lg font-black text-indigo-600">${simpatizantes}</div>
                        <div class="text-[10px] uppercase font-semibold text-indigo-500">Simpatizantes (${porcentaje}%)</div>
                    </div>
                    <div class="bg-amber-50 p-2 rounded border border-amber-100">
                        <div class="text-lg font-black text-amber-600">${apoyos}</div>
                        <div class="text-[10px] uppercase font-semibold text-amber-500">Apoyos</div>
                    </div>
                </div>
            </div>
        `

        seccionInfoWindow.setContent(contenido)
        seccionInfoWindow.setPosition(event.latLng)
        seccionInfoWindow.open(map)
    })
}

const toggleSecciones = async () => {
    viendoSecciones.value = !viendoSecciones.value

    if (viendoSecciones.value) {
        if (!seccionesLayer) {
            initSeccionesLayer()
        }
        // Si ya está descargado, solo reactivamos la capa en el mapa
        if (geoJsonSeccionesCache) {
            seccionesLayer.setMap(map)
            return
        }
        // Primera descarga desde el endpoint
        try {
            cargandoSecciones.value = true
            let response
            try {
                response = await axios.get('/spatial/secciones-geojson')
            } catch (errWeb) {
                response = await axios.get('/api/spatial/secciones-geojson')
            }
            geoJsonSeccionesCache = response.data
            const geoData = {
                type: 'FeatureCollection',
                features: geoJsonSeccionesCache.features || []
            }
            seccionesLayer.addGeoJson(geoData)
            seccionesLayer.setMap(map)
        } catch (error) {
            console.error('Error al cargar polígonos de secciones: ', error)
            viendoSecciones.value = false
        } finally {
            cargandoSecciones.value = false
        }

    } else {
        if (seccionesLayer) {
            seccionesLayer.setMap(null)
        }
        if (seccionInfoWindow) {
            seccionInfoWindow.close()
        }
    }
}

onMounted(() => {
    if (window.google && window.google.maps) {
        initMap()
        return
    }

    // Carga de Google Maps forzando v=3.64 (última versión con soporte HeatmapLayer) y loading=async
    window.initGoogleMap = initMap
    const script = document.createElement('script')
    script.src = `https://maps.googleapis.com/maps/api/js?v=3.64&key=${props.googleApiKey}&loading=async&libraries=visualization,marker&callback=initGoogleMap`
    script.async = true
    script.defer = true
    document.head.appendChild(script)
})

onUnmounted(() => {
    delete window.initGoogleMap
    if (intervaloRastreo) clearInterval(intervaloRastreo)
    
    // Limpiar marcadores de apoyos si quedaron activos
    if (marcadoresApoyos.length > 0) {
        marcadoresApoyos.forEach(marker => marker.map = null);
        marcadoresApoyos = [];
    }

    if (seccionesLayer) {
        seccionesLayer.setMap(null)
        seccionesLayer = null
    }
    
    if (seccionInfoWindow) {
        seccionInfoWindow.close()
        seccionInfoWindow = null
    }
})
</script>
