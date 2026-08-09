<?php
/**
 * Configurador del agente IA Chat — editar prompts por grupo/contexto.
 */

$tablas_ok = false;
$grupos = array();
$error_cfg = '';

$conexion = conectar_bd();
if ($conexion) {
    $check = @mysqli_query($conexion, "SHOW TABLES LIKE 'ia_agent_grupos'");
    if ($check && mysqli_num_rows($check) > 0) {
        $tablas_ok = true;
        $res = mysqli_query(
            $conexion,
            "SELECT id_grupo, codigo, nombre, descripcion, orden, activo
             FROM ia_agent_grupos
             ORDER BY orden ASC, id_grupo ASC"
        );
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $grupos[] = $row;
            }
            mysqli_free_result($res);
        }
    }
    if ($check) {
        mysqli_free_result($check);
    }
    mysqli_close($conexion);
} else {
    $error_cfg = 'Sin conexión a la base de datos.';
}

$grupo_activo = isset($_GET['grupo']) ? preg_replace('/[^a-z0-9_]/i', '', (string) $_GET['grupo']) : '';
if ($grupo_activo === '' && !empty($grupos)) {
    $grupo_activo = (string) $grupos[0]['codigo'];
}
?>
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row mb-3">
    <div class="col-12">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
          <h4 class="mb-1">Configuración del agente IA</h4>
          <p class="mb-0 text-muted">Edita los prompts por grupo (identidad, esquema BD, reglas SQL, interpretación, etc.).</p>
        </div>
        <?php if ($tablas_ok): ?>
          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-primary" id="iaCfgBtnImportar">
              <i class="icon-base ri ri-download-2-line me-1"></i>Importar desde código
            </button>
            <button type="button" class="btn btn-primary" id="iaCfgBtnNuevoPrompt" <?php echo $grupo_activo === '' ? 'disabled' : ''; ?>>
              <i class="icon-base ri ri-add-line me-1"></i>Nuevo prompt
            </button>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($error_cfg !== ''): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_cfg); ?></div>
  <?php elseif (!$tablas_ok): ?>
    <div class="alert alert-warning">
      <h6 class="alert-heading mb-2">Faltan las tablas MySQL</h6>
      <p class="mb-2">Crea estas tablas y vuelve a cargar la página. El SQL está en <code>parts/agentai/unique/install_ia_agent_tables.sql</code>.</p>
      <p class="mb-0 small">Tablas: <code>ia_agent_grupos</code>, <code>ia_agent_prompts</code>.</p>
    </div>
  <?php else: ?>

  <div class="row g-4">
    <div class="col-lg-3 col-md-4">
      <div class="card card-mobile-not-shadow h-100">
        <div class="card-header border-bottom">
          <h5 class="card-title mb-0">Grupos / contextos</h5>
        </div>
        <div class="list-group list-group-flush" id="iaCfgListaGrupos">
          <?php if (empty($grupos)): ?>
            <div class="list-group-item text-muted">No hay grupos. Pulsa «Importar desde código».</div>
          <?php else: ?>
            <?php foreach ($grupos as $g): ?>
              <?php
              $cod = (string) $g['codigo'];
              $activo_grupo = ($g['activo'] ?? '') === 'true';
              $is_sel = ($cod === $grupo_activo);
              ?>
              <a href="?grupo=<?php echo rawurlencode($cod); ?>"
                 class="list-group-item list-group-item-action ia-cfg-grupo <?php echo $is_sel ? 'active' : ''; ?>"
                 data-codigo="<?php echo htmlspecialchars($cod); ?>">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="fw-medium"><?php echo htmlspecialchars($g['nombre']); ?></span>
                  <?php if (!$activo_grupo): ?>
                    <span class="badge bg-label-secondary">off</span>
                  <?php endif; ?>
                </div>
                <small class="<?php echo $is_sel ? 'text-white-50' : 'text-muted'; ?>"><?php echo htmlspecialchars($cod); ?></small>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-9 col-md-8">
      <div class="card card-mobile-not-shadow">
        <div class="card-header border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div>
            <h5 class="card-title mb-0" id="iaCfgTituloGrupo">Prompts</h5>
            <small class="text-muted" id="iaCfgDescGrupo"></small>
          </div>
          <span class="badge bg-label-primary" id="iaCfgContadorPrompts">0</span>
        </div>
        <div class="card-body">
          <div id="iaCfgAlert" class="alert d-none" role="alert"></div>
          <div id="iaCfgListaPrompts" class="ia-cfg-prompts">
            <div class="text-muted py-4 text-center">Selecciona un grupo…</div>
          </div>
        </div>
      </div>

      <div class="card card-mobile-not-shadow mt-4 d-none" id="iaCfgEditorCard">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0" id="iaCfgEditorTitulo">Editar prompt</h5>
          <button type="button" class="btn btn-sm btn-text" id="iaCfgEditorCerrar" aria-label="Cerrar">
            <i class="ri ri-close-line fs-4"></i>
          </button>
        </div>
        <div class="card-body">
          <form id="iaCfgFormPrompt" autocomplete="off">
            <input type="hidden" id="iaCfgIdPrompt" name="id_prompt" value="0">
            <input type="hidden" id="iaCfgGrupoCodigo" name="grupo_codigo" value="<?php echo htmlspecialchars($grupo_activo); ?>">

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="iaCfgCodigo">Código</label>
                <input type="text" class="form-control" id="iaCfgCodigo" name="codigo" maxlength="64" required
                       pattern="[a-z0-9_]+" title="Solo minúsculas, números y _">
                <div class="form-text">Identificador estable (ej. <code>principal</code>, <code>generar_select</code>).</div>
              </div>
              <div class="col-md-3">
                <label class="form-label" for="iaCfgOrden">Orden</label>
                <input type="number" class="form-control" id="iaCfgOrden" name="orden" value="10">
              </div>
              <div class="col-md-3">
                <label class="form-label" for="iaCfgActivo">Activo</label>
                <select class="form-select" id="iaCfgActivo" name="activo">
                  <option value="true">Sí</option>
                  <option value="false">No</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label" for="iaCfgTitulo">Título</label>
                <input type="text" class="form-control" id="iaCfgTitulo" name="titulo" maxlength="168" required>
              </div>
              <div class="col-12 d-none" id="iaCfgDisparadoresWrap">
                <label class="form-label" for="iaCfgDisparadores">Disparadores (frases exactas)</label>
                <textarea class="form-control font-monospace" id="iaCfgDisparadores" name="disparadores" rows="6"
                          placeholder="gracias&#10;ok gracias&#10;perfecto&#10;muchas gracias"></textarea>
                <div class="form-text mb-0">
                  Una frase por línea. Si el usuario escribe <strong>exactamente</strong> una de ellas,
                  el chat responde con el contenido de abajo (sin SQL ni IA).
                  No hace falta tocar PHP: al guardar ya queda activo.
                </div>
              </div>
              <div class="col-12">
                <label class="form-label" for="iaCfgContenido" id="iaCfgContenidoLabel">Contenido del prompt</label>
                <textarea class="form-control font-monospace ia-cfg-textarea" id="iaCfgContenido" name="contenido" rows="18" required></textarea>
                <div class="d-flex justify-content-between flex-wrap gap-2 mt-1">
                  <div class="form-text mb-0" id="iaCfgHelpContenido">
                    Placeholders: <code>{{CABECERAS}}</code> (mapa de columnas),
                    <code>{{NOMBRE_USUARIO}}</code> (nombre de la sesión).
                  </div>
                  <small class="text-muted" id="iaCfgChars">0 caracteres</small>
                </div>
              </div>
              <div class="col-12 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary" id="iaCfgBtnGuardar">
                  <i class="icon-base ri ri-save-line me-1"></i>Guardar
                </button>
                <button type="button" class="btn btn-outline-danger" id="iaCfgBtnEliminar">
                  <i class="icon-base ri ri-delete-bin-line me-1"></i>Eliminar
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php endif; ?>
</div>
<!-- / Content -->

<script>
window.IA_CFG = {
  grupoActivo: <?php echo json_encode($grupo_activo); ?>,
  tablasOk: <?php echo $tablas_ok ? 'true' : 'false'; ?>,
  grupos: <?php echo json_encode($grupos, JSON_UNESCAPED_UNICODE); ?>
};
</script>
