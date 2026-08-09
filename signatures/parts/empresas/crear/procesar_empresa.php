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
$rel_id_provincia = isset($_POST['rel_id_provincia']) ? (int) $_POST['rel_id_provincia'] : 0;
$rel_id_poblacion = isset($_POST['rel_id_poblacion']) ? (int) $_POST['rel_id_poblacion'] : 0;
$rel_id_pais = isset($_POST['rel_id_pais']) ? (int) $_POST['rel_id_pais'] : 0;

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
        rel_id_provincia,
        rel_id_poblacion,
        rel_id_pais,
        fecha_creacion_empresa,
        creada_por
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssssssssiiii", 
        $nombre_empresa,
        $cif_empresa,
        $direccion_empresa,
        $poblacion_empresa,
        $provincia_empresa,
        $telefono_empresa,
        $codigo_postal_empresa,
        $pais_empresa,
        $email_empresa,
        $rel_id_provincia,
        $rel_id_poblacion,
        $rel_id_pais,
        $usuario_id
    );

    if ($stmt->execute()) {
        $id_empresa = (int) mysqli_insert_id($conexion);
        if ($id_empresa <= 0) {
            throw new Exception('No se pudo obtener el ID de la empresa');
        }

        // invoices/companys/{id}/{año}/facturas|facturas_simplificadas|facturas_rectificativas
        $root_proyecto = dirname(__DIR__, 3);
        $anio_actual = date('Y');
        $base_empresa = $root_proyecto . DIRECTORY_SEPARATOR . 'invoices' . DIRECTORY_SEPARATOR . 'companys'
            . DIRECTORY_SEPARATOR . $id_empresa . DIRECTORY_SEPARATOR . $anio_actual;
        $subcarpetas = array('facturas', 'facturas_simplificadas', 'facturas_rectificativas');
        foreach ($subcarpetas as $sub) {
            $ruta = $base_empresa . DIRECTORY_SEPARATOR . $sub;
            if (!is_dir($ruta) && !mkdir($ruta, 0755, true)) {
                throw new Exception('No se pudo crear la carpeta: ' . $ruta);
            }
        }

        $texto_action_user = "$usuario creó la empresa '$nombre_empresa'";
        $id_action_user = "34";
        $relItemAction = $_SESSION['relItemAction'];
        registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction);
        $_SESSION['relItemAction'] = "false";

        mysqli_commit($conexion);
        $stmt->close();
        $_SESSION['success'] = 'Empresa creada exitosamente';
        header('Location: ../../../empresas.php');
        exit;
    }

    throw new Exception('Error al ejecutar la consulta');
    
} catch (Exception $e) {
    // Rollback de la transacción en caso de error
    mysqli_rollback($conexion);
    $_SESSION['error'] = 'Error al crear la empresa: ' . $e->getMessage();
    header('Location: ../../../empresas.php');
    exit;
}

$conexion->close();
?>
