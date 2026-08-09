<style>
/* Evitar flash del <select> nativo antes de que Select2 lo sustituya */
#collapse_filtros_reportes_semanales:not(.reportes-semanales-filtros-ready) select.select2-custom {
    opacity: 0;
}
</style>

<!-- CSS CUSTOM reportes_semanales - unique  -->
<style>
.datatables-reportes-semanales thead tr:first-child th[colspan] .dt-column-title {
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
	font-size: 0.75rem !important;
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
  overflow: hidden !important;
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
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden !important;
  display: flex;
  flex-direction: column;
}

.card-action.card-fullscreen .card-datatable.table-responsive {
  overflow: hidden !important;
}

.card-action.card-fullscreen .card-datatable.table-responsive.dt-card-visible {
  height: inherit;
}

.card-action.card-fullscreen .card-datatable .dt-container {
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden !important;
  display: flex;
  flex-direction: column;
}

.card-action.card-fullscreen .card-datatable .dt-layout-row:first-child {
  flex: 0 0 auto;
}

.card-action.card-fullscreen .card-datatable .dt-layout-row:has(.dt-layout-table) {
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden !important;
  display: flex;
  flex-direction: column;
}

.card-action.card-fullscreen .card-datatable .dt-layout-table {
  flex: 1 1 auto;
  min-height: 0;
  overflow: auto !important;
}

.card-action.card-fullscreen .card-datatable .dt-layout-row:last-child:not(:has(.dt-layout-table)) {
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

.datatables-reportes-semanales tr.dtrg-group.dtrg-start td {
  border: 0;
  padding: 0.35rem 0.75rem;
}

.datatables-reportes-semanales .reportes-semanales-empresa-group-label {
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

.datatables-reportes-semanales thead tr:first-child th[colspan] {
  border-bottom: 0;
  font-size: 12px !important;
}

div.dt-container table.datatables-reportes-semanales {
  border-collapse: separate !important;
  border-spacing: 0 !important;
}

div.dt-container table.datatables-reportes-semanales thead th.rs-grupo {
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
div.dt-container table.datatables-reportes-semanales thead th.rs-grupo-full {
  border-radius: 10px !important;
}

div.dt-container table.datatables-reportes-semanales thead th.rs-grupo-top.rs-grupo-start {
  border-top-left-radius: 10px !important;
}

div.dt-container table.datatables-reportes-semanales thead th.rs-grupo-top.rs-grupo-end {
  border-top-right-radius: 10px !important;
}

div.dt-container table.datatables-reportes-semanales thead th.rs-grupo-bottom.rs-grupo-start {
  border-bottom-left-radius: 10px !important;
}

div.dt-container table.datatables-reportes-semanales thead th.rs-grupo-bottom.rs-grupo-end {
  border-bottom-right-radius: 10px !important;
}
*/

div.dt-container table.datatables-reportes-semanales thead th.reportes-semanales-th-beneficio-oro-plata,
div.dt-container table.datatables-reportes-semanales thead th.reportes-semanales-th-beneficio-oro-plata .dt-column-title {
  max-width: 79px !important;
}

div.dt-container table.datatables-reportes-semanales thead th.reportes-semanales-th-utilidad {
  padding: 0.3rem 0.9rem !important;
}

div.dt-container table.datatables-reportes-semanales thead th.reportes-semanales-th-utilidad .dt-column-title {
  font-size: 12px !important;
  letter-spacing: -0.5px !important;
  max-width: 134px !important;
}

.datatables-reportes-semanales thead tr:last-child th {
  font-size: 0.5rem !important;
  font-weight: 500 !important;
  padding-top: 0 !important;
  padding-bottom: 0.35rem !important;
}

.datatables-reportes-semanales thead tr:last-child th.border-0 {
  border: 0 !important;
}

.datatables-reportes-semanales th.reportes-semanales-col-oculta,
.datatables-reportes-semanales td.reportes-semanales-col-oculta {
  display: none !important;
  width: 0 !important;
  max-width: 0 !important;
  padding: 0 !important;
  border: 0 !important;
  overflow: hidden;
}

#modalEditarInformeSemanal .modal-draggable-handle {
  cursor: move;
  user-select: none;
}

#modalEditarInformeSemanal .modal-draggable-handle.is-dragging {
  cursor: grabbing;
}

#modalEditarInformeSemanal .modal-draggable-handle .btn-close {
  cursor: pointer;
}

#modalEditarInformeSemanal .modal-dialog.modal-dialog-draggable {
  margin: 0;
}

</style>
