<!-- CSS CUSTOM gastos - listar -->
<style>
/* Evitar flash del <select> nativo antes de que Select2 lo sustituya */
#collapse_filtros_gastos:not(.gastos-filtros-ready) select.select2-custom {
    opacity: 0;
}

/* Placeholder rango de fechas (flatpickr) */
#rangeFechas::placeholder {
  color: var(--bs-gray-400) !important;
}

.datatables-gastos tbody tr {
  cursor: pointer;
}

.datatables-gastos tbody tr:hover {
  background-color: rgba(105, 108, 255, 0.08);
}

.datatables-gastos {
  font-size: 0.8125rem;
}

.datatables-gastos thead th {
  padding: 0.625rem 0.5rem !important;
  font-size: 0.75rem;
  font-weight: 600;
}

.datatables-gastos tbody td {
  padding: 0.5rem 0.5rem !important;
  vertical-align: middle;
}

@media (max-width: 767px) {
  .gasto_empresa,
  .gasto_proveedor,
  .gasto_estado,
  .gasto_tipo_gasto,
  .gasto_forma_pago {
    width: 100% !important;
    flex: 0 0 100% !important;
    max-width: 100% !important;
  }
}
</style>
