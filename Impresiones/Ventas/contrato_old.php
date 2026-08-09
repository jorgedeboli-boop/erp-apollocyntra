<?php
require("../../session_file.php");

?>
<?php
require_once("../../conexion.php");

$id_venta = $_GET["id_venta"];
$id_sucursal = $suc;

$query = "SELECT 
*, ventas.id as id_venta, ventas.fecha as fecha_venta, ventas.cliente as id_cliente, ventas.precio as precio
FROM ventas 
LEFT JOIN sucursal ON ventas.id_sucursal = sucursal.id_sucursal
LEFT JOIN clientes ON clientes.id_cliente = ventas.cliente
LEFT JOIN articulos_venta ON ventas.id_articulo_venta = articulos_venta.id
WHERE ventas.id = $id_venta";


mysql_query ("SET NAMES 'utf8'");
$Item = mysql_query($query, $conexion);
$rsItem = mysql_fetch_assoc($Item);

//var_dump($rsItem);die();

$cantidadarticulos = $rsItem['cantidad_articulos'];

$preciototal = $rsItem['precio_compra'];

$date= $rsItem['fecha_venta'];
$sqldate=date('d-m-Y',strtotime($date));

///////FUNCION FECHA

$dia = date('d',strtotime($date));
$mes = date('m',strtotime($date));
$año = date('Y',strtotime($date));
$meses = array('01' => 'enero','02' => 'febrero','03' => 'marzo','04' => 'abril','05' => 'mayo','06' => 'junio','07' => 'julio','08' => 'agosto','09' => 'septiembre','10' => 'octubre','11' => 'noviembre','12' => 'diciembre');

if($meses[$mes])
{
  $d = $dia;
  $m = $meses[$mes];
  $a = $año;
}

$nombreempresa= $rsItem['empresa'];
$nombresucursal=$rsItem['nombre_sucursal'];
$direcciontienda=$rsItem['direccion_tienda'];
$poblaciontienda=$rsItem['poblacion_tienda'];
$codigopostaltienda=$rsItem['codigo_postal_tienda'];
$provinciatienda=$rsItem['provincia_tienda'];
$numeroidentificaciontienda=$rsItem['numero_identificacion_tienda'];
$calletienda=$rsItem['calle'];
$numerocalle=$rsItem['numero_calle'];
///////END FUNCION FECHA

$daten= $rsItem['f_nacimiento'];
$sqldatne=date('d-m-Y',strtotime($daten));

if($rsItem['tipo_identificacion']==dni){ $documento="checked='checked'"; $otrodocumento=""; }else{ $documento=""; $otrodocumento="checked='checked'"; $otrod=$rsItem['tipo_identificacion']; }

if($rsItem['nacionalidad']==Española){ $nacion="checked='checked'"; $otronacion=""; }else{ $nacion=""; $otronacion="checked='checked'"; $otron=$rsItem['nacionalidad']; }

if($rsItem['sexo']==Hombre){ $sexhombre="checked='checked'"; $sexmujer=""; }else{ $sexhombre=""; $sexmujer="checked='checked'"; }

if($rsItem['compra_opcion']==si){ $empenado="checked='checked'"; $comprado=""; }else{ $empenado=""; $comprado="checked='checked'"; }


$bCheckPlazos = false;
$nPlazos = null;
if ($rsItem['venta_plazos'] == 'si') {
  $bCheckPlazos = true;
  $nPlazos = intval($rsItem['numero_plazos']);
}

$bMostrarCliente = false;
if ($rsItem['id_cliente']) {
  $bMostrarCliente = true;
}


$bCheckInscripciones = false;
if (strlen($rsItem['inscripciones']) > 0 && $rsItem['inscripciones'] != 0) {
  $bCheckInscripciones = true;
}

$bCheckPiedras = false;
if (strlen($rsItem['piedras']) > 0 && $rsItem['piedras'] != 0) {
  $bCheckPiedras = true;
}


if (isset($rsItem['inicio_facturas'])) {
  $inicio_facturas = $rsItem['inicio_facturas'];
}else{
  $inicio_facturas = "";
}

$id_venta_sucursal = $rsItem['id_venta_sucursal'];
$fecha_venta = $rsItem['fecha_venta'];



?>
<html>
<head>
  <meta charset="UTF-8" />
  <style>
    body {font-family: helvetica, arial;
      font-size: 7pt;
      width:900px;
      margin:0 auto;
      color:#000000;
      text-transform: uppercase; 
    }
    p{ 
      margin: 0pt;
      line-height:auto;
      font-size:12px;
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
      padding:2px 5px;
      border: 1px solid #333333;
      border-spacing:0px;
      height:35px;
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
    .checkseleccionado{
      background-color:#0a090a;
      -webkit-border-top-left-radius:3px;
      -moz-border-radius-topleft:3px;
      border-top-left-radius:3px;
      -webkit-border-top-right-radius:3px;
      -moz-border-radius-topright:3px;
      border-top-right-radius:3px;
      -webkit-border-bottom-right-radius:3px;
      -moz-border-radius-bottomright:3px;
      border-bottom-right-radius:3px;
      -webkit-border-bottom-left-radius:3px;
      -moz-border-radius-bottomleft:3px;
      border-bottom-left-radius:3px;
      text-indent:0px;
      display:inline-block;
      color:#ffffff;
      font-family:Verdana;
      font-size:11px;
      font-weight:bold;
      font-style:normal;
      height:16px;
      line-height:16px;
      width:16px;
      text-decoration:none;
      text-align:center;
    }
    .checknoseleccionado {
      background-color:#ffffff;
      -webkit-border-top-left-radius:3px;
      -moz-border-radius-topleft:3px;
      border-top-left-radius:3px;
      -webkit-border-top-right-radius:3px;
      -moz-border-radius-topright:3px;
      border-top-right-radius:3px;
      -webkit-border-bottom-right-radius:3px;
      -moz-border-radius-bottomright:3px;
      border-bottom-right-radius:3px;
      -webkit-border-bottom-left-radius:3px;
      -moz-border-radius-bottomleft:3px;
      border-bottom-left-radius:3px;
      text-indent:0px;
      border:1px solid #a3a3a3;
      display:inline-block;
      color:#ffffff;
      font-family:Verdana;
      font-size:11px;
      font-weight:bold;
      font-style:normal;
      height:14px;
      line-height:14px;
      width:14px;
      text-decoration:none;
      text-align:center;
    }
  </style>
  <style type="text/css" media="print">
@page {
    size: auto;   /* auto is the initial value */
    margin-top: 0;  /* this affects the margin in the printer settings */
    margin-bottom:0;
}
body{
  margin-top: 50px;
}
</style>
  <script type="text/javascript"> 
    function PrintWindow() { 
      window.print(); 
      CheckWindowState(); 
    } 

    function CheckWindowState() { 
      if(document.readyState=="complete") { 
        window.close(); 
      } else { 
        setTimeout("CheckWindowState()", 10) 
      } 
    } 
//PrintWindow(); 
</script>
</head>
<body>
  <table width="100%" cellspacing="0" cellpadding="0" class="table" >
    <tr>
      <td align="center">
        <h2>FACTURA DE VENTA DE OBJETOS METALES PRECIOSOS</h2>
      </td>
    </tr>
    <tr>
      <td>
        <h3>Datos del establecimiento</h3>
      </td>
    </tr>
  </table>

  <table width="100%"  cellspacing="0" cellpadding="0" class="table" >
    <tr>
      <td width="63%" class="table td">
        <p>Nombre</p>
        <p>
          <strong>
            <?php echo $nombreempresa; ?> <?php echo $nombresucursal; ?>
          </strong>
        </p>
      </td>
      <td width="37%" class="table td">
        <p>NIF</p>
        <p>
          <strong>
            <?php echo $numeroidentificaciontienda; ?>
          </strong>
        </p>
      </td>
    </tr>
    <tr>
      <td class="table td">
        <p>Calle</p>
        <p>
          <strong>
            <?php echo $calletienda; ?>
          </strong>
        </p>
      </td>
      <td class="table td">
        <p>Nº</p>
        <p>
          <strong>
            <?php echo $numerocalle; ?>
          </strong>
        </p>
      </td>
    </tr>
    <tr>
      <td class="table td">
        <p>Localidad</p>
        <p>
          <strong>
            <?php echo $poblaciontienda; ?>
          </strong>
        </p>
      </td>
      <td class="table td">
        <p>Provincia</p>
        <p>
          <strong>
            <?php echo $provinciatienda; ?>
          </strong>
        </p>
      </td>
    </tr>
  </table>

  <table width="100%" cellspacing="0" cellpadding="0" class="table" >
    <tr>
      <td>
        <h3>Datos de la operación</h3>
      </td>
    </tr>
  </table>

  <table width="100%" cellspacing="0" cellpadding="0" class="table" >
    <tr>
      <td width="47%" class="table td">
        <p>Nº Factura</p>
        <p>
          <strong>
            <?php echo $inicio_facturas; echo $id_venta_sucursal; ?>/<?php echo date("Y", strtotime($fecha_venta)); ?>
          </strong>
        </p>
      </td>
      <td width="53%" class="table td">
        <p>Fecha</p>
        <p>
          <strong>
            <?php echo $sqldate; ?>
          </strong>
        </p>
      </td>
    </tr>
    <tr>
      <td class="table td">
        <p>Nº de interno</p>
        <p>
          <strong>
            <?php echo $rsItem['id_venta']; ?>
          </strong>
        </p>
      </td>
    </td>

    <td class="table td">
      <p>Pago a plazos</p>

      <p>
        <input type="checkbox" name="radio" <?php if(!$bCheckPlazos) echo 'checked="checked"'; ?> > No

        <input type="checkbox" name="radio" <?php if($bCheckPlazos) echo 'checked="checked"'; ?>> Si
        <?php if ($nPlazos): ?>
          <span style="margin-left: 15px">
            <?php echo $nPlazos ?> plazos</span>
          <?php endif ?>

        </p>
      </td>
    </tr>
  </table>

  
  <?php if ($bMostrarCliente): ?>


    <table width="100%" cellspacing="0" cellpadding="0" class="table" >
      <tr>
        <td>
          <h3>DATOS DE CLIENTE</h3>
        </td>
      </tr>
    </table>

    <table width="100%" cellspacing="0" cellpadding="0" class="table" >
      <tr>
        <td width="47%" class="table td">
          <p>Nombre</p>
          <p>
            <strong>
              <?php echo $rsItem['nombre'] . ", " . $rsItem['apellido']; ?>
            </strong>
          </p>
        </td>
        <td width="53%" class="table td">
          <p>Nº documento</p>
          <p>
            <strong>
              <?php echo $rsItem['identificacion']; ?>
            </strong>
          </p>
        </td>
        <!-- <td class="table td">
          <p>Tipo</p>
          <p>
            <input type="checkbox" name="radio" <?php echo $documento; ?> > DNI
            <input type="checkbox" name="radio" <?php echo $otrodocumento; ?> > Otro (indicar):
            <strong>
              <?php echo $otrod; ?>
            </strong>
          </p>
        </td> -->
      </tr>
      
      <tr>
        <td colspan="2" class="table td">
          <p>Dirección</p>
          <p>
            <strong>
              <?php echo $rsItem['direccion']; ?><br>
              <?php echo $rsItem['c_poblacion']. ", ". $rsItem['c_provincia'] ?>
            </strong>
          </p>
        </td>
      </tr>
    </table>

  <?php endif ?>

  <br><br>

  <table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
    <tr>
      <td width="20%" class="table td" style="height: 50px;padding-top:10px;">
        <p>Nº artículo</p>
        <p>
          <strong>
            <?php echo $rsItem['id_articulo_venta']; ?>
          </strong>
        </p>
      </td>
      <td colspan="2" width="80%" class="table td" style="padding-top:10px;">
        <p>Descripción</p>
        <p>
          <strong>
            <?php echo $rsItem['descripcion']; ?>
          </strong>
        </p>
      </td>
    </tr>
    <tr>
      <td class="table td" style=" height:50px; padding-top:10px;">
        <p>Peso (gr.) </p>
        <p>
          <strong>
            <?php echo $rsItem['peso']; ?>
          </strong>
        </p>
      </td>
      <td class="table td" style=" height:50px; padding-top:10px;">
        <p>Metales </p>
        <p>
          <strong>
            <?php echo $rsItem['tipo']; ?>
          </strong>
        </p>
      </td>
      <td class="table td" style=" height:50px; padding-top:10px;">
        <p>Peso quilates</p>
        <p>
          <?php if ($rsItem['ley'] != 0): ?>
            <strong>
              <?php echo $rsItem['ley']; ?> kl
            </strong>
          <?php endif ?>
        </p>
      </td>
    </tr>
    <tr>
      <td class="table td" style=" height:50px; padding-top:10px;">
        <p>Inscripciones</p>
        <p>
          <input type="checkbox" name="radio"  <?php if($bCheckInscripciones) echo 'checked="checked"'; ?>> Si
          <!-- <input type="checkbox" name="radio"  <?php if(!$bCheckInscripciones) echo 'checked="checked"'; ?>> No -->
        </p>
      </td>
      <td  colspan="2" class="table td" style=" height:50px;padding-top:10px;">
        <p>Detalles  </p>
        <p>
          <?php if ($bCheckInscripciones): ?>
            <strong>
              <?php echo $rsItem['inscripciones']; ?>
            </strong>
          <?php endif ?>
        </p>
      </td>
    </tr>
    <tr>
      <td class="table td" style=" height:50px; padding-top:10px;">
        <p>Piedras</p>
        <p>
          <input type="checkbox" name="radio" <?php if($bCheckPiedras) echo 'checked="checked"'; ?> > Si
          <!-- <input type="checkbox" name="radio" <?php if(!$bCheckPiedras) echo 'checked="checked"'; ?> > No -->
        </p>
      </td>
      <td class="table td" style=" height:50px; padding-top:10px;">
        <p>Peso quilates</p>
        <p>
          <?php if ($bCheckPiedras): ?>
            <strong>
              <?php echo $rsItem['kilate_piedras']; ?>
            </strong>
          <?php endif ?>
        </p>
      </td>
      <td class="table td" style=" height:50px;padding-top:10px;">
        <p>Clase</p>
        <p>
          <?php if ($bCheckPiedras): ?>
            <strong>
              <?php echo $rsItem['piedras']; ?>
            </strong>
          <?php endif ?>
        </p>
      </td>
    </tr>
    <tr>
      <td colspan="3" width="53%" class="table td" style=" height:50px; padding-top:10px;">
        <p>Fotografia detallada objeto</p>
        <p>
          <input type="checkbox" name="radio" checked="checked"> Si
        </p>
      </td>
    </tr>

  </table>

  <?php 
  $precio = floatval($rsItem['precio']);

  $precio_imponible = round(floatval(($precio / 121) * 100), 2);
  $precio_iva = $precio - $precio_imponible;

  ?>
  <table width="25%" cellspacing="0" cellpadding="0" class="table tablearticulo" style="margin-top:0px; margin-left:auto;" >
    <tr>
      <!-- <td width="25%" class="table td">
        <p>Base Imponible</p>
        <p>
          <strong>
            <?php echo $precio_imponible; ?> €
          </strong>
        </p>
      </td> -->
      <!-- <td class="table td">
        <p>IVA</p>
        <p>
          <strong>
            <?php echo $precio_iva; ?> €
          </strong>
        </p>
      </td> -->
      <td class="table td">
        <p>Total</p>
        <p>
          <strong>
            <?php echo $precio; ?> €
          </strong>
        </p>
      </td>
    </tr>
  </table>
  <div style="text-align: left; font-style: italic; margin:5px 0 0 0; color:#000000; font-size:5pt; ">
    CLAUSULAS APLICABLES A LAS OPERACIONES DE COMPRA DE ORO.
    <br>
    <!-- OPERACIÓN SUJETA AL RÉGIMEN ESPECIAL DE BIENES USADOS SEGÚN ARTÍCULO 135 DE LA LEY 37/1992 DEL 28 DE DICIEMBRE DEL IVA. -->
    OPERACIÓN ACOGIDA AL RÉGIMEN ESPECIAL DE BIENES USADOS (REBU) DE LA LEY 37/1992 DEL IMPUESTO SOBRE EL VALOR AÑADIDO (IVA).
    <br>
    <strong>EN ESTE CONTRATO USTED ESTÁ COMPRANDO EL OBJETO REFERENCIADO.</strong>
    <br />
	<?
	switch($sucursal){
		//ESTER JOYERIA
		case 51:
		case 3:
		case 41:
		case 4:
		case 25:
		case 24:
		case 23:
		case 16:
		case 14:
		case 17:
		case 18:
			echo ('De acuerdo con los derechos que le confiere la normativa vigente y aplicable en protección de datos podrá
				ejercer los derechos de acceso, rectificación, limitación de tratamiento, supresión (“derecho al olvido”),
				portabilidad y oposición al tratamiento de sus datos de carácter personal así como la revocación del
				consentimiento prestado para el tratamiento de los mismos, dirigiendo su petición a la dirección C/Goya, 127 
				28009, MADRID o al correo electrónico esjollerias19@gmail.com. Podrá dirigirse a la Autoridad de
				Control competente para presentar la reclamación que considere oportuna.<br /><br />
				Si desea ampliar la información sobre los procedimientos y protocolos adoptados por ESTER JOYERIA 2019 SL,
				le informamos, que puede contactar con el Delegado de Protección de Datos, dirigiéndose por escrito a la
				dirección de correo dpd.cliente@conversia.es o al teléfono 902 877 192.');
			break;
		//AYALA RECICLADOS
		case 22:
		case 21:
		case 20:
		case 15:
			echo('De acuerdo con los derechos que le confiere la normativa vigente y aplicable en protección de datos podrá
				ejercer los derechos de acceso, rectificación, limitación de tratamiento, supresión (“derecho al olvido”),
				portabilidad y oposición al tratamiento de sus datos de carácter personal así como la revocación del
				consentimiento prestado para el tratamiento de los mismos, dirigiendo su petición a la dirección C/Pensamiento 27, 3º Esc. Izda. 
				28020, MADRID o al correo electrónico ayalareciclados16@hotmail.com. Podrá dirigirse a la Autoridad de
				Control competente para presentar la reclamación que considere oportuna.<br /><br />
				Si desea ampliar la información sobre los procedimientos y protocolos adoptados por AYALA RECICLADOS 16 SLU,
				le informamos, que puede contactar con el Delegado de Protección de Datos, dirigiéndose por escrito a la
				dirección de correo dpd.cliente@conversia.es o al teléfono 902 877 192.');
			break;
		//NANOPAC
		case 47:
			echo('De acuerdo con los derechos que le confiere la normativa vigente y aplicable en protección de datos podrá
					ejercer los derechos de acceso, rectificación, limitación de tratamiento, supresión (“derecho al olvido”),
					portabilidad y oposición al tratamiento de sus datos de carácter personal así como la revocación del
					consentimiento prestado para el tratamiento de los mismos, dirigiendo su petición a la dirección Avda. Brasil, 6 Planta 1 
					28020, MADRID o al correo electrónico nanopacjoyeriaydsl@gmail.com. Podrá dirigirse a la Autoridad de
					Control competente para presentar la reclamación que considere oportuna.<br /><br />
					Si desea ampliar la información sobre los procedimientos y protocolos adoptados por NANOPAC JOYERIA Y DISEÑO SL,
					le informamos, que puede contactar con el Delegado de Protección de Datos, dirigiéndose por escrito a la
					dirección de correo dpd.cliente@conversia.es o al teléfono 902 877 192.');
			break;
		//OPELIA
		case 50:
			echo('De acuerdo con los derechos que le confiere la normativa vigente y aplicable en protección de datos podrá
					ejercer los derechos de acceso, rectificación, limitación de tratamiento, supresión (“derecho al olvido”),
					portabilidad y oposición al tratamiento de sus datos de carácter personal así como la revocación del
					consentimiento prestado para el tratamiento de los mismos, dirigiendo su petición a la dirección C/Velázquez 53, 2º Izda 
					28001, MADRID o al correo electrónico opeliaservices@gmail.com. Podrá dirigirse a la Autoridad de
					Control competente para presentar la reclamación que considere oportuna.<br /><br />
					Si desea ampliar la información sobre los procedimientos y protocolos adoptados por OPELIA SERVICES SL,
					le informamos, que puede contactar con el Delegado de Protección de Datos, dirigiéndose por escrito a la
					dirección de correo dpd.cliente@conversia.es o al teléfono 902 877 192.');
			break;		
	}
?>
  </div>
  <br />
  <table width="100%" cellspacing="0" cellpadding="0" class="table" >
    <tr>
      <td align="center">
        <h4 style="margin-bottom:1em;">En <?php echo $poblaciontienda; ?>  a <?php echo $d; ?> de  <?php echo $m; ?> de <?php echo $a; ?>
      </h4>
    </td>
  </tr>
</table>
<!-- 

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="table">
  <tr>
    <td>
      <h6>Sello y firma del establecimiento</h6>
    </td>
    <td>
      <h6>Firma del interesado</h6>
    </td>
    <td>
      <h6>&nbsp;<br /> &nbsp;</h6>
    </td>
  </tr>
</table> -->

</body>
</html>