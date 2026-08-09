<?php
/**
 * Precarga la carpeta /fonts para cachear fuentes en el navegador.
 */
if (defined('TPV_FONTS_CACHE_LOADED')) {
    return;
}
define('TPV_FONTS_CACHE_LOADED', true);

$fontsDir = dirname(__DIR__, 2) . '/fonts';
if (!is_dir($fontsDir)) {
    return;
}

$fontFiles = glob($fontsDir . '/*.woff2');
if (!$fontFiles) {
    return;
}

sort($fontFiles, SORT_STRING);

foreach ($fontFiles as $fontFile) {
    $fileName = basename($fontFile);
    $href = '/fonts/' . rawurlencode($fileName);
    echo '<link rel="preload" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" as="font" type="font/woff2" crossorigin>' . "\n";
}

$fontsCss = 'assets/css/fonts.css';
$fontsCssPath = dirname(__DIR__, 2) . '/' . $fontsCss;
$fontsCssVersion = is_file($fontsCssPath) ? filemtime($fontsCssPath) : time();
echo '<link rel="preload" href="' . htmlspecialchars($fontsCss . '?v=' . $fontsCssVersion, ENT_QUOTES, 'UTF-8') . '" as="style">' . "\n";
