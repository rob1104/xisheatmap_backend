/**
 * Permisos Spatie del módulo lista nominal (AUTH-1).
 *
 * Se leen únicamente de las props de autenticación de Inertia:
 *   $page.props.auth.permissions       = ['lista-nominal.ver', 'lista-nominal.crear']
 *   $page.props.auth.user.permissions  = (misma forma, alternativa)
 * Cada elemento puede ser el nombre del permiso o un objeto { name }.
 *
 * Política "default deny": si no llegan permisos, no se concede ninguno.
 */
import { mockActivo } from './listaNominalMock.js'

export const ACCIONES_LISTA_NOMINAL = ['ver', 'crear', 'activar', 'editar', 'eliminar']

const nombresDePermisos = (auth) => {
    const lista = auth?.permissions ?? auth?.user?.permissions
    if (!Array.isArray(lista)) return []
    return lista.map((p) => (typeof p === 'string' ? p : p?.name)).filter(Boolean)
}

/**
 * @param {Object} auth  $page.props.auth
 * @returns {{ver:boolean, crear:boolean, activar:boolean, editar:boolean, eliminar:boolean}}
 */
export const permisosListaNominal = (auth) => {
    const nombres = nombresDePermisos(auth)

    // Solo en desarrollo con datos de ejemplo (VITE_LISTA_NOMINAL_MOCK=true) y mientras
    // AUTH1 no comparta permisos se habilitan todas las acciones para poder probar la UI.
    if (nombres.length === 0 && mockActivo()) {
        return Object.fromEntries(ACCIONES_LISTA_NOMINAL.map((a) => [a, true]))
    }

    return Object.fromEntries(
        ACCIONES_LISTA_NOMINAL.map((a) => [a, nombres.includes(`lista-nominal.${a}`)])
    )
}
