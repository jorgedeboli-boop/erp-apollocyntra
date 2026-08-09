<?php if($typ_item != "blank_page"){ ?> 
<!-- Footer -->
<footer class="content-footer footer bg-footer-theme">
    <div class="container-fluid">
        <div
          class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
            <div class="mb-2 mb-md-0 w-100 w-md-auto d-flex  align-items-center justify-content-between">
              &#169;
              <script>
                document.write(new Date().getFullYear());
              </script>
              , hecho con ❤️ por
              <a href="https://quintagracia.com" target="_blank" class="footer-link fw-medium">Quinta Gracia <?php echo $typ_item; ?></a>
              <button type="button" class="btn rounded-pill btn-icon btn-primary waves-effect waves-light" id="btnFooterActualizar" title="Actualizar página" aria-label="Actualizar página">
                <span class="icon-base ri ri-refresh-line icon-17px"></span>
              </button>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2 ms-md-auto align-self-end align-self-md-center">
              <div class="d-none d-lg-inline-block">
                <a href="#" class="footer-link me-4" target="_blank">Licencia</a>
                <a href="#" target="_blank" class="footer-link me-4">Soporte</a>
                <a href="#" target="_blank" class="footer-link me-4">Documentación</a>
              </div>
              
            </div>
        </div>
    </div>
</footer>
<!-- END Footer -->
 <?php } ?>

            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        
      </div> <!-- / layout-container -->

      <div class="layout-overlay layout-menu-toggle"></div>

      <div class="drag-target"></div>

    </div> <!-- / layout-wrapper layout-content-navbar -->

    <?php require_once 'parts/universal/js-dependencies.php'; ?>
    <script>
      var APP_URL = "<?php echo APP_URL; ?>";
    </script>
    <?php require_once 'parts/'.$itemname.'/'.$type.'/javascript.php'; ?>
    <?php if (isset($redirigir_dashboard) && $redirigir_dashboard === "true") { ?>
      <?php
        $dashboard_redirect_url = (isset($_SESSION['sucursal_section']) && $_SESSION['sucursal_section'] === 'true')
          ? 'dashboard_sucursal.php'
          : 'dashboard.php';
      ?>
      <script>
        window.location.href = "<?php echo $dashboard_redirect_url; ?>?error=<?php echo $texto_action_user; ?>";
      </script>
    <?php } ?>

    <script>
      const nombreSucursal = "<?php echo $_SESSION['usuario_sucursal_nombre']; ?>";
      const usuarioSucursal = <?php echo (int) $usuario_sucursal; ?>;
    </script>
    
    <?php if(isset($_GET['error'])){ ?>
      <script>
       Swal.fire({
            title: 'Error',
            text: '<?php echo $_GET['error']; ?>',
            icon: 'error',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#dc3545'
        });
      </script>
    <?php } ?>
    <?php if (isset($menu_active_id_type_item, $id_type_Item, $itemname, $itemsSections) && is_array($itemsSections)) { ?>
    <script>
      window.__menuPageContext = <?php echo json_encode([
        'idTypeItem' => (int) $menu_active_id_type_item,
        'currentIdTypeItem' => (int) $id_type_Item,
        'itemName' => $itemname,
        'urlItem' => (string) ($itemsSections['url_item'] ?? ''),
      ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    <?php } ?>
    <?php $vCustomJs = filemtime(__DIR__ . '/custom.js'); ?>
    <script src="parts/universal/custom.js?v=<?php echo $vCustomJs; ?>"></script>
    <?php if($sucursal_section == 'true'){ ?>
      <?php $vBuscarNavbarModalsJs = filemtime(__DIR__ . '/buscar-navbar-modals-sucursal.js'); ?>
      <script src="parts/universal/buscar-navbar-modals-sucursal.js?v=<?php echo $vBuscarNavbarModalsJs; ?>"></script>
    <?php }else{ ?>
      <?php $vBuscarNavbarModalsJs = filemtime(__DIR__ . '/buscar-navbar-modals.js'); ?>
      <script src="parts/universal/buscar-navbar-modals.js?v=<?php echo $vBuscarNavbarModalsJs; ?>"></script>
    <?php } ?>
    

    <?php require_once __DIR__ . '/wizard_formacion_inject.php'; ?>
    <?php $vSessionChecker = filemtime(__DIR__ . '/../../include/session-checker.js'); ?>
    <script src="include/session-checker.js?v=<?php echo $vSessionChecker; ?>"></script>
    
    <!-- Registro de Service Worker para PWA -->
    <script src="assets/js/pwa-install.js"></script>

    <?php if ($usuario_root == "true") { ?>
    <script>window.rootDomSelectRelIdTypeItem = <?php echo isset($id_type_Item) ? (int) $id_type_Item : 0; ?>;</script>
    <button
      type="button"
      id="btnRootSelectDomElements"
      class="btn btn-primary rounded-pill waves-effect root-dom-select-fab"
      title="Seleccionar elementos DOM"
      aria-label="Seleccionar elementos DOM"
      aria-pressed="false">
      <i class="icon-base ri ri-cursor-line icon-22px"></i>
      <span class="ms-1">DOM</span>
    </button>
    <?php } ?>
  </body>
</html>