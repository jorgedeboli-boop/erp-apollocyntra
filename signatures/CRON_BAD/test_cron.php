<?php
// Verificar que el usuario esté autenticado
define('DB_HOST', 'vl24696.dinaserver.com');
define('DB_NAME', 'quint_bbdd_4822');
define('DB_USER', 'quint_27183');
define('DB_PASS', 'Soul@7891');

function conectar_bd() {
    $conexion = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if (!$conexion) {
        die('Error de conexión: ' . mysqli_connect_error());
    }
    
    mysqli_set_charset($conexion, 'utf8');
    return $conexion;
}


$conexion = conectar_bd();
$descripcion_evento = "prueba 2 server cron";
$stmt = mysqli_prepare($conexion, "
        INSERT INTO test_tabla ( descripcion_evento, fecha ) VALUES (?, NOW())
    ");
    
    if (!$stmt) {
        error_log("Error en prepare: " . mysqli_error($conexion));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "s", 
        $descripcion_evento
    );
    
    $resultado = mysqli_stmt_execute($stmt);
    
    if (!$resultado) {
        error_log("Error en execute: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return false;
    }
    
    $usuario_id = mysqli_insert_id($conexion);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

?>
