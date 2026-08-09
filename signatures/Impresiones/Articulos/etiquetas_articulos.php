<?php
/**
 * Impresión de etiquetas de artículos (venta / envío / lotes).
 *
 * Parámetros habituales (GET y/o POST según el flujo):
 * - id_articulo: una etiqueta desde `articulos_venta`.
 * - varios / por_sucursal: impresión por lotes desde central.
 * - envio=true + id_envio: etiquetas de artículos pasados a stock en un envío.
 * - reimprimir / individual: modifican trazabilidad y el cierre tras imprimir.
 */

require_once '../../include/session.php';
require_once '../../include/functions.php';

$modo_test = false;

/**
 * @param string $key
 */
function etiquetas_articulos_req_str($key)
{
    if (isset($_GET[$key])) {
        return (string) $_GET[$key];
    }
    if (isset($_POST[$key])) {
        return (string) $_POST[$key];
    }

    return '';
}

$varios = isset($_GET['varios']) ? (string) $_GET['varios'] : '';
$id_articulo_get = isset($_GET['id_articulo']) ? (int) $_GET['id_articulo'] : 0;
$envio_raw = etiquetas_articulos_req_str('envio');
$envio_activo = ($envio_raw === 'true');

$id_envio = 0;
if (isset($_GET['id_envio'])) {
    $id_envio = (int) $_GET['id_envio'];
}
if ($id_envio <= 0 && isset($_POST['id_envio'])) {
    $id_envio = (int) $_POST['id_envio'];
}

$por_sucursal = isset($_GET['por_sucursal']) ? (int) $_GET['por_sucursal'] : 0;
$individual = isset($_GET['individual']) ? (string) $_GET['individual'] : '';
$reimprimir = isset($_GET['reimprimir']) ? (string) $_GET['reimprimir'] : '';

if ($envio_activo) {
    $tipo_control_etiquetado = 'envio';
} elseif ($por_sucursal > 0) {
    $tipo_control_etiquetado = 'sucursal';
    $id_envio = 0;
} elseif ($varios === 'true') {
    $tipo_control_etiquetado = 'todo';
    $id_envio = 0;
} else {
    $tipo_control_etiquetado = 'articulo';
    $id_envio = 0;
}

$conexion = conectar_bd();
if (!$conexion || !($conexion instanceof mysqli)) {
    http_response_code(500);
    exit('Error de conexión');
}

mysqli_set_charset($conexion, 'utf8mb4');

$sucursal_remitente_env = 0;

if ($envio_activo) {
    if ($id_envio <= 0) {
        mysqli_close($conexion);
        header('Location: ../../dashboard.php');
        exit;
    }

    $q_env_perm = 'SELECT sucursal_remitente FROM envios WHERE id_envio = ? LIMIT 1';
    $stmt_perm = mysqli_prepare($conexion, $q_env_perm);
    if (!$stmt_perm) {
        mysqli_close($conexion);
        http_response_code(500);
        exit;
    }
    mysqli_stmt_bind_param($stmt_perm, 'i', $id_envio);
    mysqli_stmt_execute($stmt_perm);
    $row_perm = mysqli_stmt_fetch_assoc_compat($stmt_perm);
    mysqli_stmt_close($stmt_perm);

    if (!$row_perm) {
        mysqli_close($conexion);
        header('Location: ../../dashboard.php');
        exit;
    }

    $sucursal_remitente_env = (int) ($row_perm['sucursal_remitente'] ?? 0);
    if (
        isset($sucursal_section, $usuario_sucursal, $usuario_root)
        && $sucursal_section === 'true'
        && $usuario_root !== 'true'
        && (int) $usuario_sucursal !== $sucursal_remitente_env
    ) {
        mysqli_close($conexion);
        http_response_code(403);
        echo 'No autorizado';
        exit;
    }
}

$id_control_etiquetado = 0;
$sucursal_control_etiquetado = 2;
$total_etiquetas = contar_etiquetas_articulos_a_imprimir(
    $conexion,
    $varios,
    $por_sucursal,
    $envio_activo,
    $id_envio,
    $id_articulo_get
);

if (!$modo_test) {
    $id_control_etiquetado = crear_control_etiquetado(
        $conexion,
        (int) $usuario_id,
        $sucursal_control_etiquetado,
        $id_envio,
        $tipo_control_etiquetado,
        $total_etiquetas
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label Joyeria</title>
    <style>

        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            color: black;
            margin: 0;
            padding: 0;
            text-align: center;
            background: #cccccc;
        }
    <?php if (!$modo_test) { ?>

        main {
            display: none;
        }

    <?php } else { ?>

         main {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 2.95cm;
                height: 1.45cm;
                overflow: hidden;
                page-break-after: always;
            }

    <?php } ?>
        .main_span {
            flex: 1;
            text-align: center;
            padding: 0px;
        }
        .sku {
            font-size: 10px;
            letter-spacing: -1px;
        }
        img {
          width: 32px;
        }
        .price {
            font-size: initial;
            letter-spacing: -1px;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
                text-align: center;
                background: none;
            }

            main {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 3.4cm;
                height: 1.15cm;
                overflow: hidden;
                page-break-after: always;
            }

            .price {
                font-size: 10pt;
                letter-spacing: -0.2pt;
            }
            .sku_title {
                font-size: 7pt;
                letter-spacing: -0.2pt;
            }
            .sku {
                font-size: 10pt;
                letter-spacing: -0.2pt;
            }
            @page {
                size: 3.5cm 1.2cm;
                margin: 0;
            }
        }
    </style>
</head>
<body>
<?php
if ($varios === 'true') {
    $sql_varios_base = "
        SELECT id, precio, descripcion, id_sucursal_destino
        FROM articulos_venta
        WHERE estado IN ('noetiquetado_c', 'noetiquetado_u')
    ";
    $stmt = null;
    if ($por_sucursal > 0) {
        $sql_varios = $sql_varios_base . ' AND id_sucursal_destino = ?';
        $stmt = mysqli_prepare($conexion, $sql_varios);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $por_sucursal);
        }
    } else {
        $stmt = mysqli_prepare($conexion, $sql_varios_base);
    }

    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $res_varios = mysqli_stmt_get_result($stmt);
        while ($res_varios && ($rsItem = mysqli_fetch_assoc($res_varios))) {
            $id_articulo = (int) $rsItem['id'];
            $precio_PARSET = $rsItem['precio'];
            $precio = (int) $precio_PARSET;
            $descripcion = $rsItem['descripcion'];
            $id_sucursal_traz = (int) $rsItem['id_sucursal_destino'];
            if ($id_sucursal_traz <= 0) {
                $id_sucursal_traz = $sucursal_control_etiquetado;
            }

            ?>

            <main>
                <span class="main_span">
                    <span class="price">€<?php echo htmlspecialchars((string) $precio, ENT_QUOTES, 'UTF-8'); ?></span>
                </span>
                <span class="main_span" style="border-left: 1px dotted #ddd;">
                    <span class="sku_title">SKU</span><br>
                    <span class="sku"><?php echo htmlspecialchars((string) $id_articulo, ENT_QUOTES, 'UTF-8'); ?></span>
                    <!-- <span class="logo"><img src="../../img/icono_etiqueta.svg" alt=""/></span> -->
                </span>
            </main>

            <?php

            if (!$modo_test) {

                $stmt_up = mysqli_prepare($conexion, "UPDATE articulos_venta SET estado = 'enventa', update_register = CURDATE() WHERE id = ?");
                if ($stmt_up) {
                    mysqli_stmt_bind_param($stmt_up, 'i', $id_articulo);
                    mysqli_stmt_execute($stmt_up);
                    mysqli_stmt_close($stmt_up);
                }

                $stmt_up_rel = mysqli_prepare(
                    $conexion,
                    "UPDATE rel_articulos_estados SET estado_articulo = 'enventa' WHERE rel_id_articulo_venta = ?"
                );
                if ($stmt_up_rel) {
                    mysqli_stmt_bind_param($stmt_up_rel, 'i', $id_articulo);
                    mysqli_stmt_execute($stmt_up_rel);
                    mysqli_stmt_close($stmt_up_rel);
                }

                $comentarios_accion = 'Artículo puesto a la venta al imprimir desde impresión por lotes por el usuario: ' . $usuario . ' ';
                $accion_trazabilidad = 'enventa';
                trazabilidad_articulos_venta(0, $usuario_id, $accion_trazabilidad, $comentarios_accion, $id_sucursal_traz, $id_articulo, 0);

                $comentarios_accion = 'Etiqueta impresa desde central por lotes por el usuario: ' . $usuario . ' ';
                $accion_trazabilidad = 'etiqueta_reimpresa';
                trazabilidad_articulos_venta(0, $usuario_id, $accion_trazabilidad, $comentarios_accion, $id_sucursal_traz, $id_articulo, 0);

                insert_etiquetas_control_etiquetado($conexion, $id_control_etiquetado, $id_articulo, $precio_PARSET, $descripcion, $tipo_control_etiquetado);
            }
        }
        if ($res_varios) {
            mysqli_free_result($res_varios);
        }
        mysqli_stmt_close($stmt);
    }
} elseif ($envio_activo) {

    $sql_envio = "
        SELECT DISTINCT t.id_articulo_venta, a.precio, a.descripcion
        FROM trazabilidad_articulos t
        INNER JOIN articulos_venta a ON a.id = t.id_articulo_venta
        WHERE t.envio_id = ? AND t.accion_trazabilidad = 'pasado_stock'
    ";
    $stmt = mysqli_prepare($conexion, $sql_envio);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id_envio);
        mysqli_stmt_execute($stmt);
        $res_envio = mysqli_stmt_get_result($stmt);
        while ($res_envio && ($rsItem = mysqli_fetch_assoc($res_envio))) {
            $id_articulo = (int) $rsItem['id_articulo_venta'];
            $precio_PARSET = $rsItem['precio'];
            $precio = (int) $precio_PARSET;
            $descripcion = $rsItem['descripcion'];
            ?>

            <main>
                <span class="main_span">
                     <span class="price">€<?php echo htmlspecialchars((string) $precio, ENT_QUOTES, 'UTF-8'); ?></span>
                </span>
                <span class="main_span" style="border-left: 1px dotted #ddd;">
                <span class="sku_title">SKU</span><br>
                    <span class="sku"><?php echo htmlspecialchars((string) $id_articulo, ENT_QUOTES, 'UTF-8'); ?></span>
                    <!-- <span class="logo"><img src="../../img/icono_etiqueta.svg" alt=""/></span> -->
                </span>
            </main>

            <?php
            if (!$modo_test) {
                insert_etiquetas_control_etiquetado($conexion, $id_control_etiquetado, $id_articulo, $precio_PARSET, $descripcion, $tipo_control_etiquetado);
            }
        }
        if ($res_envio) {
            mysqli_free_result($res_envio);
        }
        mysqli_stmt_close($stmt);
    }
} else {

    $sql_one = 'SELECT id, precio, descripcion, id_sucursal_destino FROM articulos_venta WHERE id = ? LIMIT 1';
    $stmt = mysqli_prepare($conexion, $sql_one);
    $rsItem = null;
    $res_one = null;
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id_articulo_get);
        mysqli_stmt_execute($stmt);
        $res_one = mysqli_stmt_get_result($stmt);
        $rsItem = $res_one ? mysqli_fetch_assoc($res_one) : null;
        if ($res_one) {
            mysqli_free_result($res_one);
        }
        mysqli_stmt_close($stmt);
    }

    if ($rsItem) {
        $id_articulo = (int) $rsItem['id'];
        $precio_PARSET = $rsItem['precio'];
        $precio = (int) $precio_PARSET;
        $descripcion = $rsItem['descripcion'];
        $id_sucursal_traz = (int) $rsItem['id_sucursal_destino'];
        if ($id_sucursal_traz <= 0) {
            $id_sucursal_traz = $sucursal_control_etiquetado;
        }
        ?>

        <main>
            <span class="main_span">
                <span class="price">€<?php echo htmlspecialchars((string) $precio, ENT_QUOTES, 'UTF-8'); ?></span>
            </span>
            <span class="main_span" style="border-left: 1px dotted #ddd;">
                <span class="sku_title">SKU</span><br>
                <span class="sku"><?php echo htmlspecialchars((string) $id_articulo, ENT_QUOTES, 'UTF-8'); ?></span>
                <!-- <span class="logo"><img src="../../img/icono_etiqueta.svg" alt=""/></span> -->
            </span>
        </main>

        <?php
        if (!$modo_test) {

            if ($reimprimir === 'true') {
                $comentarios_accion = 'Se reimprime la etiqueta de artículo desde impresión individual por el usuario: ' . $usuario . ' ';
                $accion_trazabilidad = 'etiqueta_reimpresa';
            } else {
                $stmt_up = mysqli_prepare($conexion, "UPDATE articulos_venta SET estado = 'enventa', update_register = CURDATE() WHERE id = ?");
                if ($stmt_up) {
                    mysqli_stmt_bind_param($stmt_up, 'i', $id_articulo);
                    mysqli_stmt_execute($stmt_up);
                    mysqli_stmt_close($stmt_up);
                }

                $stmt_up_rel = mysqli_prepare(
                    $conexion,
                    "UPDATE rel_articulos_estados SET estado_articulo = 'enventa' WHERE rel_id_articulo_venta = ?"
                );
                if ($stmt_up_rel) {
                    mysqli_stmt_bind_param($stmt_up_rel, 'i', $id_articulo);
                    mysqli_stmt_execute($stmt_up_rel);
                    mysqli_stmt_close($stmt_up_rel);
                }

                $comentarios_accion = 'Artículo puesto a la venta al imprimir desde impresión individual por el usuario: ' . $usuario . ' ';
                $accion_trazabilidad = 'enventa';
            }

            trazabilidad_articulos_venta(0, $usuario_id, $accion_trazabilidad, $comentarios_accion, $id_sucursal_traz, $id_articulo, 0);

            insert_etiquetas_control_etiquetado($conexion, $id_control_etiquetado, $id_articulo, $precio_PARSET, $descripcion, $tipo_control_etiquetado);
        }
    }
}

mysqli_close($conexion);
?>

<script src="../../assets/vendor/libs/jquery/jquery.js"></script>

<?php if (!$modo_test) { ?>
    <script type="text/javascript">

        (function () {
            if (window.__etiquetaPrintDisparado) {
                return;
            }
            window.__etiquetaPrintDisparado = true;

            function notificarYCerrar() {
                try {
                    if (window.opener && !window.opener.closed) {
                        window.opener.postMessage({ type: 'etiqueta:printed' }, window.location.origin);
                        try { window.opener.location.reload(); } catch (e) {}
                    }
                } catch (e) {}
                try { window.close(); } catch (e) {}
            }

            function CheckWindowState() {
                if (document.readyState === 'complete') {
                    notificarYCerrar();
                } else {
                    setTimeout(CheckWindowState, 10);
                }
            }

            function PrintWindow() {
                try {
                    window.print();
                } catch (e) {}
                CheckWindowState();
            }

            PrintWindow();
        })();

    </script>
<?php } ?>

</body>
</html>
