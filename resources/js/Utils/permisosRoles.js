/**
 * Permisos Spatie del módulo de Roles de Sistema.
 *
 * Evalúa:
 * - Si el usuario tiene el rol 'Administrador' o el meta-permiso 'roles.gestionar', tiene bypass total.
 * - En caso contrario, evalúa los permisos granulares: 'roles.ver', 'roles.crear', 'roles.editar', 'roles.eliminar'.
 */
export const ACCIONES_ROLES = ['ver', 'crear', 'editar', 'eliminar'];

const extraerNombres = (lista) => {
    if (!Array.isArray(lista)) return [];
    return lista.map((item) => (typeof item === 'string' ? item : item?.name)).filter(Boolean);
};

/**
 * @param {Object} auth $page.props.auth
 * @returns {{ver:boolean, crear:boolean, editar:boolean, eliminar:boolean, gestionar:boolean}}
 */
export const permisosRoles = (auth) => {
    const permisos = extraerNombres(auth?.permissions ?? auth?.user?.permissions);
    const roles = extraerNombres(auth?.roles ?? auth?.user?.roles);
    const rolDirecto = auth?.user?.role;

    const esAdmin = roles.includes('Administrador') || rolDirecto === 'Administrador';
    const puedeGestionar = esAdmin || permisos.includes('roles.gestionar');

    if (puedeGestionar) {
        return {
            ver: true,
            crear: true,
            editar: true,
            eliminar: true,
            gestionar: true,
        };
    }

    return {
        ver: permisos.includes('roles.ver'),
        crear: permisos.includes('roles.crear'),
        editar: permisos.includes('roles.editar'),
        eliminar: permisos.includes('roles.eliminar'),
        gestionar: false,
    };
};