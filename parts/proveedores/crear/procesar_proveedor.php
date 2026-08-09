<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Conectar BD
$conexion = conectar_bd();
// Iniciar transacción
mysqli_begin_transaction($conexion);
    

// Verificar que se haya enviado el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../../proveedores.php');
    exit;
}

// Obtener los datos del formulario
$nombre_proveedor = trim($_POST['nombre_proveedor']);
$cif_proveedor = trim($_POST['cif_proveedor']);
$direccion_proveedor = trim($_POST['direccion_proveedor']);

$rel_id_pais = (int)$_POST['pais'];
$rel_id_provincia = (int)$_POST['c_provincia'];
$rel_id_poblacion = (int)$_POST['c_poblacion'];

$pais_proveedor = obtenerTextoPais($conexion, $rel_id_pais);
$provincia_proveedor = obtenerTextoProvincia($conexion, $rel_id_provincia);
$poblacion_proveedor = obtenerTextoPoblacion($conexion, $rel_id_poblacion);

$telefono_proveedor = preg_replace('/[^0-9]/', '', $_POST['telefono_proveedor']);
$codigo_postal_proveedor = trim($_POST['codigo_postal']);
$email_proveedor = trim($_POST['email_proveedor']);
$moneda_proveedor = trim($_POST['moneda_proveedor']);
$forma_pago_proveedor = trim($_POST['forma_pago_proveedor']);

// Procesar checkboxes de fundición
$fundicion = isset($_POST['fundicion']) ? 'true' : 'false';
$fundicion_multi_kilates = isset($_POST['fundicion_multi_kilates']) ? 'true' : 'false';

// Validar que todos los campos obligatorios estén presentes
if (empty($nombre_proveedor) || empty($cif_proveedor) || empty($direccion_proveedor) || 
    empty($poblacion_proveedor) || empty($provincia_proveedor) || empty($telefono_proveedor) || 
    empty($codigo_postal_proveedor) || empty($pais_proveedor) || empty($email_proveedor) ||
    empty($moneda_proveedor) || empty($forma_pago_proveedor)) {
    $_SESSION['error'] = 'Todos los campos son obligatorios';
    header('Location: ../../../proveedores.php');
    exit;
}

// Validar formato de email
if (!filter_var($email_proveedor, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'El formato del email no es válido';
    header('Location: ../../../proveedores.php');
    exit;
}

try {
    // Preparar la consulta SQL
    $sql = "INSERT INTO proveedores (
        nombre_proveedor, 
        cif_proveedor, 
        direccion_proveedor, 
        poblacion_proveedor, 
        provincia_proveedor, 
        telefono_proveedor, 
        codigo_postal_proveedor, 
        pais_proveedor, 
        email_proveedor,
        moneda_proveedor,
        forma_pago_proveedor,
        fundicion,
        fundicion_multi_kilates,
        creado_por,
        rel_id_provincia,
        rel_id_poblacion,
        rel_id_pais,
        fecha_creacion_proveedor
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssssssssssssssss", 
        $nombre_proveedor,
        $cif_proveedor,
        $direccion_proveedor,
        $poblacion_proveedor,
        $provincia_proveedor,
        $telefono_proveedor,
        $codigo_postal_proveedor,
        $pais_proveedor,
        $email_proveedor,
        $moneda_proveedor,
        $forma_pago_proveedor,
        $fundicion,
        $fundicion_multi_kilates,
        $usuario_id,
        $rel_id_provincia,
        $rel_id_poblacion,
        $rel_id_pais
    );
    
    $texto_action_user = "$usuario creó el proveedor Nº '$id_proveedor'";
        $id_action_user = "34";
        $relItemAction = $_SESSION['relItemAction'];
        registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction);
        $_SESSION['relItemAction'] = "false";
    
    if ($stmt->execute()) {
        // Commit de la transacción
        mysqli_commit($conexion);
        $_SESSION['success'] = 'Proveedor creado exitosamente';
        header('Location: ../../../proveedores.php');
        exit;
    } else {
        throw new Exception('Error al ejecutar la consulta');
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    // Rollback de la transacción en caso de error
    mysqli_rollback($conexion);
    $_SESSION['error'] = 'Error al crear el proveedor: ' . $e->getMessage();
    header('Location: ../../../proveedores.php');
    exit;
}

$conexion->close();
?>
