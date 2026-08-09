<?php

require("session_file.php");
?>
<?php
require_once("../conexion.php");


//Obtenemos variables y verificamos seguridad
$err = false;

//Verificamos sucursal
if ($sucursal != $_GET['sucursal'] && $userprivilegis == 2) {
  $err = true;
}

if ($err) {
  echo "Error";
  die();
}

if (isset($_GET['desde']) && $_GET['desde'] != '') {
  $fini = date('Y-m-d', strtotime($_GET['desde']));
} else {
  $fini = '';
}
if (isset($_GET['hasta']) && $_GET['hasta'] != '') {
  $ffin = date('Y-m-d', strtotime($_GET['hasta']));
} else {
  $ffin = date('Y-m-d');
}

$ffin = $ffin . " 23:59:59";

//En articulos, recibimos en vencimiento el tipo de fecha
if (isset($_GET['vencimiento']) && $_GET['vencimiento'] != '') {
  $tfecha = $_GET['vencimiento'];
} else {
  $tfecha = '';
}

if (isset($_GET['accion']) && $_GET['accion'] != '') {
  $accion = $_GET['accion'];
} else {
  $accion = '';
}


if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
  $busqueda = $_GET['busqueda'];
}




if (isset($_GET['orderby']) && $_GET['orderby'] != '') {
  $orderby = $_GET['orderby'];
} else {
  $orderby = 'id';
}

if (isset($_GET['order']) && $_GET['order'] != '') {
  $order = $_GET['order'];
} else {
  $order = 'DESC';
}


if (isset($_GET['idinicio']) && $_GET['idinicio'] != '' && $_GET['idinicio'] != 0) {
  $idinicio = $_GET['idinicio'];
} else {
  $idinicio = 0;
}

if (isset($_GET['idfin']) && $_GET['idfin'] != '' && $_GET['idfin'] != 0) {
  $idfin = $_GET['idfin'];
} else {
  $idfin = 0;
}

if (strpos($accion, 'articulos') !== false) {

  // $query = "
  // SELECT 
  // sorigen.nombre_sucursal as 'Sucursal Origen', 
  // s.nombre_sucursal as 'Sucursal Destino', 
  // av.id as 'Id Art', 
  // av.descripcion as 'Descripci�n', 
  // av.fecha_enviado as 'Fecha Env.', 
  // av.fecha_en_venta as 'Fecha Venta', 
  // av.fecha_vendido as 'Fecha Vendido', 
  // av.fecha_retirado as 'Fecha Retirado', 
  // av.precio as 'Precio', 
  // av.peso as 'Peso',
  // av.estado as 'Estado',
  // av.tipo as 'Tipo'
  // FROM articulos_venta AS av
  // LEFT JOIN sucursal AS s ON s.id_sucursal = av.id_sucursal_destino
  // LEFT JOIN sucursal AS sorigen ON sorigen.id_sucursal = av.id_sucursal_origen
  // WHERE ";

  $query = "
  SELECT 
  av.id as 'Id Art', 
  av.descripcion as 'Descripci�n', 
  ";

  switch ($btn) {
    case 'list':
    case 'retirados':
      $query .= "av.fecha_retirado as 'Fecha Retirado', ";
    case 'vendidos':
      $query .= "av.fecha_vendido as 'Fecha Vendido',";
    case 'venta':
      $query .= "av.fecha_en_venta as 'Fecha Venta', ";
    case 'enviados':
      $query .= "av.fecha_enviado as 'Fecha Env.',";
  }



  $query .= "  
  av.precio as 'Precio', 
  av.peso as 'Peso',
  av.estado as 'Estado',
  av.tipo as 'Tipo'
  FROM articulos_venta AS av
  LEFT JOIN sucursal AS s ON s.id_sucursal = av.id_sucursal_destino
  LEFT JOIN sucursal AS sorigen ON sorigen.id_sucursal = av.id_sucursal_origen
  WHERE ";

  $bMostrarTodos = ($_GET['sucursal'] == 'todas');
  if (!$bMostrarTodos) {
    $query .= " id_sucursal_destino = " . $_GET['sucursal'] . " ";
  } else {
    //Para no tener que tramitar logica de AND
    $query .= " 1=1 ";
  }


  switch ($btn) {
    case 'enviados':
      $query .= "AND estado LIKE 'enviado' ";
      break;

    case 'venta':
      $query .= "AND estado LIKE 'enventa' ";
      break;

    case 'vendidos':
      $query .= "AND estado LIKE 'vendido' ";
      break;

    case 'retirados':
      $query .= "AND estado LIKE 'retirado' ";
      break;
  }



  $busqueda = $_GET['busqueda'];

  if ($busqueda != '') {
    $and_busqueda = ' AND (';

    $and_busqueda .= ' CAST(id_articulo_sucursal as CHAR) LIKE "%' . $busqueda . '%"';
    $and_busqueda .= ' OR CAST(id as CHAR) LIKE "%' . $busqueda . '%"';
    if ($userprivilegis < 2) {
      $and_busqueda .= ' OR CAST(av.id as CHAR) like "%' . $busqueda . '%"';
      $and_busqueda .= ' OR s.nombre_sucursal like "%' . $busqueda . '%"';
    }
    $and_busqueda .= ' OR descripcion like "%' . $busqueda . '%"';
    $and_busqueda .= ' OR inscripciones like "%' . $busqueda . '%"';
    $and_busqueda .= ' OR tipo like "%' . $busqueda . '%"';
    $and_busqueda .= ' OR CAST(precio as CHAR) LIKE "%' . $busqueda . '%"';

    switch ($tfecha) {
      case '':
        $and_busqueda .= ' OR fecha_enviado like "%' . $busqueda . '%"';
        break;
      case 'enviado':
        $and_busqueda .= ' OR fecha_enviado like "%' . $busqueda . '%"';
        break;
      case 'enventa':
        $and_busqueda .= ' OR fecha_en_venta like "%' . $busqueda . '%"';
        break;
      case 'vendido':
        $and_busqueda .= ' OR fecha_vendido like "%' . $busqueda . '%"';
        break;
    }

    $and_busqueda .= ')';

    $query .= $and_busqueda . ' ';
  }


  if ($idinicio != 0) {
    $query .= " AND id >= " . $idinicio;
  }

  if ($idfin != 0) {
    $query .= " AND id <= " . $idfin;
  }


  switch ($tfecha) {
    case '':
      $query .= " AND (fecha_enviado between '" . $fini . "' and '" . $ffin . "')";
      break;
    case 'enviado':
      $query .= " AND (fecha_enviado between '" . $fini . "' and '" . $ffin . "')";
      break;
    case 'enventa':
      $query .= " AND (fecha_en_venta between '" . $fini . "' and '" . $ffin . "')";
      break;
    case 'vendido':
      $query .= " AND (fecha_vendido between '" . $fini . "' and '" . $ffin . "')";
      break;
    case 'retirado':
      $query .= " AND (fecha_retirado between '" . $fini . "' and '" . $ffin . "')";
      break;
  }


  //en caso de haber realizado una b�squeda, ordenamos por id_articulo_sucursal
  if (isset($and_busqueda)) {
    $query .= " ORDER BY id_articulo_sucursal ASC";
  } else {
    $query .= "
    ORDER BY " . $orderby . " " . $order;
  }
}


if (strpos($accion, 'ventas') !== false) {

  $query = "
  SELECT 
  ventas.id as 'Id Venta', 
  ventas.id_articulo_venta as 'Id Art�culo', 
  ventas.estado as Estado, 
  ventas.precio as Precio, 
  ventas.cantidad_contado as Contado,
  ventas.cantidad_tarjeta as Tarjeta,
  ventas.fecha as Fecha,
  ventas.venta_plazos as 'Venta Plazos',
  ventas.numero_plazos as Plazos,
  av.descripcion as Descripcion,
  c.nombre as Nombre,
  c.apellido as Apellido,
  c.identificacion as Identificacion,
  av.peso as Peso,
  ventas.tipo_pago as 'Tipo pago',
  av.tipo as Tipo
  FROM ventas
  LEFT JOIN clientes as c on c.id_cliente = cliente
  LEFT JOIN articulos_venta as av on av.id = id_articulo_venta
  WHERE
  ";

  /*
  $query = "
  SELECT 
  ventas.id as id_venta, ventas.estado as estado, ventas.precio as precio, ventas.*, c.*, av.*
  FROM ventas
  LEFT JOIN clientes as c on c.id_cliente = cliente
  LEFT JOIN articulos_venta as av on av.id = id_articulo_venta
  WHERE
  ";

  */

  $bMostrarTodos = ($sucursalid == 'todas');
  if (!$bMostrarTodos) {
    $query .= " id_sucursal = " . $_GET['sucursal'] . " ";
  } else {
    //Para no tener que tramitar logica de AND
    $query .= " 1=1 ";
  }

  switch ($btn) {
    case 'vendidos':
      $and = "AND ventas.estado = 'vendido' ";
      break;

    case 'enfecha':
      $and = "AND ventas.estado = 'enfecha' ";
      break;

    case 'vencidos':
      $and = "AND ventas.estado = 'vencido' ";
      break;

    case 'anulados':
      $and = "AND ventas.estado = 'anulado' ";
      break;
  }


  $query .= " AND (fecha between '" . $fini . "' and '" . $ffin . "') ";

  if (isset($and)) {
    $query .= $and . ' ';
  }

  if ($busqueda != '') {
    $and_busqueda = ' AND (';

    $and_busqueda .= ' CAST(id_venta_sucursal as CHAR) LIKE "%' . $busqueda . '%"';
    $and_busqueda .= ' OR CAST(id_articulo_venta as CHAR) LIKE "%' . $busqueda . '%"';
    $and_busqueda .= ' OR CAST(ventas.id as CHAR) LIKE "%' . $busqueda . '%"';

    $and_busqueda .= ' OR av.descripcion like "%' . $busqueda . '%"';
    $and_busqueda .= ' OR c.nombre like "%' . $busqueda . '%"';
    $and_busqueda .= ' OR c.apellido like "%' . $busqueda . '%"';
    $and_busqueda .= ' OR CAST(ventas.precio as CHAR) LIKE "%' . $busqueda . '%"';
    $and_busqueda .= ' OR CAST(av.peso as CHAR) LIKE "%' . $busqueda . '%"';
    $and_busqueda .= ' OR tipo_pago like "%' . $busqueda . '%"';
    $and_busqueda .= ' OR ventas.fecha like "%' . $busqueda . '%"';
    $and_busqueda .= ' OR av.tipo like "%' . $busqueda . '%"';

    $and_busqueda .= ')';

    $query .= $and_busqueda . ' ';
  }





  if ($idinicio != 0) {
    $query .= " AND id_venta_sucursal >= " . $idinicio;
  }

  if ($idfin != 0) {
    $query .= " AND id_venta_sucursal <= " . $idfin;
  }


  //en caso de haber realizado una b�squeda, ordenamos por id_articulo_sucursal
  if (isset($and_busqueda)) {
    $query .= " ORDER BY id_venta_sucursal ASC";
  } else {
    $query .= "
  ORDER BY " . $orderby . " " . $order;
  }
}

if (strpos($accion, 'lotes') !== false) {

  $sucursal = $_GET['sucursal'];

  $query = "
    SELECT 
    lotes.id_lote as 'Id Lote',
    CONCAT(clientes.nombre, ' ', clientes.apellido) as 'Cliente',
    clientes.identificacion as 'DNI',
    fecha_compra as 'F. Compra',
    fecha_vencimiento as 'F. Vencimiento',
    precio_compra as 'Precio',
    intereses_lote as 'Inter�s',
    peso as 'Peso Neto',
    peso_bruto as 'Peso Bruto',
    ROUND(precio_compra/peso,2) as 'Media eur /gr',
    cantidad_articulos as 'Unidades',
    usuarios.usuario as 'Comprado por',
    estado as 'Estado',
    tipo_de_lote as 'Tipo',
    liberado as 'Liberado'
    FROM lotes_$sucursal as lotes
    INNER JOIN clientes ON lotes.cliente = clientes.id_cliente 
    INNER JOIN usuarios ON lotes.comprado_por = usuarios.id_usuario 
    WHERE lotes.sucursal = $sucursal
  ";



  switch ($accion) {
    case 'lotes':
      $and = "AND estado_lote 
  IN ('compra','enfecha') ";
      break;

    case 'lotes_compras':
      $and = "AND estado_lote LIKE 'compra' 
  AND compra_opcion LIKE 'no'";
      break;

    case 'lotes_liberados':
      $and = "AND liberado like 'si'";
      break;

    case 'lotes_empe�os':
      $and = "AND compra_opcion like 'si'";
      break;

    case 'lotes_vencidos':
      $and = "AND estado_lote like 'vencido'";
      break;

    case 'lotes_enfecha':
      $and = "AND estado_lote like 'enfecha'";
      break;

    case 'lotes_retirados':
      $and = "AND estado_lote like 'retirado'";
      break;

    case 'lotes_enviados':
      $and = "AND estado_lote like 'enviado'";
      break;
    case 'lotes_recibidos':
      $and = "AND estado_lote like 'central'";
      break;


    default:
      # code...
      break;
  }



  $query .= $and . ' ';


  if ($busqueda != '') {
    $and_busqueda = ' AND (';


    $and_busqueda .= ' CAST(id_lote as CHAR) LIKE "%' . $busqueda . '%"';
    $and_busqueda .= ' OR usuarios.nombre_usuario like "%' . $busqueda . '%"';
    $and_busqueda .= ' OR usuarios.apellido_usuario like "%' . $busqueda . '%"';
    $and_busqueda .= ' OR clientes.nombre like "%' . $busqueda . '%"';
    $and_busqueda .= ' OR clientes.apellido like "%' . $busqueda . '%"';
    $and_busqueda .= ' OR clientes.identificacion LIKE "%' . $busqueda . '%"';
    $and_busqueda .= ' OR CAST(precio_compra AS CHAR) LIKE "%' . $busqueda . '%"';
    $and_busqueda .= ' OR CAST(peso_bruto AS CHAR) LIKE "%' . $busqueda . '%"';
    //$and_busqueda .= ' OR CAST(unidades AS CHAR) LIKE "%'.$busqueda.'%"';

    $and_busqueda .= ')';

    $query .= $and_busqueda . ' ';
  }


  //HG DEVELOPERS
  if ($vencimiento != '') {
    $query .= " AND (fecha_vencimiento between '" . $fini . "' and '" . $ffin . "')";
  } else {
    if ($accion == "lotes_enviados") {
      $query .= " AND (fecha_envio between '" . $fini . "' and '" . $ffin . "')";
    } else if ($accion == "lotes_recibidos") {
      $query .= "AND (fecha_recepcion between '" . $fini . "' and '" . $ffin . "')";
    } else {
      $query .= " AND (fecha_compra between '" . $fini . "' and '" . $ffin . "')";
    }
  }
  //END HGDEVELOPERS
  if ($idinicio != 0) {
    $query .= " AND id_lote >= " . $idinicio;
  }

  if ($idfin != 0) {
    $query .= " AND id_lote <= " . $idfin;
  }
}

// echo $query;
// die();

include("../API/MPDF54/mpdf.php");

//Create pdf object
$mpdf = new mPDF('c', 'A4', '', '', 13, 13, 13, 13, 16, 13);
// $mpdf=new mPDF('win-1252','A4','','',13,13,5,5,5,5); 
$mpdf->useOnlyCoreFonts = true;    // false is default
$mpdf->SetProtection(array('print'));
$mpdf->SetTitle("Contrato de compra de oro");
// $mpdf->SetAuthor("Silver Gold");
$mpdf->SetWatermarkText("Enviada");
$mpdf->showWatermarkText = false;
$mpdf->watermark_font = 'DejaVuSansCondensed';
$mpdf->watermarkTextAlpha = 0.1;
$mpdf->SetDisplayMode('fullpage');
$mpdf->hyphenate = true;
$mpdf->SHYlang = 'es';
$mpdf->ignore_invalid_utf8 = true;

$html = '
<html>
<head>
<meta charset="UTF-8" />
<style>
body {font-family: helvetica, arial;
    font-size: 9pt;
	 width:900px;
	 margin:0 auto;
	 color:#000000;
	 text-transform: uppercase; 
}
table{
  margin-bottom:5px;
}
p{ 
margin: 0pt;
line-height:0pt;
text-transform: uppercase; 
}
td { vertical-align: top; }
.items td {
}
.table {
	border-collapse:collapse;  
}
.tablearticulo{
	margin:0 0 20px 0;
}
.table .td{
	padding:6px 8px;
	border: 1px solid #333333;
	border-spacing:0px;
	height:30px;
}
table thead td {
	text-align: center;
}
.items td.blanktotal {
    background-color: none;
    border: none;
}
.items td.totals {
    text-align: right;
}
h2{
	text-align:center;
	font-size:13px;
}
h3{
	text-decoration:none;
	font-size:10px;
	margin-top: 10px;
}
smallbr{
font-size: 1px; 
line-height: 0; 
}
table{
  width: 100%;
}
tbody td{
  text-align: center;
}
td{
  border: 1px solid black;
}
</style>
</head>
<body>
<!--mpdf
<htmlpagefooter name="footer">

</htmlpagefooter>
<sethtmlpagefooter name="footer" value="on" />
mpdf-->
';

if (strpos($accion, "lotes") !== false) {
  $accion = "lotes";
}

$html .= '
<h2>' . date("d/m/Y") . ' - Listado de ' . $accion . ' de ' . $nombre_suc;

if ($btn != "list" && $btn != "all") {
  $html .= ' en estado ';
  switch ($btn) {
    case 'venta':
      $html .= 'en venta';
      break;

    case 'enfecha':
      $html .= 'en fecha';
      break;

    case 'compr':
      $html .= 'compra';
      break;

    case 'liber':
      $html .= 'liberados';
      break;

    case 'venc':
      $html .= 'vencidos';
      break;

    case 'retir':
      $html .= 'retirados';
      break;
    case 'envia':
      $html .= 'enviados';
      break;

    default:
      $html .= $btn;
      break;
  }
}

if ($tfecha != '') {
  $html .= ' filtrados por fecha de ';
  switch ($tfecha) {
    case 'enventa':
      $html .= 'en venta';
      break;

    default:
      $html .= $tfecha;
      break;
  }
}

$html .= '</h2>';

$Item = mysql_query($query, $conexion);
mysql_query("SET NAMES 'utf8'");

$html .= '<table cellspacing="0">';
$html .= '<thead>';
$html .= '<tr>';
while ($property = mysql_fetch_field($Item)) { //fetch table field name    
  $html .= '<td>' . $property->name . '</td>';
}
$html .= '</tr>';
$html .= '</thead>';
while ($row = mysql_fetch_row($Item))  //fetch_table_data
{
  $html .= '<tr>';
  for ($i = 0; $i < mysql_num_fields($Item); $i++) {
    $html .= '<td>' . $row[$i] . '</td>';
  }
  $html .= '</tr>';
}
$html .= '</table>';

$html .= '
</body>
</html>
';

// echo $html;
$mpdf->WriteHTML($html);
//$mpdf->Output('Contrato N� '.$rsItem['id_lote'].'.pdf','D');

/* Solo para la sucursal de eibar */
if ($sucursal === 25) {
  $mpdf->SetJS('this.print();');
}
// echo $html;
$mpdf->Output();
exit;
?>