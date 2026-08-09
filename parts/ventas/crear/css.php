<style>
#tabla_articulos_venta thead th {
	background-color: #f8f9fa !important;
	font-weight: 600;
	font-size: 0.72rem;
	padding-block: 12px 9px;
}

#tabla_articulos_venta {
    table-layout: fixed;
    width: 100%;
}

#tabla_articulos_venta tbody td {
    vertical-align: middle;
}

#tabla_articulos_venta tbody td:nth-child(1) {
    width: 300px;
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.invoice-preview-card {
    box-shadow: 0 0.25rem 1.125rem rgba(75, 85, 99, 0.1);
}

.invoice-actions .card {
    position: sticky;
    top: 1rem;
}
.desenfocar {
    filter: blur(0px);
    opacity: 1;
    transition: all 0.5s ease;
}

.desenfocar.formulario-borroso {
    filter: blur(5px);
    opacity: 0.5;
    pointer-events: none;
    user-select: none;
}
.texto_direccion {
    color: #232b43;
    font-size: 0.85rem;
    line-height: initial;
}

/* Skeleton estático suave */
.skeleton {
  background-color: #e8e8e8;
  border-radius: 4px;
  display: block;
  margin-left: auto; /* Alinea a la derecha */
}

/* Tamaños específicos para cada elemento */
.skeleton-h5 {
  height: 24px;
  width: 180px;
}

.skeleton-line {
  height: 14px;
  width: 150px;
}

.skeleton-line-short {
  height: 14px;
  width: 200px;
}

.skeleton-line-medium {
  height: 14px;
  width: 180px;
}

</style>
