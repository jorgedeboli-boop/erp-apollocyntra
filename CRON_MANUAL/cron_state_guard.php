<?php

/**
 * CRON_MANUAL: no bloquea por $crons_state (permite recalcular históricos).
 * Copia adaptada de CRON/cron_state_guard.php — no modificar CRON/.
 */

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? ($_SERVER['SCRIPT_NAME'] ?? '/CRON_MANUAL/');
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'cron-manual';

require_once __DIR__ . '/../include/config.php';
