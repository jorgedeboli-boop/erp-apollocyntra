<?php
/**
 * modal_ia_chat.php — UI del asistente IA
 * Incluir: <?php include 'parts/agentai/modal_ia_chat.php'; ?>
 */
require_once __DIR__ . '/../../camera/render-config-script.php';

$__ia_css_v = @filemtime(__DIR__ . '/css/modal_ia_chat.css') ?: time();
$__ia_js_v  = @filemtime(__DIR__ . '/js/modal_ia_chat.js') ?: time();

// Misma base que la página (evita POST de export a /parts/... sin prefijo de app)
$__sn = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
$__p  = strpos($__sn, '/parts/');
$ia_chat_ajax_url = ($__p !== false)
    ? (substr($__sn, 0, $__p) . '/parts/agentai/ajax_ia_chat.php')
    : '/parts/agentai/ajax_ia_chat.php';
$ia_cam_js_qrcode = ($__p !== false) ? (substr($__sn, 0, $__p) . '/js/qrcode.min.js') : 'js/qrcode.min.js';
$ia_cam_js_qr = ($__p !== false) ? (substr($__sn, 0, $__p) . '/camera/js/camera-qr.js') : 'camera/js/camera-qr.js';
$ia_cam_js_dp = ($__p !== false) ? (substr($__sn, 0, $__p) . '/camera/js/camera-doc-panel.js') : 'camera/js/camera-doc-panel.js';
?>
<link rel="stylesheet" href="parts/agentai/css/modal_ia_chat.css?v=<?php echo (int) $__ia_css_v; ?>" />

<!-- ===================== MODAL ===================== -->
<div class="modal fade" id="modalIAChat" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalIAChatLabel" aria-hidden="true"
  data-ia-camera-usuario-id="<?php echo (int) (isset($usuario_id) && $usuario_id !== '' ? $usuario_id : 0); ?>"
  data-ia-camera-sucursal="<?php echo (int) (isset($usuario_sucursal) && $usuario_sucursal !== '' ? $usuario_sucursal : 0); ?>">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down" role="document">
    <div class="modal-content">

      <!-- CABECERA -->
      <div class="modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2" id="modalIAChatLabel">
          <span class="ia-chat-brand-logo d-inline-flex align-items-center flex-shrink-0" aria-hidden="true">
            <svg class="ia-chat-brand-svg" id="Capa_1" data-name="Capa 1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 333.25 43.94"><defs><linearGradient id="Degradado_sin_nombre_2" x1="-451.88" y1="509.69" x2="-450.84" y2="508.65" gradientTransform="matrix(40.33, 0, 0, -40.28, 18225.97, 20534.02)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#396fb6"/><stop offset="0.5" stop-color="#448ccb"/><stop offset="1" stop-color="#43a8df"/></linearGradient><linearGradient id="Degradado_sin_nombre_2-2" x1="-451.29" y1="509.72" x2="-450.25" y2="508.68" gradientTransform="matrix(35.95, 0, 0, -40.28, 16262.64, 20534.06)" xlink:href="#Degradado_sin_nombre_2"/><linearGradient id="Degradado_sin_nombre_2-3" x1="105.36" y1="52.21" x2="83.58" y2="14.48" gradientTransform="matrix(1, 0, 0, 1, 0, 0)" xlink:href="#Degradado_sin_nombre_2"/><linearGradient id="Degradado_sin_nombre_2-4" x1="139.17" y1="44.11" x2="119.35" y2="9.79" gradientTransform="matrix(1, 0, 0, 1, 0, 0)" xlink:href="#Degradado_sin_nombre_2"/><linearGradient id="Degradado_sin_nombre_2-5" x1="155.45" y1="43.69" x2="141.46" y2="19.46" gradientTransform="matrix(1, 0, 0, 1, 0, 0)" xlink:href="#Degradado_sin_nombre_2"/><linearGradient id="Degradado_sin_nombre_2-6" x1="177.4" y1="44.11" x2="157.59" y2="9.79" gradientTransform="matrix(1, 0, 0, 1, 0, 0)" xlink:href="#Degradado_sin_nombre_2"/><linearGradient id="Degradado_sin_nombre_2-7" x1="208.47" y1="40.01" x2="187.54" y2="3.76" gradientTransform="matrix(1, 0, 0, 1, 0, 0)" xlink:href="#Degradado_sin_nombre_2"/><linearGradient id="Degradado_sin_nombre_2-8" x1="236.2" y1="46.69" x2="213.38" y2="7.16" gradientTransform="matrix(1, 0, 0, 1, 0, 0)" xlink:href="#Degradado_sin_nombre_2"/><linearGradient id="Degradado_sin_nombre_2-9" x1="269.57" y1="48.3" x2="244.89" y2="5.54" gradientTransform="matrix(1, 0, 0, 1, 0, 0)" xlink:href="#Degradado_sin_nombre_2"/><linearGradient id="Degradado_sin_nombre_2-10" x1="304.27" y1="40.01" x2="283.34" y2="3.76" gradientTransform="matrix(1, 0, 0, 1, 0, 0)" xlink:href="#Degradado_sin_nombre_2"/><linearGradient id="Degradado_sin_nombre_2-11" x1="332" y1="46.69" x2="309.18" y2="7.16" gradientTransform="matrix(1, 0, 0, 1, 0, 0)" xlink:href="#Degradado_sin_nombre_2"/></defs><title>logotipo_chat_ia</title><path d="M21.56,5a20.95,20.95,0,1,0,21,21A21,21,0,0,0,21.56,5Zm0,34.32a13.38,13.38,0,0,1,0-26.75,13.38,13.38,0,1,1,0,26.75Z" transform="translate(-0.62 -4.96)" style="fill:url(#Degradado_sin_nombre_2)"/><path d="M35.11,45.14a3.79,3.79,0,0,1,7.57,0,3.79,3.79,0,0,1-7.57,0Z" transform="translate(-0.62 -4.96)" style="fill:#448ccb"/><path d="M56.33,5c3.82,0,9.17.74,13.49,4.27a3.86,3.86,0,0,1,.51,5.29c-1.13,1.6-3.41,1.37-5.23.4a19,19,0,0,0-8.77-2.39,13.38,13.38,0,0,0,0,26.75A19.37,19.37,0,0,0,65.27,37V29.09a3.76,3.76,0,0,1,7.51,0V38a5.85,5.85,0,0,1-.4,2.67,4.7,4.7,0,0,1-1.65,1.71,26.57,26.57,0,0,1-14.4,4.44A20.95,20.95,0,0,1,56.33,5Z" transform="translate(-0.62 -4.96)" style="fill:url(#Degradado_sin_nombre_2-2)"/><path d="M57.76,25.34a3.78,3.78,0,1,1-3.82,3.75A3.78,3.78,0,0,1,57.76,25.34Z" transform="translate(-0.62 -4.96)" style="fill:#448ccb"/><path d="M86.61,45a3.62,3.62,0,0,1-2,1.92,3.49,3.49,0,0,1-2.83-.09,3.6,3.6,0,0,1-1.91-2A3.47,3.47,0,0,1,79.94,42L94.88,8.83a3.68,3.68,0,0,1,6.68,0L116.5,42a3.47,3.47,0,0,1,.09,2.82,3.55,3.55,0,0,1-1.94,2,3.42,3.42,0,0,1-2.8.09,3.68,3.68,0,0,1-2-1.92l-2.2-4.93-.08,0H88.84ZM104.3,32.76,98.22,19.27,92.14,32.76Z" transform="translate(-0.62 -4.96)" style="fill:url(#Degradado_sin_nombre_2-3)"/><path d="M137.22,25.83c3.6,2.36,5.22,5.9,4.91,10.58a11.32,11.32,0,0,1-4.48,8.41,13.66,13.66,0,0,1-9.81,2.8A13.25,13.25,0,0,1,119,43.71a3.56,3.56,0,0,1-1.09-2.6A3.67,3.67,0,0,1,119,38.52a3.67,3.67,0,0,1,5.19,0,6.24,6.24,0,0,0,4.13,1.77,6.8,6.8,0,0,0,4.51-1.06,4.23,4.23,0,0,0,1.94-3.36,5,5,0,0,0-.4-2.66A3.72,3.72,0,0,0,133.2,32,8.44,8.44,0,0,0,129,30.59a13.71,13.71,0,0,1-9.36-4.16,11.27,11.27,0,0,1-3.22-9,11.29,11.29,0,0,1,4.48-8.42,13.82,13.82,0,0,1,9.81-2.82A13.07,13.07,0,0,1,136,7.66a15.4,15.4,0,0,1,3.48,2.54,3.65,3.65,0,0,1,.17,5.19,3.48,3.48,0,0,1-2.54,1.14,3.61,3.61,0,0,1-2.65-1,6.71,6.71,0,0,0-4.22-2,6.79,6.79,0,0,0-4.51,1A4.28,4.28,0,0,0,123.76,18a4.24,4.24,0,0,0,1.46,3.63,6.87,6.87,0,0,0,4.3,1.68h0A15.36,15.36,0,0,1,137.22,25.83Z" transform="translate(-0.62 -4.96)" style="fill:url(#Degradado_sin_nombre_2-4)"/><path d="M148.47,16a3.71,3.71,0,0,1,3.65,3.68V43.48a3.71,3.71,0,0,1-3.65,3.68,3.69,3.69,0,0,1-3.68-3.68V19.67A3.69,3.69,0,0,1,148.47,16Z" transform="translate(-0.62 -4.96)" style="fill:url(#Degradado_sin_nombre_2-5)"/><path d="M175.46,25.83c3.59,2.36,5.22,5.9,4.91,10.58a11.32,11.32,0,0,1-4.48,8.41,13.66,13.66,0,0,1-9.81,2.8,13.29,13.29,0,0,1-8.82-3.91,3.59,3.59,0,0,1-1.08-2.6,3.71,3.71,0,0,1,1.08-2.59,3.57,3.57,0,0,1,2.6-1.06,3.5,3.5,0,0,1,2.59,1.09,6.28,6.28,0,0,0,4.14,1.77,6.8,6.8,0,0,0,4.51-1.06A4.22,4.22,0,0,0,173,35.9a4.93,4.93,0,0,0-.39-2.66,3.72,3.72,0,0,0-1.2-1.28,8.48,8.48,0,0,0-4.2-1.37,13.68,13.68,0,0,1-9.35-4.16,11.24,11.24,0,0,1-3.22-9,11.28,11.28,0,0,1,4.47-8.42A13.83,13.83,0,0,1,169,6.23a13,13,0,0,1,5.24,1.43,15.19,15.19,0,0,1,3.48,2.54,3.43,3.43,0,0,1,1.17,2.56,3.64,3.64,0,0,1-3.53,3.77,3.65,3.65,0,0,1-2.66-1,7.09,7.09,0,0,0-8.72-1A4.26,4.26,0,0,0,162,18a4.24,4.24,0,0,0,1.45,3.63,6.9,6.9,0,0,0,4.31,1.68h0A15.36,15.36,0,0,1,175.46,25.83Z" transform="translate(-0.62 -4.96)" style="fill:url(#Degradado_sin_nombre_2-6)"/><path d="M206.73,6.69a3.59,3.59,0,0,1,2.6,1.08,3.54,3.54,0,0,1,1.08,2.6,3.5,3.5,0,0,1-1.08,2.56,3.56,3.56,0,0,1-2.6,1.09h-8V43.48a3.48,3.48,0,0,1-1.06,2.6,3.68,3.68,0,0,1-6.27-2.6V14h-8a3.71,3.71,0,0,1-3.68-3.65,3.69,3.69,0,0,1,3.68-3.68Z" transform="translate(-0.62 -4.96)" style="fill:url(#Degradado_sin_nombre_2-7)"/><path d="M218.85,14v9.15h11.81a3.72,3.72,0,0,1,3.68,3.65,3.7,3.7,0,0,1-3.68,3.68H218.85v9.33h15.54a3.48,3.48,0,0,1,2.6,1.06,3.68,3.68,0,0,1-2.6,6.27H215.2a3.69,3.69,0,0,1-3.68-3.68V10.37a3.69,3.69,0,0,1,3.68-3.68h19.19a3.69,3.69,0,0,1,3.68,3.68A3.5,3.5,0,0,1,237,12.93a3.51,3.51,0,0,1-2.6,1.09Z" transform="translate(-0.62 -4.96)" style="fill:url(#Degradado_sin_nombre_2-8)"/><path d="M270.54,6.69a3.6,3.6,0,0,1,3.65,3.65V43.48a3.83,3.83,0,0,1-.66,2.14A3.61,3.61,0,0,1,271.76,47a3.66,3.66,0,0,1-2.22.06,3.42,3.42,0,0,1-1.85-1.23l-20.11-25v22.7a3.67,3.67,0,1,1-7.33,0V10.34a3.5,3.5,0,0,1,.68-2.11,3.44,3.44,0,0,1,1.77-1.34,3.65,3.65,0,0,1,2.22-.06,3.56,3.56,0,0,1,1.86,1.23l20.08,25V10.34a3.64,3.64,0,0,1,3.68-3.65Z" transform="translate(-0.62 -4.96)" style="fill:url(#Degradado_sin_nombre_2-9)"/><path d="M302.53,6.69a3.61,3.61,0,0,1,2.6,1.08,3.54,3.54,0,0,1,1.08,2.6,3.5,3.5,0,0,1-1.08,2.56,3.58,3.58,0,0,1-2.6,1.09h-8V43.48a3.52,3.52,0,0,1-1.06,2.6,3.66,3.66,0,0,1-5.19,0,3.55,3.55,0,0,1-1.09-2.6V14h-7.95a3.51,3.51,0,0,1-2.6-1.09,3.5,3.5,0,0,1-1.08-2.56,3.69,3.69,0,0,1,3.68-3.68Z" transform="translate(-0.62 -4.96)" style="fill:url(#Degradado_sin_nombre_2-10)"/><path d="M314.64,14v9.15h11.81a3.66,3.66,0,0,1,2.6,6.25,3.54,3.54,0,0,1-2.6,1.08H314.64v9.33h15.55a3.46,3.46,0,0,1,2.59,1.06,3.68,3.68,0,0,1-2.59,6.27H311a3.7,3.7,0,0,1-3.68-3.68V10.37A3.7,3.7,0,0,1,311,6.69h19.2a3.52,3.52,0,0,1,2.59,1.08,3.51,3.51,0,0,1,1.09,2.6,3.47,3.47,0,0,1-1.09,2.56A3.49,3.49,0,0,1,330.19,14Z" transform="translate(-0.62 -4.96)" style="fill:url(#Degradado_sin_nombre_2-11)"/><path d="M148.48,6.19a3.78,3.78,0,1,1-3.8,3.75A3.78,3.78,0,0,1,148.48,6.19Z" transform="translate(-0.62 -4.96)" style="fill:#448ccb"/></svg>
          </span>
        </h5>
        <button type="button" id="iaChatNuevaConv" class="btn btn-link ia-chat-nueva shadow-none text-decoration-none" title="Nueva conversación" aria-label="Nueva conversación">
          <i class="icon-base ri ri-add-line"></i>
        </button>
        <div class="dropdown ia-chat-hist-dropdown">
          <button type="button" id="iaChatHistToggle" class="btn btn-link ia-chat-hist shadow-none text-decoration-none dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false" title="Conversaciones guardadas" aria-label="Conversaciones guardadas">
            <i class="icon-base ri ri-chat-history-line"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end ia-chat-hist-menu" id="iaChatHistMenu">
            <li><span class="dropdown-item-text text-muted small">Sin conversaciones</span></li>
          </ul>
        </div>
        <button type="button" id="iaChatFullscreen" class="btn btn-link ia-chat-fullscreen shadow-none text-decoration-none" title="Pantalla completa" aria-label="Pantalla completa" aria-pressed="false">
          <i class="icon-base ri ri-fullscreen-line"></i>
        </button>
        <button type="button" id="iaChatTts" class="btn btn-link ia-chat-tts shadow-none text-decoration-none" title="Activar respuesta hablada" aria-label="Activar respuesta hablada" aria-pressed="false">
          <i class="icon-base ri ri-volume-up-line"></i>
        </button>
        <button type="button" class="btn btn-link ia-chat-close shadow-none text-decoration-none" data-bs-dismiss="modal" aria-label="Cerrar">
          <i class="ri ri-close-line fs-3 lh-1"></i>
        </button>
      </div>

      <!-- CUERPO -->
      <div class="modal-body">

        <!-- Zona de mensajes -->
        <div id="iaChatMensajes" class="ia-chat-mensajes">

          <!-- Mensaje de bienvenida -->
          <div class="ia-msg ia-msg-bot">
            <div class="ia-msg-burbuja">
              👋 Hola, <span class="ia-chat-nombre-usuario"><?php echo $usuario; ?></span> <br> ¿Qué quieres saber?
            </div>
          </div>

        </div>

        <!-- Input de mensaje -->
        <div class="ia-chat-input-area">
          <div class="ia-chat-field-wrap">
            <div class="ia-chat-field-row">
              <div class="btn-group ia-chat-adjunto-toggle d-none" id="iaChatAdjuntoToggle" aria-hidden="true">
                <button type="button"
                  class="btn btn-primary btn-icon rounded-pill dropdown-toggle hide-arrow waves-effect"
                  data-bs-toggle="dropdown"
                  aria-expanded="false"
                  aria-label="Adjuntar imagen"
                  title="Adjuntar">
                  <i class="icon-base ri ri-add-line icon-28px"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="javascript:void(0);" onclick="iaChatAdjuntoSubirFoto();">Subir foto</a></li>
                  <li><a class="dropdown-item" href="javascript:void(0);" onclick="iaChatAdjuntoHacerFoto();">Hacer foto</a></li>
                </ul>
              </div>
              <label for="iaChatInput" class="visually-hidden">Consulta en lenguaje natural</label>
              <input
                type="text"
                id="iaChatInput"
                class="form-control border-0 shadow-none ia-chat-field"
                placeholder="Ej. utilidad de marzo, ¿cuánto hemos ganado?, beneficios entre dos fechas…"
                autocomplete="off"
              >
            </div>
            <div class="ia-chat-actions-row">
              <button type="button" id="iaChatMic" class="ia-chat-mic" title="Dictar por voz" aria-label="Dictar por voz" aria-pressed="false">
                <i class="icon-base ri ri-mic-fill"></i>
              </button>
              <button type="button" id="iaChatEnviar" onclick="iaChatEnviar()" class="ia-chat-send ia-btn-enviar" title="Enviar">
                <i class="icon-base ri ri-arrow-up-line"></i>
              </button>
            </div>
          </div>
        </div>

      </div><!-- /modal-body -->

      <!-- PIE
      <div class="modal-footer">
        <small class="text-muted me-auto">
          <i class="ti ti-database me-1"></i> Datos en tiempo real de tu base de datos
        </small>
        <button type="button" class="btn btn-outline-secondary waves-effect" data-bs-dismiss="modal">Cerrar</button>
      </div>
 -->
    </div>
  </div>
</div>

<!-- Modal subir documento / foto (mismo criterio visual que lotes; nombre universal) -->
<div class="modal fade" id="modalSubirFotoUniversal" tabindex="-1" aria-labelledby="modalSubirFotoUniversalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSubirFotoUniversalLabel">Subir documento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formSubirFotoUniversal" enctype="multipart/form-data">
          <div class="mb-3">
            <label for="archivo_foto_universal" class="form-label">Seleccionar archivo</label>
            <input type="file" class="form-control" id="archivo_foto_universal" name="archivo_foto" accept=".jpg,.jpeg,.gif,.png,.webp,.WEBP,.pdf,.PDF,.JPG,.JPEG,.GIF,.PNG" required>
            <div class="form-text">Formatos permitidos: JPG, JPEG, GIF, PNG, WEBP, PDF (máximo 5 MB).</div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="iaChatSubirFotoUniversalConfirmar()">
          <span class="spinner-border spinner-border-sm me-2 d-none" id="iaChatSubirFotoUniversalSpinner" role="status" aria-hidden="true"></span>
          Subir
        </button>
      </div>
    </div>
  </div>
</div>


<script>
var IA_CHAT_URL = <?php echo json_encode($ia_chat_ajax_url); ?>;
var IA_CHAT_USUARIO_ID = <?php echo (int) (isset($usuario_id) && $usuario_id !== '' ? $usuario_id : 0); ?>;
var IA_CHAT_USUARIO_NOMBRE = <?php echo json_encode(isset($usuario) ? (string) $usuario : '', JSON_UNESCAPED_UNICODE); ?>;
var IA_CAM_JS_QRCODE = <?php echo json_encode($ia_cam_js_qrcode); ?>;
var IA_CAM_JS_QR = <?php echo json_encode($ia_cam_js_qr); ?>;
var IA_CAM_JS_DP = <?php echo json_encode($ia_cam_js_dp); ?>;
</script>
<script src="parts/agentai/js/modal_ia_chat.js?v=<?php echo (int) $__ia_js_v; ?>"></script>
