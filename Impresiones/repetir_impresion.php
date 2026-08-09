<?php
/**
 * Reimpresión de etiquetas desde etiquetas_control_etiquetado.
 *
 * GET:
 * - id_control_etiquetado: imprime todas las etiquetas del control.
 * - id_etiqueta: imprime una etiqueta concreta.
 */

require_once __DIR__ . '/../include/session.php';
require_once __DIR__ . '/../include/functions.php';

$id_control_etiquetado = isset($_GET['id_control_etiquetado']) ? (int) $_GET['id_control_etiquetado'] : 0;
$id_etiqueta = isset($_GET['id_etiqueta']) ? (int) $_GET['id_etiqueta'] : 0;

if ($id_control_etiquetado <= 0 && $id_etiqueta <= 0) {
    http_response_code(400);
    exit('Parámetros no válidos');
}

$conexion = conectar_bd();
if (!$conexion || !($conexion instanceof mysqli)) {
    http_response_code(500);
    exit('Error de conexión');
}

mysqli_set_charset($conexion, 'utf8mb4');

$etiquetas = array();

if ($id_etiqueta > 0) {
    $sql = '
        SELECT id_etiqueta, rel_sku_etiquetado, precio_sku, descripcion_sku
        FROM etiquetas_control_etiquetado
        WHERE id_etiqueta = ?
        LIMIT 1
    ';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id_etiqueta);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($res && ($row = mysqli_fetch_assoc($res))) {
            $etiquetas[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $sql = '
        SELECT id_etiqueta, rel_sku_etiquetado, precio_sku, descripcion_sku
        FROM etiquetas_control_etiquetado
        WHERE rel_id_control_etiquetado = ?
        ORDER BY id_etiqueta ASC
    ';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id_control_etiquetado);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($res && ($row = mysqli_fetch_assoc($res))) {
            $etiquetas[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($conexion);

if (empty($etiquetas)) {
    http_response_code(404);
    exit('No se encontraron etiquetas para reimprimir');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reimpresión etiquetas</title>
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
        main {
            display: none;
        }
        .main_span {
            flex: 1;
            text-align: center;
            padding: 0;
        }
        .sku {
            font-size: 10px;
            letter-spacing: -1px;
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
<?php foreach ($etiquetas as $item) {
    $id_articulo = (int) ($item['rel_sku_etiquetado'] ?? 0);
    $precio = (int) ($item['precio_sku'] ?? 0);
    ?>
    <main>
        <span class="main_span">
            <span class="price">€<?php echo htmlspecialchars((string) $precio, ENT_QUOTES, 'UTF-8'); ?></span>
        </span>
        <span class="main_span" style="border-left: 1px dotted #ddd;">
            <span class="sku_title">SKU</span><br>
            <span class="sku"><?php echo htmlspecialchars((string) $id_articulo, ENT_QUOTES, 'UTF-8'); ?></span>
        </span>
    </main>
<?php } ?>

<script src="../assets/vendor/libs/jquery/jquery.js"></script>
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
</body>
</html>
