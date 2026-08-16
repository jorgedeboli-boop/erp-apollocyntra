<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <?php
  // Cargar datos del cliente
  $id_cliente = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  
  if ($id_cliente) {
      $conexion = conectar_bd();
      
                        // Consulta para obtener datos del cliente
                  $query_cliente = "
                      SELECT 
                          c.id_cliente,
                          c.nombre,
                          c.apellido,
                          c.tipo_identificacion,
                          c.identificacion,
                          c.nacionalidad,
                          c.telefono,
                          c.estado,
                          c.f_alta,
                          c.delete_state
                      FROM clientes c
                      WHERE c.id_cliente = ?
                  ";
      
      $stmt_cliente = mysqli_prepare($conexion, $query_cliente);
      mysqli_stmt_bind_param($stmt_cliente, 'i', $id_cliente);
      mysqli_stmt_execute($stmt_cliente);
      $result_cliente = mysqli_stmt_get_result($stmt_cliente);
      
      
      if ($result_cliente && mysqli_num_rows($result_cliente) > 0) {

          $cliente = mysqli_fetch_assoc($result_cliente);
          $delete_state = $cliente['delete_state'];
          if ($delete_state == 'true') {
            $texto_action_user = "Usuario intenta ver el cliente Nº '$id_cliente' eliminado";
            registrar_accion_usuario_not_access_id($texto_action_user, $id_type_Item);
            $cliente = null;
            $datos_cliente = null;
            $direccion_cliente = null;
            mysqli_close($conexion);
            header('Location: clientes.php');
            exit;
          }
          
          // Consulta para obtener datos adicionales del cliente
          $query_datos = "
              SELECT 
                  dc.id_datos_cliente,
                  dc.rel_id_cliente,
                  dc.f_nacimiento,
                  dc.movil,
                  dc.email,
                  dc.observaciones,
                  dc.publicidad,
                  dc.sexo,
                  dc.f_vencimiento,
                  dc.firma_cliente
              FROM datos_clientes dc
              WHERE dc.rel_id_cliente = ?
          ";
          
          $stmt_datos = mysqli_prepare($conexion, $query_datos);
          mysqli_stmt_bind_param($stmt_datos, 'i', $id_cliente);
          mysqli_stmt_execute($stmt_datos);
          $result_datos = mysqli_stmt_get_result($stmt_datos);
          
          $datos_cliente = null;
          if ($result_datos && mysqli_num_rows($result_datos) > 0) {
              $datos_cliente = mysqli_fetch_assoc($result_datos);
          }
          
          mysqli_stmt_close($stmt_datos);
          
          // Consulta para obtener dirección del cliente
          $query_direccion = "
              SELECT 
                  d.id_direcciones,
                  d.direccion,
                  d.c_provincia,
                  d.c_poblacion,
                  d.c_pais,
                  d.codigo_postal,
                  d.observaciones_direccion,
                  d.rel_id_provincia,
                  d.rel_id_pais,
                  d.rel_id_poblacion
              FROM direcciones d
              WHERE d.rel_id_item = ? AND d.type_direccion = 'clientes'
              LIMIT 1
          ";
          
          $stmt_direccion = mysqli_prepare($conexion, $query_direccion);
          mysqli_stmt_bind_param($stmt_direccion, 'i', $id_cliente);
          mysqli_stmt_execute($stmt_direccion);
          $result_direccion = mysqli_stmt_get_result($stmt_direccion);
          
          $direccion_cliente = null;
          if ($result_direccion && mysqli_num_rows($result_direccion) > 0) {
              $direccion_cliente = mysqli_fetch_assoc($result_direccion);
          }
          
          mysqli_stmt_close($stmt_direccion);
          mysqli_stmt_close($stmt_cliente);
      } else {
          echo '<div class="alert alert-danger">Cliente no encontrado</div>';
          $cliente = null;
          $datos_cliente = null;
          $direccion_cliente = null;
      }
      
      mysqli_close($conexion);
  } else {
      echo '<div class="alert alert-danger">ID de cliente no válido</div>';
      $cliente = null;
      $datos_cliente = null;
      $direccion_cliente = null;
  }
  ?>

              <!-- Header -->
              <div class="row">
                <div class="col-12">
                  <div class="card mb-6">
                    
                    <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
                      
                      <div class="flex-grow-1 mt-4 mt-sm-12">
                        <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
                          <div class="user-profile-info">
                            <h4 class="mb-2"><?php echo isset($cliente['nombre']) ? htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']) : 'Cliente no encontrado'; ?></h4>
                            <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4">
                              <li class="list-inline-item">
                                <i class="icon-base ri ri-calendar-line me-2 icon-24px"></i><span class="fw-medium">Alta: <?php echo isset($cliente['f_alta']) ? date('d/m/Y', strtotime($cliente['f_alta'])) : 'N/A'; ?></span>
                              </li>
                            </ul>
                          </div>
                          <div class="d-flex gap-2">
                            <button name="btnClientes" type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='clientes.php'">
                              <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Clientes
                            </button>

                            <?php if ($puede_acceder_borrar || comprobarStateElementRelUser('btnEliminarCliente', $usuario_id, $usuario_privilegio_id, $id_type_Item)): ?>
                            <button name="btnEliminarCliente" id="btnEliminarCliente" type="button" class="btn btn-danger waves-effect waves-light" onclick="confirmarEliminarCliente(<?php echo $id_cliente; ?>, <? echo $_SESSION['relItemAction']; ?>, <? echo $_SESSION['itemname']; ?>)">
                              <i class="icon-base ri ri-delete-bin-line icon-16px me-2"></i>Eliminar Cliente
                            </button>
                            <?php endif; ?>
                            <?php if ($puede_acceder_editar || comprobarStateElementRelUser('btnEditarCliente', $usuario_id, $usuario_privilegio_id, $id_type_Item)): ?>
                            <a name="btnEditarCliente" id="btnEditarCliente" href="editar_cliente.php?id=<?php echo $id_cliente; ?>" class="btn btn-primary waves-effect waves-light">
                              <i class="icon-base ri ri-edit-line icon-16px me-2"></i>Editar Cliente
                            </a>
                            <?php endif; ?>
                            <?php if ($puede_acceder_editar || comprobarStateElementRelUser('btnToggleEstado', $usuario_id, $usuario_privilegio_id, $id_type_Item)): ?>
                            <button type="button" id="btnToggleEstado" class="btn <?php echo (isset($cliente['estado']) && $cliente['estado'] === 'habilitado') ? 'btn-danger' : 'btn-success'; ?> waves-effect waves-light" onclick="toggleEstadoCliente(<?php echo $id_cliente; ?>)">
                              <i class="icon-base ri <?php echo (isset($cliente['estado']) && $cliente['estado'] === 'habilitado') ? 'ri-user-forbid-line' : 'ri-user-follow-line'; ?> icon-16px me-2"></i>
                              <?php echo (isset($cliente['estado']) && $cliente['estado'] === 'habilitado') ? 'Deshabilitar' : 'Habilitar'; ?>
                            </button>
                            <?php endif; ?>

                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!--/ Header -->

              <!-- Navbar pills -->
              <div class="row">
                <div class="col-md-12">
                  <div class="nav-align-top">
                    <ul class="nav nav-pills flex-column flex-sm-row mb-6 row-gap-2" role="tablist">
                      <li class="nav-item">
                        <button
                          class="nav-link active waves-effect waves-light"
                          id="tab-perfil"
                          data-bs-toggle="tab"
                          data-bs-target="#pane-perfil"
                          type="button"
                          role="tab"
                          aria-controls="pane-perfil"
                          aria-selected="true"
                        ><i class="icon-base ri ri-user-3-line icon-sm me-2"></i>Perfil</button>
                      </li>
                      <li class="nav-item">
                        <button
                          class="nav-link waves-effect waves-light"
                          id="tab-lotes"
                          data-bs-toggle="tab"
                          data-bs-target="#pane-lotes"
                          type="button"
                          role="tab"
                          aria-controls="pane-lotes"
                          aria-selected="false"
                        ><i class="icon-base ri ri-team-line icon-sm me-2"></i>Lotes</button>
                      </li>
                      <li class="nav-item">
                        <button
                          class="nav-link waves-effect waves-light"
                          id="tab-empenos"
                          data-bs-toggle="tab"
                          data-bs-target="#pane-empenos"
                          type="button"
                          role="tab"
                          aria-controls="pane-empenos"
                          aria-selected="false"
                        ><i class="icon-base ri ri-computer-line icon-sm me-2"></i>Empeños</button>
                      </li>
                      <li class="nav-item">
                        <button
                          class="nav-link waves-effect waves-light"
                          id="tab-ventas"
                          data-bs-toggle="tab"
                          data-bs-target="#pane-ventas"
                          type="button"
                          role="tab"
                          aria-controls="pane-ventas"
                          aria-selected="false"
                        ><i class="icon-base ri ri-link-m icon-sm me-2"></i>Ventas</button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
              <!--/ Navbar pills -->

              <!-- User Profile Content -->
              <div class="tab-content p-0">
                <div class="tab-pane fade show active" id="pane-perfil" role="tabpanel" aria-labelledby="tab-perfil">
                  <div class="row">
                <div class="col-xl-4 col-lg-5 col-md-5">
                  <!-- About User -->
                  <div class="card mb-6">
                    <div class="card-body">
                      <small class="card-text text-uppercase text-body-secondary small">Información Personal</small>
                      <ul class="list-unstyled my-3 py-1">
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-user-3-line icon-24px"></i><span class="fw-medium mx-2">Nombre:</span> <span><?php echo isset($cliente['nombre']) ? htmlspecialchars($cliente['nombre']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-user-3-line icon-24px"></i><span class="fw-medium mx-2">Apellido:</span> <span><?php echo isset($cliente['apellido']) ? htmlspecialchars($cliente['apellido']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-check-line icon-24px"></i><span class="fw-medium mx-2">Estado:</span> <span><span class="badge bg-label-<?php echo (isset($cliente['estado']) && $cliente['estado'] === 'habilitado') ? 'success' : 'danger'; ?> me-2 ms-2 rounded-pill"><?php echo isset($cliente['estado']) ? htmlspecialchars($cliente['estado']) : 'N/A'; ?></span></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-flag-2-line icon-24px"></i><span class="fw-medium mx-2">Nacionalidad:</span> <span><?php echo isset($cliente['nacionalidad']) ? htmlspecialchars($cliente['nacionalidad']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-2">
                          <i class="icon-base ri ri-translate-2 icon-24px"></i><span class="fw-medium mx-2">Sexo:</span> <span><?php echo isset($datos_cliente['sexo']) ? htmlspecialchars($datos_cliente['sexo']) : 'N/A'; ?></span>
                        </li>
                      </ul>
                      <small class="card-text text-uppercase text-body-secondary small">Contacto</small>
                      <ul class="list-unstyled my-3 py-1">
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-phone-line icon-24px"></i><span class="fw-medium mx-2">Teléfono:</span> <span><?php echo isset($cliente['telefono']) ? htmlspecialchars($cliente['telefono']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-2">
                          <i class="icon-base ri ri-mail-open-line icon-24px"></i><span class="fw-medium mx-2">Email:</span> <span><?php echo isset($datos_cliente['email']) ? htmlspecialchars($datos_cliente['email']) : 'N/A'; ?></span>
                        </li>
                      </ul>
                      <small class="card-text text-uppercase text-body-secondary small">Identificación</small>
                      <ul class="list-unstyled mb-0 mt-3 pt-1">
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-id-card-line icon-24px text-body me-2"></i>
                          <div class="d-flex flex-wrap">
                            <span class="fw-medium me-2">TIPO DE DOCUMENTO:</span><span class="me-2"><?php echo isset($cliente['tipo_identificacion']) ? htmlspecialchars($cliente['tipo_identificacion']) : 'N/A'; ?></span><span><?php echo isset($cliente['identificacion']) ? htmlspecialchars($cliente['identificacion']) : 'N/A'; ?></span>
                          </div>
                        </li>
                        <li class="d-flex align-items-center">
                          <i class="icon-base ri ri-calendar-line icon-24px text-body me-2"></i>
                          <div class="d-flex flex-wrap">
                            <span class="fw-medium me-2">Vencimiento:</span><span><?php echo isset($datos_cliente['f_vencimiento']) ? date('d/m/Y', strtotime($datos_cliente['f_vencimiento'])) : 'N/A'; ?></span>
                          </div>
                        </li>
                      </ul>
                      <small class="card-text text-uppercase text-body-secondary small">Información adicional</small>
                      <ul class="list-unstyled mb-0 mt-3 pt-1">
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-calendar-line icon-24px"></i><span class="fw-medium mx-2">Fecha nacimiento:</span> <span><?php echo isset($datos_cliente['f_nacimiento']) ? date('d/m/Y', strtotime($datos_cliente['f_nacimiento'])) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-mail-check-line icon-24px"></i><span class="fw-medium mx-2">Publicidad:</span> <span><?php echo isset($datos_cliente['publicidad']) ? htmlspecialchars($datos_cliente['publicidad']) : 'N/A'; ?></span>
                        </li>
                      </ul>
                      <small class="card-text text-uppercase text-body-secondary small">Dirección</small>
                      <div class="row mt-3 pt-1">
                        <?php if ($direccion_cliente): ?>
                          <div class="col-md-12">
                            <div class="mb-3">
                              <span class="fw-medium d-block mb-1">Dirección</span>
                              <p class="text-body-secondary mb-0"><?php echo htmlspecialchars($direccion_cliente['direccion']); ?></p>
                            </div>
                          </div>
                          <div class="col-6">
                            <div class="mb-3">
                              <span class="fw-medium d-block mb-1">Población</span>
                              <p class="text-body-secondary mb-0"><?php echo htmlspecialchars($direccion_cliente['c_poblacion']); ?></p>
                            </div>
                          </div>
                          <div class="col-6">
                            <div class="mb-3">
                              <span class="fw-medium d-block mb-1">Provincia</span>
                              <p class="text-body-secondary mb-0"><?php echo htmlspecialchars($direccion_cliente['c_provincia']); ?></p>
                            </div>
                          </div>
                          <div class="col-6">
                            <div class="mb-3">
                              <span class="fw-medium d-block mb-1">País</span>
                              <p class="text-body-secondary mb-0"><?php echo htmlspecialchars($direccion_cliente['c_pais']); ?></p>
                            </div>
                          </div>
                          <div class="col-6">
                            <div class="mb-3">
                              <span class="fw-medium d-block mb-1">Código postal</span>
                              <p class="text-body-secondary mb-0"><?php echo htmlspecialchars($direccion_cliente['codigo_postal']); ?></p>
                            </div>
                          </div>
                          <?php if (!empty($direccion_cliente['observaciones_direccion'])): ?>
                            <div class="col-12">
                              <span class="fw-medium d-block mb-1">Observaciones (dirección)</span>
                              <p class="text-body-secondary mb-0"><?php echo htmlspecialchars($direccion_cliente['observaciones_direccion']); ?></p>
                            </div>
                          <?php endif; ?>
                        <?php else: ?>
                          <div class="col-12">
                            <div class="alert alert-info mb-0 py-2">
                              <i class="icon-base ri ri-information-line me-2"></i>
                              No hay dirección registrada para este cliente.
                            </div>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <!--/ About User -->
                </div>


                <div class="col-xl-8 col-lg-7 col-md-7">

                  <!-- Documentación -->
                  <div class="card card-action mb-6">
                    <div class="card-header mb-0 pt-2 pb-1">
                      <h5 class="text-uppercase text-body-secondary small card-title mb-0 me-auto p-2">Documentación del cliente</h5>
                      
                      <div class="position-absolute" style="right: 13px; top: 13px;">
                        <?php if ($puede_acceder_fotos_cliente_edit): ?>
                        <button type="button" class="p-2 btn rounded-pill btn-icon btn-primary waves-effect waves-light" id="btnFotoMovilCliente" onclick="abrirModalFotoMovilCliente()">
                          <span class="icon-base ri ri-camera-ai-fill icon-22px"></span>
                        </button>
                        <button type="button" class="p-2 btn rounded-pill btn-icon btn-primary waves-effect waves-light" onclick="abrirModalSubirFoto()">
                          <span class="icon-base ri ri-upload-line icon-22px"></span>
                        </button>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="card-body">
                      <div class="row visor_documento_global" id="visor_documentos_cliente">
                        <div class="col-12">
                          <!-- Contenedor de imágenes -->
                          <div id="contenedor_imagenes" class="row g-3">
                            <!-- Las imágenes se cargarán aquí dinámicamente -->
                          </div>
                          
                          <!-- Mensaje cuando no hay imágenes -->
                          <div id="sin_imagenes" class="text-center py-5" style="display: none;">
                            <i class="icon-base ri ri-image-line icon-48px text-body-secondary mb-3"></i>
                            <p class="text-body-secondary mb-0">No hay documentos cargados para este cliente</p>
                          </div>
                          
                          <!-- Loading -->
                          <div id="loading_imagenes" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                              <span class="visually-hidden">Cargando...</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                      
                      <!-- Modal para subir foto -->
                      <div class="modal fade" id="modalSubirFoto" tabindex="-1" aria-labelledby="modalSubirFotoLabel" aria-hidden="true">
                        <div class="modal-dialog">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title" id="modalSubirFotoLabel">Subir Documento</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <form id="formSubirFoto" enctype="multipart/form-data">
                                <div class="mb-3">
                                  <label for="archivo_foto" class="form-label">Seleccionar archivo</label>
                                  <input type="file" class="form-control" id="archivo_foto" name="archivo_foto" accept=".jpg,.jpeg,.gif,.png,.pdf,.PDF,.JPG,.JPEG,.GIF,.PNG" required>
                                  <div class="form-text">Formatos permitidos: JPG, JPEG, GIF, PNG, PDF (máximo 5MB). Las imágenes se redimensionan automáticamente a 800px de ancho máximo.</div>
                                </div>
                              </form>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                              <button type="button" class="btn btn-primary" onclick="subirFoto()">
                                <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                Subir
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Modal QR cliente: inyectado por camera/js/camera-doc-panel.js (fetch camera/api/doc_panel.php) -->
                      
                      <!-- Modal para ampliar imagen -->
                      <div class="modal fade" id="modalAmpliarImagen" tabindex="-1" aria-labelledby="modalAmpliarImagenLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title" id="modalAmpliarImagenLabel">Vista Ampliada</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <img id="imagen_ampliada" src="" alt="Imagen ampliada" class="img-fluid">
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                          </div>
                        </div>
                      </div>
                  </div>
                  <!--/ Documentación -->

                  <!-- Comentarios del cliente -->
                  <div class="card card-action mb-6">
                    <div class="card-header align-items-center">
                      <h5 class="card-action-title mb-0">
                        <i class="icon-base ri ri-chat-3-line icon-24px text-body me-4"></i>Comentarios sobre el cliente
                      </h5>
                    </div>
                    <div class="card-body pt-4">
                      <textarea class="form-control" id="textarea_comentarios_cliente" rows="5"><?php echo htmlspecialchars((string)(isset($datos_cliente['observaciones']) ? $datos_cliente['observaciones'] : ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                      <?php if ($puede_acceder_editar): ?>
                      <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-primary" id="btnActualizarComentariosCliente" onclick="actualizarComentariosCliente(<?php echo (int) $id_cliente; ?>)">
                          <i class="icon-base ri ri-save-3-line icon-16px me-2"></i>Actualizar comentario
                        </button>
                      </div>
                      <?php endif; ?>
                    </div>
                  </div>
                  <!--/ Comentarios del cliente -->

                  <!-- Project table -->
                  
                  <!-- /Project table -->
                </div>
              </div>
                </div>
              <!--/ pane-perfil -->

                <div class="tab-pane fade" id="pane-lotes" role="tabpanel" aria-labelledby="tab-lotes">
                  <div class="card mb-6">
                    <div class="card-header border-bottom card-header-forms">
                      <h5 class="card-title mb-0"><i class="icon-base ri ri-team-line icon-20px me-2"></i>Lotes del cliente</h5>
                    </div>
                    <div class="card-body pb-0"></div>
                    <div class="card-datatable table-responsive">
                      <table class="datatables-lotes-cliente table border-top" id="tabla_lotes_cliente" style="width: 100%;">
                        <thead>
                          <tr>
                            <th>ID Lote</th>
                            <th>Identificador</th>
                            <th>Tipo</th>
                            <th>Peso</th>
                            <th>Compra</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                          </tr>
                        </thead>
                      </table>
                    </div>
                  </div>
                </div>

                <div class="tab-pane fade" id="pane-empenos" role="tabpanel" aria-labelledby="tab-empenos">
                  <div class="card mb-6">
                    <div class="card-header border-bottom card-header-forms">
                      <h5 class="card-title mb-0"><i class="icon-base ri ri-computer-line icon-20px me-2"></i>Empeños del cliente</h5>
                    </div>
                    <div class="card-body pb-0"></div>
                    <div class="card-datatable table-responsive">
                      <table class="datatables-empenos-cliente table border-top" id="tabla_empenos_cliente" style="width: 100%;">
                        <thead>
                          <tr>
                            <th>ID Lote</th>
                            <th>Identificador</th>
                            <th>Tipo</th>
                            <th>Importe</th>
                            <th>Fecha</th>
                            <th>Vencimiento</th>
                            <th>Estado</th>
                          </tr>
                        </thead>
                      </table>
                    </div>
                  </div>
                </div>

                <div class="tab-pane fade" id="pane-ventas" role="tabpanel" aria-labelledby="tab-ventas">
                  <div class="card mb-6">
                    <div class="card-header border-bottom card-header-forms">
                      <h5 class="card-title mb-0"><i class="icon-base ri ri-link-m icon-20px me-2"></i>Ventas del cliente</h5>
                    </div>
                    <div class="card-body pb-0"></div>
                    <div class="card-datatable table-responsive">
                      <table class="datatables-ventas-cliente table border-top" id="tabla_ventas_cliente" style="width: 100%;">
                        <thead>
                          <tr>
                            <th>Nº Venta</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Plazos</th>
                            <th>Pago</th>
                            <th>ID</th>
                          </tr>
                        </thead>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              </div><!-- / Content -->