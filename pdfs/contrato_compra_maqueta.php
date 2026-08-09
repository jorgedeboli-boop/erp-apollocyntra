<?php
/**
 * Vista previa HTML del contrato de compra.
 * Uso: contrato_compra_maqueta.php?id_lote=1408&id_sucursal=22
 *      contrato_compra_maqueta.php?id_lote=1408  (detecta sucursal automáticamente)
 */
require_once '../include/session.php';
require_once '../include/functions.php';
require_once __DIR__ . '/contrato_compra_datos.php';
require_once __DIR__ . '/contrato_compra_plantilla.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Location: ../dashboard.php');
    exit;
}

$id_lote = isset($_GET['id_lote']) ? (int) $_GET['id_lote'] : 0;
$id_sucursal = isset($_GET['id_sucursal']) ? (int) $_GET['id_sucursal'] : 0;

if ($id_lote <= 0) {
    header('HTTP/1.0 400 Bad Request');
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="es"><body style="font-family:arial;padding:20px;">';
    echo '<h1>Parámetro requerido</h1>';
    echo '<p>Indica el lote en la URL, por ejemplo:</p>';
    echo '<p><code>contrato_compra_maqueta.php?id_lote=1408&amp;id_sucursal=22</code></p>';
    echo '<p>También puedes omitir <code>id_sucursal</code> y se buscará automáticamente:</p>';
    echo '<p><code>contrato_compra_maqueta.php?id_lote=1408</code></p>';
    echo '</body></html>';
    exit;
}

try {
    $conexion = conectar_bd();
    $datos = contrato_compra_cargar_datos($conexion, $id_lote, $id_sucursal);
    mysqli_close($conexion);

    if ($datos === null) {
        header('HTTP/1.0 404 Not Found');
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="es"><body style="font-family:arial;padding:20px;">';
        echo '<h1>Lote no encontrado</h1>';
        echo '<p>No se encontró el lote <strong>' . htmlspecialchars((string) $id_lote, ENT_QUOTES, 'UTF-8') . '</strong>';
        if ($id_sucursal > 0) {
            echo ' en la sucursal <strong>' . htmlspecialchars((string) $id_sucursal, ENT_QUOTES, 'UTF-8') . '</strong>';
        }
        echo '.</p>';
        echo '<p>Comprueba que el <code>id_sucursal</code> sea el ID numérico de la sucursal (no el nombre).</p>';
        echo '</body></html>';
        exit;
    }

    header('Content-Type: text/html; charset=UTF-8');
    echo contrato_compra_plantilla_maqueta($datos);
} catch (Exception $e) {
    header('HTTP/1.0 500 Internal Server Error');
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="es"><body style="font-family:arial;padding:20px;">';
    echo '<h1>Error al cargar la maqueta</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p><small>' . htmlspecialchars($e->getFile() . ':' . $e->getLine(), ENT_QUOTES, 'UTF-8') . '</small></p>';
    echo '</body></html>';
}
