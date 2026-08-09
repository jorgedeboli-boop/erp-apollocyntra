<?php
declare(strict_types=1);

/**
 * Marca plazos de venta vencidos el día de ejecución, actualiza la venta,
 * registra acciones_cron y crea un nuevo plazo si aún no se alcanzó numero_plazos.
 */

function cron_revisar_plazos_vencidos(mysqli $conexion): array
{
    $resumen = [
        'plazos_procesados' => 0,
        'plazos_nuevos' => 0,
        'errores' => 0,
    ];

    @mysqli_set_charset($conexion, 'utf8');

    $hoy = date('Y-m-d');
    $hoyEsc = mysqli_real_escape_string($conexion, $hoy);

    $query_vencidos = "SELECT * FROM ventas_plazos
        WHERE estado = 'Pendiente'
          AND DATE(fecha_vencimiento) = '{$hoyEsc}'";

    $rVencidos = mysqli_query($conexion, $query_vencidos);
    if ($rVencidos === false) {
        throw new RuntimeException('Error consultando plazos vencidos: ' . mysqli_error($conexion));
    }

    while ($aVencido = mysqli_fetch_assoc($rVencidos)) {
        $idPlazo = (int)($aVencido['id'] ?? 0);
        $idVenta = (int)($aVencido['id_venta'] ?? 0);
        if ($idPlazo <= 0 || $idVenta <= 0) {
            $resumen['errores']++;
            continue;
        }

        $sql_venta = 'SELECT * FROM ventas WHERE ventas.id = ' . $idVenta;
        $rsQuery = mysqli_query($conexion, $sql_venta);
        if ($rsQuery === false) {
            throw new RuntimeException('Error consultando venta: ' . mysqli_error($conexion));
        }
        $rsVenta = mysqli_fetch_assoc($rsQuery);
        if ($rsVenta === null) {
            $resumen['errores']++;
            continue;
        }

        $idSucursal = (int)($rsVenta['id_sucursal'] ?? 0);
        $idVentaGeneral = (int)($rsVenta['id'] ?? 0);
        $idVentaSucursal = (int)($rsVenta['id_venta_sucursal'] ?? 0);

        $sql_update_plazo = "UPDATE ventas_plazos SET estado = 'Vencido', fecha_vencido = '{$hoyEsc}' WHERE id = {$idPlazo}";
        if (!mysqli_query($conexion, $sql_update_plazo)) {
            throw new RuntimeException('Error actualizando plazo: ' . mysqli_error($conexion));
        }

        $msg1 = mysqli_real_escape_string(
            $conexion,
            'Plazo vencido de la venta Nº ' . $idVentaSucursal . '. Id_venta general: ' . $idVentaGeneral
        );
        $sql_acciones_cron = "INSERT INTO acciones_cron (id_sucursal, tipo_accion, id_lote, mensaje)
            VALUES ({$idSucursal},'Plazo vencido',{$idPlazo}, '{$msg1}')";
        if (!mysqli_query($conexion, $sql_acciones_cron)) {
            throw new RuntimeException('Error insertando acciones_cron (plazo vencido): ' . mysqli_error($conexion));
        }

        $sql_update_venta = "UPDATE ventas SET estado = 'vencido' WHERE id = {$idVenta}";
        if (!mysqli_query($conexion, $sql_update_venta)) {
            throw new RuntimeException('Error actualizando venta a vencido: ' . mysqli_error($conexion));
        }

        $msg2 = mysqli_real_escape_string(
            $conexion,
            'Venta pasada a vencido: ' . $idVentaSucursal . '. Id_venta general: ' . $idVentaGeneral
        );
        $sql_acciones_cron = "INSERT INTO acciones_cron (id_sucursal, tipo_accion, id_lote, mensaje)
            VALUES ({$idSucursal},'Venta vencida',{$idVenta}, '{$msg2}')";
        if (!mysqli_query($conexion, $sql_acciones_cron)) {
            throw new RuntimeException('Error insertando acciones_cron (venta vencida): ' . mysqli_error($conexion));
        }

        $sql_datos_venta = 'SELECT * FROM ventas WHERE id = ' . $idVenta;
        $rDatos = mysqli_query($conexion, $sql_datos_venta);
        if ($rDatos === false) {
            throw new RuntimeException('Error releyendo venta: ' . mysqli_error($conexion));
        }
        $aVenta = mysqli_fetch_assoc($rDatos);
        if ($aVenta === null) {
            $resumen['errores']++;
            continue;
        }

        $sql_plazos_creados = 'SELECT COUNT(*) as cuenta FROM ventas_plazos WHERE id_venta = ' . $idVenta;
        $rCuenta = mysqli_query($conexion, $sql_plazos_creados);
        if ($rCuenta === false) {
            throw new RuntimeException('Error contando plazos: ' . mysqli_error($conexion));
        }
        $rowCuenta = mysqli_fetch_assoc($rCuenta);
        $plazos_creados = (int)($rowCuenta['cuenta'] ?? 0);
        $numero_plazos = (int)($aVenta['numero_plazos'] ?? 0);

        $importeNum = (float)($aVencido['importe'] ?? 0);

        if ($plazos_creados < $numero_plazos) {
            $fechaNueva = date('Y-m-d H:i:s', strtotime('+1 month'));
            $fechaNuevaEsc = mysqli_real_escape_string($conexion, $fechaNueva);

            $sql_insert_plazo = "INSERT INTO ventas_plazos (
                id_venta,
                estado,
                fecha_vencimiento,
                importe
            ) VALUES (
                {$idVenta},
                'Pendiente',
                '{$fechaNuevaEsc}',
                {$importeNum}
            )";
            if (!mysqli_query($conexion, $sql_insert_plazo)) {
                throw new RuntimeException('Error insertando nuevo plazo: ' . mysqli_error($conexion));
            }

            $id_plazo_nuevo = (int)mysqli_insert_id($conexion);
            $msg3 = mysqli_real_escape_string(
                $conexion,
                'Nuevo plazo creado con id: ' . $id_plazo_nuevo
            );
            $sql_acciones_cron = "INSERT INTO acciones_cron (id_sucursal, tipo_accion, id_lote, mensaje)
                VALUES ({$idSucursal},'Plazo',{$id_plazo_nuevo}, '{$msg3}')";
            if (!mysqli_query($conexion, $sql_acciones_cron)) {
                throw new RuntimeException('Error insertando acciones_cron (nuevo plazo): ' . mysqli_error($conexion));
            }

            $resumen['plazos_nuevos']++;
        }

        $resumen['plazos_procesados']++;
    }

    return $resumen;
}
