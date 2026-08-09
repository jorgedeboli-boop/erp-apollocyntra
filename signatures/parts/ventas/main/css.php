<!-- CSS CUSTOM venta - main (visor comprobantes alineado con lotes/main) -->
<style>
#tabla_articulos_venta_ficha th {
  padding-block: 11px;
  font-size: 11px;
  text-transform: initial;
  padding: 11px 5px !important;
  text-align: center;
}
#tabla_articulos_venta_ficha td {
  padding-block: 11px;
  font-size: 11px;
  text-transform: initial;
  padding: 11px 5px !important;
  text-align: center;
}
#modalAmpliarImagenVenta .modal-body {
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: #fff;
}
#modalAmpliarImagenVenta img {
  max-width: 100%;
  max-height: 80vh;
  object-fit: contain;
}
#modalAmpliarImagenVenta .modal-dialog {
  max-width: 95vw;
  max-height: 95vh;
  margin: 1rem auto;
}
#visor_documentos_articulos_venta .card {
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  border: 1px solid #e0e0e0;
  height: 100%;
}
#visor_documentos_articulos_venta .card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
#visor_documentos_articulos_venta .card img {
  border-radius: 4px;
  transition: opacity 0.2s ease-in-out;
  width: 100% !important;
  height: auto;
  object-fit: cover;
}
#visor_documentos_articulos_venta .pdf-preview {
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
#visor_documentos_articulos_venta .pdf-preview:hover {
  border-color: #007bff;
  background-color: #e7f3ff;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
}
#visor_documentos_articulos_venta .card img:hover {
  opacity: 0.9;
}
#visor_documentos_articulos_venta .position-relative {
  overflow: hidden;
  border-radius: 4px;
}
#visor_documentos_articulos_venta .position-absolute {
  z-index: 10;
}
#visor_documentos_articulos_venta .card:not(:hover) .btn-danger {
  opacity: 0.4;
}
#modalAmpliarImagenArticulosVenta .modal-body {
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: #000;
}
#modalAmpliarImagenArticulosVenta img {
  max-width: 100%;
  max-height: 80vh;
  object-fit: contain;
}
#modalAmpliarImagenArticulosVenta .modal-dialog {
  max-width: 95vw;
  max-height: 95vh;
  margin: 1rem auto;
}
#loading_imagenes_articulos_venta .spinner-border {
  width: 2rem;
  height: 2rem;
}
#sin_imagenes_articulos_venta .icon-48px {
  font-size: 3rem;
  color: #6c757d;
}
#visor_documentos_cliente_venta .card {
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  border: 1px solid #e0e0e0;
  height: 100%;
}
#visor_documentos_cliente_venta .card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
#visor_documentos_cliente_venta .card img {
  border-radius: 4px;
  transition: opacity 0.2s ease-in-out;
  width: 100% !important;
  height: auto;
  object-fit: cover;
}
#visor_documentos_cliente_venta .pdf-preview {
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
#visor_documentos_cliente_venta .pdf-preview:hover {
  border-color: #007bff;
  background-color: #e7f3ff;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
}
#visor_documentos_cliente_venta .card img:hover {
  opacity: 0.9;
}
#visor_documentos_cliente_venta .position-relative {
  overflow: hidden;
  border-radius: 4px;
}
#visor_documentos_cliente_venta .position-absolute {
  z-index: 10;
}
#visor_documentos_cliente_venta .card:not(:hover) .btn-danger {
  opacity: 0.4;
}
#modalAmpliarImagenClienteVenta .modal-body {
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: #000;
}
#modalAmpliarImagenClienteVenta img {
  max-width: 100%;
  max-height: 80vh;
  object-fit: contain;
}
#modalAmpliarImagenClienteVenta .modal-dialog {
  max-width: 95vw;
  max-height: 95vh;
  margin: 1rem auto;
}
#loading_imagenes_cliente_venta .spinner-border {
  width: 2rem;
  height: 2rem;
}
#sin_imagenes_cliente_venta .icon-48px {
  font-size: 3rem;
  color: #6c757d;
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
</style>
