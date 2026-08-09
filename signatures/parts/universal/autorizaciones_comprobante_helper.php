<?php
/**
 * HTML del botón comprobante (compartido por load_list y poll).
 */
function renderComprobanteAutorizacion($imagen, $idApunte, $idAutorizacion)
{
    $imagen = trim((string) ($imagen ?? ''));
    if ($imagen === '') {
        return '<span class="text-muted">-</span>';
    }

    $imagenEsc = htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8');
    $ref = $idApunte ? (string) $idApunte : (string) $idAutorizacion;
    $refEsc = htmlspecialchars($ref, ENT_QUOTES, 'UTF-8');
    $btnStyle = 'width: 1.6rem;height: 1.6rem !important;color: white;';
    $ext = strtolower(pathinfo($imagen, PATHINFO_EXTENSION));

    if ($ext === 'pdf') {
        return '<button type="button" class="btn rounded-pill btn-icon btn-danger waves-effect waves-light ver-pdf-autorizacion" '
            . 'data-foto="' . $imagenEsc . '" data-lote="' . $refEsc . '" title="Ver pdf" style="' . $btnStyle . '">'
            . '<span class="icon-base ri ri-file-pdf-2-fill icon-17px"></span></button>';
    }

    $extensionesImagen = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'jfif'];
    if (in_array($ext, $extensionesImagen, true)) {
        return '<button type="button" class="btn rounded-pill btn-icon btn-info waves-effect waves-light ver-foto-autorizacion" '
            . 'data-foto="' . $imagenEsc . '" data-lote="' . $refEsc . '" title="Ver foto" style="' . $btnStyle . '">'
            . '<span class="icon-base ri ri-image-circle-ai-fill icon-17px"></span></button>';
    }

    return '<span class="text-muted">-</span>';
}
