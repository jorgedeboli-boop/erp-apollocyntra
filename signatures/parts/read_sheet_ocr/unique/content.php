<!-- Content -->
        <div class="container-fluid flex-grow-1 container-p-y" id="ocrFacturaRoot"
             data-usuario-id="<?php echo (int) ($usuario_id ?? 0); ?>"
             data-sucursal-id="<?php echo (int) ($usuario_sucursal ?? 0); ?>">
        <div class="row g-4">

        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <label class="form-label fw-semibold">Archivo de factura de proveedor</label>
                    <div class="mb-2">
                        <label class="form-label mb-1 small">Documento principal</label>
                        <div class="input-group">
                            <input type="file" class="form-control" id="inputFacturaPrincipal"
                                   accept=".pdf,.xls,.xlsx,image/jpeg,image/png,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
                            <button type="button" class="btn btn-primary" id="btnFotoMovilPrincipal"
                                    onclick="abrirModalFotoMovilFactura('principal')" title="Foto desde móvil">
                                <span class="icon-base ri ri-camera-ai-fill icon-22px"></span>
                            </button>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label mb-1 small">Página / anexo adicional <span class="text-muted">— opcional</span></label>
                        <div class="input-group">
                            <input type="file" class="form-control" id="inputFacturaAdicional"
                                   accept=".pdf,.xls,.xlsx,image/jpeg,image/png,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
                            <button type="button" class="btn btn-primary" id="btnFotoMovilAdicional"
                                    onclick="abrirModalFotoMovilFactura('adicional')" title="Foto desde móvil">
                                <span class="icon-base ri ri-camera-ai-fill icon-22px"></span>
                            </button>
                        </div>
                    </div>
                    <div class="form-text">PDF, Excel (XLS/XLSX), JPG o PNG. Sube al menos un archivo.</div>

                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <img id="previewPrincipal" class="img-fluid rounded border" alt="Vista previa principal">
                        </div>
                        <div class="col-6">
                            <img id="previewAdicional" class="img-fluid rounded border" alt="Vista previa adicional">
                        </div>
                    </div>

                    <div class="d-grid mt-3">
                        <button class="btn btn-primary" id="btnLeer" disabled>
                            <span class="spinner-border spinner-border-sm me-2" id="spinner"></span>
                            Leer factura
                        </button>
                    </div>

                    <div class="alert alert-danger mt-3 d-none" id="alertaError"></div>
                    <div class="alert alert-success mt-3 d-none" id="alertaOk">
                        Factura leída en <span id="tiempoLectura"></span>s
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Datos extraídos</h6>

                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label mb-0 small">Tipo documento</label>
                            <input type="text" class="form-control form-control-sm" id="tipo_documento" readonly>
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">Nº factura</label>
                            <input type="text" class="form-control form-control-sm" id="numero_factura">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">Moneda</label>
                            <input type="text" class="form-control form-control-sm" id="moneda">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">F. factura</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_factura">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">F. vencimiento</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_vencimiento">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">Forma de pago</label>
                            <input type="text" class="form-control form-control-sm" id="forma_pago">
                        </div>

                        <div class="col-12"><hr class="my-1"><small class="text-muted">Proveedor</small></div>
                        <div class="col-8">
                            <label class="form-label mb-0 small">Nombre</label>
                            <input type="text" class="form-control form-control-sm" id="proveedor_nombre">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">CIF/NIF</label>
                            <input type="text" class="form-control form-control-sm" id="proveedor_cif">
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-0 small">Dirección</label>
                            <input type="text" class="form-control form-control-sm" id="proveedor_direccion">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">Población</label>
                            <input type="text" class="form-control form-control-sm" id="proveedor_poblacion">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">Provincia</label>
                            <input type="text" class="form-control form-control-sm" id="proveedor_provincia">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">C.P.</label>
                            <input type="text" class="form-control form-control-sm" id="proveedor_codigo_postal">
                        </div>

                        <div class="col-12"><hr class="my-1"><small class="text-muted">Cliente / receptor</small></div>
                        <div class="col-8">
                            <label class="form-label mb-0 small">Nombre</label>
                            <input type="text" class="form-control form-control-sm" id="cliente_nombre">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">CIF/NIF</label>
                            <input type="text" class="form-control form-control-sm" id="cliente_cif">
                        </div>

                        <div class="col-12"><hr class="my-1"><small class="text-muted">Importes</small></div>
                        <div class="col-3">
                            <label class="form-label mb-0 small">Base imponible</label>
                            <input type="text" class="form-control form-control-sm" id="base_imponible">
                        </div>
                        <div class="col-3">
                            <label class="form-label mb-0 small">Tipo IVA</label>
                            <input type="text" class="form-control form-control-sm" id="tipo_iva">
                        </div>
                        <div class="col-3">
                            <label class="form-label mb-0 small">Importe IVA</label>
                            <input type="text" class="form-control form-control-sm" id="importe_iva">
                        </div>
                        <div class="col-3">
                            <label class="form-label mb-0 small">Total</label>
                            <input type="text" class="form-control form-control-sm fw-bold" id="total">
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-0 small">Concepto</label>
                            <input type="text" class="form-control form-control-sm" id="concepto">
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-0 small">Observaciones</label>
                            <textarea class="form-control form-control-sm" id="observaciones" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-0 small">Líneas de detalle</label>
                            <pre id="lineasRaw" class="border rounded p-2 bg-light mb-0"></pre>
                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="#" id="toggleJson" class="small">Ver JSON completo</a>
                        <pre id="jsonRaw" class="mt-2"></pre>
                    </div>
                </div>
            </div>
        </div>

    </div>
        </div>
        <!-- / Content -->
