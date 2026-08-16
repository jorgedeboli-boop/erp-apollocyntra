<!-- CSS CUSTOM STOCK UNIQUE -->
<style>
#stock_filtros_container:not(.stock-filtros-ready) select.select2-custom {
    opacity: 0;
}

#stock_filtros_container:not(.stock-filtros-ready) .articulo_tipo,
#stock_filtros_container:not(.stock-filtros-ready) .articulo_origen {
    min-height: 38px;
}

.card-datatable {
    padding-top: 1rem;
}

.datatables-stock {
    font-size: 0.875rem;
}

.datatables-stock thead th {
    padding: 0.625rem 0.5rem !important;
    font-size: 0.75rem;
    font-weight: 600;
}

.datatables-stock tbody td {
    padding: 0.5rem 0.5rem;
    font-size: 0.8125rem;
}

.datatables-stock tbody tr {
    cursor: pointer;
}

.datatables-stock tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.datatables-stock .icon-base {
    font-size: 1rem;
}
@media (max-width: 767px) {
    .articulo_tipo,
    .articulo_origen {
        width: 100% !important;
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
}
</style>
