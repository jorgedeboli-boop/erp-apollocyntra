<!doctype html>
<?php if($typ_item != "blank_page"){ ?>
<html lang="<?php echo !empty($app_charset_html) ? htmlspecialchars($app_charset_html) : 'es'; ?>" class="layout-wide customizer-hide layout-navbar-fixed layout-menu-fixed" dir="ltr" data-skin="default" data-bs-theme="light" data-assets-path="assets/" data-template="vertical-menu-template-no-customizer">
<?php }else{ ?>
<html lang="<?php echo !empty($app_charset_html) ? htmlspecialchars($app_charset_html) : 'es'; ?>" class="layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" data-skin="default" data-bs-theme="light" data-assets-path="assets/" data-template="horizontal-menu-template-no-customizer">
<?php } ?>
<script>
(function () {
  var templateName = document.documentElement.getAttribute('data-template') || 'vertical-menu-template-no-customizer';
  var theme = localStorage.getItem('templateCustomizer-' + templateName + '--Theme');
  if (theme === 'system') {
    theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  if (theme === 'dark' || theme === 'light') {
    document.documentElement.setAttribute('data-bs-theme', theme);
  }
})();
</script>
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="Content-Language" content="<?php echo !empty($app_charset_html) ? htmlspecialchars($app_charset_html) : 'es'; ?>">
    <!--<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0, viewport-fit=cover" />
    <meta name="robots" content="noindex, nofollow" />
    <title><?php echo APP_NAME." - ".$itemnameText; ?></title>
    <meta name="description" content="Panel de control TPV Quinta Gracia" />
    <link rel="icon" type="image/x-icon" href="assets/img/icons/app/favicon.ico" />
    
    <!-- PWA Meta Tags -->
    <meta name="application-name" content="TPV Quinta Gracia">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TPV Quinta Gracia">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#ffffff">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-tap-highlight" content="no">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- iOS App Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/icons/app/ios/180.png">
    <link rel="apple-touch-icon" sizes="167x167" href="assets/img/icons/app/ios/167.png">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/img/icons/app/ios/152.png">
    <link rel="apple-touch-icon" sizes="120x120" href="assets/img/icons/app/ios/120.png">
    
    <!-- Android Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="assets/img/icons/app/android/android-launchericon-192-192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="assets/img/icons/app/android/android-launchericon-512-512.png">
    <?php require_once 'parts/universal/fonts.php'; ?>
    <?php
    $vCssDependencies = filemtime(__DIR__ . '/css-dependencies.php');
    require_once __DIR__ . '/css-dependencies.php';

    $cssModuleFile = __DIR__ . '/../' . $itemname . '/' . $type . '/css.php';
    if (is_file($cssModuleFile)) {
        require_once $cssModuleFile;
    }
    ?>
    <script src="assets/vendor/js/helpers.js"></script>
    <script src="parts/universal/theme-persistence.js"></script>
    <script src="assets/js/config.js"></script>
  </head>