<?php
/**
 * Plantilla HTML del contrato de empeño (opción de compra).
 */

require_once __DIR__ . '/contrato_compra_plantilla.php';

function contrato_empeno_esc($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function contrato_empeno_plantilla_estilos($modo = 'pdf')
{
    $css = '
body {
    font-family: freesans, helvetica, arial, sans-serif;
    font-size: 9pt;
    color: #000;
    margin: 0;
    padding: 0;
}
table.cc-items {
    width: 100%;
    border-collapse: collapse;
    font-size: 8pt;
    line-height: 13pt;
}
.cc-seccion-titulo {
    font-size: 8pt;
    font-weight: bold;
    margin: 6px 0 4px 0;
}
table.cc-items thead td {
    background-color: #EEEEEE;
    text-align: center;
    font-weight: bold;
    padding: 4px 3px;
    font-size: 7pt;
    line-height: 10pt;
}
table.cc-items tbody td {
    padding: 4px 3px;
    vertical-align: top;
    border-bottom: 1px solid #eee;
}
table.cc-totales {
    width: 100%;
    border-collapse: collapse;
    font-size: 8pt;
    margin-top: 12px;
}
table.cc-totales thead td {
    background-color: #eeeeee;
    text-align: center;
    font-weight: 600;
    padding: 6px;
    font-size: 9pt;
}
table.cc-totales tbody td {
    text-align: center;
    padding: 8px;
    font-size: 10pt;
    font-weight: 600;
}
table.cc-renovaciones {
    width: 100%;
    border-collapse: collapse;
    font-size: 7pt;
    margin-top: 14px;
}
table.cc-renovaciones thead td {
    background-color: #EEEEEE;
    text-align: center;
    font-weight: bold;
    padding: 4px 3px;
}
table.cc-renovaciones tbody td {
    padding: 4px 3px;
    text-align: center;
    vertical-align: top;
}
.cc-titulo-legal {
    display: block;
    font-size: 6pt;
    font-weight: bold;
    color: #696969;
    font-style: italic;
    margin-top: 10px;
    margin-bottom: 3px;
}
.cc-texto-legal {
    font-size: 6pt;
    color: #696969;
    font-style: italic;
    text-align: justify;
    line-height: 1.35;
    margin: 0 0 8px 0;
}
.cc-seccion-titulo {
    font-size: 8pt;
    font-weight: bold;
    margin: 10px 0 4px 0;
}
.cc-header table,
.cc-footer table {
    font-family: freesans, helvetica, arial, sans-serif;
}
';

    if ($modo === 'web') {
        $css .= contrato_compra_plantilla_estilos('web');
    }

    return $css;
}

function contrato_empeno_plantilla_header($d, $opts = array())
{
    $web = !empty($opts['web']);
    $h = 'contrato_empeno_esc';
    $rs = $d['rsItem'];

    $logoSrc = $web
        ? (isset($d['logo_src_web']) ? $d['logo_src_web'] : '')
        : (isset($d['logo_path_abs']) ? $d['logo_path_abs'] : '');
    $logoImg = $logoSrc !== ''
        ? '<img src="' . contrato_empeno_esc($logoSrc) . '" style="width:200px; max-height:50px;">'
        : '';

    return '
<div class="cc-header">
<table width="100%" cellpadding="0" cellspacing="0" style="font-size:8pt; color:#000;">
<tr>
    <td width="52%" style="vertical-align:top;">' . $logoImg . '</td>
    <td width="48%" style="vertical-align:top; text-align:right;">
        <div style="font-size:13pt; font-weight:bold; margin-bottom:4px;">Contrato opción de compra</div>
        <div style="font-size:9pt;">Fecha de compra: <strong>' . $h($d['sqldate']) . '</strong></div>
        <div style="font-size:9pt;">Lote Nº <strong>' . $h($rs['id_lote']) . '</strong></div>
    </td>
</tr>
</table>
<table width="100%" cellpadding="3" cellspacing="0" style="font-size:7.5pt; margin-top:6px; padding-bottom:4px;">
<tr>
    <td width="26%" style="vertical-align:top;">
        <span style="background:#eee; line-height: 1.35; font-weight:bold;">Datos del establecimiento</span><br>
        ' . $h($rs['empresa']) . '<br>
        ' . $h($rs['identificacion_tienda']) . ' ' . $h($rs['numero_identificacion_tienda']) . '<br>
        ' . $h($rs['direccion_tienda']) . '<br>
        ' . $h($rs['poblacion_tienda']) . ', ' . $h($rs['codigo_postal_tienda']) . ', ' . $h($rs['provincia_tienda']) . '
    </td>
    <td width="22%" style="vertical-align:top;">
        <br>
        Correo: ' . $h($rs['email_tienda']) . ' <br>
        Tel.: ' . $h($rs['telefono_tienda']) . '<br>
        Móvil: ' . $h($rs['movil_tienda']) . '
    </td>
    <td width="4%">&nbsp;</td>
    <td width="24%" style="vertical-align:top;">
        <span style="background:#eee; line-height: 1.35; font-weight:bold;">Interesado</span><br>
        ' . $h($d['nombre_cliente']) . '<br>
        ' . $h($d['tipo_identificacion_cliente'] . $rs['identificacion']) . '<br>
        Nacionalidad: ' . $h($rs['nacionalidad']) . '<br>
        F. nacimiento: ' . $h($d['fecha_nacimiento']) . '
    </td>
    <td width="24%" style="vertical-align:top;">
        <br>
        ' . $h($rs['direccion_cliente']) . '<br>
        ' . $h($rs['poblacion_cliente']) . ', ' . $h($rs['cp_cliente']) . ', ' . $h($rs['provincia_cliente']) . '<br>
        Tel.: ' . $h($rs['telefono']) . '<br>
        Sexo: ' . $h($rs['sexo']) . '
    </td>
</tr>
<tr>
    <td colspan="5" style="padding-top:3px; font-size:7.5pt; line-height: 0pt;">
        Datos de la venta. El vendedor reconoce que los artículos que se detallan a continuación son de su legítima propiedad.
    </td>
</tr>
</table>
</div>';
}

function contrato_empeno_plantilla_footer($d, $opts = array())
{
    $web = !empty($opts['web']);
    $sello = $web
        ? (isset($d['sello_footer_web']) ? $d['sello_footer_web'] : $d['sello_footer'])
        : $d['sello_footer'];

    return '
<div class="cc-footer">
<table width="100%" cellpadding="2" cellspacing="0" style="font-size:7.5pt;">
<tr>
    <td width="33%" style="text-align:center; vertical-align:top;">
        <strong>El comprador:</strong>
        <table width="110" align="center" cellpadding="0" cellspacing="0" style="margin:0 auto;">
        <tr><td style="padding-top:8px; text-align:center;">' . $sello . '</td></tr>
        </table>
    </td>
    <td width="33%" style="text-align:center; vertical-align:top;">
        <strong>El vendedor:</strong>
        <table width="100%" align="center" cellpadding="0" cellspacing="0">
        <tr><td style="padding-top:8px; text-align:center;">' . $d['firma_footer'] . '</td></tr>
        </table>
    </td>
    <td width="34%" style="text-align:center; vertical-align:top;">
        <strong>Lote retirado:</strong>
    </td>
</tr>
</table>
</div>';
}

function contrato_empeno_plantilla_articulos($d)
{
    $h = 'contrato_empeno_esc';
    $rs = $d['rsItem'];

    $html = '
    <div class="cc-seccion-titulo" style="margin-top: 10px;">Objeto/s del presente contrato (individualizados)</div>
<table class="cc-items">
<thead>
<tr>
    <td width="5%">Nº</td>
    <td width="5%">Us.</td>
    <td width="65%">Descripción de los artículos</td>
    <td width="25%">Inscripciones</td>
</tr>
</thead>
<tbody>';

    foreach ($d['articulos'] as $art) {
        $html .= '
    <tr>
        <td align="center">' . $h($art['id_articulo_lote']) . '</td>
        <td align="center">' . $h($art['unidades']) . '</td>
        <td>' . $h($art['descripcion_articulo']) . '</td>
        <td>' . $h($art['inscripciones']) . '</td>
    </tr>';
    }

    $html .= '
</tbody>
</table>

<table class="cc-items" style="margin-top:8px;">
<tr>
    <td width="5%" align="center" style="background-color:#EEEEEE; font-weight:bold;">Total</td>
    <td width="5%" align="center" style="background-color:#EEEEEE; font-weight:bold;">' . $h($d['smatoria']) . '</td>
    <td width="65%">&nbsp;</td>
    <td width="25%">&nbsp;</td>
</tr>
</table>

<table class="cc-totales">
<thead>
<tr>
    <td width="25%">Gramos</td>
    <td width="25%">Importe</td>
    <td width="25%">Vencimiento</td>
    <td width="25%">Importe recompra</td>
</tr>
</thead>
<tbody>
<tr>
    <td>' . $h($rs['peso']) . ' grs</td>
    <td>' . $h($rs['precio_compra']) . ' €</td>
    <td>' . $h($d['sqldatef']) . '</td>
    <td>' . $h($rs['precio_recompra']) . ' €</td>
</tr>
</tbody>
</table>';

    return $html;
}

function contrato_empeno_plantilla_renovaciones($d)
{
    if (empty($d['renovaciones'])) {
        return '';
    }

    $h = 'contrato_empeno_esc';
    $html = '
<div class="cc-seccion-titulo">Renovaciones</div>
<table class="cc-renovaciones">
<thead>
<tr>
    <td width="20%">Nº</td>
    <td width="20%">F. Renovación</td>
    <td width="20%">Importe</td>
    <td width="20%">F. vencimiento</td>
    <td width="20%">Estado</td>
</tr>
</thead>
<tbody>';

    foreach ($d['renovaciones'] as $ren) {
        $html .= '
    <tr>
        <td>' . $h($ren['numero']) . '</td>
        <td>' . $h($ren['fecha_renovacion']) . '</td>
        <td>' . $h($ren['importe_renovacion']) . ' €</td>
        <td>' . $h($ren['proximo_vencimiento']) . '</td>
        <td>' . $h($ren['estado_historico']) . '</td>
    </tr>';
    }

    $html .= '
</tbody>
</table>';

    return $html;
}

function contrato_empeno_plantilla_adelantos($d)
{
    if (empty($d['total_adelantos'])) {
        return '';
    }

    $h = 'contrato_empeno_esc';
    $html = '
<div class="cc-seccion-titulo">Adelantos de capital <span style="font-weight:normal; font-size:7pt;">(capital inicial: ' . $h($d['precio_compra_inicial']) . ')</span></div>
<table class="cc-renovaciones">
<thead>
<tr>
    <td>Nº</td>
    <td>Importe</td>
    <td>F. Adelanto</td>
    <td>Capital</td>
    <td>Precio recompra</td>
    <td>Importe renovacion</td>
</tr>
</thead>
<tbody>';

    foreach ($d['adelantos'] as $adel) {
        $html .= '
    <tr>
        <td>' . $h($adel['numero']) . '</td>
        <td>' . $h($adel['importe_adelanto']) . ' €</td>
        <td>' . $h($adel['fecha_adelanto']) . '</td>
        <td>' . $h($adel['nuevo_capital']) . ' €</td>
        <td>' . $h($adel['nuevo_precio_recompra']) . ' €</td>
        <td>' . $h($adel['importe_renovacion']) . ' €</td>
    </tr>';
    }

    $html .= '
</tbody>
</table>';

    return $html;
}

function contrato_empeno_plantilla_legales($d)
{
    $h = 'contrato_empeno_esc';
    $rs = $d['rsItem'];
    $html = '';

    if (!empty($d['textos_legales'])) {
        foreach ($d['textos_legales'] as $bloque) {
            $html .= '<strong class="cc-titulo-legal">' . $h($bloque['titulo']) . '</strong>';
            $html .= '<p class="cc-texto-legal">' . $bloque['contenido'] . '</p>';
        }
        return $html;
    }

    return '
    <strong class="cc-titulo-legal">TÉRMINOS Y CONDICIONES</strong>
    <p class="cc-texto-legal">
        Este contrato se rige por las condiciones generales de la empresa ' . $h($rs['empresa']) . '.
        Para cualquier consulta o reclamación, puede dirigirse a:
        Dirección: ' . $h($d['direccion_empresa']) . '.
        Teléfono: ' . $h($rs['telefono_tienda']) . '.
        Email: ' . $h($d['correo_electronico_empresa']) . '.
    </p>';
}

function contrato_empeno_plantilla_html_pdf($contenido)
{
    return '
<html>
<head>
<meta charset="UTF-8" />
<style>' . contrato_empeno_plantilla_estilos('pdf') . '</style>
</head>
<body>
' . $contenido . '
</body>
</html>';
}

function contrato_empeno_plantilla_body_pdf_pagina1($d)
{
    return contrato_empeno_plantilla_html_pdf(
        contrato_empeno_plantilla_articulos($d)
        . contrato_empeno_plantilla_renovaciones($d)
        . contrato_empeno_plantilla_adelantos($d)
    );
}

function contrato_empeno_plantilla_body_pdf_pagina2($d)
{
    return contrato_empeno_plantilla_html_pdf(
        contrato_empeno_plantilla_legales($d)
        . contrato_compra_plantilla_fotos_dni($d, ['inline' => true])
    );
}

function contrato_empeno_plantilla_maqueta($d)
{
    $idLote = (int) $d['id_lote'];
    $idSucursal = (int) $d['id_sucursal'];
    $pdfUrl = 'contrato_empeno.php?id_lote=' . $idLote . '&id_sucursal=' . $idSucursal;

    $pagina1 = '
<section class="cc-hoja">
    <span class="cc-etiqueta-pagina">Página 1 — artículos, renovaciones y adelantos</span>
    <div class="cc-hoja-inner">
        ' . contrato_empeno_plantilla_header($d, ['web' => true]) . '
        <div class="cc-contenido">'
            . contrato_empeno_plantilla_articulos($d)
            . contrato_empeno_plantilla_renovaciones($d)
            . contrato_empeno_plantilla_adelantos($d)
        . '</div>
        ' . contrato_empeno_plantilla_footer($d, ['web' => true]) . '
    </div>
</section>';

    $pagina2 = '
<section class="cc-hoja">
    <span class="cc-etiqueta-pagina">Página 2 — textos legales y fotos DNI</span>
    <div class="cc-hoja-inner">
        <div class="cc-contenido">'
            . contrato_empeno_plantilla_legales($d)
            . contrato_compra_plantilla_fotos_dni($d, ['web' => true, 'inline' => true])
        . '</div>
        ' . contrato_empeno_plantilla_footer($d, ['web' => true]) . '
    </div>
</section>';

    return '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Maqueta contrato empeño — Lote ' . contrato_empeno_esc($idLote) . '</title>
<style>' . contrato_empeno_plantilla_estilos('web') . '</style>
</head>
<body class="cc-maqueta-body">
<div class="cc-toolbar">
    <strong>Maqueta HTML</strong> — Lote ' . contrato_empeno_esc($idLote) . ' / Sucursal ' . contrato_empeno_esc($idSucursal) . '
    &nbsp;|&nbsp;
    <a href="' . contrato_empeno_esc($pdfUrl) . '">Generar PDF</a>
    <button type="button" onclick="window.print()">Imprimir vista previa</button>
    <span style="color:#666;">Editar: <code>pdfs/contrato_empeno_plantilla.php</code></span>
</div>
' . $pagina1 . $pagina2 . '
</body>
</html>';
}
