<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Conectar BD
$conexion = conectar_bd();
// Iniciar transacción
mysqli_begin_transaction($conexion);
    

// Verificar que se haya enviado el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../../Languages.php');
    exit;
}

// Obtener los datos del formulario
$cod_LP = trim($_POST['cod_LP']);
$description = trim($_POST['description']);
$rel_id_country = (int)$_POST['rel_id_country'];
$stateLang = isset($_POST['stateLang']) ? 'true' : 'false';

// Validar que todos los campos obligatorios estén presentes
if (empty($cod_LP) || empty($description) || empty($rel_id_country)) {
    $_SESSION['error'] = 'Todos los campos son obligatorios';
    header('Location: ../../../Languages.php');
    exit;
}

// Validar formato del código
if (strlen($cod_LP) < 2 || strlen($cod_LP) > 5) {
    $_SESSION['error'] = 'El código debe tener entre 2 y 5 caracteres';
    header('Location: ../../../Languages.php');
    exit;
}

try {
    // Preparar la consulta SQL
    $sql = "INSERT INTO Languages (
        cod_LP, 
        description, 
        rel_id_country, 
        stateLang
    ) VALUES (?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssis", 
        $cod_LP,
        $description,
        $rel_id_country,
        $stateLang
    );
    
    if ($stmt->execute()) {
        // Commit de la transacción
        mysqli_commit($conexion);
        $_SESSION['success'] = 'Language creado exitosamente';
        header('Location: ../../../Languages.php');
        exit;
    } else {
        throw new Exception('Error al ejecutar la consulta');
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    // Rollback de la transacción en caso de error
    mysqli_rollback($conexion);
    $_SESSION['error'] = 'Error al crear el Language: ' . $e->getMessage();
    header('Location: ../../../Languages.php');
    exit;
}

$conexion->close();
?>
