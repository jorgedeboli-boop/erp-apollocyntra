<?php require_once __DIR__ . '/../../../camera/render-config-script.php'; ?>
<?php
$vQrcodeMin = filemtime(__DIR__ . '/../../../js/qrcode.min.js');
$vCameraQr = filemtime(__DIR__ . '/../../../camera/js/camera-qr.js');
$vCameraDocPanel = filemtime(__DIR__ . '/../../../camera/js/camera-doc-panel.js');
?>
<script src="js/qrcode.min.js?v=<?php echo $vQrcodeMin; ?>"></script>
<script src="camera/js/camera-qr.js?v=<?php echo $vCameraQr; ?>"></script>
<script src="camera/js/camera-doc-panel.js?v=<?php echo $vCameraDocPanel; ?>"></script>
<script>
$(function () {

    let archivoFrente = null;
    let archivoDorso = null;

    function actualizarEstadoBoton() {
        $('#btnLeer').prop('disabled', !archivoFrente && !archivoDorso);
    }

    function mostrarPreview(archivo, selectorPreview) {
        const $preview = $(selectorPreview);
        if (archivo && archivo.type.indexOf('image') === 0) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $preview.attr('src', e.target.result).show();
            };
            reader.readAsDataURL(archivo);
        } else {
            $preview.hide().attr('src', '');
        }
    }

    function onCambioArchivo() {
        $('#alertaError, #alertaOk').addClass('d-none');
        actualizarEstadoBoton();
    }

    function resolverUrlFotoMovil(data) {
        const td = (data && data.token_data) ? data.token_data : {};
        let url = td.foto_url ? String(td.foto_url) : '';
        if (!url && td.nombre_foto) {
            const nom = String(td.nombre_foto).replace(/\\/g, '/').split('/').pop() || '';
            if (nom) {
                if (typeof window.APP_URL === 'string' && window.APP_URL) {
                    url = window.APP_URL.replace(/\/$/, '') + '/photos/' + encodeURIComponent(nom);
                } else {
                    url = window.location.origin + '/photos/' + encodeURIComponent(nom);
                }
            }
        }
        return url;
    }

    function asignarFotoDesdeUrl(url, cara) {
        return fetch(url)
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('No se pudo descargar la foto');
                }
                return response.blob();
            })
            .then(function (blob) {
                const nombre = cara === 'dorso' ? 'documento-dorso.jpg' : 'documento-frente.jpg';
                const file = new File([blob], nombre, { type: blob.type || 'image/jpeg' });
                if (cara === 'dorso') {
                    archivoDorso = file;
                    mostrarPreview(archivoDorso, '#previewDorso');
                } else {
                    archivoFrente = file;
                    mostrarPreview(archivoFrente, '#previewFrente');
                }
                onCambioArchivo();
            });
    }

    window.abrirModalFotoMovilOcr = function (cara) {
        const root = document.getElementById('ocrDocumentoRoot');
        const uid = parseInt(root ? root.getAttribute('data-usuario-id') : '0', 10);
        const sid = parseInt(root ? root.getAttribute('data-sucursal-id') : '0', 10);

        if (!uid || !sid) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: 'No se puede usar la foto desde móvil: falta usuario o sucursal en sesión.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            } else {
                alert('No se puede usar la foto desde móvil: falta usuario o sucursal en sesión.');
            }
            return;
        }

        if (!window.CameraDocPanel || typeof window.CameraDocPanel.open !== 'function') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: 'No está disponible el módulo de cámara.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            } else {
                alert('No está disponible el módulo de cámara.');
            }
            return;
        }

        if (typeof window.generarNuevoQR !== 'function' || typeof QRCode === 'undefined') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: 'No está cargada la librería de QR. Recarga la página.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            } else {
                alert('No está cargada la librería de QR. Recarga la página.');
            }
            return;
        }

        window.__ocrCaraActiva = cara === 'dorso' ? 'dorso' : 'frente';

        window.CameraDocPanel.open({
            tipo: 'documento_ocr',
            id: uid,
            idSucursal: sid,
            onTokenUtilizado: function (tipoQr, data) {
                if (tipoQr !== 'documento_ocr') {
                    return;
                }
                const url = resolverUrlFotoMovil(data);
                if (!url) {
                    console.warn('OCR: token utilizado sin foto', data);
                    return;
                }
                asignarFotoDesdeUrl(url, window.__ocrCaraActiva).catch(function (err) {
                    console.error('OCR foto móvil', err);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Error',
                            text: 'No se pudo cargar la foto del móvil.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            }
        }).catch(function (err) {
            console.error('CameraDocPanel OCR', err);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: err && err.message ? err.message : 'No se pudo abrir el código QR.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    };

    $('#inputDocumentoFrente').on('change', function () {
        archivoFrente = this.files[0] || null;
        mostrarPreview(archivoFrente, '#previewFrente');
        onCambioArchivo();
    });

    $('#inputDocumentoDorso').on('change', function () {
        archivoDorso = this.files[0] || null;
        mostrarPreview(archivoDorso, '#previewDorso');
        onCambioArchivo();
    });

    $('#btnLeer').on('click', function () {
        if (!archivoFrente && !archivoDorso) return;

        const formData = new FormData();
        if (archivoFrente) {
            formData.append('documento_frente', archivoFrente);
        }
        if (archivoDorso) {
            formData.append('documento_dorso', archivoDorso);
        }

        const inicio = Date.now();

        $('#spinner').show();
        $('#btnLeer').prop('disabled', true);
        $('#alertaError, #alertaOk').addClass('d-none');

        $.ajax({
            url: 'parts/read_document_ocr/unique/leer_documento.php',
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
                    $('#sexo_texto').val(d.sexo_texto || '');
                    $('#nacionalidad_codigo').val(d.nacionalidad_codigo || '');
                    $('#nacionalidad').val(d.nacionalidad || '');
                    $('#fecha_expedicion').val(d.fecha_expedicion || d.fecha_emision || '');
                    $('#fecha_caducidad').val(d.fecha_caducidad || '');
                    $('#pais_emisor').val(d.pais_emisor || '');
                    $('#direccion').val(d.direccion || '');
                    $('#poblacion').val(d.poblacion || '');
                    $('#pais_residencia').val(d.pais_residencia || '');
                    $('#provincia').val(d.provincia || '');
                    $('#codigo_postal').val(d.codigo_postal || '');

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
                actualizarEstadoBoton();
            }
        });
    });

    $('#toggleJson').on('click', function (e) {
        e.preventDefault();
        $('#jsonRaw').toggle();
    });

});
</script>
