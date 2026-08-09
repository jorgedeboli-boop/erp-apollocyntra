<body>
  <?php require_once 'parts/universal/lodaer.php'; ?>
  <?php if($typ_item != "blank_page"){ ?>
  <div class="layout-wrapper layout-content-navbar"> <!-- layout-wrapper layout-content-navbar -->
  <?php }else{ ?>
  <div class="layout-wrapper layout-navbar-full layout-horizontal layout-without-menu"> <!-- layout-wrapper layout-navbar-full layout-horizontal layout-without-menu -->
  <?php } ?>
    <div class="layout-container"> <!-- Layout container -->
    <?php if($typ_item != "blank_page"){ require_once 'parts/universal/menu.php';  } ?> <!-- menu -->
      <?php if($typ_item == "blank_page"){ require_once 'parts/universal/navbar_blank_page.php'; } ?>  <!-- navbar blank_page -->
      <div class="layout-page"> <!-- Layout page -->
        <?php if($typ_item != "blank_page"){ require_once 'parts/universal/menu.php';  } ?> <!-- menu -->
        <?php if($typ_item != "blank_page"){ require_once 'parts/universal/navbar.php'; } ?> <!-- navbar -->
         <div class="content-wrapper"><!-- Content wrapper -->