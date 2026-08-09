<?php
/**
 * Vista imprimible de etiqueta para artículo de venta (SKU).
 */
require_once __DIR__ . '/../include/session.php';
require_once __DIR__ . '/../include/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('HTTP/1.0 400 Bad Request');
    exit('ID no válido');
}

$conexion = conectar_bd();
$stmt = mysqli_prepare($conexion, 'SELECT * FROM articulos_venta WHERE id = ? LIMIT 1');
if (!$stmt) {
    mysqli_close($conexion);
    header('HTTP/1.0 500 Internal Server Error');
    exit('Error de base de datos');
}
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$art = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);
mysqli_close($conexion);

if (!$art) {
    header('HTTP/1.0 404 Not Found');
    exit('Artículo no encontrado');
}

$precio_fmt = isset($art['precio']) ? number_format((float) $art['precio'], 2, ',', '.') : '—';
$peso_fmt = isset($art['peso']) ? number_format((float) $art['peso'], 2, ',', '.') : '—';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Etiqueta SKU <?php echo htmlspecialchars((string) $art['id']); ?></title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
      margin: 0;
      padding: 16px;
      color: #222;
    }
    .no-print { margin-bottom: 12px; }
    .etiqueta {
      max-width: 320px;
      border: 2px solid #333;
      border-radius: 8px;
      padding: 14px 16px;
      margin: 0 auto;
    }
    .sku { font-size: 1.35rem; font-weight: 700; letter-spacing: 0.02em; margin-bottom: 8px; }
    .id-suc { font-size: 0.85rem; color: #555; margin-bottom: 10px; }
    .desc { font-size: 0.95rem; line-height: 1.35; margin-bottom: 10px; word-break: break-word; }
    .meta { font-size: 0.8rem; color: #444; }
    .meta span { display: block; margin-top: 4px; }
    .btn-print {
      padding: 8px 16px;
      font-size: 14px;
      cursor: pointer;
      border: none;
      border-radius: 6px;
      background: #696cff;
      color: #fff;
    }
    .btn-print:hover { filter: brightness(0.95); }
    @media print {
      .no-print { display: none !important; }
      body { padding: 0; }
      .etiqueta { border: 2px solid #000; }
    }
  </style>
</head>
<body>
  <div class="no-print">
    <button type="button" class="btn-print" onclick="window.print()">Imprimir</button>
  </div>
  <div class="etiqueta">
    <div class="sku">SKU <?php echo htmlspecialchars((string) $art['id']); ?></div>
    <div class="id-suc">ID sucursal: <?php echo htmlspecialchars((string) ($art['id_articulo_sucursal'] ?? '—')); ?></div>
    <div class="desc"><?php echo htmlspecialchars((string) ($art['descripcion'] ?? '')); ?></div>
    <div class="meta">
      <span><strong>Tipo:</strong> <?php echo htmlspecialchars((string) ($art['tipo'] ?? '—')); ?></span>
      <span><strong>Ley:</strong> <?php echo htmlspecialchars((string) ($art['ley'] ?? '—')); ?></span>
      <span><strong>Peso:</strong> <?php echo $peso_fmt; ?> g</span>
      <span><strong>Precio:</strong> <?php echo $precio_fmt; ?> €</span>
      <span><strong>Estado:</strong> <?php echo htmlspecialchars((string) ($art['estado'] ?? '—')); ?></span>
    </div>
  </div>
</body>
</html>
