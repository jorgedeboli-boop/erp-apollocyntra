<?php
require_once __DIR__ . '/fonts-cache.php';

$fontsCss = 'assets/css/fonts.css';
$fontsCssPath = dirname(__DIR__, 2) . '/' . $fontsCss;
$fontsCssVersion = is_file($fontsCssPath) ? filemtime($fontsCssPath) : time();
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($fontsCss . '?v=' . $fontsCssVersion, ENT_QUOTES, 'UTF-8'); ?>">
