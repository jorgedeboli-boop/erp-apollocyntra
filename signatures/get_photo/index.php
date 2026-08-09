<?php
session_start();
require_once '../include/functions.php';

/// AQUI CONECTAREMOS A LA BASE DE DATOS
$conexion = conectar_bd();
if (!$conexion) {
    $error = 'Error al conectar a la base de datos';
    exit();
}

// Recibir parámetros GET
$type = $_GET['type'] ?? null;
$id = $_GET['id'] ?? null;
$token = $_GET['token'] ?? null;
$id_token =  $_GET['id_token'] ?? null;
$id_sucursal = $_GET['id_sucursal'] ?? null;

// Inicializar array de tokens usados en sesión
if (!isset($_SESSION['tokens_usados'])) {
    $_SESSION['tokens_usados'] = [];
}

// Variables de estado
$error = null;
$success = false;

// Validar que los parámetros existan
if (!$type || !$id || !$token || !$id_token || !$id_sucursal) {
    //dime que parametro falta de a uno por uno
    if (!$type) {
        $error = 'Faltan parámetros requeridos (type)';
    } elseif (!$id) {
        $error = 'Faltan parámetros requeridos (id)';
    } elseif (!$token) {
        $error = 'Faltan parámetros requeridos (token)';
    } elseif (!$id_token) {
        $error = 'Faltan parámetros requeridos (id_token)';
    } elseif (!$id_sucursal) {
        $error = 'Faltan parámetros requeridos (id_sucursal)';
    }

} elseif (in_array($token, $_SESSION['tokens_usados'])) {
    $error = 'Error: Token ya fue utilizado anteriormente';
} else {
    $_SESSION['tokens_usados'][] = $token;
    $success = true;
}

// Marcar token usado al abrir la cámara (el TPV hace polling con procesar_consultar_token).
// Excepción venta, articulo_venta, cliente, adelanto_venta, plazo_venta e ia_chat: el token se marca al guardar (id_token en POST) para que el visor del TPV se actualice cuando la foto ya está en BD.
$id_token_int = isset($id_token) ? (int) $id_token : 0;
if ($id_token_int > 0 && ($type ?? '') !== 'venta' && ($type ?? '') !== 'articulo_venta' && ($type ?? '') !== 'cliente' && ($type ?? '') !== 'adelanto_venta' && ($type ?? '') !== 'plazo_venta' && ($type ?? '') !== 'ia_chat') {
    $query = "UPDATE tokens_actions SET state_token = 'false' WHERE id_token = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_token_int);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
mysqli_close($conexion);


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="robots" content="noindex, nofollow" />
    <title>Quinta gracia APP - Capturar Foto</title>
    <meta name="description" content="Panel de control TPV Quinta Gracia" />
    <meta name="theme-color" content="#000000" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="apple-mobile-web-app-title" content="Capturar foto" />
    <link rel="manifest" href="manifest.webmanifest" />
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
    <link rel="apple-touch-icon" href="icons/icon-192.png" />
    <link rel="stylesheet" href="../assets/css/fonts.css">
    <link rel="stylesheet" href="../assets/vendor/fonts/iconify-icons.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="../assets/vendor/css/core.css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/@form-validation/form-validation.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/swiper/swiper.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/apex-charts/apex-charts.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/animate-css/animate.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/sweetalert2/sweetalert2.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="../parts/universal/custom.css?v=1761088282&nocache=1" />
    <link rel="stylesheet" href="../assets/vendor/libs/tagify/tagify.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/bootstrap-select/bootstrap-select.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/typeahead-js/typeahead.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/spinkit/spinkit.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        body {
            background: #000;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 0px !important;
            padding-inline: 0px !important;
            max-width: 100% !important;
        }

        .media-container {
            flex: 1;
            width: 100%;
            background: #000;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        #video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        #canvas {
            display: none;
        }

        #photoPreview {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: none;
        }

        .controls {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            
            padding: 12px;
            background: transparent;
            flex-wrap: wrap;
        }

    
       
    </style>
</head>
<body>
    <div class="container">
        <div class="media-container">
            <video id="video" playsinline autoplay muted></video>
            <canvas id="canvas"></canvas>
            <img id="photoPreview" alt="Foto capturada">
        </div>

        <div class="controls">
            <button class="btn btn-primary" id="btnAbrirCamara" type="button" style="display: none;"><i class="icon-base ri ri-camera-line me-2"></i>Abrir cámara</button>
            <button class="btn btn-success" id="btnHacerFoto" style="display: none;"> <i class="icon-base ri ri-camera-line me-2"></i> HACER FOTO</button>
            <button class="btn btn-danger" id="btnCerrar" style="display: none;"> <i class="icon-base ri ri-close-line me-2"></i> Cerrar</button>
            <button class="btn btn-primary" id="btnGuardarFoto" style="display: none;"> <i class="icon-base ri ri-save-line me-2"></i> Guardar Foto</button>
            <button class="btn btn-secondary" id="btnReiniciar" style="display: none;"> <i class="icon-base ri ri-refresh-line me-2"></i> Otra Foto</button>
        </div>
    </div>

    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="../assets/vendor/libs/sweetalert2/sweetalert2.js"></script>
  
    <script>
        // Mostrar error si existe
        <?php if ($error): ?>
        Swal.fire({
            title: 'Error',
            text: '<?php echo addslashes($error); ?>',
            icon: 'error',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#dc3545'
        });
        <?php endif; ?>
    </script>
    
    <script>
        /** Tras el Swal de éxito: intentar cerrar la pestaña (p. ej. ventana abierta con window.open desde el TPV). Si el navegador lo bloquea, about:blank vacía la vista. */
        function cerrarPestanaGetPhoto() {
            window.close();
            setTimeout(function () {
                window.location.replace('about:blank');
            }, 400);
        }

        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const photoPreview = document.getElementById('photoPreview');
        const btnAbrirCamara = document.getElementById('btnAbrirCamara');
        const btnHacerFoto = document.getElementById('btnHacerFoto');
        const btnCerrar = document.getElementById('btnCerrar');
        const btnGuardarFoto = document.getElementById('btnGuardarFoto');
        const btnReiniciar = document.getElementById('btnReiniciar');
        let stream = null;

        const mediaConstraints = {
            video: {
                facingMode: 'environment',
                width: { ideal: 1920, min: 1280 },
                height: { ideal: 1080, min: 720 }
            },
            audio: false
        };

        function mostrarErrorCamara(error) {
            const detalle = (error && error.message) ? String(error.message) : 'No se pudo acceder a la cámara.';
            Swal.fire({
                title: 'No se pudo usar la cámara',
                text: detalle,
                footer: 'Si aparece el permiso, elige Permitir. Para guardarlo para este sitio: en Chrome, candado de la barra de dirección → Configuración del sitio → Cámara → Permitir. Los sitios web no pueden marcar “siempre permitir” por programación; lo decide el navegador.',
                icon: 'warning',
                confirmButtonText: 'Entendido'
            });
        }

        async function abrirCamara() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                mostrarErrorCamara({ message: 'Tu navegador no permite acceso a la cámara desde esta página (usa HTTPS y un navegador actual).' });
                btnAbrirCamara.style.display = 'inline-block';
                throw new Error('getUserMedia no disponible');
            }
            try {
                if (stream) {
                    stream.getTracks().forEach(function (t) { t.stop(); });
                    stream = null;
                }
                stream = await navigator.mediaDevices.getUserMedia(mediaConstraints);

                video.srcObject = stream;
                video.onloadedmetadata = function () {
                    video.style.display = 'block';
                    video.play();
                };

                setTimeout(function () {
                    video.style.display = 'block';
                }, 1000);

                btnAbrirCamara.style.display = 'none';
                btnHacerFoto.style.display = 'block';
                btnCerrar.style.display = 'block';
                photoPreview.style.display = 'none';
            } catch (error) {
                btnAbrirCamara.style.display = 'inline-block';
                mostrarErrorCamara(error);
                throw error;
            }
        }

        btnAbrirCamara.addEventListener('click', function () {
            abrirCamara();
        });

        <?php if (empty($error)): ?>
        document.addEventListener('DOMContentLoaded', function () {
            abrirCamara().catch(function () { /* ya se muestra aviso y el botón manual */ });
        });
        <?php endif; ?>

        // Hacer foto
        btnHacerFoto.addEventListener('click', () => {
            const context = canvas.getContext('2d');
            
            // Redimensionar a máximo 1200px de ancho
            let width = video.videoWidth;
            let height = video.videoHeight;
            
            if (width > 1200) {
                height = Math.round((height * 1200) / width);
                width = 1200;
            }
            
            canvas.width = width;
            canvas.height = height;
            context.drawImage(video, 0, 0, width, height);

            photoPreview.src = canvas.toDataURL('image/jpeg');
            photoPreview.style.display = 'block';

            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            video.style.display = 'none';
            btnHacerFoto.style.display = 'none';
            btnCerrar.style.display = 'none';
            btnGuardarFoto.style.display = 'block';
            btnReiniciar.style.display = 'block';
            btnAbrirCamara.style.display = 'none';
        });

        // Cerrar cámara
        btnCerrar.addEventListener('click', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            video.style.display = 'none';
            photoPreview.style.display = 'none';
            btnHacerFoto.style.display = 'none';
            btnCerrar.style.display = 'none';
            btnReiniciar.style.display = 'none';
            btnAbrirCamara.style.display = 'block';
        });

        // Reiniciar (volver a la cámara)
        btnReiniciar.addEventListener('click', function () {
            abrirCamara().then(function () {
                btnGuardarFoto.style.display = 'none';
                btnReiniciar.style.display = 'none';
            }).catch(function () {});
        });

        // Guardar foto
        btnGuardarFoto.addEventListener('click', () => {
            // Obtener parámetros de la URL
            const urlParams = new URLSearchParams(window.location.search);
            const type = urlParams.get('type');
            const id = urlParams.get('id');
            const id_sucursal = urlParams.get('id_sucursal');
            if (!type || !id || !id_sucursal) {
                Swal.fire({
                    title: 'Error',
                    text: 'Faltan parámetros necesarios',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }
            
            // Convertir canvas a blob
            canvas.toBlob(async (blob) => {
                const formData = new FormData();
                formData.append('archivo_foto', blob, 'foto_' + Date.now() + '.jpg');
                formData.append('camera_type', type);
                
                // Agregar parámetros según el tipo
                if (type === 'cliente') {
                    formData.append('id_cliente', id);
                    const idTokenCliente = urlParams.get('id_token');
                    if (idTokenCliente) {
                        formData.append('id_token', idTokenCliente);
                    }
                } else if (type === 'lote') {
                    formData.append('id_lote', id);
                    formData.append('id_sucursal', id_sucursal);
                } else if (type === 'gasto') {
                    formData.append('id_gasto', id);
                    formData.append('id_sucursal', id_sucursal);
                    const idTokenGasto = urlParams.get('id_token');
                    if (idTokenGasto) {
                        formData.append('id_token', idTokenGasto);
                    }
                }else if (type === 'renovacion') {
                    formData.append('id_renovacion', id);
                    formData.append('id_sucursal', id_sucursal);
                }else if (type === 'adelanto' || type === 'adelanto_venta') {
                    formData.append('id_foto', id);
                    formData.append('id_sucursal', id_sucursal);
                    const idTokenFoto = urlParams.get('id_token');
                    if (idTokenFoto) {
                        formData.append('id_token', idTokenFoto);
                    }
                }else if (type === 'autorizar_gasto') {
                    formData.append('id_autorizacion', id);
                    formData.append('id_sucursal', id_sucursal);
                } else if (type === 'articulo') {
                    formData.append('id_articulo', id);
                    formData.append('id_sucursal', id_sucursal);
                } else if (type === 'venta') {
                    formData.append('id_venta', id);
                    formData.append('id_sucursal', id_sucursal);
                    const idTokenQr = urlParams.get('id_token');
                    if (idTokenQr) {
                        formData.append('id_token', idTokenQr);
                    }
                } else if (type === 'articulo_venta') {
                    formData.append('id_venta', id);
                    formData.append('id_sucursal', id_sucursal);
                    const idTokenAv = urlParams.get('id_token');
                    if (idTokenAv) {
                        formData.append('id_token', idTokenAv);
                    }
                } else if (type === 'plazo_venta') {
                    formData.append('id_foto', id);
                    formData.append('id_sucursal', id_sucursal);
                    const idTokenPl = urlParams.get('id_token');
                    if (idTokenPl) {
                        formData.append('id_token', idTokenPl);
                    }
                }
                
                const url = '../camera/subir_foto.php';
                
                // Deshabilitar botón y mostrar loading
                btnGuardarFoto.disabled = true;
                btnGuardarFoto.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
                
                // Enviar foto
                fetch(url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: '¡Foto Guardada!',
                            text: data.message || 'La foto se ha guardado exitosamente',
                            icon: 'success',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#198754',
                            timer: 5000,
                            timerProgressBar: true
                        }).then(function () {
                            cerrarPestanaGetPhoto();
                        });
                    } else {
                        throw new Error(data.error || 'Error desconocido');
                    }
                })
                .catch(error => {
                    console.error('Error al guardar foto:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'No se pudo guardar la foto: ' + error.message,
                        icon: 'error',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#dc3545'
                    });
                    
                    // Restaurar botón
                    btnGuardarFoto.disabled = false;
                    btnGuardarFoto.innerHTML = '<i class="icon-base ri ri-save-line me-2"></i> Guardar Foto';
                });
            }, 'image/jpeg', 1.0);
        });

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('sw.js', { scope: './' }).catch(function () {});
            });
        }
    </script>
</body>
</html>