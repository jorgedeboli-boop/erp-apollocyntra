 <!-- Navbar -->
 <button
  type="button"
  id="btn_refresh_dashboard_recepcion"
  class="btn rounded-pill btn-icon btn-primary waves-effect waves-light"
  style="position: fixed; bottom: 5px; right: 5px; display: block; width: 43px; height: 43px; z-index: 1050;"
  onclick="window.location.reload()"
  aria-label="Actualizar página"
  title="Actualizar página"
>
  <span class="icon-base ri ri-refresh-line icon-30px p-3"></span>
</button>
 <nav class="layout-navbar layout-navbar-pageblank navbar navbar-expand-xl align-items-center bg-body shadow-none" id="layout-navbar">
            <?php if($itemname != 'dashboard_recepcion_lotes'){ ?>
              <button type="button" id="btnNavbarBack" class="navbar-back-btn btn rounded-pill btn-text-primary waves-effect waves-light" title="Volver atrás" aria-label="Volver atrás">
                  <span class="icon-base ri ri-arrow-left-line icon-22px"></span>
                  <span id="textobackbtn" class="navbar-back-btn-text">Volver atrás</span>
              </button>
            <?php }else{ /*echo "<span style='margin-right: 0.7rem;'></span>";*/ } ?>
            <!--
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-2 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                <i class="icon-base ri ri-menu-line icon-22px"></i>
              </a>
            </div>
            -->
            <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
              <div class="navbar-nav align-items-center d-none">
                <div class="nav-item navbar-search-wrapper mb-0">
                  <a class="nav-item nav-link search-toggler px-0" href="javascript:void(0);">
                    <span class="d-inline-block text-body-secondary fw-normal" id="autocomplete"></span>
                  </a>
                </div>
            </div>  
              <ul class="navbar-nav d-flex flex-row align-items-center ms-md-auto w-100" id="ul_nav_bar_blank_page">
              
              <?php include 'parts/universal/extras_nav_bar_'.obtenerIntemNameUrlBlankPageExtras($id_type_Item).'.php'; ?>
              
               <?php if($usuario_acceso_ia == 'true'){ ?>
                <li class="nav-item ms-2">
                  <button type="button" id="btnAsistenteIA" class="btn btn-primary rounded-pill waves-effect" data-bs-toggle="modal" data-bs-target="#modalIAChat">
                    <i class="icon-base ri ri-chat-ai-fill icon-22px md-2"></i> <span class="d-none d-sm-block ms-2">Asistente IA</span>
                  </button>
                </li> <?php } ?>

                <li class="nav-item dropdown-style-switcher dropdown ms-2">
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
                <!--
                <li class="nav-item d-none d-sm-block ms-2">
                  <span class="badge bg-label-primary rounded-pill sucursal_nombre"><?php echo isset($_SESSION['usuario_sucursal_nombre']) ? htmlspecialchars($_SESSION['usuario_sucursal_nombre']) : 'Sucursal'; ?></span>
                </li>
                
                <li class="nav-item d-none d-sm-block 4 ms-2">
                  <span class="badge bg-label-primary rounded-pill privilegio_nombre"><?php echo !empty($usuario_privilegio_nombre) ? sanitizar_dato_sesion($usuario_privilegio_nombre) : 'Privilegio'; ?></span>
                </li>
                -->
                <!-- User -->
                <li class="nav-item d-sm-block ms-2">
                    <button type="button" id="close_session" class="btn rounded-pill btn-icon btn-label-primary waves-effect" onclick="confirmarCerrarSesion(); return false;">
                    <i class="icon-base ri ri-close-large-line icon-22px md-2"></i>
                  </button>
                </li>
                <!--/ User -->
              </ul>
            </div>
          </nav>

          <!-- / Navbar -->
          <script>window.REQUIERE_ARQUEO_CAJA = <?php echo !empty($requiere_arqueo_caja_sucursal) ? 'true' : 'false'; ?>;</script>
          <?php if ($usuario_acceso_ia == 'true'){ ?>
            <?php include 'parts/agentai/modal_ia_chat.php'; ?>
          <?php } ?>
          <?php include __DIR__ . '/modal_actualizar_precio_proveedor.php'; ?>