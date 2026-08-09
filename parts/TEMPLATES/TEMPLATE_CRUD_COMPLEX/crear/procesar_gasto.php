<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Conectar BD
$conexion = conectar_bd();
// Iniciar transacción
mysqli_begin_transaction($conexion);
    

// Verificar que se haya enviado el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../../gastos.php');
    exit;
}

// Obtener los datos del formulario
$nombre_gasto = trim($_POST['nombre_gasto']);
$cif_gasto = trim($_POST['cif_gasto']);
$direccion_gasto = trim($_POST['direccion_gasto']);
$poblacion_gasto = trim($_POST['poblacion_gasto']);
$provincia_gasto = trim($_POST['provincia_gasto']);
$telefono_gasto = preg_replace('/[^0-9]/', '', $_POST['telefono_gasto']);
$codigo_postal_gasto = trim($_POST['codigo_postal_gasto']);
$pais_gasto = trim($_POST['pais_gasto']);
$email_gasto = trim($_POST['email_gasto']);

// Validar que todos los campos obligatorios estén presentes
if (empty($nombre_gasto) || empty($cif_gasto) || empty($direccion_gasto) || 
    empty($poblacion_gasto) || empty($provincia_gasto) || empty($telefono_gasto) || 
    empty($codigo_postal_gasto) || empty($pais_gasto) || empty($email_gasto)) {
    $_SESSION['error'] = 'Todos los campos son obligatorios';
    header('Location: ../../../gastos.php');
    exit;
}

// Validar formato de email
if (!filter_var($email_gasto, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'El formato del email no es válido';
    header('Location: ../../../gastos.php');
    exit;
}

try {
    // Preparar la consulta SQL
    $sql = "INSERT INTO gastos (
        nombre_gasto, 
        cif_gasto, 
        direccion_gasto, 
        poblacion_gasto, 
        provincia_gasto, 
        telefono_gasto, 
        codigo_postal_gasto, 
        pais_gasto, 
        email_gasto,
        fecha_creacion_gasto,
        creada_por
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssssssssi", 
        $nombre_gasto,
        $cif_gasto,
        $direccion_gasto,
        $poblacion_gasto,
        $provincia_gasto,
        $telefono_gasto,
        $codigo_postal_gasto,
        $pais_gasto,
        $email_gasto,
        $usuario_id
    );
    
    if ($stmt->execute()) {
        // Commit de la transacción
        mysqli_commit($conexion);
        $_SESSION['success'] = 'Empresa creada exitosamente';
        header('Location: ../../../gastos.php');
        exit;
    } else {
        throw new Exception('Error al ejecutar la consulta');
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    // Rollback de la transacción en caso de error
    mysqli_rollback($conexion);
    $_SESSION['error'] = 'Error al crear la gasto: ' . $e->getMessage();
    header('Location: ../../../gastos.php');
    exit;
}

$conexion->close();
?>
