<!-- CSS específico para la lista de usuarios -->
<style>
/* Estilos específicos del módulo usuarios */
/* Los estilos universales están ahora en parts/universal/custom.css */

/* Estilos específicos para la tabla de usuarios */
.datatables-users .avatar-initial {
    width: 40px;
    height: 40px;
    font-size: 16px;
    font-weight: 600;
}

.datatables-users {
    font-size: 0.8125rem;
}

.datatables-users thead th {
    padding: 0.625rem 0.5rem !important;
    font-size: 0.75rem;
    font-weight: 600;
}

.datatables-users tbody td {
    padding: 0.5rem 0.5rem !important;
    vertical-align: middle;
}

.datatables-users tbody tr {
    cursor: pointer;
}

.datatables-users tbody tr:hover {
    background-color: rgba(105, 108, 255, 0.08);
}

.card-datatable .datatables-users .badge {
	font-size: 12.5px;
	padding: 7px 9px;
	display: inline-block;
	align-items: center;
	max-width: 100%;
	text-align: center;
	border-radius: 5px;
	text-transform: capitalize;
	box-shadow: 0 1px 1px rgba(0, 0, 0, 0.3) inset, 0 1px 0 rgba(255, 255, 255, 0.2);
	width: 180px;
	text-align: center;
}

.badge.bg-label-primary {
    background-color: rgba(105, 108, 255, 0.16) !important;
    color: #696cff !important;
}

.badge.bg-label-secondary {
    background-color: rgba(133, 146, 163, 0.16) !important;
    color: #8592a3 !important;
}

.badge.bg-label-success {
    background-color: rgba(40, 199, 111, 0.16) !important;
    color: #28c76f !important;
}

.badge.bg-label-danger {
    background-color: rgba(234, 84, 85, 0.16) !important;
    color: #ea5455 !important;
}

.badge.bg-label-warning {
    background-color: rgba(255, 159, 67, 0.16) !important;
    color: #ff9f43 !important;
}

.badge.bg-label-info {
    background-color: rgba(0, 207, 232, 0.16) !important;
    color: #00cfe8 !important;
}

/* Estilos específicos para las tarjetas de estadísticas de usuarios */
.card .avatar-initial {
    width: 48px;
    height: 48px;
    font-size: 20px;
}

/* Estilos específicos para el header de filtros de usuarios */
.card-header-forms .row {
    align-items: center;
    gap: 1rem;
}

.card-header-forms .col-md-4 {
    display: flex;
    align-items: center;
}

/* Responsive adjustments específicas para usuarios */
@media (max-width: 768px) {
    .card-header-forms .row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .card-header-forms .col-md-4 {
        margin-bottom: 1rem;
        width: 100%;
    }
}

@media (max-width: 576px) {
    .card-header-forms .col-md-4 {
        margin-bottom: 0.75rem;
    }
}
</style>
