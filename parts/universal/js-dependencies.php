    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="assets/vendor/libs/@algolia/autocomplete-js.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="assets/vendor/libs/hammer/hammer.js"></script>
    <script src="assets/vendor/libs/i18n/i18n.js"></script>
    <script src="assets/vendor/js/menu.js"></script>
    <script src="assets/vendor/libs/moment/moment.js"></script>
    <script src="assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <script src="assets/vendor/libs/select2/select2.js"></script>
    <script src="assets/vendor/libs/flatpickr/flatpickr.js"></script>
    <!-- Configuración de idioma español para Select2 -->
    <script src="parts/universal/select2-espanol.js"></script>
    <!-- <script src="assets/vendor/libs/@form-validation/popular.js"></script> -->
    <!-- <script src="assets/vendor/libs/@form-validation/bootstrap5.js"></script> -->
    <!-- <script src="assets/vendor/libs/@form-validation/auto-focus.js"></script> -->
    <script src="assets/vendor/libs/apex-charts/apexcharts.js"></script>
    <script src="assets/vendor/libs/cleave-zen/cleave-zen.js"></script>
    <script src="js/html2canvas.min.js"></script>
    <script src="assets/vendor/libs/sweetalert2/sweetalert2.js"></script>
    <script src="assets/vendor/libs/tagify/tagify.js"></script>
    <script src="assets/vendor/libs/bootstrap-select/bootstrap-select.js"></script>
    <script src="assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="assets/vendor/libs/bloodhound/bloodhound.js"></script>
     <script src="assets/vendor/libs/@form-validation/popular.js"></script>
     <script src="assets/vendor/libs/@form-validation/bootstrap5.js"></script> 
     <script src="assets/vendor/libs/@form-validation/auto-focus.js"></script> 
     <script src="assets/vendor/libs/notiflix/notiflix.js"></script>
    <script src="assets/vendor/libs/sortablejs/sortable.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/vendor/libs/notyf/notyf.js"></script>
    <script src="assets/js/ui-popover.js"></script>
    <script src="parts/universal/notificaciones.js"></script>
    <?php $vAutorizarAction = filemtime(__DIR__ . '/autorizar_action.js'); ?>
    <script src="parts/universal/autorizar_action.js?v=<?php echo $vAutorizarAction; ?>"></script>
    <script src="assets/js/cards-actions.js"></script>
  
    <!-- Configuración de idioma español para DataTables -->
    <script src="parts/universal/datatables-espanol.js"></script>
    <!-- Buscador DataTables con input-group (todos los listados)-->
    <script src="parts/universal/datatables-search-custom.js"></script>
    <?php if (!empty($usuario_root) && $usuario_root === 'true') { ?>
    <?php $vRootSelectsElements = filemtime(__DIR__ . '/root-selects-elements-items.js'); ?>
    <script src="parts/universal/root-selects-elements-items.js?v=<?php echo $vRootSelectsElements; ?>"></script>
    <?php } ?>

    