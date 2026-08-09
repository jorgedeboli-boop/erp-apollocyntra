<!-- Content -->
        <div class="container-fluid flex-grow-1 container-p-y" id="ocrDocumentoRoot"
             data-usuario-id="<?php echo (int) ($usuario_id ?? 0); ?>"
             data-sucursal-id="<?php echo (int) ($usuario_sucursal ?? 0); ?>">
        <div class="row g-4">

        <!-- Columna izquierda: subida -->
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <label class="form-label fw-semibold">Fotos del documento</label>
                    <div class="mb-2">
                        <label class="form-label mb-1 small">Frente (anverso)</label>
                        <div class="input-group">
                            <input type="file" class="form-control" id="inputDocumentoFrente"
                                   accept="image/*,application/pdf" capture="environment">
                            <button type="button" class="btn btn-primary" id="btnFotoMovilFrente"
                                    onclick="abrirModalFotoMovilOcr('frente')" title="Hacer foto desde móvil">
                                <span class="icon-base ri ri-camera-ai-fill icon-22px"></span>
                            </button>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label mb-1 small">Dorso (reverso) <span class="text-muted">— opcional</span></label>
                        <div class="input-group">
                            <input type="file" class="form-control" id="inputDocumentoDorso"
                                   accept="image/*,application/pdf" capture="environment">
                            <button type="button" class="btn btn-primary" id="btnFotoMovilDorso"
                                    onclick="abrirModalFotoMovilOcr('dorso')" title="Hacer foto desde móvil">
                                <span class="icon-base ri ri-camera-ai-fill icon-22px"></span>
                            </button>
                        </div>
                    </div>
                    <div class="form-text">Sube al menos una cara. DNI, NIE, tarjeta de residencia o pasaporte español.</div>

                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <img id="previewFrente" class="img-fluid rounded border" alt="Vista previa frente" style="display:none; max-height: 180px;">
                        </div>
                        <div class="col-6">
                            <img id="previewDorso" class="img-fluid rounded border" alt="Vista previa dorso" style="display:none; max-height: 180px;">
                        </div>
                    </div>

                    <div class="d-grid mt-3">
                        <button class="btn btn-primary" id="btnLeer" disabled>
                            <span class="spinner-border spinner-border-sm me-2" id="spinner"></span>
                            Leer documento
                        </button>
                    </div>

                    <div class="alert alert-danger mt-3 d-none" id="alertaError"></div>
                    <div class="alert alert-success mt-3 d-none" id="alertaOk">
                        Documento leído en <span id="tiempoLectura"></span>s
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna derecha: resultado -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Datos extraídos</h6>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label mb-0 small">Tipo documento</label>
                            <input type="text" class="form-control form-control-sm" id="tipo_documento" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label mb-0 small">Nº documento</label>
                            <input type="text" class="form-control form-control-sm" id="numero_documento">
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-0 small">Nombre</label>
                            <input type="text" class="form-control form-control-sm" id="nombre">
                        </div>
                        <div class="col-6">
                            <label class="form-label mb-0 small">Apellido 1</label>
                            <input type="text" class="form-control form-control-sm" id="apellido1">
                        </div>
                        <div class="col-6">
                            <label class="form-label mb-0 small">Apellido 2</label>
                            <input type="text" class="form-control form-control-sm" id="apellido2">
                        </div>
                        <div class="col-3">
                            <label class="form-label mb-0 small">F. nacimiento</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_nacimiento">
                        </div>
                        <div class="col-2">
                            <label class="form-label mb-0 small">Cód. sexo</label>
                            <input type="text" class="form-control form-control-sm" id="sexo" maxlength="1" readonly>
                        </div>
                        <div class="col-3">
                            <label class="form-label mb-0 small">Sexo</label>
                            <input type="text" class="form-control form-control-sm" id="sexo_texto" readonly>
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">Cód. nacionalidad</label>
                            <input type="text" class="form-control form-control-sm" id="nacionalidad_codigo" maxlength="3" readonly>
                        </div>
                        <div class="col-8">
                            <label class="form-label mb-0 small">Nacionalidad</label>
                            <input type="text" class="form-control form-control-sm" id="nacionalidad">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">F. expedición / emisión</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_expedicion">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">F. caducidad</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_caducidad">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">País emisor</label>
                            <input type="text" class="form-control form-control-sm" id="pais_emisor">
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-0 small">Dirección</label>
                            <input type="text" class="form-control form-control-sm" id="direccion">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">Población</label>
                            <input type="text" class="form-control form-control-sm" id="poblacion">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">País de residencia</label>
                            <input type="text" class="form-control form-control-sm" id="pais_residencia">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">Provincia</label>
                            <input type="text" class="form-control form-control-sm" id="provincia">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">Código postal</label>
                            <input type="text" class="form-control form-control-sm" id="codigo_postal">
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