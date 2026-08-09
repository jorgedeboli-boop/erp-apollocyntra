<?php
require_once '../../../../include/config.php';

// Verificar si la tabla existe
$sql = "SHOW TABLES LIKE 'grupos_movimientos'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "La tabla 'grupos_movimientos' existe.<br>";
    
    // Mostrar estructura de la tabla
    $sql_structure = "DESCRIBE grupos_movimientos";
    $result_structure = $conn->query($sql_structure);
    
    if ($result_structure) {
        echo "<h3>Estructura de la tabla:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $result_structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Contar registros
        $sql_count = "SELECT COUNT(*) as total FROM grupos_movimientos";
        $result_count = $conn->query($sql_count);
        if ($result_count) {
            $count = $result_count->fetch_assoc()['total'];
            echo "<br>Total de registros: " . $count;
        }
    }
    
} else {
    echo "La tabla 'grupos_movimientos' NO existe.<br>";
    echo "Necesitas crearla con la siguiente estructura:<br><br>";
    echo "<code>";
    echo "CREATE TABLE `grupos_movimientos` (<br>";
    echo "  `id_grupo` int(9) NOT NULL AUTO_INCREMENT,<br>";
    echo "  `nombre_grupo` varchar(180) NOT NULL,<br>";
    echo "  `tipo_grupo` varchar(28) NOT NULL,<br>";
    echo "  PRIMARY KEY (`id_grupo`)<br>";
    echo ") ENGINE=MyISAM DEFAULT CHARSET=latin1;<br>";
    echo "</code>";
}
?>
