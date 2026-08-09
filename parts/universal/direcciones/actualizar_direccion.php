<?php
// OBTENER DATOS DE LAS DIRECCIONES
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
         $observaciones_direccion = isset($_POST['observaciones_direccion']) ? trim($_POST['observaciones_direccion']) : '';
         // FIN DE OBTENER DATOS DE LAS DIRECCIONES

        // INSERTO LA DIRECCION
        $id_direccion = actualizarDireccion($id_type_direccion, $type_direccion, $direccion, $provincia_texto, $poblacion_texto, $pais_texto, $codigo_postal, $provincia_id, $pais_id, $poblacion_id, $observaciones_direccion);
        if (!$id_direccion) {
            throw new Exception("Error al actualizar la dirección");
        }
?>