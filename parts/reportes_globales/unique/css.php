<style>
/* Evitar flash del <select> nativo antes de que Select2 lo sustituya */
#collapse_filtros_reportes_globales:not(.reportes-globales-filtros-ready) select.select2-custom {
    opacity: 0;
}
.flatpickr-calendar.open {
	z-index: 10749;
}
.swal2-container {
	z-index: 10919999 !important;
}
/* Selector mostrar/ocultar columnas (toolbar entre Exportar y Buscar) */
.card-datatable .row:has(> .dt-reportes-globales-columnas-cell),
.dt-container .row:has(> .dt-reportes-globales-columnas-cell),
.dt-reportes-globales-toolbar-row {
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

.card-datatable .row:has(> .dt-reportes-globales-columnas-cell) > .dt-layout-start,
.card-datatable .row:has(> .dt-reportes-globales-columnas-cell) > .dt-layout-end,
.dt-reportes-globales-toolbar-row > .dt-layout-start,
.dt-reportes-globales-toolbar-row > .dt-layout-end {
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

.card-datatable .row:has(> .dt-reportes-globales-columnas-cell) > .dt-layout-end .dt-search,
.dt-reportes-globales-toolbar-row > .dt-layout-end .dt-search {
    width: 100%;
    max-width: 280px;
    min-width: 0;
}

.card-datatable .row:has(> .dt-reportes-globales-columnas-cell) > .dt-layout-end .dt-search input,
.dt-reportes-globales-toolbar-row > .dt-layout-end .dt-search input {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    box-sizing: border-box !important;
}

.dt-reportes-globales-columnas-cell {
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

.dt-reportes-globales-columnas-dropdown {
    display: inline-flex;
}

.dt-reportes-globales-columnas-dropdown .button-exportar {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
}

.dt-buttons .button-imprimir-reportes-globales {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
}

.reportes-globales-columnas-menu {
    min-width: 260px;
    max-height: 340px;
    overflow-y: auto;
}

.reportes-globales-columnas-toggle .form-check-label {
    cursor: pointer;
    font-size: 0.875rem;
}

@media (max-width: 767px) {
    .card-datatable .row:has(> .dt-reportes-globales-columnas-cell),
    .dt-reportes-globales-toolbar-row {
        flex-wrap: wrap !important;
        min-height: 0 !important;
    }

    .dt-reportes-globales-columnas-cell {
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

    .dt-reportes-globales-columnas-dropdown,
    .dt-reportes-globales-columnas-dropdown .button-exportar {
        width: 100%;
    }
}
</style>

<!-- CSS CUSTOM reportes_globales - unique  -->
<style>
.datatables-reportes-globales thead tr:first-child th[colspan] .dt-column-title {
	font-size: 12px !important;
	letter-spacing: -0.5px;
}
div.dt-container table.dataTable thead th {
	padding:0.0rem 0.2rem 0.1rem !important;
	font-size: 0.5rem !important;
	font-weight: 600 !important;
  
}
div.dt-container table.dataTable thead {
	height: 50px;
}

.card-datatable .table {
	font-size: 0.68rem !important;
}
.totales-porcentajes {
  width: 64px;
  text-align: center;
  padding: 6px 5px !important;
  letter-spacing: -0.5px !important;
  font-size: 14px !important;
	display: block;
	border-radius: 5px;
  margin: 0 auto;
  background-color: rgba(40, 199, 111, 0.16) !important;
  color: #28c76f !important;
  font-weight: 600;
}
.totales-euros {
	font-size: 14px;
	padding: 6px 9px;
	display: block;
	width: 83px !important;
	text-align: right !important;
	letter-spacing: -0.5px;
	border-radius: 5px;
	margin: 0 auto;
	background-color: rgba(40, 199, 111, 0.16) !important;
	color: #28c76f !important;
	font-weight: 600;
}
.nototals {
  background-color: #e8e9ed !important;
  color: #47474729 !important;
}
.totales .card-datatable td:has(> .totales-porcentajes),
.totales .card-datatable td:has(> .totales-euros) {
  padding: 0 !important;
  text-align: center !important;
  vertical-align: middle !important;
}

.card-action.card-fullscreen {
  display: flex !important;
  flex-direction: column;
  overflow: auto !important;
  padding: 0px;
  background: var(--bs-body-bg, #fff);
}

.card-action.card-fullscreen .card-header {
  padding-top: 12px;
  padding-bottom: 12px;
}
.card-action.card-fullscreen .card-header,
.card-action.card-fullscreen .card-body {
  flex: 0 0 auto;
  background: var(--bs-body-bg, #fff);
}

.card-action.card-fullscreen .card-datatable {
  flex: 0 0 auto;
  min-height: 0;
  overflow: visible !important;
  display: block;
  height: auto !important;
}

.card-action.card-fullscreen .card-datatable.table-responsive {
  overflow: visible !important;
}

.card-action.card-fullscreen .card-datatable.table-responsive.dt-card-visible {
  height: auto !important;
}

.card-action.card-fullscreen .card-datatable .dt-container {
  flex: none;
  min-height: 0;
  overflow: visible !important;
  display: block;
  height: auto !important;
}

/* Toolbar / cabecera del datatable: sin cambios de comportamiento */
.card-action.card-fullscreen .card-datatable .dt-layout-row:first-child {
  flex: 0 0 auto;
}

.card-action.card-fullscreen .card-datatable .dt-layout-row:has(.dt-layout-table) {
  flex: none;
  min-height: 0;
  overflow: visible !important;
  display: block;
  height: auto !important;
}

.card-action.card-fullscreen .card-datatable .dt-layout-table {
  flex: none;
  min-height: 0;
  overflow: visible !important;
  height: auto !important;
}

/* Footer del datatable: flujo normal, sin fixed bottom */
.card-action.card-fullscreen .card-datatable .dt-layout-row:last-child:not(:has(.dt-layout-table)) {
  flex: 0 0 auto;
  position: static !important;
  bottom: auto !important;
}

.card-action.card-fullscreen #totales_reportes_globales {
  flex: 0 0 auto;
}

.card-action.card-fullscreen .swal2-container {
  position: absolute !important;
  z-index: 2000 !important;
}

.card-action.card-fullscreen .modal {
  z-index: 2000;
}

.card-action.card-fullscreen .modal-backdrop {
  z-index: 1990;
}

.datatables-reportes-globales tr.dtrg-group.dtrg-start td {
  border: 0;
  padding: 0.35rem 0.75rem;
}

.datatables-reportes-globales .reportes-globales-empresa-group-label {
  color: #007bff !important;
  font-size: 15px;
  line-height: 12px;
  padding: 0 !important;
  margin: 2px 0 0 0 !important;
  text-align: left !important;
  vertical-align: middle !important;
  align-items: center !important;
  display: inline-flex;
  font-weight: 500 !important;
}

.datatables-reportes-globales thead tr:first-child th[colspan] {
  border-bottom: 0;
  font-size: 12px !important;
}

div.dt-container table.datatables-reportes-globales {
  border-collapse: separate !important;
  border-spacing: 0 !important;
}

div.dt-container table.datatables-reportes-globales thead th.rs-grupo {
  overflow: hidden;
}
.rs-grupo-top{
  text-align: center !important;
}
.rs-grupo-end{
  text-align: right !important;
}
.rs-grupo-start{
  text-align: left !important;
}
.rs-grupo-bottom{
  text-align: center !important;
}
.rs-grupo-full{
  text-align: center !important;
}
/*
div.dt-container table.datatables-reportes-globales thead th.rs-grupo-full {
  border-radius: 10px !important;
}

div.dt-container table.datatables-reportes-globales thead th.rs-grupo-top.rs-grupo-start {
  border-top-left-radius: 10px !important;
}

div.dt-container table.datatables-reportes-globales thead th.rs-grupo-top.rs-grupo-end {
  border-top-right-radius: 10px !important;
}

div.dt-container table.datatables-reportes-globales thead th.rs-grupo-bottom.rs-grupo-start {
  border-bottom-left-radius: 10px !important;
}

div.dt-container table.datatables-reportes-globales thead th.rs-grupo-bottom.rs-grupo-end {
  border-bottom-right-radius: 10px !important;
}
*/

div.dt-container table.datatables-reportes-globales thead th.reportes-globales-th-beneficio-oro-plata,
div.dt-container table.datatables-reportes-globales thead th.reportes-globales-th-beneficio-oro-plata .dt-column-title {
  max-width: 79px !important;
}

div.dt-container table.datatables-reportes-globales thead th.reportes-globales-th-utilidad {
  padding: 0.3rem 0.9rem !important;
}

div.dt-container table.datatables-reportes-globales thead th.reportes-globales-th-utilidad .dt-column-title {
  font-size: 12px !important;
  letter-spacing: -0.5px !important;
  max-width: 134px !important;
}

.datatables-reportes-globales thead tr:last-child th {
  font-size: 0.5rem !important;
  font-weight: 500 !important;
  padding-top: 0 !important;
  padding-bottom: 0.35rem !important;
}

.datatables-reportes-globales thead tr:last-child th.border-0 {
  border: 0 !important;
}

.datatables-reportes-globales th.reportes-globales-col-oculta,
.datatables-reportes-globales td.reportes-globales-col-oculta {
  display: none !important;
  width: 0 !important;
  max-width: 0 !important;
  padding: 0 !important;
  border: 0 !important;
  overflow: hidden;
}

#modalEditarInformeGlobal .modal-draggable-handle {
  cursor: move;
  user-select: none;
}

#modalEditarInformeGlobal .modal-draggable-handle.is-dragging {
  cursor: grabbing;
}

#modalEditarInformeGlobal .modal-draggable-handle .btn-close {
  cursor: pointer;
}

#modalEditarInformeGlobal .modal-dialog.modal-dialog-draggable {
  margin: 0;
}

.rg-lotes-descuadrados-lista {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  max-height: 140px;
  overflow-y: auto;
}

.rg-lote-descuadrado-item {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  width: 100%;
  text-align: left;
  border: 1px solid rgba(255, 171, 0, 0.35);
  background: rgba(255, 171, 0, 0.08);
  border-radius: 0.375rem;
  padding: 0.3rem 0.45rem;
  cursor: pointer;
  transition: background-color 0.15s ease, border-color 0.15s ease;
}

.rg-lote-descuadrado-item:hover {
  background: rgba(255, 171, 0, 0.16);
  border-color: rgba(255, 171, 0, 0.55);
}

.rg-lote-descuadrado-titulo {
  font-weight: 600;
  font-size: 0.8125rem;
  color: inherit;
  line-height: 1.2;
}

.rg-lote-descuadrado-meta {
  font-size: 0.7rem;
  color: var(--bs-secondary-color, #6c757d);
  line-height: 1.2;
}

</style>
