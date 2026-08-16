<!-- CSS CUSTOM ARTICULOS LISTAR -->
<style>
/* Evitar flash del <select> nativo antes de que Select2 lo sustituya */
#articulos_filtros_container:not(.articulos-filtros-ready) select.select2-custom {
    opacity: 0;
}

#articulos_filtros_container:not(.articulos-filtros-ready) .articulo_origen,
#articulos_filtros_container:not(.articulos-filtros-ready) .articulo_auditado {
    min-height: 38px;
}

/* Selector mostrar/ocultar columnas (toolbar entre Exportar y Buscar) */
.card-datatable .row:has(> .dt-articulos-columnas-cell),
.dt-container .row:has(> .dt-articulos-columnas-cell),
.dt-articulos-toolbar-row {
    position: relative !important;
    display: flex !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
    justify-content: space-between !important;
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    /* Padding interno (sustituye m-2 del row) para no desbordar ni pegarse al borde */
    padding-left: 1rem !important;
    padding-right: 1rem !important;
    --bs-gutter-x: 0;
    gap: 0.75rem;
}

.card-datatable .row:has(> .dt-articulos-columnas-cell) > .dt-layout-start,
.card-datatable .row:has(> .dt-articulos-columnas-cell) > .dt-layout-end,
.dt-articulos-toolbar-row > .dt-layout-start,
.dt-articulos-toolbar-row > .dt-layout-end {
    position: relative;
    z-index: 2;
    flex: 0 1 auto !important;
    width: auto !important;
    min-width: 0 !important;
    max-width: calc(50% - 4.5rem) !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
}

.card-datatable .row:has(> .dt-articulos-columnas-cell) > .dt-layout-end .dt-search,
.dt-articulos-toolbar-row > .dt-layout-end .dt-search {
    width: 100%;
    max-width: 280px;
    min-width: 0;
}

.card-datatable .row:has(> .dt-articulos-columnas-cell) > .dt-layout-end .dt-search input,
.dt-articulos-toolbar-row > .dt-layout-end .dt-search input {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    box-sizing: border-box !important;
}

.dt-articulos-columnas-cell {
    position: absolute !important;
    left: 50% !important;
    top: 50% !important;
    transform: translate(-50%, -50%) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: auto !important;
    max-width: none !important;
    flex: 0 0 auto !important;
    margin: 0 !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    z-index: 1;
    pointer-events: auto;
}

.dt-articulos-columnas-dropdown {
    display: inline-flex;
}

.dt-articulos-columnas-dropdown .button-exportar {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
}

.articulos-columnas-menu {
    min-width: 220px;
    max-height: 340px;
    overflow-y: auto;
}

.articulos-columnas-toggle .form-check-label {
    cursor: pointer;
    font-size: 0.875rem;
}

@media (max-width: 767px) {
    .card-datatable .row:has(> .dt-articulos-columnas-cell),
    .dt-articulos-toolbar-row {
        flex-wrap: wrap !important;
        min-height: 0 !important;
    }

    .dt-articulos-columnas-cell {
        position: static !important;
        left: auto !important;
        top: auto !important;
        transform: none !important;
        flex: 0 0 100% !important;
        width: 100% !important;
        max-width: 100% !important;
        order: 2;
        margin-top: 0.25rem !important;
        margin-bottom: 0.25rem !important;
    }

    .dt-articulos-columnas-dropdown,
    .dt-articulos-columnas-dropdown .button-exportar {
        width: 100%;
    }
}

/* Padding para la tabla dentro de la card */
.card-datatable {
    padding-top: 1rem;
}

/* Tabla más compacta */
.datatables-articulos-venta {
    font-size: 0.875rem;
}

.datatables-articulos-venta thead th {
    padding: 0.625rem 0.5rem !important; /* Reducir padding en headers */
    font-size: 0.75rem;
    font-weight: 600;
}

.datatables-articulos-venta tbody td {
    padding: 0.5rem 0.5rem;
    font-size: 0.8125rem;
}

/* Cursor pointer en filas */
.datatables-articulos-venta tbody tr {
    cursor: pointer;
}

.datatables-articulos-venta tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Badges y iconos más compactos 
.datatables-articulos-venta .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}*/

.datatables-articulos-venta .icon-base {
    font-size: 1rem;
}
@media (max-width: 767px) {
    .articulo_origen {
        width: 100% !important;
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
}
</style>
