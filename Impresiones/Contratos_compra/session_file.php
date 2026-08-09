<?php
require_once __DIR__ . '/../../include/session.php';

// Compatibilidad con scripts legacy que usan $sucursal
if (!isset($sucursal) || $sucursal === '' || $sucursal === 0) {
    $sucursal = isset($usuario_sucursal) ? (int) $usuario_sucursal : 0;
}

// Conexión legacy mysql_* (si existe en el servidor)
$legacyConexionPath = __DIR__ . '/../../conexion.php';
if (is_file($legacyConexionPath)) {
    include_once $legacyConexionPath;
}

// Conexión mysqli (generaSello, etc.) si no quedó definida la legacy
if (!isset($conexion) || !$conexion) {
    $conexion = conectar_bd();
}

function generateSignatureContratoFinal($encodeData, $textSignature)
{
    $prefix = 'signature_';
    $extencionFile = 'svg';
    $file_name = uniqid($prefix) . '.' . $extencionFile;
    $encodeData = substr($encodeData, strpos($encodeData, ',') + 1);
    $decodeData = base64_decode($encodeData);
    $handle = fopen($file_name, 'x+');
    fwrite($handle, $decodeData);
    fclose($handle);
    $signatureFinal = '
    <div style="width: 200px; display: block; margin: 0 auto; font-size:14px; font-weight:bold; text-align:center; "><br>
    ' . $textSignature . '
    <img src="' . $file_name . '" alt="" style="width: 333px; margin-left: -56px; height: 324px; margin-top: -108px;"/>
    </div>
    ';
    return $signatureFinal;
}
