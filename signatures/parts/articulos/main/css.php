<style>
#tabla_trazabilidad_articulo thead th {
	background-color: #f8f9fa !important;
	font-weight: 600;
	font-size: 0.72rem;
	padding-block: 12px 9px;
    text-align: center;
}

#tabla_trazabilidad_articulo {
    table-layout: fixed;
    width: 100%;
}

#tabla_trazabilidad_articulo tbody td {
    vertical-align: middle;
    text-align: center;
}

#tabla_trazabilidad_articulo tbody td:nth-child(1) {
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

<!-- CSS CUSTOM lote  -->
    #tablaArticulos th {
        padding-block: 11px;
  font-size: 11px;
  text-transform: initial;
  padding: 11px 5px !important;
  text-align: center;
}
#tablaArticulos td {
	padding-block: 11px;
	font-size: 11px;
    text-transform: initial;
    padding: 11px 5px !important;
    text-align: center;
}
/* Responsive para dispositivos móviles */
@media (max-width: 768px) {
    #modalSubirFoto .modal-dialog {
        margin: 1rem;
    }
}

/* Animaciones para las imágenes */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Ocultar flechitas en inputs de número */
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type=number] {
    -moz-appearance: textfield;
}
#repetir_firma, #solicitar_firma {
    display: none;
}
.btnlistestados {
        width: 100%;
        margin-bottom: 10px;
        max-width: 280px;
    }
    .invoice-content ul {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .invoice-to {
        float: left;
        width: 100%;
    }
    .invoice-to span {
        line-height: 29px !important;
    }
    .btn_changue_estado {
        margin: 0 auto;
        display: block;
    }
    .widget-title h5{
        float: none;
    }
    .changue_estado{
        display: none;
    }
    .invoice-to li span, .invoice-from li span {
    display: block;
    line-height: 22px;
}
#tablaRenovaciones th {
	padding-block: 11px;
  font-size: 11px;
  text-transform: initial;
  padding: 11px 5px !important;
  text-align: center;
}
#tablaRenovaciones td {
	padding-block: 11px;
  font-size: 11px;
  text-transform: initial;
  padding: 11px 5px !important;
  text-align: center;
}
#tablaAdelantoCapital th {
	padding-block: 11px;
  font-size: 11px;
  text-transform: initial;
  padding: 11px 5px !important;
  text-align: center;
}
#tablaRenovaciones td {
	padding-block: 11px;
  font-size: 11px;
  text-transform: initial;
  padding: 11px 5px !important;
  text-align: center;
}
#tablaTrazabilidad th {
	padding-block: 11px;
  font-size: 11px;
  text-transform: initial;
  padding: 11px 5px !important;
  text-align: center;
}
#tablaTrazabilidad td {
	padding-block: 11px;
	font-size: 11px;
	text-transform: initial;
	padding: 11px 5px !important;
	text-align: center;
}

/* Visor documentos artículo (articulos_venta_imagenes) */
#visor_documentos_articulo .card {
	transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
	border: 1px solid #e0e0e0;
	height: 100%;
}
#visor_documentos_articulo .card:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
#visor_documentos_articulo .card img {
	border-radius: 4px;
	transition: opacity 0.2s ease-in-out;
	width: 100% !important;
	height: auto;
	object-fit: cover;
}
#visor_documentos_articulo .card img:hover {
	opacity: 0.9;
}
#visor_documentos_articulo .pdf-preview {
	text-align: center;
	padding: 2rem 1rem;
	cursor: pointer;
	transition: all 0.2s ease-in-out;
	border: 2px dashed #e0e0e0;
	border-radius: 8px;
	background-color: #f8f9fa;
	min-height: 200px;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	position: relative;
}
#visor_documentos_articulo .pdf-preview:hover {
	border-color: #007bff;
	background-color: #e7f3ff;
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
}
#visor_documentos_articulo .pdf-preview::before {
	content: '📄';
	position: absolute;
	top: 0.5rem;
	right: 0.5rem;
	font-size: 1.2rem;
	opacity: 0.7;
}
#visor_documentos_articulo .pdf-preview::after {
	content: '📥 Descargar';
	position: absolute;
	bottom: 0.5rem;
	left: 50%;
	transform: translateX(-50%);
	font-size: 0.75rem;
	color: #007bff;
	font-weight: 500;
	opacity: 0;
	transition: opacity 0.2s ease-in-out;
	background-color: rgba(255, 255, 255, 0.9);
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	white-space: nowrap;
}
#visor_documentos_articulo .pdf-preview:hover::after {
	opacity: 1;
}
#visor_documentos_articulo .pdf-preview .icon-48px {
	font-size: 3rem;
	margin-bottom: 1rem;
}
#visor_documentos_articulo .pdf-preview small {
	font-size: 0.875rem;
	word-break: break-all;
	max-width: 100%;
}
#visor_documentos_articulo .btn-danger {
	opacity: 0.8;
	transition: opacity 0.2s ease-in-out;
}
#visor_documentos_articulo .btn-danger:hover {
	opacity: 1;
}
#visor_documentos_articulo .position-relative {
	overflow: hidden;
	border-radius: 4px;
}
#visor_documentos_articulo .position-absolute {
	z-index: 10;
}
#visor_documentos_articulo .btn-sm.btn-danger {
	padding: 0.25rem 0.5rem;
	font-size: 0.75rem;
	border-radius: 4px;
}
#visor_documentos_articulo .card:not(:hover) .btn-danger {
	opacity: 0.4;
}
#modalAmpliarImagenArticulo .modal-body {
	display: flex;
	justify-content: center;
	align-items: center;
	background-color: #000;
}
#modalAmpliarImagenArticulo img {
	max-width: 100%;
	max-height: 80vh;
	object-fit: contain;
	cursor: zoom-out;
	transition: transform 0.2s ease-in-out;
}
#modalAmpliarImagenArticulo img:hover {
	transform: scale(1.02);
}
#modalAmpliarImagenArticulo .modal-dialog {
	max-width: 95vw;
	max-height: 95vh;
	margin: 1rem auto;
}
#modalAmpliarImagenArticulo .modal-content {
	max-height: 95vh;
	overflow: hidden;
}
#loading_imagenes_articulo .spinner-border {
	width: 2rem;
	height: 2rem;
}
#sin_imagenes_articulo .icon-48px {
	font-size: 3rem;
	color: #6c757d;
}
@media (max-width: 768px) {
	#visor_documentos_articulo .col-sm-6 {
		margin-bottom: 1rem;
		flex: 0 0 50%;
		max-width: 50%;
	}
	#modalSubirFotoArticulo .modal-dialog {
		margin: 1rem;
	}
	#modalAmpliarImagenArticulo .modal-dialog {
		margin: 0.5rem;
	}
	#modalAmpliarImagenArticulo .modal-body {
		min-height: 300px;
		max-height: 60vh;
		padding: 0.5rem;
	}
	#modalAmpliarImagenArticulo img {
		max-width: 95%;
		max-height: 95%;
	}
}
@keyframes fadeInArticuloDoc {
	from {
		opacity: 0;
		transform: translateY(20px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}
#visor_documentos_articulo .card {
	animation: fadeInArticuloDoc 0.5s ease-out;
}
</style>