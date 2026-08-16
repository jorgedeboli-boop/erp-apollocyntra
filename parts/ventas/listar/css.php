<style>
/* Evitar flash del <select> nativo antes de que Select2 lo sustituya */
#collapse_filtros:not(.ventas-filtros-ready) select.select2-custom {
    opacity: 0;
}
</style>

<style>
/* Responsive adjustments for filters */
@media (max-width: 767px) {
    .venta_tipo,
    .venta_web,
    .venta_forma_pago {
        width: 100% !important;
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
}
</style>
