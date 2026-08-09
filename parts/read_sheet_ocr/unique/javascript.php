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

    let archivoPrincipal = null;
    let archivoAdicional = null;

    function actualizarEstadoBoton() {
        $('#btnLeer').prop('disabled', !archivoPrincipal && !archivoAdicional);
    }

    function esImagen(archivo) {
        return archivo && archivo.type.indexOf('image') === 0;
    }

    function mostrarPreview(archivo, selectorPreview) {
        const $preview = $(selectorPreview);
        if (esImagen(archivo)) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $preview.attr('src', e.target.result).show();
            };
            reader.readAsDataURL(archivo);
        } else if (archivo) {
            $preview.hide().attr('src', '');
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

    function asignarFotoDesdeUrl(url, destino) {
        return fetch(url)
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('No se pudo descargar la foto');
                }
                return response.blob();
            })
            .then(function (blob) {
                const nombre = destino === 'adicional' ? 'factura-adicional.jpg' : 'factura-principal.jpg';
                const file = new File([blob], nombre, { type: blob.type || 'image/jpeg' });
                if (destino === 'adicional') {
                    archivoAdicional = file;
                    mostrarPreview(archivoAdicional, '#previewAdicional');
                } else {
                    archivoPrincipal = file;
                    mostrarPreview(archivoPrincipal, '#previewPrincipal');
                }
                onCambioArchivo();
            });
    }

    function rellenarDatos(d) {
        $('#tipo_documento').val(d.tipo_documento || '');
        $('#numero_factura').val(d.numero_factura || '');
        $('#moneda').val(d.moneda || '');
        $('#fecha_factura').val(d.fecha_factura || '');
        $('#fecha_vencimiento').val(d.fecha_vencimiento || '');
        $('#forma_pago').val(d.forma_pago || '');
        $('#proveedor_nombre').val(d.proveedor_nombre || '');
        $('#proveedor_cif').val(d.proveedor_cif || '');
        $('#proveedor_direccion').val(d.proveedor_direccion || '');
        $('#proveedor_poblacion').val(d.proveedor_poblacion || '');
        $('#proveedor_provincia').val(d.proveedor_provincia || '');
        $('#proveedor_codigo_postal').val(d.proveedor_codigo_postal || '');
        $('#cliente_nombre').val(d.cliente_nombre || '');
        $('#cliente_cif').val(d.cliente_cif || '');
        $('#base_imponible').val(d.base_imponible || '');
        $('#tipo_iva').val(d.tipo_iva || '');
        $('#importe_iva').val(d.importe_iva || '');
        $('#total').val(d.total || '');
        $('#concepto').val(d.concepto || '');
        $('#observaciones').val(d.observaciones || '');
        $('#lineasRaw').text(Array.isArray(d.lineas) ? JSON.stringify(d.lineas, null, 2) : '[]');
        $('#jsonRaw').text(JSON.stringify(d, null, 2));
    }

    window.abrirModalFotoMovilFactura = function (destino) {
        const root = document.getElementById('ocrFacturaRoot');
        const uid = parseInt(root ? root.getAttribute('data-usuario-id') : '0', 10);
        const sid = parseInt(root ? root.getAttribute('data-sucursal-id') : '0', 10);

        if (!uid || !sid) {
            Swal.fire({
                title: 'Error',
                text: 'No se puede usar la foto desde móvil: falta usuario o sucursal en sesión.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
            return;
        }

        if (!window.CameraDocPanel || typeof window.CameraDocPanel.open !== 'function') {
            Swal.fire({
                title: 'Error',
                text: 'No está disponible el módulo de cámara.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
            return;
        }

        window.__ocrFacturaDestino = destino === 'adicional' ? 'adicional' : 'principal';

        window.CameraDocPanel.open({
            tipo: 'factura_ocr',
            id: uid,
            idSucursal: sid,
            onTokenUtilizado: function (tipoQr, data) {
                if (tipoQr !== 'factura_ocr') {
                    return;
                }
                const url = resolverUrlFotoMovil(data);
                if (!url) {
                    return;
                }
                asignarFotoDesdeUrl(url, window.__ocrFacturaDestino).catch(function () {
                    Swal.fire({
                        title: 'Error',
                        text: 'No se pudo cargar la foto del móvil.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                });
            }
        }).catch(function (err) {
            Swal.fire({
                title: 'Error',
                text: err && err.message ? err.message : 'No se pudo abrir el código QR.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        });
    };

    $('#inputFacturaPrincipal').on('change', function () {
        archivoPrincipal = this.files[0] || null;
        mostrarPreview(archivoPrincipal, '#previewPrincipal');
        onCambioArchivo();
    });

    $('#inputFacturaAdicional').on('change', function () {
        archivoAdicional = this.files[0] || null;
        mostrarPreview(archivoAdicional, '#previewAdicional');
        onCambioArchivo();
    });

    $('#btnLeer').on('click', function () {
        if (!archivoPrincipal && !archivoAdicional) {
            return;
        }

        const formData = new FormData();
        if (archivoPrincipal) {
            formData.append('factura_principal', archivoPrincipal);
        }
        if (archivoAdicional) {
            formData.append('factura_adicional', archivoAdicional);
        }

        const inicio = Date.now();

        $('#spinner').show();
        $('#btnLeer').prop('disabled', true);
        $('#alertaError, #alertaOk').addClass('d-none');

        $.ajax({
            url: 'parts/read_sheet_ocr/unique/leer_factura.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (r) {
                if (r.success && r.datos) {
                    rellenarDatos(r.datos);
                    const segundos = ((Date.now() - inicio) / 1000).toFixed(1);
                    $('#tiempoLectura').text(segundos);
                    $('#alertaOk').removeClass('d-none');
                } else {
                    $('#alertaError').text(r.error || 'Respuesta inesperada del servidor').removeClass('d-none');
                }
            },
            error: function (xhr) {
                let msg = 'Error al procesar la factura';
                try {
                    const r = JSON.parse(xhr.responseText);
                    if (r.error) {
                        msg = r.error;
                    }
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
