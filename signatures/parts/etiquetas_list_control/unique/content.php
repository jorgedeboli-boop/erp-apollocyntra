<?php
$id_control_etiquetado = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$titulo_control = 'Control de etiquetado';
$info_control = '';
$url_imprimir_todo = '';
$total_etiquetas_control = 0;
$mostrar_info_envio = false;
$id_envio_control = 0;
$nombre_sucursal_envio = '';
$tipoTxt = '';

if ($id_control_etiquetado > 0) {
    $url_imprimir_todo = 'Impresiones/repetir_impresion.php?id_control_etiquetado=' . $id_control_etiquetado;

    $conexion_ctrl = conectar_bd();
    if ($conexion_ctrl) {
        $sql_ctrl = "
            SELECT ce.id_control_etiquetado, ce.fecha_etiquetado, ce.hora_etiquetado,
                   ce.tipo_control_etiquetado, ce.envio_etiquetado, ce.total_etiquetas,
                   u.nombre_usuario, u.usuario, s.nombre_sucursal,
                   s_env.nombre_sucursal AS nombre_sucursal_envio
            FROM control_etiquetado ce
            LEFT JOIN usuarios u ON ce.usuario_etiquetado = u.id_usuario
            LEFT JOIN sucursal s ON ce.sucursal_etiquetado = s.id_sucursal
            LEFT JOIN envios e ON ce.envio_etiquetado = e.id_envio
            LEFT JOIN sucursal s_env ON e.sucursal_remitente = s_env.id_sucursal
            WHERE ce.id_control_etiquetado = ?
            LIMIT 1
        ";
        $stmt_ctrl = mysqli_prepare($conexion_ctrl, $sql_ctrl);
        if ($stmt_ctrl) {
            mysqli_stmt_bind_param($stmt_ctrl, 'i', $id_control_etiquetado);
            mysqli_stmt_execute($stmt_ctrl);
            $res_ctrl = mysqli_stmt_get_result($stmt_ctrl);
            if ($row_ctrl = mysqli_fetch_assoc($res_ctrl)) {
                $fecha = $row_ctrl['fecha_etiquetado'] ?? '';
                $fechaFmt = ($fecha !== '' && $fecha !== '0000-00-00') ? date('d/m/Y', strtotime($fecha)) : '-';
                $hora = $row_ctrl['hora_etiquetado'] ?? '';
                $horaFmt = ($hora !== '' && $hora !== '00:00:00') ? date('H:i', strtotime($hora)) : '-';
                $usuarioTxt = trim((string) ($row_ctrl['nombre_usuario'] ?? ''));
                if ($usuarioTxt === '') {
                    $usuarioTxt = trim((string) ($row_ctrl['usuario'] ?? ''));
                }
                $sucursalTxt = trim((string) ($row_ctrl['nombre_sucursal'] ?? ''));
                $tipoTxt = (string) ($row_ctrl['tipo_control_etiquetado'] ?? '');
                $id_envio_control = (int) ($row_ctrl['envio_etiquetado'] ?? 0);
                $nombre_sucursal_envio = trim((string) ($row_ctrl['nombre_sucursal_envio'] ?? ''));
                if (strtolower($tipoTxt) === 'envio' && $id_envio_control > 0) {
                    $mostrar_info_envio = true;
                }
                $totalEtiquetasTxt = (int) ($row_ctrl['total_etiquetas'] ?? 0);
                $total_etiquetas_control = $totalEtiquetasTxt;
                $titulo_control = 'Etiquetas del control Nº ' . $id_control_etiquetado;
                $info_control = $fechaFmt . ' ' . $horaFmt
                    . ($usuarioTxt !== '' ? ' · ' . $usuarioTxt : '')
                    . ($sucursalTxt !== '' ? ' · ' . $sucursalTxt : '')
                    . ($tipoTxt !== '' ? ' · ' . ucfirst($tipoTxt) : '')
                    . ' · Total etiquetas: ' . $totalEtiquetasTxt;
            }
            mysqli_stmt_close($stmt_ctrl);
        }
        mysqli_close($conexion_ctrl);
    }
}
?>
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="card card-mobile-not-shadow">
    <div class="card-header border-bottom card-header-forms">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 w-100">
        <div>
          <h5 class="card-title mb-0"><?php echo htmlspecialchars($titulo_control, ENT_QUOTES, 'UTF-8'); ?></h5>
          <?php if ($info_control !== '') { ?>
            <small class="text-muted"><?php echo htmlspecialchars($info_control, ENT_QUOTES, 'UTF-8'); ?></small>
          <?php } ?>
          <?php if ($mostrar_info_envio) { ?>
            <small class="text-muted d-block mt-1">
              Nº de envío:
              <a
                href="Envio.php?id=<?php echo (int) $id_envio_control; ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="fw-medium"
              ><?php echo (int) $id_envio_control; ?></a><?php if ($nombre_sucursal_envio !== '') { ?>
              · Sucursal: <?php echo htmlspecialchars($nombre_sucursal_envio, ENT_QUOTES, 'UTF-8'); ?><?php } ?>
            </small>
          <?php } ?>
        </div>
        <div class="d-flex align-items-center flex-wrap gap-2">
          <a href="control_etiquetado.php" class="btn btn-text-primary waves-effect">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Control etiquetado
          </a>
        <?php if ($id_control_etiquetado > 0 && $url_imprimir_todo !== '') { ?>
          <a
            href="<?php echo htmlspecialchars($url_imprimir_todo, ENT_QUOTES, 'UTF-8'); ?>"
            target="_blank"
            class="btn btn-success waves-effect waves-light etiqueta-repetir-print-link-masivo"
            id="btn_repetir_imprimir_todo"
          >
            <i class="icon-base ri ri-printer-line me-1"></i>
            Imprimir todo
          </a>
        <?php } ?>
        </div>
      </div>
    </div>

    <input type="hidden" id="id_control_etiquetado" value="<?php echo (int) $id_control_etiquetado; ?>">
    <input type="hidden" id="total_etiquetas_control" value="<?php echo (int) $total_etiquetas_control; ?>">

    <div class="card-datatable table-responsive pt-0">
      <table class="datatables-etiquetas-list-control table border-top">
        <thead>
          <tr>
            <th>SKU</th>
            <th>Descripción</th>
            <th>Precio</th>
            <th>Fecha control</th>
            <th>Tipo</th>
            <th>Imprimir</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
<!-- / Content -->
