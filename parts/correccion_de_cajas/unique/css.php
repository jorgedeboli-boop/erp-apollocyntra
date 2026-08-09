<!-- CSS CUSTOM correccion_de_cajas - unique -->
<style>
  #modal-correccion-movimientos .drag-handle {
    cursor: grab;
  }
  #modal-correccion-movimientos .drag-handle:active {
    cursor: grabbing;
  }
  #modal-correccion-movimientos .input-correccion-importe {
    width: 96px;
    min-width: 72px;
    padding: 0.2rem 0.35rem;
    margin-left: auto;
  }
  #modal-correccion-movimientos tr.movimiento-id-erroneo {
    --bs-table-bg: rgba(var(--bs-warning-rgb), 0.18);
  }
  #modal-correccion-movimientos .drag-handle {
    width: 36px;
    text-align: center;
    color: var(--bs-secondary-color);
  }
  #modal-correccion-movimientos.sortable-ghost {
    opacity: 0.45;
  }
</style>
