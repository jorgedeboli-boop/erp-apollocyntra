 <!-- Navbar -->

 <nav class="layout-navbar container-fluid navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
            
  <button type="button" id="btnNavbarBack" class="navbar-back-btn btn rounded-pill btn-text-primary waves-effect waves-light" title="Volver atrás" aria-label="Volver atrás">
      <span class="icon-base ri ri-arrow-left-line icon-22px"></span>
      <span id="textobackbtn" class="navbar-back-btn-text">Volver atrás</span>
  </button>

  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-2 me-xl-0 d-xl-none">
    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
      <i class="icon-base ri ri-menu-line icon-22px"></i>
    </a>
  </div>

  <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
  
    <div class="navbar-nav align-items-center d-none">
      <div class="nav-item navbar-search-wrapper mb-0">
        <a class="nav-item nav-link search-toggler px-0" href="javascript:void(0);">
          <span class="d-inline-block text-body-secondary fw-normal" id="autocomplete"></span>
        </a>
      </div>
    </div>
    
    <ul class="navbar-nav flex-row align-items-center ms-md-auto">

      <?php if($usuario_acceso_ia == 'true'){ ?>
      <li class="nav-item order-1 ms-2">
        <button type="button" id="btnAsistenteIA" class="btn btn-primary rounded-pill waves-effect" data-bs-toggle="modal" data-bs-target="#modalIAChat">
          <i class="icon-base ri ri-chat-ai-fill icon-22px md-2"></i> <span class="d-none d-sm-block ms-2">Asistente IA</span>
        </button>
      </li> <?php } ?>

      <li class="nav-item d-none d-sm-block order-3 ms-2">
        <button type="button" id="searchArticuloNav" class="btn btn-label-primary rounded-pill waves-effect" aria-label="Buscar artículo" title="Buscar artículo">
          Buscar artículo <i class="icon-base ri ri-search-2-line icon-18px ms-1"></i>
        </button>
      </li>

      <li class="nav-item dropdown-style-switcher dropdown order-4 ms-2"  data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Cambiar modo pantalla">
        <a class="dropdown-toggle hide-arrow btn btn-icon btn-label-primary rounded-pill waves-effect" id="nav-theme" href="javascript:void(0);" data-bs-toggle="dropdown">
          <i class="icon-base ri ri-sun-line icon-24px theme-icon-active"></i>
          <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">
          <li>
            <button
              type="button"
              class="dropdown-item align-items-center active"
              data-bs-theme-value="light"
              aria-pressed="false">
              <span><i class="icon-base ri ri-sun-line icon-24px me-3" data-icon="sun-line"></i>Claro</span>
            </button>
          </li>
          <li>
            <button
              type="button"
              class="dropdown-item align-items-center"
              data-bs-theme-value="dark"
              aria-pressed="true">
              <span
                ><i class="icon-base ri ri-moon-clear-line icon-24px me-3" data-icon="moon-clear-line"></i
                >Oscuro</span
              >
            </button>
          </li>
          <li>
            <button
              type="button"
              class="dropdown-item align-items-center"
              data-bs-theme-value="system"
              aria-pressed="false">
              <span
                ><i class="icon-base ri ri-computer-line icon-24px me-3" data-icon="computer-line"></i
                >Sistema</span
              >
            </button>
          </li>
        </ul>
      </li>

      <!-- User -->
      <li class="nav-item navbar-dropdown dropdown-user dropdown order-last ms-2">
        <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
          <div class="avatar avatar-online">
            <span class="avatar-initial rounded-circle bg-label-primary"><?php echo generar_iniciales_usuario($_SESSION['usuario_nombre_completo'] ?? '', $_SESSION['usuario_nombre'] ?? ''); ?></span>
          </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end mt-3 py-2">
          <?php if(usuario_puede_acceder_item($usuario_privilegio_id, 147) || $usuario_root == "true"){ ?>
          <li>
            <a class="dropdown-item" href="usuario.php?id=<?php echo $_SESSION['usuario_id']; ?>">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-2">
                  <div class="avatar avatar-online">
                      <span class="avatar-initial rounded-circle bg-label-primary"><?php echo generar_iniciales_usuario($_SESSION['usuario_nombre_completo'] ?? '', $_SESSION['usuario_nombre'] ?? ''); ?></span>                        
                  </div>
                </div>
                <div class="flex-grow-1">
                  <h6 class="mb-0 small"><?php echo sanitizar_dato_sesion($usuario_nombre_completo); ?></h6>
                  <small class="text-body-secondary"><?php echo sanitizar_dato_sesion($usuario_privilegio_nombre); ?></small>
                </div>
              </div>
            </a>
          </li>

          <li>
            <div class="dropdown-divider"></div>
          </li>

          <li>
            <a class="dropdown-item" href="usuario.php?id=<?php echo $_SESSION['usuario_id']; ?>">
              <i class="icon-base ri ri-user-3-line icon-22px me-3"></i
              ><span class="align-middle">Mi Perfil</span>
            </a>
          </li>

          <li>
            <a class="dropdown-item" href="editar_usuario.php?id=<?php echo $_SESSION['usuario_id']; ?>">
              <i class="icon-base ri ri-settings-4-line icon-22px me-3"></i
              ><span class="align-middle">Configuración</span>
            </a>
          </li>
          
          <li>
            <div class="dropdown-divider"></div>
          </li>
          <?php }else{ ?>
          <li>
            <span class="dropdown-item-text">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-2  text-primary">
                <i class="icon-base ri ri-information-2-line icon-28px"></i>
                </div>
                <div class="flex-grow-1">
                  <h6 class="mb-0 small lh-1">Usuario</h6>
                  <small class="text-body-secondary"><?php echo sanitizar_dato_sesion($usuario_nombre); ?></small>
                </div>
              </div>
            </span>
          </li>
          <li>
            <span class="dropdown-item-text">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-2  text-primary">
                <i class="icon-base ri ri-user-3-line icon-28px"></i>
                </div>
                <div class="flex-grow-1">
                  <h6 class="mb-0 small lh-1">Nombre completo</h6>
                  <small class="text-body-secondary"><?php echo sanitizar_dato_sesion($usuario_nombre_completo); ?></small>
                </div>
              </div>
            </span>
          </li>
          <li>
            <span class="dropdown-item-text">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-2  text-primary">
                <i class="icon-base ri ri-building-line icon-28px"></i>
                </div>
                <div class="flex-grow-1">
                  <h6 class="mb-0 small lh-1">Sucursal</h6>
                  <small class="text-body-secondary"><?php echo sanitizar_dato_sesion($usuario_sucursal_nombre); ?></small>
                </div>
              </div>
            </span>
          </li>
          <li>
            <span class="dropdown-item-text">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-2  text-primary">
                <i class="icon-base ri ri-user-forbid-line icon-28px"></i>
                </div>
                <div class="flex-grow-1">
                  <h6 class="mb-0 small lh-1">Jerarquía</h6>
                  <small class="text-body-secondary"><?php echo sanitizar_dato_sesion($usuario_privilegio_nombre); ?></small>
                </div>
              </div>
            </span>
          </li>
          <?php } ?>
          <li>
            <div class="d-grid px-4 pt-2 pb-1">
              <button type="button" id="btnCerrarSesionNavbar" class="btn btn-sm btn-danger d-flex w-100">
                <small class="align-middle">Cerrar Sesión</small>
                <i class="icon-base ri ri-logout-box-r-line ms-2 icon-16px"></i>
              </button>
            </div>
          </li>

        </ul>
      </li>
      <!--/ User -->
    </ul>
  </div>
</nav>

<div class="modal modal-transparent fade" id="modalBuscarArticulo" tabindex="-1">
  <div class="modal-dialog modal-content-buscar">
    <div class="modal-content">
      <div class="modal-body position-relative">
        <div class="input-group input-group-lg mb-4 cotenedor-buscar bg-label-primary">
          <input
            type="text"
            class="form-control bg-white border-0 inputs-buscar"
            id="searchArticulo"
            placeholder="Buscar artículo..."
            aria-label="Buscar artículo"
            inputmode="text"
            maxlength="30"
            autocomplete="off">
          <button class="btn btn-primary btnBuscar" type="button" id="btnBuscarArticulo"><i class="icon-base ri ri-search-line"></i></button>
        </div>
        <div class="text-white" id="searchArticuloResultados">
        <div class="search-articulo-tabla-wrap" id="searchArticuloTablaWrap" hidden aria-hidden="true">
          <div class="search-navbar-scroll-fade">
            <div id="searchArticuloTablaBody" class="search-articulo-lista" aria-live="polite"></div>
          </div>
          <p id="searchArticuloNoEncontrado" class="search-articulo-estado text-center mb-0" hidden>No se encontraron artículos.</p>
        </div>
        </div>
      </div>
    </div>
  </div>
</div>

          <!-- / Navbar -->
          <script>window.REQUIERE_ARQUEO_CAJA = <?php echo !empty($requiere_arqueo_caja_sucursal) ? 'true' : 'false'; ?>;</script>
          <?php if ($usuario_acceso_ia == 'true'){ ?>
            <?php include 'parts/agentai/modal_ia_chat.php'; ?>
          <?php } ?>