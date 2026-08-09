<?php

/**
 * Sucursales activas para tareas del cron.
 */

/**
 * @return array<int, array<string, mixed>>
 */
function cron_obtener_sucursales_activas($conexion)
{
    $sucursales = array();

    $sql = "SELECT * FROM sucursal WHERE estado_tienda = 'habilitada' ORDER BY id_sucursal ASC";
    $resultado = mysqli_query($conexion, $sql);

    if (!$resultado) {
        throw new Exception('Error al obtener sucursales activas: ' . mysqli_error($conexion));
    }

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $sucursales[] = $fila;
    }

    mysqli_free_result($resultado);

    return $sucursales;
}

/**
 * Sucursales habilitadas con SMS de empeño activo.
 *
 * @param mysqli $conexion
 * @return array<int, array<string, mixed>>
 */
function cron_obtener_sucursales_sms_empeno($conexion)
{
    $sucursales = array();

    $sql = "SELECT * FROM sucursal
            WHERE estado_tienda = 'habilitada'
              AND sms_state_empeno = 'true'
            ORDER BY id_sucursal ASC";
    $resultado = mysqli_query($conexion, $sql);

    if (!$resultado) {
        throw new Exception('Error al obtener sucursales SMS empeño: ' . mysqli_error($conexion));
    }

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $sucursales[] = $fila;
    }

    mysqli_free_result($resultado);

    return $sucursales;
}
