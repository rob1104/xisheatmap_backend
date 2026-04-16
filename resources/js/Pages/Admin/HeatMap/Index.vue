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
                    <button @click="toggleHeatmap" class="px-4 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-sm font-semibold transition-colors">
                        Capa
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

const props = defineProps({
    coordenadas: Array,
    googleApiKey: String
})

const mapContainer = ref(null)
const cargando = ref(true)

// Variable reactiva para el contador dinámico (Inicia con el total)
const puntosVisibles = ref(props.coordenadas.length)

let map = null
let heatmap = null
let heatMapData = [] // Lo sacamos aquí para que el evento del mapa pueda leerlo

const initMap = () => {
    //Centro por defecto: Ciudad Victoria, Tamaulipas (Plaza Juárez)
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
        // Estilos opcionales (puedes borrarlos si quieres el mapa de colores estándar)

    })

    // Transformamos los datos
    heatMapData = props.coordenadas.map(coord => {
        return new window.google.maps.LatLng(
            parseFloat(coord.latitud),
            parseFloat(coord.longitud)
        )
    })

    // Capa del Mapa de Calor
    heatmap = new window.google.maps.visualization.HeatmapLayer({
        data: heatMapData,
        map: map,
        radius: 25,
        opacity: 0.8
    })

    // Centrado automático inicial
    if (props.coordenadas.length > 1) {
        const bounds = new window.google.maps.LatLngBounds()
        heatMapData.forEach(point => bounds.extend(point))
        map.fitBounds(bounds)
    }

    // --- MAGIA: EL ESCÁNER DINÁMICO ---
    // Usamos el evento 'idle' (cuando el mapa deja de moverse) para no saturar el navegador
    map.addListener('idle', () => {
        const bounds = map.getBounds()
        if (!bounds) return

        let count = 0
        // Escaneamos cuántos puntos caen dentro de la pantalla actual
        heatMapData.forEach(point => {
            if (bounds.contains(point)) {
                count++
            }
        })

        puntosVisibles.value = count
    })
    // ----------------------------------

    cargando.value = false
}

onMounted(() => {
    if (window.google && window.google.maps) {
        initMap()
        return
    }

    window.initGoogleMap = initMap
    const script = document.createElement('script')
    script.src = `https://maps.googleapis.com/maps/api/js?key=${props.googleApiKey}&libraries=visualization&callback=initGoogleMap`
    script.async = true
    script.defer = true
    document.head.appendChild(script)
})

onUnmounted(() => {
    delete window.initGoogleMap
})

const toggleHeatmap = () => {
    if (heatmap) {
        heatmap.setMap(heatmap.getMap() ? null : map)
    }
}

const changeRadius = () => {
    if (heatmap) {
        const currentRadius = heatmap.get('radius') || 25
        heatmap.set('radius', currentRadius === 25 ? 50 : 25)
    }
}
</script>
