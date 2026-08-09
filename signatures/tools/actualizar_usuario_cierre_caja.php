<?php
/**
 * Mantenimiento: rellenar usuario_cierre en cierre_caja_{sucursal}
 *
 * Usuarios: estado_usuario = 'true' y sucursal_usuario asignada (> 0).
 *
 * Uso en navegador (requiere sesión root o super_admin):
 *   /tools/actualizar_usuario_cierre_caja.php              → solo vista previa
 *   /tools/actualizar_usuario_cierre_caja.php?confirmar=1 → ejecuta UPDATE
 */

require_once __DIR__ . '/../include/session.php';
require_once __DIR__ . '/../include/functions.php';

$esRoot = (isset($usuario_root) && $usuario_root === 'true');
$esSuperAdmin = (isset($usuario_super_administrador) && $usuario_super_administrador === 'true');

if (!$esRoot && !$esSuperAdmin) {
    http_response_code(403);
    exit('Acceso denegado. Solo usuario root o super administrador.');
}

$confirmar = isset($_GET['confirmar']) && (string) $_GET['confirmar'] === '1';
$conexion = conectar_bd();

if (!$conexion) {
    exit('Error de conexión a la base de datos.');
}

header('Content-Type: text/html; charset=utf-8');

$resultados = [];
$totalFilasActualizadas = 0;
$errores = [];

$queryUsuarios = "
    SELECT
        u.id_usuario,
        u.usuario,
        u.nombre_usuario,
        u.apellido_usuario,
        u.sucursal_usuario,
        COALESCE(s.nombre_sucursal, '') AS nombre_sucursal,
        COALESCE(p.nombre_privilegio, '') AS nombre_privilegio,
        COALESCE(p.sucursal_section, 'false') AS sucursal_section
    FROM usuarios u
    LEFT JOIN privilegios_usuarios p ON u.privilegio_usuario = p.id_privilegios
    LEFT JOIN sucursal s ON u.sucursal_usuario = s.id_sucursal
    WHERE u.estado_usuario = 'true'
      AND u.sucursal_usuario IS NOT NULL
      AND u.sucursal_usuario > 0
    ORDER BY u.sucursal_usuario ASC, u.id_usuario ASC
";

$resultUsuarios = mysqli_query($conexion, $queryUsuarios);

if (!$resultUsuarios) {
    mysqli_close($conexion);
    exit('Error al consultar usuarios: ' . htmlspecialchars(mysqli_error($conexion), ENT_QUOTES, 'UTF-8'));
}

$usuarios = [];
while ($row = mysqli_fetch_assoc($resultUsuarios)) {
    $usuarios[] = $row;
}
mysqli_free_result($resultUsuarios);

$sucursalesProcesadas = [];

foreach ($usuarios as $usuario) {
    $idUsuario = (int) $usuario['id_usuario'];
    $idSucursal = (int) $usuario['sucursal_usuario'];
    $tablaCierre = 'cierre_caja_' . $idSucursal;

    $fila = [
        'id_usuario' => $idUsuario,
        'usuario' => $usuario['usuario'],
        'nombre' => trim($usuario['nombre_usuario'] . ' ' . $usuario['apellido_usuario']),
        'sucursal_usuario' => $idSucursal,
        'nombre_sucursal' => $usuario['nombre_sucursal'],
        'privilegio' => $usuario['nombre_privilegio'],
        'tabla' => $tablaCierre,
        'tabla_existe' => false,
        'columna_existe' => false,
        'pendientes' => 0,
        'actualizados' => 0,
        'estado' => 'ok',
        'mensaje' => '',
    ];

    if (isset($sucursalesProcesadas[$idSucursal])) {
        $fila['estado'] = 'omitido';
        $fila['mensaje'] = 'Sucursal ya procesada con usuario #' . $sucursalesProcesadas[$idSucursal];
        $resultados[] = $fila;
        continue;
    }

    $checkTabla = mysqli_query($conexion, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conexion, $tablaCierre) . "'");
    if (!$checkTabla || mysqli_num_rows($checkTabla) === 0) {
        $fila['estado'] = 'aviso';
        $fila['mensaje'] = 'No existe la tabla ' . $tablaCierre;
        $resultados[] = $fila;
        continue;
    }
    $fila['tabla_existe'] = true;

    $checkColumna = mysqli_query($conexion, "SHOW COLUMNS FROM `{$tablaCierre}` LIKE 'usuario_cierre'");
    if (!$checkColumna || mysqli_num_rows($checkColumna) === 0) {
        $fila['estado'] = 'aviso';
        $fila['mensaje'] = 'La tabla no tiene la columna usuario_cierre';
        $resultados[] = $fila;
        continue;
    }
    $fila['columna_existe'] = true;

    $sqlPendientes = "SELECT COUNT(*) AS total FROM `{$tablaCierre}`
                      WHERE usuario_cierre IS NULL OR usuario_cierre = 0 OR usuario_cierre = ''";
    $resPendientes = mysqli_query($conexion, $sqlPendientes);
    if ($resPendientes) {
        $rowPendientes = mysqli_fetch_assoc($resPendientes);
        $fila['pendientes'] = (int) ($rowPendientes['total'] ?? 0);
        mysqli_free_result($resPendientes);
    }

    if ($confirmar && $fila['pendientes'] > 0) {
        $stmtUpdate = mysqli_prepare(
            $conexion,
            "UPDATE `{$tablaCierre}`
             SET usuario_cierre = ?
             WHERE usuario_cierre IS NULL OR usuario_cierre = 0 OR usuario_cierre = ''"
        );

        if (!$stmtUpdate) {
            $fila['estado'] = 'error';
            $fila['mensaje'] = mysqli_error($conexion);
            $errores[] = $tablaCierre . ': ' . $fila['mensaje'];
        } else {
            mysqli_stmt_bind_param($stmtUpdate, 'i', $idUsuario);
            if (!mysqli_stmt_execute($stmtUpdate)) {
                $fila['estado'] = 'error';
                $fila['mensaje'] = mysqli_stmt_error($stmtUpdate);
                $errores[] = $tablaCierre . ': ' . $fila['mensaje'];
            } else {
                $fila['actualizados'] = mysqli_stmt_affected_rows($stmtUpdate);
                $totalFilasActualizadas += $fila['actualizados'];
                $fila['mensaje'] = $fila['actualizados'] . ' fila(s) actualizada(s)';
                $sucursalesProcesadas[$idSucursal] = $idUsuario;
            }
            mysqli_stmt_close($stmtUpdate);
        }
    } elseif ($confirmar && $fila['pendientes'] === 0) {
        $fila['estado'] = 'sin_cambios';
        $fila['mensaje'] = 'No hay registros pendientes de usuario_cierre';
        $sucursalesProcesadas[$idSucursal] = $idUsuario;
    } else {
        $fila['mensaje'] = $fila['pendientes'] . ' registro(s) pendiente(s) de actualizar';
        $sucursalesProcesadas[$idSucursal] = $idUsuario;
    }

    $resultados[] = $fila;
}

mysqli_close($conexion);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Actualizar usuario_cierre en cierre_caja</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 24px; color: #222; }
        h1 { font-size: 1.35rem; margin-bottom: 8px; }
        .meta { margin-bottom: 20px; color: #555; }
        table { border-collapse: collapse; width: 100%; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; vertical-align: top; }
        th { background: #f5f5f5; }
        .ok { color: #198754; }
        .aviso { color: #856404; }
        .error { color: #dc3545; }
        .omitido { color: #6c757d; }
        .sin_cambios { color: #0d6efd; }
        .actions { margin: 20px 0; }
        .btn {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }
        .btn-primary { background: #0d6efd; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; margin-left: 8px; }
        .alert {
            padding: 12px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        .alert-info { background: #e7f1ff; border: 1px solid #b6d4fe; }
        .alert-success { background: #d1e7dd; border: 1px solid #badbcc; }
        .alert-danger { background: #f8d7da; border: 1px solid #f5c2c7; }
    </style>
</head>
<body>
    <h1>Actualizar usuario_cierre en tablas cierre_caja_*</h1>

    <div class="meta">
        Modo: <strong><?php echo $confirmar ? 'EJECUCIÓN' : 'VISTA PREVIA'; ?></strong><br>
        Usuarios encontrados: <strong><?php echo count($usuarios); ?></strong><br>
        Sucursales distintas: <strong><?php echo count($sucursalesProcesadas); ?></strong>
        <?php if ($confirmar): ?>
            <br>Filas actualizadas: <strong><?php echo $totalFilasActualizadas; ?></strong>
        <?php endif; ?>
    </div>

    <?php if (!$confirmar): ?>
        <div class="alert alert-info">
            Esta es una vista previa. No se ha modificado ningún dato.
            Solo se actualizarán filas con <code>usuario_cierre</code> vacío, NULL o 0.
            Por sucursal se usa el primer usuario activo encontrado (orden por id_usuario).
        </div>
    <?php elseif (empty($errores)): ?>
        <div class="alert alert-success">Proceso completado sin errores.</div>
    <?php else: ?>
        <div class="alert alert-danger">
            Se produjeron errores:<br>
            <?php echo implode('<br>', array_map('htmlspecialchars', $errores)); ?>
        </div>
    <?php endif; ?>

    <div class="actions">
        <?php if (!$confirmar): ?>
            <a class="btn btn-primary" href="?confirmar=1">Ejecutar actualización</a>
        <?php endif; ?>
        <a class="btn btn-secondary" href="?">Recargar vista previa</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID usuario</th>
                <th>Usuario</th>
                <th>Nombre</th>
                <th>Sucursal ID</th>
                <th>Sucursal</th>
                <th>Privilegio</th>
                <th>Tabla</th>
                <th>Pendientes</th>
                <th>Actualizados</th>
                <th>Estado</th>
                <th>Detalle</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($resultados)): ?>
                <tr><td colspan="11">No hay usuarios que cumplan los criterios.</td></tr>
            <?php else: ?>
                <?php foreach ($resultados as $r): ?>
                    <tr>
                        <td><?php echo (int) $r['id_usuario']; ?></td>
                        <td><?php echo htmlspecialchars($r['usuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int) $r['sucursal_usuario']; ?></td>
                        <td><?php echo htmlspecialchars($r['nombre_sucursal'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($r['privilegio'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($r['tabla'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int) $r['pendientes']; ?></td>
                        <td><?php echo (int) $r['actualizados']; ?></td>
                        <td class="<?php echo htmlspecialchars($r['estado'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($r['estado'], ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td><?php echo htmlspecialchars($r['mensaje'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
