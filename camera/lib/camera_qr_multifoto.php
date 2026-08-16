<?php
/**
 * QR móvil: varias fotos por sesión de token (cliente, lote, artículo, venta, ticket, gasto).
 * No aplica a adelanto, plazo_venta, renovación, autorizar_gasto, etc.
 */

/**
 * @return string[]
 */
function camera_qr_tipos_multifoto(): array
{
    return array('cliente', 'lote', 'articulo', 'venta', 'articulo_venta', 'gasto');
}

/**
 * Tipos de QR que en este ERP no usan sucursal (columna eliminada).
 *
 * @param string $tipo
 */
function camera_tipo_sin_sucursal($tipo)
{
    return in_array((string) $tipo, array('cliente', 'articulo'), true);
}

function camera_qr_es_multifoto(string $tipo): bool
{
    return in_array($tipo, camera_qr_tipos_multifoto(), true);
}

function camera_token_ensure_foto_count_column($conexion): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $r = @mysqli_query($conexion, "SHOW COLUMNS FROM tokens_actions LIKE 'foto_subidas_count'");
    if ($r && mysqli_num_rows($r) === 0) {
        @mysqli_query(
            $conexion,
            'ALTER TABLE tokens_actions ADD COLUMN foto_subidas_count INT NOT NULL DEFAULT 0'
        );
    }
    if ($r) {
        mysqli_free_result($r);
    }
    $done = true;
}

/**
 * type_item era ENUM sin 'articulo' (y otros tipos de cámara).
 * Se amplía a VARCHAR para poder guardar tokens de ficha de artículo.
 */
function camera_token_ensure_type_item_column($conexion): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $r = @mysqli_query($conexion, "SHOW COLUMNS FROM tokens_actions LIKE 'type_item'");
    if ($r && ($col = mysqli_fetch_assoc($r))) {
        $type = strtolower((string) ($col['Type'] ?? ''));
        if (strpos($type, 'enum(') === 0) {
            @mysqli_query($conexion, 'ALTER TABLE tokens_actions MODIFY type_item VARCHAR(32) NOT NULL');
        }
        mysqli_free_result($r);
    }
    $done = true;
}

function camera_marcar_token_usado($conexion, $id_token_qr): void
{
    $id_token_qr = (int) $id_token_qr;
    if ($id_token_qr <= 0) {
        return;
    }
    $stmtT = mysqli_prepare($conexion, "UPDATE tokens_actions SET state_token = 'false' WHERE id_token = ?");
    if ($stmtT) {
        mysqli_stmt_bind_param($stmtT, 'i', $id_token_qr);
        mysqli_stmt_execute($stmtT);
        mysqli_stmt_close($stmtT);
    }
}

/**
 * Tras subir foto: multifoto incrementa contador; el resto marca token consumido.
 */
function camera_token_post_upload($conexion, int $id_token_qr, string $camera_type): void
{
    if ($id_token_qr <= 0) {
        return;
    }
    if (camera_qr_es_multifoto($camera_type)) {
        camera_token_ensure_foto_count_column($conexion);
        $stmt = mysqli_prepare(
            $conexion,
            'UPDATE tokens_actions SET foto_subidas_count = foto_subidas_count + 1 WHERE id_token = ?'
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id_token_qr);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        return;
    }
    camera_marcar_token_usado($conexion, $id_token_qr);
}
