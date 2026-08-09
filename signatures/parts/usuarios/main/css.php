<!-- CSS específico para usuario -->
<style>
.usuario-jerarquia-search-items {
  border: none !important;
  font-size: 20px;
  font-weight: lighter;
}
.usuario-jerarquia-search-items:focus,
.usuario-jerarquia-search-items:focus-visible,
.usuario-jerarquia-search-items:focus-within {
  outline: none !important;
  box-shadow: none !important;
  border: none !important;
}
.usuario-jerarquia-search-group,
.usuario-jerarquia-search-group:focus-within,
.usuario-jerarquia-search-group .input-group-text {
  border: none !important;
  outline: none !important;
  box-shadow: none !important;
}
#usuario-jerarquia-clear-search {
  border: none !important;
  outline: none !important;
  box-shadow: none !important;
  display: block;
  padding: 12px 0 0;
}
#usuario-jerarquia-clear-search:hover,
#usuario-jerarquia-clear-search:focus,
#usuario-jerarquia-clear-search:focus-within,
#usuario-jerarquia-clear-search:focus-visible {
  background: none !important;
  outline: none !important;
  box-shadow: none !important;
  border: none !important;
}
#usuario-jerarquia-clear-search .ri-close-line {
  width: 28px;
  height: 28px;
  font-weight: lighter;
}
#usuario-jerarquia-items-container .table.table-flush-spacing thead tr > td:first-child,
#usuario-jerarquia-items-container .table.table-flush-spacing tbody tr > td:first-child {
  padding-inline-start: 20px;
}
#usuario-jerarquia-items-container .table.table-flush-spacing thead tr > td:last-child,
#usuario-jerarquia-items-container .table.table-flush-spacing tbody tr > td:last-child {
  padding-inline-end: 20px;
}
.permiso-item-checkbox-usuario-solo,
.permiso-item-checkbox-usuario-solo:checked,
.permiso-item-checkbox-usuario-solo:disabled {
  border-color: #dc3545;
  background-color: #dc3545;
  opacity: 1;
}
.permiso-item-checkbox-usuario:disabled:not(.permiso-item-checkbox-usuario-solo) {
  opacity: 0.65;
}
.permiso-leyenda-normal,
.permiso-leyenda-solo-usuario {
  width: 1rem;
  height: 1rem;
  border-radius: 0.25em;
  border: 1px solid rgba(67, 89, 113, 0.4);
}
.permiso-leyenda-normal {
  background-color: var(--bs-primary, #696cff);
  border-color: var(--bs-primary, #696cff);
}
.permiso-leyenda-solo-usuario {
  background-color: #dc3545;
  border-color: #dc3545;
}
</style>
