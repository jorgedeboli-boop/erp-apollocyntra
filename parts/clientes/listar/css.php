<!-- CSS CUSTOM CLIENTES LISTAR -->
<style>

/* Evitar flash del <select> nativo antes de que Select2 lo sustituya */
#collapse_filtros_clientes:not(.clientes-filtros-ready) select.select2-custom {
    opacity: 0;
}

#collapse_filtros_clientes:not(.clientes-filtros-ready) .user_tipo_identificacion,
#collapse_filtros_clientes:not(.clientes-filtros-ready) .user_provincia,
#collapse_filtros_clientes:not(.clientes-filtros-ready) .user_sucursal,
#collapse_filtros_clientes:not(.clientes-filtros-ready) .user_estado {
    min-height: 38px;
}

.datatables-users {
    font-size: 0.8125rem !important; /* Un punto menos que el estándar */
}

.datatables-users thead th {
    padding: 0.625rem 0.5rem !important; /* Reducir padding en headers */
    font-size: 0.75rem !important;
    font-weight: 600 !important;
}

.datatables-users tbody td {
    padding: 0.5rem 0.5rem !important; /* Reducir padding en celdas */
    vertical-align: middle !important;
}

/* Estilos para los badges de estado */
.badge.bg-label-success {
    background-color: rgba(40, 199, 111, 0.16) !important;
    color: #28c76f !important;
}

.badge.bg-label-danger {
    background-color: rgba(234, 84, 85, 0.16) !important;
    color: #ea5455 !important;
}

/* NOTA: Los estilos de Select2 y botones de exportación están ahora en parts/universal/custom.css */

/* Estilos para los botones de acción */
.btn-icon {
    width: 2.5rem;
    height: 2.5rem;
}

/* Estilos para el dropdown de acciones */
.dropdown-menu {
    border: 0;
    box-shadow: 0 0.25rem 1.125rem rgba(75, 85, 99, 0.1);
    border-radius: 0.5rem;
}

.dropdown-item {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

/* Estilos para las tarjetas de estadísticas */
.card {
    border: 0;
    box-shadow: 0 0.25rem 1.125rem rgba(75, 85, 99, 0.1);
    border-radius: 0.5rem;
}

.card-body {
    padding: 1.5rem;
}

/* Estilos para los iconos de las tarjetas */
.avatar-initial {
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
}

/* Estilos para el loading de estadísticas */
.stats-loading {
    margin-left: 0.5rem;
}

/* Hacer las filas de la tabla clickeables */
.datatables-users tbody tr {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.datatables-users tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.05) !important;
}

</style>