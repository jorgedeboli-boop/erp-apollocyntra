<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Conectar BD
$conexion = conectar_bd();
// Iniciar transacción
mysqli_begin_transaction($conexion);
    

// Verificar que se haya enviado el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../../empresas.php');
    exit;
}

// Obtener los datos del formulario
$nombre_empresa = trim($_POST['nombre_empresa']);
$cif_empresa = trim($_POST['cif_empresa']);
$direccion_empresa = trim($_POST['direccion_empresa']);
$poblacion_empresa = trim($_POST['poblacion_empresa']);
$provincia_empresa = trim($_POST['provincia_empresa']);
$telefono_empresa = preg_replace('/[^0-9]/', '', $_POST['telefono_empresa']);
$codigo_postal_empresa = trim($_POST['codigo_postal_empresa']);
$pais_empresa = trim($_POST['pais_empresa']);
$email_empresa = trim($_POST['email_empresa']);

// Validar que todos los campos obligatorios estén presentes
if (empty($nombre_empresa) || empty($cif_empresa) || empty($direccion_empresa) || 
    empty($poblacion_empresa) || empty($provincia_empresa) || empty($telefono_empresa) || 
    empty($codigo_postal_empresa) || empty($pais_empresa) || empty($email_empresa)) {
    $_SESSION['error'] = 'Todos los campos son obligatorios';
    header('Location: ../../../empresas.php');
    exit;
}

// Validar formato de email
if (!filter_var($email_empresa, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'El formato del email no es válido';
    header('Location: ../../../empresas.php');
    exit;
}

try {
    // Preparar la consulta SQL
    $sql = "INSERT INTO empresas (
        nombre_empresa, 
        cif_empresa, 
        direccion_empresa, 
        poblacion_empresa, 
        provincia_empresa, 
        telefono_empresa, 
        codigo_postal_empresa, 
        pais_empresa, 
        email_empresa,
        fecha_creacion_empresa,
        creada_por
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssssssssi", 
        $nombre_empresa,
        $cif_empresa,
        $direccion_empresa,
        $poblacion_empresa,
        $provincia_empresa,
        $telefono_empresa,
        $codigo_postal_empresa,
        $pais_empresa,
        $email_empresa,
        $usuario_id
    );
    
    if ($stmt->execute()) {
        // Commit de la transacción
        mysqli_commit($conexion);
        $_SESSION['success'] = 'Empresa creada exitosamente';
        header('Location: ../../../empresas.php');
        exit;
    } else {
        throw new Exception('Error al ejecutar la consulta');
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    // Rollback de la transacción en caso de error
    mysqli_rollback($conexion);
    $_SESSION['error'] = 'Error al crear la empresa: ' . $e->getMessage();
    header('Location: ../../../empresas.php');
    exit;
}

$conexion->close();
?>
