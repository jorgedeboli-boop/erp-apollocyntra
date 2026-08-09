<?php
$id_sucursal = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$nombre_sucursal = '';
$id_empresa = 0;
$id_client_fiskaly = '';
$url_api_fiskaly = '';
$error_listado = '';

if ($id_sucursal <= 0) {
    $error_listado = 'ID de sucursal no válido. Accede desde Fiskaly Manager.';
} else {
    $conexion = conectar_bd();
    if (!$conexion) {
        $error_listado = 'No se pudo conectar a la base de datos.';
    } else {
        $stmt = mysqli_prepare(
            $conexion,
            'SELECT id_sucursal, nombre_sucursal, empresa_id FROM sucursal WHERE id_sucursal = ? LIMIT 1'
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id_sucursal);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $rowSuc = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if (!$rowSuc) {
                $error_listado = 'Sucursal no encontrada.';
            } else {
                $nombre_sucursal = (string) ($rowSuc['nombre_sucursal'] ?? '');
                $id_empresa = (int) ($rowSuc['empresa_id'] ?? 0);
            }
        } else {
            $error_listado = 'Error al consultar la sucursal.';
        }
        mysqli_close($conexion);
    }

    if ($error_listado === '' && $id_empresa > 0) {
        $url_api_fiskaly = (string) (obtenerUrlApiFiskalyPorEmpresa($id_empresa) ?: '');
        $creds = function_exists('fiskalyObtenerCredencialesSucursal')
            ? fiskalyObtenerCredencialesSucursal($id_sucursal, $id_empresa)
            : null;
        $id_client_fiskaly = is_array($creds) && !empty($creds['id_client_fiskaly'])
            ? trim((string) $creds['id_client_fiskaly'])
            : '';

        if ($url_api_fiskaly === '') {
            $error_listado = 'URL API Fiskaly no configurada para esta empresa.';
        } elseif ($id_client_fiskaly === '') {
            $error_listado = 'La sucursal no tiene id_client Fiskaly. Asóciala desde Fiskaly Manager.';
        }
    } elseif ($error_listado === '') {
        $error_listado = 'La sucursal no tiene empresa asociada.';
    }
}

echo '<script>';
echo 'window.idSucursalFiskaly = ' . (int) $id_sucursal . ';';
echo 'window.idEmpresaFiskaly = ' . (int) $id_empresa . ';';
echo 'window.idClientFiskaly = ' . json_encode($id_client_fiskaly) . ';';
echo 'window.urlApiFiskaly = ' . json_encode($url_api_fiskaly) . ';';
echo 'window.nombreSucursalFiskaly = ' . json_encode($nombre_sucursal) . ';';
echo '</script>';
?>
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card mb-6">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div>
            <h5 class="card-title mb-1">Facturas Fiskaly</h5>
            <div class="text-muted small">
              <?php if ($nombre_sucursal !== '') : ?>
                Sucursal: <strong><?php echo htmlspecialchars($nombre_sucursal, ENT_QUOTES, 'UTF-8'); ?></strong>
                (ID <?php echo (int) $id_sucursal; ?>)
              <?php else : ?>
                Sucursal ID <?php echo (int) $id_sucursal; ?>
              <?php endif; ?>
              <?php if ($id_client_fiskaly !== '') : ?>
                · Client: <code><?php echo htmlspecialchars($id_client_fiskaly, ENT_QUOTES, 'UTF-8'); ?></code>
              <?php endif; ?>
            </div>
          </div>
          <div class="d-flex gap-2">
            <?php if ($id_empresa > 0) : ?>
              <a href="fiskaly_manager.php?id=<?php echo (int) $id_empresa; ?>" class="btn btn-outline-secondary btn-sm">
                <i class="icon-base ri ri-arrow-left-line me-1"></i>Fiskaly Manager
              </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="card-body pt-5">
          <?php if ($error_listado !== '') : ?>
            <div class="alert alert-warning mb-0">
              <i class="icon-base ri ri-error-warning-line me-2"></i><?php echo htmlspecialchars($error_listado, ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php else : ?>
            <div id="alerta_fiskaly_sesion" class="alert alert-danger d-none mb-4">
              <i class="icon-base ri ri-lock-line me-2"></i>
              No hay sesión Fiskaly en el navegador. Conéctate primero en
              <a href="fiskaly_manager.php?id=<?php echo (int) $id_empresa; ?>" class="alert-link">Fiskaly Manager</a>
              y vuelve a abrir este listado.
            </div>
            <div class="card-datatable table-responsive">
              <table class="datatables-fiskaly-invoices table border-top">
                <thead>
                  <tr>
                    <th>ID INVOICE</th>
                    <th>CLIENT</th>
                    <th>TBAI</th>
                    <th>URL</th>
                    <th>ISSUED AT</th>
                    <th>SIGNER</th>
                    <th>ESTADO</th>
                    <th>CANCELLATION</th>
                    <th>REGISTRATION</th>
                    <th>REGISTRATION CSV</th>
                    <th>CODE</th>
                    <th>DESCRIPTION</th>
                    <th>ACCIONES</th>
                  </tr>
                </thead>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- / Content -->
