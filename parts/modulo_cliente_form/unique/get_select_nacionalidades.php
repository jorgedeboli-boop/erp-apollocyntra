<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

$id_selected = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$required = isset($_GET['required']) ? (bool)$_GET['required'] : true;

// Generar el select con el ID seleccionado
$conexion = conectar_bd();

if (!$conexion) {
    echo "<select class='form-select select2' id='nacionalidad' name='nacionalidad'" . ($required ? ' required' : '') . ">";
    echo "<option value=''>Error de conexión</option>";
    echo "</select>";
    exit;
}

$query = "SELECT id, nombre_nacionalidad FROM nacionalidades ORDER BY nombre_nacionalidad ASC";
$resultado = mysqli_query($conexion, $query);

if (!$resultado) {
    echo "<select class='form-select select2' id='nacionalidad' name='nacionalidad'" . ($required ? ' required' : '') . ">";
    echo "<option value=''>Error en consulta</option>";
    echo "</select>";
    mysqli_close($conexion);
    exit;
}

echo "<select class='form-select select2' id='nacionalidad' name='nacionalidad'" . ($required ? ' required' : '') . ">";
echo "<option value=''>Seleccionar...</option>";

// Opción "Española" con valor 54 - verificar si debe estar seleccionada
$selected_espanola = ($id_selected == 54) ? 'selected' : '';
echo "<option value='54' {$selected_espanola}>Española</option>";

while ($row = mysqli_fetch_assoc($resultado)) {
    if($row['id'] != 54) {
        $selected = ($row['id'] == $id_selected) ? 'selected' : '';
        echo "<option value='{$row['id']}' {$selected}>" . htmlspecialchars($row['nombre_nacionalidad']) . "</option>";
    }
}

echo "</select>";
mysqli_close($conexion);
?>
