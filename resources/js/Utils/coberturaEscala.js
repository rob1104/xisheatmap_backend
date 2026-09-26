/**
 * Escala visual continua de Cobertura Electoral.
 *
 * Mientras el cliente no defina metas/umbrales, la cobertura
 * se pinta con un gradiente continuo de un solo tono
 * Claro = poca cobertura
 * Oscuro = mucha. 
 * 
 */

// Rampa secuencial azul (claro -> oscuro), validada para daltonismo.
export const RAMPA_COBERTURA = [
    '#cde2fb', '#b7d3f6', '#9ec5f4', '#86b6ef', '#6da7ec', '#5598e7', '#3987e5',
    '#2a78d6', '#256abf', '#1c5cab', '#184f95', '#104281', '#0d366b',
]

// Estilo para secciones sin Lista Nominal en el corte activo.
export const COLOR_SIN_DATO = '#94a3b8'

const hexARgb = (hex) => {
    const n = parseInt(hex.slice(1), 16)
    return [(n >> 16) & 255, (n >> 8) & 255, n & 255]
}

const rgbAHex = (rgb) =>
    '#' + rgb.map((c) => Math.round(c).toString(16).padStart(2, '0')).join('')

/**
 * Obtiene el porcentaje de cobertura de una sección.
 * Devuelve null cuando la sección no tiene datos.
 */
export const obtenerCobertura = (props) => {
    const pct = props?.porcentaje_cobertura
    if (pct === null || pct === undefined || pct === '') return null
    const numero = Number(pct)
    return Number.isFinite(numero) ? numero : null
}

/**
 * Tope
 */
export const calcularTopeEscala = (valores) => {
    const validos = valores.filter((v) => v !== null && Number.isFinite(v))
    if (validos.length === 0) return 10
    const maximo = Math.min(Math.max(...validos), 100)
    return Math.min(Math.max(Math.ceil(maximo / 5) * 5, 5), 100)
}

/**
 * Color de relleno para un porcentaje dado, interpolado a lo largo de la subida.
 * Valores por encima del tope (o de 100 %) se muestran con el tono más oscuro.
 */
export const colorPorCobertura = (porcentaje, tope) => {
    const t = Math.min(Math.max(porcentaje / (tope || 1), 0), 1)
    const posicion = t * (RAMPA_COBERTURA.length - 1)
    const i = Math.floor(posicion)
    if (i >= RAMPA_COBERTURA.length - 1) return RAMPA_COBERTURA[RAMPA_COBERTURA.length - 1]

    const a = hexARgb(RAMPA_COBERTURA[i])
    const b = hexARgb(RAMPA_COBERTURA[i + 1])
    const f = posicion - i
    return rgbAHex(a.map((c, k) => c + (b[k] - c) * f))
}

/**
 * Degradado CSS para la leyenda.
 */
export const degradadoCss = () =>
    `linear-gradient(to right, ${RAMPA_COBERTURA.join(', ')})`

export const formatoNumero = (n) =>
    n === null || n === undefined ? '—' : Number(n).toLocaleString('es-MX')

export const formatoPorcentaje = (n, decimales = 1) =>
    n === null || n === undefined
        ? '—'
        : `${Number(n).toLocaleString('es-MX', {
              minimumFractionDigits: decimales,
              maximumFractionDigits: decimales,
          })}\u00a0%`
