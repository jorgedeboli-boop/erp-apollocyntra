<?php
$mysqli_figueredoapp = get_mysqli_figueredoapp();
if (!$mysqli_figueredoapp) {
    return;
}

$direccion = trim($_POST['direccion']);
$provincia_no_id = trim($_POST['provincia_no_id'] ?? '');
$poblacion_no_id = trim($_POST['poblacion_no_id'] ?? '');
$pais_id = (int)$_POST['pais'];
$provincia_id = (int)($_POST['c_provincia'] ?? 0);
$poblacion_id = (int)($_POST['c_poblacion'] ?? 0);
$pais_texto = '';
$provincia_texto = '';
$poblacion_texto = '';
if (!empty($provincia_no_id)) {
    $provincia_texto = $provincia_no_id;
    $poblacion_texto = $poblacion_no_id;
} else {
    $provincia_texto = obtenerTextoProvincia($conexion, $provincia_id);
    $poblacion_texto = obtenerTextoPoblacion($conexion, $poblacion_id);
}
$pais_texto = obtenerTextoPais($conexion, $pais_id);
$codigo_postal = trim($_POST['codigo_postal']);

// UPDATE en Figueredo
$sql = "UPDATE clientes SET
    nombre='$nombre',
    apellido='$apellido',
    direccion='$direccion',
    c_poblacion='$poblacion_texto',
    c_provincia='$provincia_texto',
    codigo_postal='$codigo_postal',
    tipo_identificacion='$tipo_identificacion_texto',
    sexo='$sexo',
    identificacion='$identificacion',
    nacionalidad='$nacionalidad_texto',
    f_nacimiento='$f_nacimiento',
    telefono='$telefono',
    email='$email',
    observaciones='$observaciones',
    f_vencimiento='$f_vencimiento',
    sucursal='$sucursal'
    WHERE id_cliente = $id_cliente";

$result = mysqli_query($mysqli_figueredoapp, $sql);

if(!$result){ 
    throw new Exception('Error al actualizar en Figueredo: ' . mysqli_error($mysqli_figueredoapp)); 
}
?>

