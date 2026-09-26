/**
 * Contenido HTML del InfoWindow de una sección electoral.
 * Google Maps InfoWindow recibe HTML plano, por eso se armo como texto.
 */
import { formatoNumero, formatoPorcentaje, obtenerCobertura } from './coberturaEscala.js'

const escapar = (valor) =>
    String(valor ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')

const fila = (etiqueta, valor) => `
    <div class="flex justify-between gap-4"><span>${etiqueta}</span><strong class="text-gray-800 tabular-nums">${valor}</strong></div>`

/**
 * @param {Object} p  properties del Feature (contrato BE-3)
 * @param {Object|null} corteActivo  summary.corte_activo
 */
export const construirInfoWindowSeccion = (p, corteActivo = null) => {
    const seccion = escapar(p.seccion)
    const tipo = p.tipo === 1 ? 'Urbana' : 'Rural'
    const listaNominal = p.total_lista_nominal ?? null
    const simpatizantes = Number(p.total_simpatizantes || 0)
    const apoyos = Number(p.total_apoyos || 0)
    const cobertura = obtenerCobertura(p)
    const relativo = p.porcentaje_relativo_municipio ?? p.porcentaje ?? null

    let bloqueCobertura
    if (listaNominal && cobertura !== null) {
        const ancho = Math.min(Math.max(cobertura, 0), 100)
        bloqueCobertura = `
            <div class="mt-3 pt-3 border-t border-gray-200">
                <div class="flex items-baseline justify-between mb-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500">Cobertura electoral</span>
                    <span class="text-lg font-black text-blue-800 tabular-nums">${escapar(formatoPorcentaje(cobertura))}</span>
                </div>
                <div class="h-2.5 w-full rounded-full bg-gray-100 overflow-hidden" role="progressbar"
                     aria-valuemin="0" aria-valuemax="100" aria-valuenow="${ancho}">
                    <div class="h-full rounded-full bg-blue-700" style="width: ${ancho}%"></div>
                </div>
                <div class="mt-1 text-[11px] text-gray-600 tabular-nums">
                    <strong class="text-gray-800">${escapar(formatoNumero(simpatizantes))}</strong>
                    de ${escapar(formatoNumero(listaNominal))} electores en Lista Nominal
                </div>
            </div>`
    } else {
        bloqueCobertura = `
            <div class="mt-3 pt-3 border-t border-gray-200">
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Cobertura electoral</div>
                <div class="text-[11px] font-semibold text-amber-700 bg-amber-50 border border-amber-100 rounded px-2 py-1">
                    Sin Lista Nominal en el corte activo
                </div>
            </div>`
    }

    const pieCorte = corteActivo?.fecha_corte
        ? `<div class="mt-2 text-[10px] text-gray-400">Corte LN: ${escapar(String(corteActivo.fecha_corte).slice(0, 10))}</div>`
        : ''

    return `
        <div class="p-3 font-sans text-slate-800" style="min-width: 240px; max-width: 280px;">
            <div class="flex items-center justify-between pb-2 mb-2 border-b border-gray-200">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Sección Electoral</span>
                <span class="text-base font-extrabold text-gray-900">${seccion}</span>
            </div>
            <div class="space-y-1 text-xs text-gray-600">
                ${fila('Distrito Federal:', escapar(p.distrito_federal ?? '—'))}
                ${fila('Distrito Local:', escapar(p.distrito_local ?? '—'))}
                ${fila('Tipo:', tipo)}
            </div>

            <div class="space-y-1 text-xs text-gray-600 mt-3 pt-3 border-t border-gray-200">
                ${fila('Electores en Lista Nominal:', escapar(formatoNumero(listaNominal)))}
                ${fila('Simpatizantes (ubicados por GPS):', escapar(formatoNumero(simpatizantes)))}
                ${fila('Apoyos:', escapar(formatoNumero(apoyos)))}
                ${relativo !== null ? fila('Del total municipal:', escapar(formatoPorcentaje(relativo, 2))) : ''}
            </div>
            ${bloqueCobertura}
            ${pieCorte}
        </div>`
}
