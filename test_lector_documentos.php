<?php
/**
 * test_lector_documentos.php
 * Página de prueba del lector de documentos con Gemini
 * BORRAR O PROTEGER después de probar
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Test - Lector de documentos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background: #f5f5f5; }
  #preview {
    max-width: 100%;
    max-height: 300px;
    display: none;
    border-radius: 8px;
    border: 1px solid #ddd;
  }
  #jsonRaw {
    font-size: 12px;
    max-height: 250px;
    overflow: auto;
    background: #212529;
    color: #7ee787;
    border-radius: 8px;
    padding: 12px;
    display: none;
  }
  .spinner-border { display: none; }
</style>
</head>
<body>

<div class="container py-4" style="max-width: 900px;">

    <h4 class="mb-4">🪪 Test lector de documentos (Gemini)</h4>

    <div class="row g-4">

        <!-- Columna izquierda: subida -->
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <label class="form-label fw-semibold">Foto del documento</label>
                    <input type="file" class="form-control" id="inputDocumento" 
                           accept="image/*,application/pdf" capture="environment">
                    <div class="form-text">DNI, NIE, tarjeta de residencia o pasaporte</div>

                    <img id="preview" class="mt-3" alt="Vista previa">

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
                        <div class="col-4">
                            <label class="form-label mb-0 small">F. nacimiento</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_nacimiento">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">Sexo</label>
                            <input type="text" class="form-control form-control-sm" id="sexo">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">Nacionalidad</label>
                            <input type="text" class="form-control form-control-sm" id="nacionalidad">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-0 small">F. expedición</label>
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

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(function () {

    let archivoSeleccionado = null;

    $('#inputDocumento').on('change', function () {
        archivoSeleccionado = this.files[0] || null;
        $('#btnLeer').prop('disabled', !archivoSeleccionado);
        $('#alertaError, #alertaOk').addClass('d-none');

        // Vista previa (solo imágenes)
        if (archivoSeleccionado && archivoSeleccionado.type.indexOf('image') === 0) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#preview').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(archivoSeleccionado);
        } else {
            $('#preview').hide();
        }
    });

    $('#btnLeer').on('click', function () {
        if (!archivoSeleccionado) return;

        const formData = new FormData();
        formData.append('documento', archivoSeleccionado);

        const inicio = Date.now();

        $('#spinner').show();
        $('#btnLeer').prop('disabled', true);
        $('#alertaError, #alertaOk').addClass('d-none');

        $.ajax({
            url: 'leer_documento.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (r) {
                if (r.success && r.datos) {
                    const d = r.datos;
                    $('#tipo_documento').val(d.tipo_documento || '');
                    $('#numero_documento').val(d.numero_documento || '');
                    $('#nombre').val(d.nombre || '');
                    $('#apellido1').val(d.apellido1 || '');
                    $('#apellido2').val(d.apellido2 || '');
                    $('#fecha_nacimiento').val(d.fecha_nacimiento || '');
                    $('#sexo').val(d.sexo || '');
                    $('#nacionalidad').val(d.nacionalidad || '');
                    $('#fecha_expedicion').val(d.fecha_expedicion || '');
                    $('#fecha_caducidad').val(d.fecha_caducidad || '');
                    $('#pais_emisor').val(d.pais_emisor || '');

                    $('#jsonRaw').text(JSON.stringify(d, null, 2));

                    const segundos = ((Date.now() - inicio) / 1000).toFixed(1);
                    $('#tiempoLectura').text(segundos);
                    $('#alertaOk').removeClass('d-none');
                } else {
                    $('#alertaError').text(r.error || 'Respuesta inesperada del servidor').removeClass('d-none');
                }
            },
            error: function (xhr) {
                let msg = 'Error al procesar la imagen';
                try {
                    const r = JSON.parse(xhr.responseText);
                    if (r.error) msg = r.error;
                } catch (e) {}
                $('#alertaError').text(msg + ' (HTTP ' + xhr.status + ')').removeClass('d-none');
            },
            complete: function () {
                $('#spinner').hide();
                $('#btnLeer').prop('disabled', false);
            }
        });
    });

    $('#toggleJson').on('click', function (e) {
        e.preventDefault();
        $('#jsonRaw').toggle();
    });

});
</script>

</body>
</html>