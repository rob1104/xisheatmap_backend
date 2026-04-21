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

                    <div class="bg-indigo-50 border border-indigo-100 px-4 py-2 rounded-lg flex items-center shadow-inner w-full sm:w-auto justify-center">
                        <span class="text-xs text-indigo-800 font-bold uppercase tracking-wider mr-3">En esta vista:</span>
                        <span class="text-2xl font-extrabold text-indigo-600">{{ puntosVisibles }}</span>
                        <span class="text-xs text-indigo-500 ml-2">simpatizantes</span>
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

                    <button @click="changeRadius" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-sm font-semibold transition-colors">
                        Radio
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
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'

const props = defineProps({
    coordenadas: Array,
    googleApiKey: String
})

const mapContainer = ref(null)
const cargando = ref(true)

// Variables reactivas
const puntosVisibles = ref(props.coordenadas.length)
const viendoSimpatizantes = ref(true)
const viendoBrigadistas = ref(false)

let map = null
let heatmap = null
let heatMapData = []

// Variables para brigadistas
let marcadoresBrigadistas = {}
let intervaloRastreo = null

const initMap = () => {
    let centerLat = 23.7369
    let centerLng = -99.1411

    if (props.coordenadas.length > 0) {
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

    heatmap = new window.google.maps.visualization.HeatmapLayer({
        data: heatMapData,
        map: map,
        radius: 25,
        opacity: 0.8
    })

    if (props.coordenadas.length > 1) {
        const bounds = new window.google.maps.LatLngBounds()
        heatMapData.forEach(point => bounds.extend(point))
        map.fitBounds(bounds)
    }

    map.addListener('idle', () => {
        const bounds = map.getBounds()
        if (!bounds) return
        let count = 0
        heatMapData.forEach(point => {
            if (bounds.contains(point)) count++
        })
        puntosVisibles.value = count
    })

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

const toggleHeatmap = () => {
    if (heatmap) {
        heatmap.setMap(heatmap.getMap() ? null : map)
        viendoSimpatizantes.value = heatmap.getMap() !== null
    }
}

const changeRadius = () => {
    if (heatmap) {
        const currentRadius = heatmap.get('radius') || 25
        heatmap.set('radius', currentRadius === 25 ? 50 : 25)
    }
}

onMounted(() => {
    if (window.google && window.google.maps) {
        initMap()
        return
    }

    // Un solo montaje del script con las librerías correctas
    window.initGoogleMap = initMap
    const script = document.createElement('script')
    script.src = `https://maps.googleapis.com/maps/api/js?key=${props.googleApiKey}&libraries=visualization,marker&callback=initGoogleMap`
    script.async = true
    script.defer = true
    document.head.appendChild(script)
})

onUnmounted(() => {
    delete window.initGoogleMap
    if (intervaloRastreo) clearInterval(intervaloRastreo)
})
</script>
