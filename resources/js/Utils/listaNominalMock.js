/**
 * DATOS DE EJEMPLO de Lista Nominal.
 *
 * Permite solo construir y probar
 *
 * Se activa SOLO si el .env tiene:  VITE_LISTA_NOMINAL_MOCK=true
 * Cuando este terminado borrar (false)
 * 
 */

export const mockActivo = () => import.meta.env.VITE_LISTA_NOMINAL_MOCK === 'true'

// Número pseudoaleatorio estable por sección (siempre da lo mismo para la misma sección).
const aleatorioEstable = (semilla, salt) => {
    let h = 2166136261 ^ salt
    const texto = String(semilla)
    for (let i = 0; i < texto.length; i++) {
        h ^= texto.charCodeAt(i)
        h = Math.imul(h, 16777619)
    }
    return ((h >>> 0) % 10000) / 10000
}

export const CORTE_ACTIVO_MOCK = {
    id: 3,
    fecha_corte: '2024-03-31',
    fuente: 'INE - Dirección Ejecutiva del Registro Federal de Electores',
    descripcion: 'Corte definitivo Proceso Electoral 2024 (DATOS DE EJEMPLO)',
}

/**
 * Agrega a cada sección los campos nuevos del contrato de BE-3
 * (total_lista_nominal, padron_electoral, porcentaje_cobertura, ...) y
 * reconstruye el bloque summary.
 */
export const enriquecerGeoJsonConMock = (geojson) => {
    const features = (geojson.features || []).map((feature) => {
        const p = { ...feature.properties }
        const clave = p.seccion ?? p.id

        // ~4 % de las secciones sin Lista Nominal cargada (caso "sin dato")
        const sinDato = aleatorioEstable(clave, 7) < 0.04
        const listaNominal = sinDato ? null : 600 + Math.round(aleatorioEstable(clave, 1) * 1900)

        // ~8 % sin simpatizantes; el resto entre 0 % y 26 % de cobertura
        const cobertura = aleatorioEstable(clave, 3) < 0.08 ? 0 : aleatorioEstable(clave, 2) * 0.26
        const simpatizantes = listaNominal ? Math.round(listaNominal * cobertura) : Math.round(aleatorioEstable(clave, 4) * 40)

        p.total_lista_nominal = listaNominal
        p.padron_electoral = listaNominal ? listaNominal + Math.round(aleatorioEstable(clave, 5) * 70) : null
        p.total_simpatizantes = simpatizantes
        p.porcentaje_cobertura = listaNominal ? Math.round((simpatizantes / listaNominal) * 10000) / 100 : null

        return { ...feature, properties: p }
    })

    const totalLN = features.reduce((s, f) => s + (f.properties.total_lista_nominal || 0), 0)
    const totalPadron = features.reduce((s, f) => s + (f.properties.padron_electoral || 0), 0)
    const clasificados = features.reduce((s, f) => s + (f.properties.total_simpatizantes || 0), 0)
    const sinClasificacion = Math.round(clasificados * 0.09)
    const apoyos = features.reduce((s, f) => s + (f.properties.total_apoyos || 0), 0)

    features.forEach((f) => {
        f.properties.porcentaje_relativo_municipio = clasificados
            ? Math.round((f.properties.total_simpatizantes / clasificados) * 10000) / 100
            : 0
    })

    return {
        ...geojson,
        summary: {
            municipio: 41,
            corte_activo: CORTE_ACTIVO_MOCK,
            total_secciones: features.length,
            total_lista_nominal: totalLN,
            total_padron_electoral: totalPadron,
            simpatizantes: {
                clasificados_en_secciones: clasificados,
                sin_clasificacion_territorial: sinClasificacion,
                total_general: clasificados + sinClasificacion,
            },
            apoyos: { clasificados_en_secciones: apoyos, total_general: apoyos },
            cobertura_global_pct: totalLN ? Math.round((clasificados / totalLN) * 10000) / 100 : 0,
            _datos_de_ejemplo: true,
        },
        features,
    }
}

/**
 * Cortes de ejemplo para el módulo administrativo (FE-1).
 */
export const cortesMock = () => [
    {
        id: 3,
        fecha_corte: '2024-03-31',
        fuente: 'INE - Dirección Ejecutiva del Registro Federal de Electores',
        descripcion: 'Corte definitivo Proceso Electoral 2024',
        is_active: true,
        detalles_count: 168,
        total_lista_nominal: 245120,
        created_at: '2026-09-20T10:15:00',
    },
    {
        id: 2,
        fecha_corte: '2023-09-30',
        fuente: 'INE - Dirección Ejecutiva del Registro Federal de Electores',
        descripcion: 'Corte semestral',
        is_active: false,
        detalles_count: 166,
        total_lista_nominal: 239870,
        created_at: '2026-09-18T12:40:00',
    },
    {
        id: 1,
        fecha_corte: '2021-03-31',
        fuente: 'INE - Estadísticas de Lista Nominal y Padrón Electoral',
        descripcion: null,
        is_active: false,
        detalles_count: 165,
        total_lista_nominal: 228450,
        created_at: '2026-09-15T09:05:00',
    },
]

/**
 * Resumen de importación de ejemplo (respuesta esperada de BE-1/BE-2).
 */
export const resumenImportacionMock = (nombreArchivo) => ({
    archivo: nombreArchivo,
    total_procesadas: 170,
    insertadas: 168,
    actualizadas: 0,
    sin_poligono_geografico: ['9998', '9999'],
})
