<?php
/**
 * Plantilla HTML del contrato de compra.
 * Editar este archivo para ajustar maquetación (header, body, footer, saltos de página).
 */

function contrato_compra_esc($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * @param string $modo pdf|web
 */
function contrato_compra_plantilla_estilos($modo = 'pdf')
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
    margin-top: 18px;
}
table.cc-totales thead td {
	background-color: #eeeeee;
	text-align: center;
	font-weight: 600;
	padding: 6px;
	font-size: 10pt;
}
table.cc-totales tbody td {
	text-align: center;
	padding: 10px;
	font-size: 11pt;
	font-weight: 600;
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
    margin: 0px 0px 6px 0px;
}
.cc-salto-pagina {
    page-break-before: always;
    break-before: page;
}
.cc-header table,
.cc-footer table {
    font-family: freesans, helvetica, arial, sans-serif;
}
';

    if ($modo === 'web') {
        $css .= '
body.cc-maqueta-body {
    background: #e8e8e8;
    padding: 20px;
}
.cc-toolbar {
    max-width: 210mm;
    margin: 0 auto 12px;
    padding: 10px 14px;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-family: arial, sans-serif;
    font-size: 13px;
}
.cc-toolbar a, .cc-toolbar button {
    margin-right: 10px;
}
.cc-hoja {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto 20px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.15);
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    position: relative;
}
.cc-hoja-inner {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 8mm 8mm 0;
    box-sizing: border-box;
}
.cc-header {
    flex-shrink: 0;
    border-bottom: 1px dashed #bbb;
    padding-bottom: 6px;
    margin-bottom: 8px;
}
.cc-contenido {
    flex: 1;
}
.cc-footer {
    flex-shrink: 0;
    margin-top: 12px;
    padding-top: 6px;
    padding-bottom: 8mm;
}
.cc-etiqueta-pagina {
    position: absolute;
    top: 4mm;
    right: 8mm;
    font-size: 8pt;
    color: #999;
    font-family: arial, sans-serif;
}
@media print {
    body.cc-maqueta-body { background: #fff; padding: 0; }
    .cc-toolbar { display: none; }
    .cc-hoja {
        box-shadow: none;
        margin: 0;
        page-break-after: always;
    }
    .cc-etiqueta-pagina { color: #ccc; }
}
';
    }

    return $css;
}

/**
 * @param array<string,mixed> $d
 * @param array{web?:bool} $opts
 */
function contrato_compra_plantilla_header($d, $opts = array())
{
    $web = !empty($opts['web']);
    $h = 'contrato_compra_esc';
    $rs = $d['rsItem'];

    $logoSrc = $web
        ? (isset($d['logo_src_web']) ? $d['logo_src_web'] : '')
        : (isset($d['logo_path_abs']) ? $d['logo_path_abs'] : '');
    $logoImg = $logoSrc !== ''
        ? '<img src="' . contrato_compra_esc($logoSrc) . '" style="width:200px; max-height:50px;">'
        : '';

    return '
<div class="cc-header">
<table width="100%" cellpadding="0" cellspacing="0" style="font-size:8pt; color:#000;">
<tr>
    <td width="52%" style="vertical-align:top;">' . $logoImg . '</td>
    <td width="48%" style="vertical-align:top; text-align:right;">
        <div style="font-size:13pt; font-weight:bold; margin-bottom:4px;">Contrato de compra</div>
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

/**
 * @param array<string,mixed> $d
 * @param array{web?:bool} $opts
 */
function contrato_compra_plantilla_footer($d, $opts = array())
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

/**
 * Tabla de artículos + totales.
 *
 * @param array<string,mixed> $d
 */
function contrato_compra_plantilla_articulos($d)
{
    $h = 'contrato_compra_esc';
    $rs = $d['rsItem'];

    $html = '
<div class="cc-seccion-titulo">Objeto/s del presente contrato (individualizados)</div>
<table class="cc-items">
<thead>
<tr>
    <td width="5%">Nº</td>
    <td width="4%">Us.</td>
    <td width="24%">Descripción</td>
    <td width="12%">Peso neto/bruto</td>
    <td width="9%">Tipo metal</td>
    <td width="8%">Kilates</td>
    <td width="12%">Inscripciones</td>
    <td width="12%">Piedras</td>
    <td width="14%">Precio</td>
</tr>
</thead>
<tbody>';

    foreach ($d['articulos'] as $art) {
        $html .= '
    <tr>
        <td align="center">' . $h($art['id_articulo_lote']) . '</td>
        <td align="center">' . $h($art['unidades']) . '</td>
        <td>' . $h($art['descripcion_articulo']) . '</td>
        <td align="center">' . $h($art['peso_neto_bruto']) . '</td>
        <td align="center">' . $h($art['tipo_de_articulo']) . '</td>
        <td align="center">' . $h($art['ley']) . '</td>
        <td align="center">' . $h($art['inscripciones']) . '</td>
        <td align="center">' . $h($art['piedras']) . '</td>
        <td align="center">' . $h($art['precio_compra_articulo']) . ' €</td>
    </tr>';
    }

    $html .= '
</tbody>
</table>

<table class="cc-totales">
<thead>
<tr>
    <td width="25%">Precio total</td>
    <td width="25%">Peso total objetos</td>
    <td width="25%">Peso bruto objetos</td>
    <td width="25%">Total objetos</td>
</tr>
</thead>
<tbody>
<tr>
    <td>' . $h($rs['precio_compra']) . ' €</td>
    <td>' . $h($rs['peso']) . ' grs</td>
    <td>' . $h($rs['peso_bruto']) . ' grs</td>
    <td>' . $h($d['smatoria']) . '</td>
</tr>
</tbody>
</table>';

    return $html;
}

/**
 * Textos legales (página 2).
 *
 * @param array<string,mixed> $d
 */
function contrato_compra_plantilla_legales($d)
{
    $h = 'contrato_compra_esc';
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

/**
 * Envoltorio HTML mínimo para fragmentos WriteHTML de mPDF.
 */
function contrato_compra_plantilla_html_pdf($contenido)
{
    return '
<html>
<head>
<meta charset="UTF-8" />
<style>' . contrato_compra_plantilla_estilos('pdf') . '</style>
</head>
<body>
' . $contenido . '
</body>
</html>';
}

/**
 * Página 1 del PDF: artículos y totales.
 *
 * @param array<string,mixed> $d
 */
function contrato_compra_plantilla_body_pdf_pagina1($d)
{
    return contrato_compra_plantilla_html_pdf(contrato_compra_plantilla_articulos($d));
}

/**
 * Página 2 del PDF: textos legales y fotos DNI.
 *
 * @param array<string,mixed> $d
 */
function contrato_compra_plantilla_body_pdf_pagina2($d)
{
    return contrato_compra_plantilla_html_pdf(
        contrato_compra_plantilla_legales($d)
        . contrato_compra_plantilla_fotos_dni($d, ['inline' => true])
    );
}

/**
 * Cuerpo completo (legacy); preferir pagina1 + pagina2 desde contrato_compra.php.
 *
 * @param array<string,mixed> $d
 */
function contrato_compra_plantilla_body_pdf($d)
{
    return contrato_compra_plantilla_html_pdf(
        contrato_compra_plantilla_articulos($d)
        . contrato_compra_plantilla_legales($d)
        . contrato_compra_plantilla_fotos_dni($d, ['inline' => true])
    );
}

/**
 * Fotos del DNI del cliente (anverso / reverso).
 *
 * @param array<string,mixed> $d
 * @param array{web?:bool}    $opts
 */
function contrato_compra_plantilla_fotos_dni($d, $opts = array())
{
    $web = !empty($opts['web']);
    $fotos = isset($d['fotos_dni_cliente']) && is_array($d['fotos_dni_cliente']) ? $d['fotos_dni_cliente'] : [];
    if ($fotos === []) {
        return '';
    }

    $cells = '';
    foreach ($fotos as $foto) {
        $src = $web
            ? (isset($foto['src_web']) ? (string) $foto['src_web'] : '')
            : (isset($foto['path_abs']) ? (string) $foto['path_abs'] : '');

        if ($src === '') {
            continue;
        }

        if (!$web && !is_readable($src)) {
            continue;
        }

        $cells .= '<td style="width:50%; text-align:center; vertical-align:top; padding:8px;">'
            . '<img src="' . contrato_compra_esc($src) . '" width="400" height="auto" alt="" />'
            . '</td>';
    }

    if ($cells === '') {
        return '';
    }

    $marginTop = !empty($opts['inline']) ? '20px' : '20px';

    return '
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 2pt; margin-bottom:0;">
<tr>' . $cells . '</tr>
</table>';
}

/**
 * Maqueta completa para vista previa en navegador (simula 2 hojas A4).
 *
 * @param array<string,mixed> $d
 */
function contrato_compra_plantilla_maqueta($d)
{
    $idLote = (int) $d['id_lote'];
    $idSucursal = (int) $d['id_sucursal'];
    $pdfUrl = 'contrato_compra.php?id_lote=' . $idLote . '&id_sucursal=' . $idSucursal;

    $pagina1 = '
<section class="cc-hoja">
    <span class="cc-etiqueta-pagina">Página 1 — artículos</span>
    <div class="cc-hoja-inner">
        ' . contrato_compra_plantilla_header($d, ['web' => true]) . '
        <div class="cc-contenido">' . contrato_compra_plantilla_articulos($d) . '</div>
        ' . contrato_compra_plantilla_footer($d, ['web' => true]) . '
    </div>
</section>';

    $pagina2 = '
<section class="cc-hoja">
    <span class="cc-etiqueta-pagina">Página 2 — textos legales y fotos DNI</span>
    <div class="cc-hoja-inner">
        <div class="cc-contenido">'
            . contrato_compra_plantilla_legales($d)
            . contrato_compra_plantilla_fotos_dni($d, ['web' => true, 'inline' => true])
        . '</div>
        ' . contrato_compra_plantilla_footer($d, ['web' => true]) . '
    </div>
</section>';

    return '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Maqueta contrato compra — Lote ' . contrato_compra_esc($idLote) . '</title>
<style>' . contrato_compra_plantilla_estilos('web') . '</style>
</head>
<body class="cc-maqueta-body">
<div class="cc-toolbar">
    <strong>Maqueta HTML</strong> — Lote ' . contrato_compra_esc($idLote) . ' / Sucursal ' . contrato_compra_esc($idSucursal) . '
    &nbsp;|&nbsp;
    <a href="' . contrato_compra_esc($pdfUrl) . '">Generar PDF</a>
    <button type="button" onclick="window.print()">Imprimir vista previa</button>
    <span style="color:#666;">Editar: <code>pdfs/contrato_compra_plantilla.php</code></span>
</div>
' . $pagina1 . $pagina2 . '
</body>
</html>';
}
