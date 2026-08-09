
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
    <?php $vBuscarNavbarModalsJs = filemtime(__DIR__ . '/buscar-navbar-modals.js'); ?>
    <script src="parts/universal/buscar-navbar-modals.js?v=<?php echo $vBuscarNavbarModalsJs; ?>"></script>
    <?php require_once __DIR__ . '/wizard_formacion_inject.php'; ?>
    <?php $vSessionChecker = filemtime(__DIR__ . '/../../include/session-checker.js'); ?>
    <script src="include/session-checker.js?v=<?php echo $vSessionChecker; ?>"></script>
    
    <!-- Registro de Service Worker para PWA -->
    <script src="assets/js/pwa-install.js"></script>
  </body>
</html>