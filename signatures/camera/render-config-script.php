<?php
/**
 * Emite <script> con window.CAMERA_QR para páginas del TPV.
 * Incluir ANTES de camera/js/camera-qr.js (tras bootstrap/jQuery si aplica).
 *
 * Ejemplo (desde parts/lotes/main/…):
 *   <?php require __DIR__ . '/../../../camera/render-config-script.php'; ?>
 *   <script src="../../../camera/js/camera-qr.js"></script>
 */
require_once __DIR__ . '/bootstrap.php';

if (!defined('CAMERA_RENDER_CONFIG_DONE')) {
    define('CAMERA_RENDER_CONFIG_DONE', true);
    $cameraQrConfig = camera_qr_default_js_config();
    ?>
<script>
window.CAMERA_QR = Object.assign({}, window.CAMERA_QR || {}, <?php echo json_encode($cameraQrConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>);
</script>
    <?php
}
?>