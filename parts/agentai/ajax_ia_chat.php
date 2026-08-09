<?php
require_once __DIR__ . '/../../include/session.php';
require_once __DIR__ . '/../../include/ia_claude.php';
require_once __DIR__ . '/ia_contexto_bd_extra.php';
require_once __DIR__ . '/ia_agent_config.php';
require_once __DIR__ . '/ia_flujos_especiales.php';

set_time_limit(120);
ini_set('max_execution_time', 120);
session_write_close();

define('IA_MAX_FILAS', 500);
define('IA_HISTORIAL_DIR', __DIR__ . '/historiales/');
require_once __DIR__ . '/ia_conversaciones.php';
define('IA_PRECIO_ORO_URL', 'https://www.andorrano-joyeria.com/precio-del-oro');

/**
 * Clave de columna en el resultado SQL → cabecera en tabla/exportes del chat.
 * Se refleja en IA_CONTEXTO_BD (anexo CABECERAS) y en la respuesta JSON (cabeceras_tabla).
 */
function ia_etiquetas_columna_chat() {
    return array(
        'nombre_sucursal' => 'Sucursal',
        'id_sucursal' => 'Nº',
        'id_cliente' => 'Nº',
        'nombre' => 'Nombre',
        'apellido' => 'Apellido',
        'tipo_identificacion' => 'Tipo identificación',
        'identificacion' => 'Identificación',
        'telefono' => 'Teléfono',
        'email' => 'Email',
        'nacionalidad' => 'Nacionalidad',
        'f_alta' => 'Fecha alta',
        'delete_state' => 'Estado',
        'f_nacimiento' => 'Fecha nacimiento',
        'movil' => 'Movil',
        'email' => 'Email',
        'observaciones' => 'Observaciones',
        'publicidad' => 'Publicidad',
        'sexo' => 'Sexo',
        'f_vencimiento' => 'Fecha vencimiento',
        'firma_cliente' => 'Firma',
        'direccion' => 'Dirección',
        'c_provincia' => 'Provincia',
        'c_poblacion' => 'Población',
        'codigo_postal' => 'Código postal',
        'observaciones_direccion' => 'Observaciones',
        'rel_id_provincia' => 'Provincia',
        'rel_id_pais' => 'País',
        'rel_id_poblacion' => 'Población',
        'nombre_sucursal' => 'Sucursal',
        'nombre_corto' => 'Nombre corto',
        'sum_item' => 'Suma de items',
        'fecha_liberacion' => 'Fecha liberación',
        'dias_liberacion' => 'Días liberación',
        'identificacion_tienda' => 'Identificación',
        'numero_identificacion_tienda' => 'Número identificación',
        'direccion_tienda' => 'Dirección',
        'poblacion_tienda' => 'Población',
        'provincia_tienda' => 'Provincia',
        'codigo_postal_tienda' => 'Código postal',
        'email_tienda' => 'Email',
        'telefono_tienda' => 'Teléfono',
        'movil_tienda' => 'Movil',
        'responsable_tienda' => 'Responsable',
        'estado_tienda' => 'Estado',
        'empresa' => 'Empresa',
        'calle' => 'Calle',
        'numero_calle' => 'Número',
        'intereses' => 'Intereses',
        'intereses_segunda' => 'Intereses segunda',
        'vasco' => 'Vasco',
        'aleman' => 'Alemán',
        'activa' => 'Activa',
        'precio_oro_tienda' => 'Precio oro',
        'porcentaje_venta_plazos' => 'Porcentaje venta plazos',
        'privilegio_usuario' => 'Privilegio',
        'nombre_privilegio' => 'Privilegio',
        'color_label_privilegio' => 'Color label',
        'sucursal_section' => 'Sucursal',
        'estado_usuario' => 'Estado',
        'telefono_usuario' => 'Teléfono',
        'sucursal_usuario' => 'Sucursal',
        'observaciones_usuario' => 'Observaciones',
        'firma_usuario' => 'Firma',
        'ultimo_acceso' => 'Último acceso',
        'fecAlta' => 'Fecha alta',
        'usuario_root' => 'Root',
        'nombre_usuario' => 'Nombre',
        'apellido_usuario' => 'Apellido',
        'email' => 'Email',
        'id_usuario' => 'Nº',
        'usuario' => 'Usuario',
        'sku_articulo' => 'SKU',
        'descripcion_articulo_rel' => 'Descripción',
        'precio_venta' => 'Precio',
        'fecha_venta' => 'Fecha',
        'hora_venta' => 'Hora',
        'total_ventas' => 'Total ventas',
        'venta_web' => 'Venta web',
        'cantidad_articulos' => 'Cantidad artículos',
        'motivo_anulacion' => 'Motivo anulacion',
        'fecha_anulacion' => 'Fecha anulacion',
        'anulado_por' => 'Anulado por',
        'intereses' => 'Intereses',
        'porcentaje_plazos' => 'Porcentaje plazos',
        'numero_plazos' => 'Número plazos',
        'importe_total' => 'Importe total',
        'importe_total_plazos' => 'Importe total plazos',
        'importe_total_contado' => 'Importe total contado',
        'importe_total_tarjeta' => 'Importe total tarjeta',
        'importe_total_bizum' => 'Importe total bizum',
        'importe_total_transferencia' => 'Importe total transferencia',
        'importe_total_combinado' => 'Importe total combinado',
        'importe_total_anulado' => 'Importe total anulado',
        'importe_total_anulado_plazos' => 'Importe total anulado plazos',
        'importe_total_anulado_contado' => 'Importe total anulado contado',
        'importe_total_anulado_tarjeta' => 'Importe total anulado tarjeta',
        'importe_total_anulado_bizum' => 'Importe total anulado bizum',
        'importe_total_anulado_transferencia' => 'Importe total anulado transferencia',
        'importe_total_anulado_combinado' => 'Importe total anulado combinado',
        'fecha_informe' => 'Fecha informe',
        'sucursal_informe' => 'Sucursal',
        'total_euros_ventas' => 'Euros ventas',
        'total_media_ventas' => 'Media ventas',
        'total_gramos_ventas' => 'Gramos ventas',
        'total_euros_lotes_compra_oro' => 'Euros compra oro',
        'total_lotes_compra_oro' => 'Lotes compra oro',
        'total_gramos_compra_oro' => 'Gramos compra oro',
        'total_euros_lotes_compra_plata' => 'Euros compra plata',
        'total_lotes_compra_plata' => 'Lotes compra plata',
        'total_gramos_compra_plata' => 'Gramos compra plata',
        'total_euros_lotes_empenios' => 'Euros empeños',
        'total_lotes_empenios' => 'Lotes empeños',
        'total_gramos_empenios' => 'Gramos empeños',
        'total_euros_lotes_empenios_oro' => 'Euros empeños oro',
        'total_euros_lotes_empenios_plata' => 'Euros empeños plata',
        'total_euros_renovaciones' => 'Euros renovaciones',
        'total_renovaciones' => 'Nº renovaciones',
        'total_euros_devoluciones' => 'Euros devoluciones',
        'total_devoluciones' => 'Nº devoluciones',
        'total_caja_entradas' => 'Caja entradas',
        'total_caja_salidas' => 'Caja salidas',
        'total_gastos' => 'Gastos',
        'total_entradas' => 'Total entradas',
        'total_salidas' => 'Total salidas',
        'beneficio_tienda' => 'Beneficio tienda',
        'matriz_beneficio_tienda' => 'Matriz beneficio',
        'total_beneficio_ventas' => 'Beneficio ventas',
        'total_coste_art_venta' => 'Coste art. venta',
        'stock_articulos' => 'Stock artículos',
        'stock_valorizado_eruo' => 'Stock valorizado €',
        'coste_stock_valorizado' => 'Coste stock valorizado',
        'ventas_web' => 'Ventas web',
        'total_euros_ventas_web' => 'Euros ventas web',
        'ventas_tarjeta_euros' => 'Ventas tarjeta €',
        'ventas_contado_euros' => 'Ventas contado €',
        'ventas_transferencia_euros' => 'Ventas transferencia €',
        'ventas_bizum_euros' => 'Ventas bizum €',
        'total_empenyos_retirados' => 'Empeños retirados',
        'total_euros_empenyos_retirados' => 'Euros empeños retirados',
        'total_empenyos_vencidos' => 'Empeños vencidos',
        'total_empenyos_perdidos' => 'Empeños perdidos',
        'total_euros_empenios_perdidos' => 'Euros empeños perdidos',
        'precio_oro' => 'Precio oro',
        'semana_numero' => 'Semana',
        'toral_repapraciones_euro' => 'Euros reparaciones',
        'total_reparaciones' => 'Nº reparaciones',
    );
}

function ia_contexto_bd_etiquetas_anexo() {
    $lines = array(
        '',
        'CABECERAS EN TABLAS DEL ASISTENTE (clave de columna en el resultado del SELECT → título mostrado en la tabla del chat y en exportes):',
    );
    foreach (ia_etiquetas_columna_chat() as $clave => $etiqueta) {
        $lines[] = '- ' . $clave . ' → "' . $etiqueta . '"';
    }
    return implode("\n", $lines);
}

/**
 * Etiqueta visible para una clave de columna (exportes, cabeceras_tabla en JSON).
 */
function ia_etiqueta_columna_listado($clave) {
    $map = ia_etiquetas_columna_chat();
    $k   = (string) $clave;
    foreach ($map as $dbKey => $etiqueta) {
        if (strcasecmp($k, $dbKey) === 0) {
            return $etiqueta;   
        }
    }
    return $clave;
}

// ─── ESQUEMA Y CONTEXTO DE BASE DE DATOS ─────────────────────────────────────

define('IA_CONTEXTO_BD', "
Eres un asistente de análisis de datos para una joyería española.
Tienes acceso a una base de datos MariaDB 10.1.

ALCANCE ESTRICTO (OBLIGATORIO — única fuente de verdad):
- Solo puedes usar las tablas documentadas en este mensaje (bloques «TABLA: …»), incluida la ampliación A–E al final. No existen más tablas para este asistente.
- NUNCA inventes, supongas ni cites tablas que no aparezcan con «TABLA:» (en especial no uses lotes_{id}, articulos_{id}, movimientos_de_caja_{id} por sucursal).
- NUNCA listes ni enumeres tablas de la base de datos que no aparezcan aquí con «TABLA:».
- Si preguntan qué puedes consultar o si hay «otras tablas»: explica que solo trabajas con el esquema de abajo (clientes, ventas, lotes/empeños, caja, gastos, envíos, facturas, informe diario, etc. según bloques TABLA) sin nombrar tablas ajenas a este contexto.
- Para datos concretos devuelve SOLO un SELECT usando únicamente tablas de este contexto y sus relaciones indicadas.

TABLAS DISPONIBLES (solo estas):

TABLA: clientes
- id_cliente            INT PK 
- nombre                VARCHAR → Nombre del cliente
- apellido              VARCHAR → Apellido del cliente
- tipo_identificacion   VARCHAR  (ej: 'DNI', 'NIE', 'PASAPORTE')
- identificacion        VARCHAR → Número del documento del cliente
- nacionalidad          VARCHAR → Nacionalidad del cliente
- telefono              VARCHAR → Teléfono del cliente
- sucursal              INT      -> FK a sucursal.id_sucursal (sucursal de alta / asignación del cliente)
- estado                ENUM('habilitado','deshabilitado') → Estado del cliente
- f_alta                DATE     -> Fecha de alta del cliente
- delete_state          ENUM('false','true')  -- 'true' = eliminado lógicamente

TABLA: datos_clientes  (datos adicionales del cliente; suele ampliar la ficha más allá de clientes)
- id_datos_cliente      INT PK AUTO_INCREMENT
- rel_id_cliente        INT NOT NULL  -> FK: rel_id_cliente = clientes.id_cliente
- f_nacimiento          DATE → Fecha de nacimiento del cliente (fecha de nacimiento del cliente)
- movil                 VARCHAR(14) → Movil del cliente
- email                 VARCHAR(180) → Email del cliente
- observaciones         VARCHAR(720) → Observaciones del cliente
- publicidad            VARCHAR(64) → Publicidad del cliente
- sexo                  VARCHAR(16) → Sexo del cliente
- f_vencimiento         DATE → Fecha de vencimiento del cliente
- firma_cliente         TEXT → Firma del cliente (firma del cliente en formato base64)
Nota: aquí están móvil, email, nacimiento, sexo, observaciones, etc.; para nombre/documento base usa clientes.

TABLA: direcciones  (direcciones postales y datos de ubicación por tipo de entidad)
- id_direcciones        INT PK AUTO_INCREMENT
- rel_id_item           INT NOT NULL → ID de la entidad (clientes, proveedores, empresas, sucursales, usuarios, envios)
- type_direccion        ENUM('clientes','proveedores','empresas','sucursales','usuarios','envios')
- direccion             VARCHAR(680) → Dirección de la dirección
- c_provincia           VARCHAR(180) → Provincia de la dirección
- c_poblacion           VARCHAR(180) → Población de la dirección
- c_pais                VARCHAR(180) → País de la dirección
- codigo_postal         VARCHAR(180) → Código postal de la dirección
- observaciones_direccion VARCHAR(720) → Observaciones de la dirección
- rel_id_provincia      INT → ID de la provincia
- rel_id_pais           INT → ID del país
- rel_id_poblacion      INT → ID de la población
Para direcciones de clientes: type_direccion = 'clientes' AND rel_id_item = clientes.id_cliente

TABLA: sucursal  (tiendas / puntos de venta)
- id_sucursal           INT PK AUTO_INCREMENT
- nombre_sucursal       VARCHAR(64)  — cabecera en tabla del asistente: ver mapa CABECERAS al final de este contexto
- nombre_corto          VARCHAR(10) → Nombre corto de la sucursal
- sum_item              INT → Suma de items de la sucursal
- fecha_liberacion      VARCHAR(18) → Fecha de liberación de la sucursal
- dias_liberacion       VARCHAR(9) → Días de liberación de la sucursal
- identificacion_tienda VARCHAR(64) → Identificación de la sucursal
- numero_identificacion_tienda VARCHAR(9) → Número de identificación de la tienda
- direccion_tienda      VARCHAR(64) → Dirección de la sucursal
- poblacion_tienda      VARCHAR(64) → Población de la sucursal
- provincia_tienda      VARCHAR(64) → Provincia de la sucursal
- codigo_postal_tienda  INT → Código postal de la sucursal
- email_tienda          VARCHAR(64) → Email de la sucursal
- telefono_tienda       INT → Teléfono de la sucursal
- movil_tienda          INT → Movil de la sucursal
- responsable_tienda    INT → Responsable de la sucursal
- estado_tienda         ENUM('habilitada','desabilitada') → Estado de la sucursal
- empresa               VARCHAR(180) → Empresa de la sucursal
- calle                 VARCHAR(180) → Calle de la sucursal
- numero_calle          INT → Número de la calle de la tienda
- intereses             INT → Intereses de la sucursal
- intereses_segunda     INT → Intereses segunda de la sucursal
- vasco                 ENUM('Si','No') → Si la sucursal es vasca
- aleman                ENUM('Si','No') → Si la sucursal es alemana
- activa                ENUM('true','false') → Si la tienda está activa
- precio_oro_tienda     DECIMAL(7,2) → Precio del oro de la sucursal
- porcentaje_venta_plazos INT → Porcentaje de venta a plazos de la tienda
- inicio_facturas       VARCHAR(10) → Inicio de facturas de la sucursal
- porcentaje_gastos_adelantos INT → Porcentaje de gastos de adelantos de la sucursal
- logotipo_sucursal     VARCHAR(128) → Logotipo de la sucursal
- valor_meses_perdidos_empenos INT → Valor de meses perdidos de empenos de la sucursal
- empresa_id            INT → ID de la empresa de la sucursal
- sms_state             ENUM('false','true') → Si el SMS está activo
- sms_state_empeno      ENUM('false','true') → Si el SMS de empenos está activo
- adelanto_capital      ENUM('false','true') → Si el adelanto de capital está activo
- firma_digital         ENUM('false','true') → Si la firma digital está activo
- codigo_firmas         VARCHAR(6) → Código de firmas de la sucursal
- sello_sucursal        INT → Sello de la sucursal
- sello_image           VARCHAR(168) → Imagen del sello de la sucursal
- fecha_inicio_firma    DATE → Fecha de inicio de la firma de la sucursal
- new_sitema_caja       ENUM('false','true') → Si el nuevo sistema de caja está activo
- caja_cerrada          ENUM('false','true') → Si la caja está cerrada (si la caja está cerrada)
- sucursal_web          ENUM('false','true') → Si la sucursal es web
- sms_contado           ENUM('false','true') → Si el SMS de contado está activo
- sms_otros_metodos_pago ENUM('false','true') → Si el SMS de otros métodos de pago está activo
- dias_devolucion       INT → Días de devolución de la sucursal
- matriz_beneficio_sucursal DECIMAL(7,2) → Matriz de beneficio de la sucursal
- codigo_gasto          ENUM('true','false') → Si el código de gasto está activo
- fundicion_multi_kilates ENUM('false','true') → Si la fundición es multi-kilates
- fecha_alta            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
- reiniciar_numero_factura ENUM('false','true') → Si se reinicia el número de factura
- tipo_sucursal         ENUM('true','false') → Si la sucursal es tipo sucursal
- prefijo_factura_simplificada VARCHAR(10) → Prefijo de la factura simplificada
- prefijo_factura_rectificativa VARCHAR(10) → Prefijo de la factura rectificativa
- prefijo_factura_rectificativa_simplificadas VARCHAR(10) → Prefijo de la factura rectificativa simplificada

TABLA: usuarios  (empleados / cuentas de acceso al sistema)
- id_usuario            INT PK AUTO_INCREMENT
- usuario               VARCHAR(50) UNIQUE → login de acceso
- password              existe en BD: NUNCA la incluyas en SELECT ni en el SQL
- nombre_usuario        VARCHAR(180) → Nombre del usuario
- apellido_usuario      VARCHAR(156) → Apellido del usuario
- email                 VARCHAR(100) → Email del usuario
- estado_usuario        ENUM('true','false') — 'true' = usuario activo
- telefono_usuario      INT → Teléfono del usuario
- sucursal_usuario      INT  -> FK a sucursal.id_sucursal
- privilegio_usuario    INT -> FK a privilegios_usuarios.id_privilegios (rol / nivel de permisos) -> privilegios_usuarios.nombre_privilegio
- observaciones_usuario TEXT
- firma_usuario         TEXT (firma; puede ser muy larga: evita SELECT * si no la necesitas)
- ultimo_acceso         DATETIME
- fecAlta               DATE (fecha de alta del usuario)
- usuario_root          ENUM('false','true') — 'true' = usuario root
Nombre legible: CONCAT_WS(' ', nombre_usuario, apellido_usuario). Direcciones de usuario: direcciones.type_direccion = 'usuarios' AND direcciones.rel_id_item = usuarios.id_usuario

TABLA: privilegios_usuarios  (catálogo de privilegios / roles)
- id_privilegios           INT PK AUTO_INCREMENT
- nombre_privilegio       VARCHAR(26) → nombre legible del privilegio
- color_label_privilegio  VARCHAR(28) → color UI (etiqueta)
- sucursal_section        ENUM('false','true') — sección sucursal en menú/UI
- root_section            ENUM('true','false')
- super_administrador     ENUM('false','true')
- auditoria_section       ENUM('false','true')
- recepcion_lotes_section ENUM('false','true')
- central_section         ENUM('false','true')

TABLA: articulos_venta  (fichas de artículo / stock en tienda; PK es id. NO uses esta tabla para listar las líneas vendidas de una venta: eso es rel_articulos_venta)
- id                      INT PK AUTO_INCREMENT
- id_sucursal_origen      INT NOT NULL -> FK a sucursal.id_sucursal
- id_lote_origen         INT NOT NULL (lote de origen)
- id_sucursal_destino    INT NOT NULL -> FK a sucursal.id_sucursal
- id_articulo_sucursal   INT NOT NULL (obsoleto: no uses este campo para JOINs ni para SKU; identifica por articulos_venta.id)
- descripcion             TEXT NOT NULL
- ley                     INT NOT NULL
- inscripciones_descripcion TEXT NOT NULL
- tipo                    ENUM('oro','plata','acero','otros') NOT NULL
- peso                    DECIMAL(9,2) NOT NULL
- piedras_descripcion     TEXT NOT NULL
- kilate_piedras          VARCHAR(256) NOT NULL
- precio                  DECIMAL(9,2) NOT NULL
- estado                  ENUM('noetiquetado_c','noetiquetado_u','enventa','enviado','retirado','vendido','vendido_web','enreparacion','mermado','reservado') NOT NULL
- fecha_enviado           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
- fecha_en_venta          TIMESTAMP (puede ser 0000-00-00 si no aplica)
- fecha_vendido           DATE NOT NULL
- hora_vendido            TIME NOT NULL
- observaciones           TEXT NOT NULL
- piedras                 TEXT NOT NULL
- inscripciones           TEXT NOT NULL
- fecha_retirado          TIMESTAMP (puede ser 0000-00-00 si no aplica)
- motivo_retirado         TEXT NOT NULL
- precio_coste            DECIMAL(9,2) NOT NULL → COSTE del artículo. Para «coste total de artículos vendidos» usa SUM(articulos_venta.precio_coste) filtrando por fecha_vendido y estado = 'vendido' (NO uses rel_articulos_venta.coste_articulo_venta para ese total)
- id_articulo_original    INT NOT NULL
- creado_por              INT NOT NULL -> FK a usuarios.id_usuario (quien dio de alta)
- fecha_alta              DATE NOT NULL
- origen_articulo         ENUM('central','sucursal') NOT NULL
- articulo_web            ENUM('false','true') NOT NULL
- id_prestashop           INT NOT NULL
- id_order_web            VARCHAR(68) NOT NULL
- update_register         DATE NOT NULL
- columna_parsetfecha     DATE NOT NULL
- estado_articulo         VARCHAR(168) NOT NULL
- categoria_articulo      INT NOT NULL
- nombre_sucursal_venta   VARCHAR(124) NOT NULL (denominación tienda en contexto venta)
- last_id_venta           INT NOT NULL -> FK a ventas.id (última venta asociada; 0 o sin uso si no vendido)
- id_venta_sucursal       INT NOT NULL
- motivo_merma            VARCHAR(168) NOT NULL
- fecha_mermado           DATE NOT NULL
- tipo_articulo           ENUM('oro','plata','acero','otros') NOT NULL
- precio_gramo            DECIMAL(7,2) NOT NULL
- column_update_disparador INT NOT NULL
- tipo_iva_articulo       ENUM('IVA','IPSI','IGIC','OTHER') NOT NULL DEFAULT 'IVA'
- system_codigo_regimen   ENUM('REBU','INVERSION','GENERAL') NOT NULL DEFAULT 'REBU'
- precio_sin_iva          DECIMAL(15,2) NOT NULL
- precio_coste_calculado  DECIMAL(15,2) NOT NULL

TABLA: ventas
- id                    INT PK
- id_sucursal           INT      -> FK a sucursal.id_sucursal
- Con articulos_venta enlaza por: ventas.id = articulos_venta.last_id_venta (no uses id_articulo_venta, es obsoleto)
- id_venta_sucursal     INT → ID de la venta de la sucursal
- cliente               INT      -> FK a clientes.id_cliente
- comprado_por          INT      -> FK a usuarios.id_usuario (id del empleado que compró la venta)
- intereses             INT → Intereses de la venta
- estado                ENUM('vendido','enfecha','vencido','anulado') → Estado de la venta
- venta_plazos          ENUM('si','no') → Si la venta se realizó a plazos
- porcentaje_plazos     INT → Porcentaje de intereses de la venta a plazos
- numero_plazos         INT → Número de plazos de la venta a plazos
- tipo_pago             ENUM('contado', 'tarjeta', 'transferencia', 'bizum', 'mixto') → Tipo de pago de la venta
- precio                DECIMAL(15,2) → Importe total de la venta
- cantidad_contado      DECIMAL(15,2) → Importe total de la venta en contado
- cantidad_tarjeta      DECIMAL(15,2) → Importe total de la venta en tarjeta
- cantidad_transferencia DECIMAL(15,2) → Importe total de la venta en transferencia
- cantidad_bizum        DECIMAL(15,2) → Importe total de la venta en bizum
- fecha                 TIMESTAMP  (fecha y hora de la venta)
- cantidad_articulos    INT → Número de artículos vendidos
- motivo_anulacion      TEXT → Motivo de anulación de la venta (si la venta está anulada)
- fecha_anulacion       DATE → Fecha de anulación de la venta (si la venta está anulada)
- anulado_por           INT → ID del usuario que anuló la venta (si la venta está anulada)
- venta_web             ENUM('false','true') → Si la venta se realizó en la web (si la venta está anulada)

TABLA: rel_articulos_venta  (líneas de artículo ya vendidas / desglose por venta; aquí están los artículos vendidos)
- id_rel_art_venta        INT PK AUTO_INCREMENT (uso interno; en listados al usuario NO lo pongas en el SELECT)
- sku_articulo            INT NOT NULL -> FK a articulos_venta.id — en listados este valor ES el SKU (primera columna útil; NO confundas con id_rel_art_venta)
- sucursal_venta          INT NOT NULL -> FK a sucursal.id_sucursal
- descripcion_articulo_rel TEXT NOT NULL
- id_venta_rel            INT NOT NULL
- rel_id_venta            INT NOT NULL -> FK a ventas.id (cabecera de venta a la que pertenece la línea)
- precio_venta            DECIMAL(7,2) NOT NULL → importe de venta de la línea (lo cobrado por ese artículo)
- fecha_venta             DATE NOT NULL
- hora_venta              TIME NOT NULL
- vendido_por             INT NOT NULL -> FK a usuarios.id_usuario
- venta_web               ENUM('false','true') NOT NULL
- backupfecha             DATETIME NOT NULL
- id_order_web            INT NOT NULL
- coste_articulo_venta    DECIMAL(15,2) NOT NULL → coste copiado en la línea de venta (uso interno/ticket). Para totales de «coste de artículos vendidos» NO uses esta columna: usa articulos_venta.precio_coste
- tipo_iva_articulo       ENUM('IVA','IPSI','IGIC','OTHER') NOT NULL
- system_codigo_regimen   ENUM('REBU','INVERSION','GENERAL') NOT NULL
- estado_rel_Articulo     ENUM('vendido','devuelto') NOT NULL — 'devuelto' = línea anulada por devolución; exclúyela en totales de ventas/coste activas

RELACIONES:
- rel_articulos_venta.rel_id_venta = ventas.id → FK a ventas.id (listar artículos de una venta por esta clave)
- rel_articulos_venta.sku_articulo = articulos_venta.id → FK a articulos_venta.id (datos del artículo original)
- rel_articulos_venta.sucursal_venta = sucursal.id_sucursal → FK a sucursal.id_sucursal
- rel_articulos_venta.vendido_por = usuarios.id_usuario → FK a usuarios.id_usuario
- articulos_venta.last_id_venta = ventas.id → FK a ventas.id (relación venta–artículo en ficha/stock)
- articulos_venta.id_sucursal_origen = sucursal.id_sucursal → FK a sucursal.id_sucursal
- articulos_venta.id_sucursal_destino = sucursal.id_sucursal → FK a sucursal.id_sucursal
- articulos_venta.creado_por = usuarios.id_usuario → FK a usuarios.id_usuario
- ventas.cliente = clientes.id_cliente → FK a clientes.id_cliente
- ventas.id_sucursal = sucursal.id_sucursal → FK a sucursal.id_sucursal
- ventas.comprado_por = usuarios.id_usuario → FK a usuarios.id_usuario (empleado que realizó la venta)
- usuarios.sucursal_usuario = sucursal.id_sucursal → FK a sucursal.id_sucursal
- usuarios.privilegio_usuario = privilegios_usuarios.id_privilegios → FK a privilegios_usuarios.id_privilegios
- clientes.sucursal = sucursal.id_sucursal → FK a sucursal.id_sucursal
- datos_clientes.rel_id_cliente = clientes.id_cliente → FK a clientes.id_cliente
- direcciones (clientes): direcciones.type_direccion = 'clientes' AND direcciones.rel_id_item = clientes.id_cliente → FK a clientes.id_cliente
- direcciones (usuarios): direcciones.type_direccion = 'usuarios' AND direcciones.rel_id_item = usuarios.id_usuario → FK a usuarios.id_usuario

EJEMPLOS DE PREGUNTAS CON SUCURSAL:
- Artículos/líneas vendidas (ticket o por sucursal): SELECT rel_articulos_venta.sku_articulo, rel_articulos_venta.descripcion_articulo_rel, rel_articulos_venta.precio_venta, rel_articulos_venta.fecha_venta, rel_articulos_venta.hora_venta ... WHERE rel_id_venta = ... (sin id_rel_art_venta; sku_articulo es el SKU)
- 'la sucursal que más vende': JOIN ventas ON ventas.id_sucursal = sucursal.id_sucursal, filtrar ventas no anuladas, SUM(ventas.precio), GROUP BY sucursal.id_sucursal, ORDER BY total DESC
- Ventas por nombre de tienda: sucursal.nombre_sucursal
- Clientes de una sucursal: clientes.sucursal = id o JOIN sucursal ON clientes.sucursal = sucursal.id_sucursal
- Sucursales: sucursal.id_sucursal, sucursal.nombre_sucursal
- Sucursales habilitadas: sucursal.estado_tienda = 'habilitada'
- Sucursales deshabilitadas: sucursal.estado_tienda = 'desabilitada'
- Sucursales desabilitadas: sucursal.estado_tienda = 'desabilitada'

REGLAS IMPORTANTES:
- Excluye siempre clientes eliminados: clientes.delete_state = 'false'
- Ventas anuladas: ventas.estado = 'anulada'
- Para ventas activas filtra: ventas.estado != 'anulada'
- Fecha actual: CURDATE() o NOW()
- Para comparar solo fecha de un TIMESTAMP: DATE(ventas.fecha)
- Mes actual: MONTH(ventas.fecha) = MONTH(NOW()) AND YEAR(ventas.fecha) = YEAR(NOW())
- Año pasado: YEAR(columna_fecha) = YEAR(CURDATE()) - 1  (o BETWEEN 'YYYY-01-01' AND 'YYYY-12-31')
- IMPORTES vs COSTE (no confundir):
  · Importe/facturación/«cuánto se ha vendido» → SUM(ventas.precio) o SUM(rel_articulos_venta.precio_venta)
  · Coste/coste total de artículos vendidos → SUM(articulos_venta.precio_coste) sobre articulos_venta con estado = 'vendido' y fecha_vendido del periodo. NUNCA uses ventas.precio ni rel_articulos_venta.coste_articulo_venta para ese total
  · Ejemplo coste año pasado: SELECT SUM(precio_coste) AS coste_total FROM articulos_venta WHERE fecha_vendido BETWEEN CONCAT(YEAR(CURDATE()) - 1, '-01-01') AND CONCAT(YEAR(CURDATE()) - 1, '-12-31') AND estado = 'vendido'
- En totales por línea de ticket (precio cobrado) puedes filtrar rel_articulos_venta.estado_rel_Articulo = 'vendido' (excluye devoluciones)
- Si no se especifica sucursal, consulta todas
- Para direcciones de cliente usa siempre type_direccion = 'clientes' y rel_id_item = clientes.id_cliente
- datos_clientes puede no existir para todos los clientes: usa LEFT JOIN datos_clientes dc ON dc.rel_id_cliente = clientes.id_cliente
- Devuelve SOLO consultas SELECT, nunca INSERT/UPDATE/DELETE/DROP/ALTER
- Nunca incluyas la columna usuarios.password en el SQL
- Limita con LIMIT cuando no se pida un total o agregado
- No uses punto y coma al final
- No uses bloques de código markdown, solo el SQL puro
- Evita JOINs innecesarios: si el SELECT/WHERE solo usa una tabla, no joins otras «por si acaso» (ralentiza y puede alterar totales)
- Las ventas a plazos: ventas.venta_plazos = 'si'
- Las ventas sin plazos: ventas.venta_plazos = 'no'
- Si te piden exportar resultados puedes exportarlos a excel y pdf
- Si te piden generar un informe, puedes generar un informe en excel y pdf
- Solo activa los botones de excel y pdf si te lo piden explícitamente
- Si te piden limpiar el chat, borrar el chat, borrar el historial de la conversacion y el chat se reinicia
- Si te preguntan en euros, usa el símbolo € y sigue el contexto de la pregunta
- Si te preguntan sobre una sucursal, usa sucursal.id_sucursal y sucursal.nombre_sucursal
- Si te preguntan sobre un cliente, usa clientes.id_cliente; datos extra LEFT JOIN datos_clientes; dirección JOIN direcciones con type_direccion = 'clientes'
- Si te preguntan sobre un usuario, usa usuarios.id_usuario, usuarios.usuario, usuarios.nombre_usuario, usuarios.apellido_usuario; tienda JOIN sucursal ON usuarios.sucursal_usuario = sucursal.id_sucursal; rol JOIN privilegios_usuarios pu ON pu.id_privilegios = usuarios.privilegio_usuario
- Si te preguntan sobre una venta, usa ventas.id y ventas.id_venta_sucursal; sucursal JOIN sucursal ON ventas.id_sucursal = sucursal.id_sucursal; cliente JOIN clientes ON ventas.cliente = clientes.id_cliente
- Si te preguntan artículo en **stock**, usa **articulos_venta** con **articulos_venta.estado = 'enventa'**
- Si te preguntan **líneas vendidas** o desglose de ticket, usa **rel_articulos_venta** con rel_id_venta = ventas.id; LEFT JOIN articulos_venta av ON av.id = rel_articulos_venta.sku_articulo; en listados incluye sku_articulo y no id_rel_art_venta
- Si piden lotes/empeños/renovaciones/adelantos/envíos/caja/gastos/facturas/etc.: usa las tablas de la ampliación A–E (lotes_joyeria, articulos_lotes, historico_renovaciones_gobal, …). Nunca tablas por sucursal lotes_N
- Si preguntan por TOTALES, sumas, medias o resúmenes agregados de un día/semana/mes/año/rango (ventas, compras oro/plata, empeños, renovaciones, caja, gastos, stock valorizado, etc.): preferir TABLA informe_diario (generada ~21:30) con SUM()/AVG(), filtrar por fecha_informe y estado_informe='finalizado'; JOIN sucursal si piden nombre. Detalle por operación → tablas operativas. Utilidad/beneficio/rentabilidad/ganancia → flujo servidor (no informe_diario).
- Si el usuario solo agradece (p. ej. \"gracias\", \"muchas gracias\"): responde exactamente la frase: Dios te bendiga (sin SQL; el servidor lo gestiona aparte)
- Si te pregunta por el precio del oro: NO generes SQL; el servidor responde desde precios_oro (euros/gramo). Hoy = registro de hoy (o el más reciente si no hay); ayer = día anterior al último registro; por defecto todos los kilates; si pide 18k/etc. solo ese.
- Si preguntan utilidad, beneficio, rentabilidad o ganancia de tienda(s)/sucursal(es) (también: «cuánto hemos ganado», «dame el beneficio», «cómo van los beneficios», «resultado neto», «cuál es la rentabilidad», «sácame la utilidad», etc.): NO generes SQL; el servidor calcula en varias consultas en orden fijo, filtra por el periodo de la pregunta (mes concreto, fecha, mes/año actual, año pasado, entre dos fechas; por defecto mes actual) y responde en texto con desglose (sin tabla)
- Si te preguntan sobre un privilegio, usa privilegios_usuarios.id_privilegios y privilegios_usuarios.nombre_privilegio
- Si te preguntan sobre un privilegio de la sucursal, usa privilegios_usuarios con sucursal_section / central_section / auditoria_section según el caso
- Si te preguntan sobre un usuario, usa usuarios.id_usuario, usuarios.usuario (login), usuarios.nombre_usuario, usuarios.apellido_usuario; para la tienda JOIN sucursal ON usuarios.sucursal_usuario = sucursal.id_sucursal; para el nombre del rol o permisos JOIN privilegios_usuarios pu ON pu.id_privilegios = usuarios.privilegio_usuario
- Si te preguntan sobre **rel_articulos_venta** (líneas vendidas): JOIN ventas v ON v.id = rel_articulos_venta.rel_id_venta; sucursal JOIN sucursal s ON s.id_sucursal = rel_articulos_venta.sucursal_venta; vendedor JOIN usuarios u ON u.id_usuario = rel_articulos_venta.vendido_por; artículo original LEFT JOIN articulos_venta av ON av.id = rel_articulos_venta.sku_articulo
- Si te preguntan sobre **articulos_venta** en circuito tienda/stock (no listado por venta vendida), usa articulos_venta.id y articulos_venta.descripcion; stock en venta: **estado = 'enventa'**; para tienda destino JOIN sucursal ON articulos_venta.id_sucursal_destino = sucursal.id_sucursal; origen JOIN sucursal so ON articulos_venta.id_sucursal_origen = so.id_sucursal; usuario JOIN usuarios ON articulos_venta.creado_por = usuarios.id_usuario; última venta asociada JOIN ventas ON articulos_venta.last_id_venta = ventas.id; venta sucursal JOIN ventas ON articulos_venta.id_venta_sucursal = ventas.id_venta_sucursal
" . ia_contexto_bd_extra_texto() . ia_contexto_bd_etiquetas_anexo());

/**
 * Tablas que el chat IA puede usar en SELECT (deben coincidir con bloques TABLA: en IA_CONTEXTO_BD).
 */
function ia_tablas_permitidas_chat() {
    return array_values(array_unique(array_merge(
        array(
            'clientes',
            'datos_clientes',
            'direcciones',
            'sucursal',
            'usuarios',
            'privilegios_usuarios',
            'articulos_venta',
            'ventas',
            'rel_articulos_venta',
        ),
        ia_tablas_permitidas_extra()
    )));
}

/**
 * Pregunta sobre capacidades/alcance (no pide datos concretos ni SQL).
 */
function ia_pregunta_es_informativa_sin_datos($pregunta) {
    $p = function_exists('mb_strtolower') ? mb_strtolower(trim($pregunta), 'UTF-8') : strtolower(trim($pregunta));
    if ($p === '') {
        return false;
    }
    if (preg_match('/\b(cuánto|cuánta|cuántos|cuántas|listado|lista de|dame |dame los|dame las|muestra|muéstrame|muestrame|consulta los|consulta las|totales|suma |promedio|ventas de|exportar|excel|pdf|cuántos hay|cuantos hay|hay en total|del mes|de ayer|de hoy|nombre de|busca |encuentra |top \d|últimas ventas|ultimas ventas)\b/u', $p)) {
        return false;
    }
    $indicadores = array(
        'puedo pedirte',
        'puedo pedir',
        'puedes consultar',
        'puedo consultar',
        'otras tablas',
        'otra tabla',
        'qué tablas',
        'que tablas',
        'cuáles tablas',
        'cuales tablas',
        'listar tablas',
        'listar las tablas',
        'tablas de la base',
        'tablas de la bd',
        'tablas tienes',
        'tablas tiene',
        'qué puedes hacer',
        'que puedes hacer',
        'para qué sirves',
        'a qué datos',
        'a que datos',
        'tienes acceso',
        'tienes acceso a',
        'puedes ver en la base',
        'solo estas tablas',
        'sólo estas tablas',
        'qué información tienes',
        'que información tienes',
        'qué sabes consultar',
        'que sabes consultar',
        'más tablas',
        'mas tablas',
    );
    foreach ($indicadores as $needle) {
        if (strpos($p, $needle) !== false) {
            return true;
        }
    }
    if (preg_match('/\b(tabla|tablas|base de datos|bbdd)\b/u', $p)
        && preg_match('/\b(puedo|puedes|consultar|acceso|otras?|más|mas|pedir)\b/u', $p)
        && strlen($p) < 200) {
        return true;
    }
    return false;
}

function ia_respuesta_parece_sql_select($texto) {
    return ia_extraer_sql_de_respuesta($texto) !== '';
}

/**
 * Extrae un SELECT aunque venga envuelto en markdown o texto explicativo.
 *
 * @return string SQL vacío si no hay SELECT válido
 */
function ia_extraer_sql_de_respuesta($texto) {
    $texto = trim((string) $texto);
    if ($texto === '') {
        return '';
    }

    if (preg_match('/```(?:sql)?\s*(SELECT[\s\S]*?)\s*```/iu', $texto, $m)) {
        $sql = trim($m[1]);
    } elseif (preg_match('/\b(SELECT[\s\S]+)/iu', $texto, $m)) {
        $sql = trim($m[1]);
    } else {
        return '';
    }

    $sql = preg_replace('/^```[a-z]*\n?/iu', '', $sql);
    $sql = preg_replace('/```\s*$/u', '', $sql);
    $sql = trim($sql);

    // Cortar explicaciones posteriores al SQL
    if (preg_match('/\n\s*(?:Nota:|Explicaci[oó]n:|Este query|Esta consulta|--)/iu', $sql, $m, PREG_OFFSET_CAPTURE)) {
        $sql = substr($sql, 0, $m[0][1]);
    }

    $sql = rtrim(trim($sql), ';');

    if (!preg_match('/^SELECT\b/is', ltrim($sql))) {
        return '';
    }

    return $sql;
}

function ia_sql_tablas_referenciadas($sql) {
    $tablas = array();
    if (preg_match_all('/\b(?:FROM|JOIN)\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $coincidencias)) {
        foreach ($coincidencias[1] as $nombre) {
            $tablas[] = strtolower($nombre);
        }
    }
    return array_values(array_unique($tablas));
}

function ia_sql_tablas_no_permitidas($sql) {
    $permitidas = array_flip(array_map('strtolower', ia_tablas_permitidas_chat()));
    $no_permitidas = array();
    foreach (ia_sql_tablas_referenciadas($sql) as $tabla) {
        if (!isset($permitidas[$tabla])) {
            $no_permitidas[] = $tabla;
        }
    }
    return $no_permitidas;
}

function ia_responder_pregunta_informativa($pregunta, $usuario_id) {
    $lista_tablas = implode(', ', ia_tablas_permitidas_chat());
    $contexto = ia_agent_contexto_bd_efectivo(IA_CONTEXTO_BD);
    $identidad = ia_agent_prompt_o_fallback(
        'identidad',
        'base',
        'Eres el asistente de consultas de una joyería en España.'
    );
    $reglas_inf = ia_agent_prompt_o_fallback(
        'informativa',
        'instrucciones',
        "El usuario pregunta por TUS CAPACIDADES o el ALCANCE de datos; NO ejecutes una consulta ahora.\n"
        . "Responde en español, breve (máximo 6 líneas), claro y amable.\n"
        . "Solo puedes obtener datos mediante SELECT sobre las tablas del contexto.\n"
        . "NO cites ni listes nombres de tablas que no aparezcan en el contexto (bloques TABLA:).\n"
        . "NO digas que tienes acceso a toda la base de datos ni inventes tablas fuera del contexto.\n"
        . "Si preguntan por «otras tablas», explica que solo trabajas con el esquema del contexto.\n"
        . "No generes SQL ni bloques de código."
    );
    $prompt = $identidad . "\n\n"
        . "CONTEXTO (única fuente de verdad):\n"
        . $contexto
        . "\n\nREGLAS DE ESTA RESPUESTA:\n"
        . $reglas_inf
        . "\n- Solo puedes obtener datos mediante SELECT sobre estas tablas: {$lista_tablas}.\n\n"
        . "Pregunta del usuario: " . $pregunta;

    $respuesta = ia_llamar_claude(array(array('role' => 'user', 'content' => $prompt)), 500);
    if (!$respuesta || trim($respuesta) === '') {
        return 'Solo puedo consultar datos de lectura (SELECT) sobre el esquema definido para este asistente '
            . '(clientes, ventas, artículos, lotes/empeños, renovaciones, caja, gastos, envíos, facturas, etc.). '
            . '¿Qué necesitas consultar?';
    }

    $texto = ia_strip_markdown_tables(trim($respuesta));
    ia_historial_apend_turno($usuario_id, $pregunta, $texto);
    return nl2br(htmlspecialchars($texto));
}

// ─── FUNCIONES DE HISTORIAL EN FICHERO JSON ──────────────────────────────────

function ia_historial_path($usuario_id) {
    return IA_HISTORIAL_DIR . 'historial_' . (int)$usuario_id . '.json';
}

function ia_historial_leer($usuario_id) {
    $path = ia_historial_path($usuario_id);
    if (!file_exists($path)) return array();
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    return is_array($data) ? $data : array();
}

function ia_historial_guardar($usuario_id, $historial) {
    if (!is_dir(IA_HISTORIAL_DIR)) {
        mkdir(IA_HISTORIAL_DIR, 0755, true);
    }
    file_put_contents(
        ia_historial_path($usuario_id),
        json_encode($historial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

function ia_historial_limpiar($usuario_id) {
    $path = ia_historial_path($usuario_id);
    if (file_exists($path)) unlink($path);
}

// ─── CONEXIÓN ────────────────────────────────────────────────────────────────

$conexion = conectar_bd();

// ─── ROUTER ──────────────────────────────────────────────────────────────────

if (defined('IA_AGENT_SOLO_DEFINICIONES') && IA_AGENT_SOLO_DEFINICIONES) {
    return;
}

$accion = isset($_REQUEST['accion']) ? $_REQUEST['accion'] : 'chat';

switch ($accion) {
    case 'limpiar_historial':
        $usuario_id = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
        ia_historial_limpiar($usuario_id);
        // También deja una conversación UI nueva (archiva la anterior si tenía mensajes)
        ia_conv_nueva($usuario_id, '');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => true));
        exit;
    case 'conv_obtener_activa':
    case 'conv_guardar':
    case 'conv_nueva':
    case 'conv_listar':
    case 'conv_cargar':
        $usuario_id = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
        ia_conv_handler_ajax($usuario_id);
        exit;
    case 'export_excel':
        ia_export_excel($conexion);
        break;
    case 'export_pdf':
        ia_export_pdf($conexion);
        break;
    case 'analizar_documento_chat':
        ia_analizar_documento_chat_handler();
        exit;
    default:
        ia_chat($conexion);
        break;
}

// ─── ACCIÓN: CHAT ────────────────────────────────────────────────────────────

/**
 * Alta de cliente/lote solo con documentación: no generar ni ejecutar SQL (el chat no hace INSERT).
 * Alineado con iaChatEsSoloFlujoAdjuntoDocumentacion en modal_ia_chat.php.
 */
function ia_pregunta_es_solo_flujo_adjunto_documentacion($pregunta) {
    if (!ia_mensaje_sugiere_adjuntos_creacion($pregunta)) {
        return false;
    }
    $p = function_exists('mb_strtolower') ? mb_strtolower(trim($pregunta), 'UTF-8') : strtolower(trim($pregunta));
    if (strlen($p) > 240) {
        return false;
    }
    if (preg_match('/\b(cuánto|cuánta|cuántos|cuántas|listado|lista de|dame|muestra|muestrame|muéstrame|consulta|totales|total |suma |promedio|informe|exportar|excel|pdf|ventas de|sql|tabla con|cuántos hay|dame los|dame las)\b/u', $p)) {
        return false;
    }
    return true;
}

function ia_responder_json_chat($usuario_id, $pregunta_raw, $texto_plano, $mostrar_adjuntos = false) {
    ia_historial_apend_turno($usuario_id, $pregunta_raw, $texto_plano);
    echo json_encode(array(
        'ok'               => true,
        'mostrar_adjuntos' => $mostrar_adjuntos,
        'texto'            => nl2br(htmlspecialchars($texto_plano)),
        'filas'            => array(),
        'sql'              => '',
        'sql_crudo'        => '',
        'total'            => 0,
        'cabeceras_tabla'  => array(),
    ));
    exit;
}

/**
 * Saludos y mensajes sociales sin consulta de datos (sin SQL).
 */
function ia_pregunta_es_conversacional($pregunta) {
    $raw = preg_replace('/\s+/u', ' ', trim((string) $pregunta));
    $raw = preg_replace('/[!¡\.…,;:]+$/u', '', $raw);
    if ($raw === '') {
        return false;
    }

    $p = function_exists('mb_strtolower') ? mb_strtolower($raw, 'UTF-8') : strtolower($raw);

    if (preg_match(
        '/\b(cuánto|cuánta|cuántos|cuántas|cuanto|cuanta|cuantos|cuantas|listado|lista de|dame |dame los|dame las|'
        . 'muestra|muéstrame|muestrame|consulta|totales|total |suma |promedio|ventas de|exportar|excel|pdf|'
        . 'clientes de|artículos|articulos|usuarios de|sucursal de|sql|hay en total|del mes|de ayer|de hoy)\b/u',
        $p
    )) {
        return false;
    }

    $patrones = array(
        '/^(hola|hey|buenas|saludos|qu[eé]\s+tal)$/u',
        '/^(hola|hey|buenas|saludos|qu[eé]\s+tal)\s+(qu[eé]\s+tal|que\s+tal)$/u',
        '/^buen\s+d[ií]a$/u',
        '/^buenos\s+d[ií]as$/u',
        '/^buenas\s+tardes$/u',
        '/^buenas\s+noches$/u',
        '/^buena\s+noche$/u',
        '/^(adi[oó]s|hasta\s+luego|nos\s+vemos|chao|bye)$/u',
        '/^(ok|vale|perfecto|entendido|genial|de\s+acuerdo)$/u',
    );
    foreach ($patrones as $re) {
        if (preg_match($re, $p)) {
            return true;
        }
    }

    return false;
}

function ia_respuesta_conversacional($pregunta) {
    $p = function_exists('mb_strtolower') ? mb_strtolower(trim((string) $pregunta), 'UTF-8') : strtolower(trim((string) $pregunta));

    if (preg_match('/buenas\s+noches|buena\s+noche/u', $p)) {
        return 'Buenas noches. ¿En qué puedo ayudarte? Puedo consultar datos de clientes, ventas, sucursales, usuarios y artículos.';
    }
    if (preg_match('/buenas\s+tardes/u', $p)) {
        return 'Buenas tardes. ¿Qué necesitas consultar?';
    }
    if (preg_match('/buenos\s+d[ií]as|buen\s+d[ií]a/u', $p)) {
        return 'Buenos días. ¿En qué puedo ayudarte?';
    }
    if (preg_match('/^(adi[oó]s|hasta\s+luego|nos\s+vemos|chao|bye)/u', $p)) {
        return 'Hasta luego. Cuando quieras, aquí estaré para ayudarte con tus consultas.';
    }
    if (preg_match('/^(ok|vale|perfecto|entendido|genial|de\s+acuerdo)$/u', $p)) {
        return 'De acuerdo. Si necesitas algo más, dímelo.';
    }

    return 'Hola. Soy tu asistente de consultas. Puedo ayudarte con datos de clientes, ventas, sucursales, usuarios y artículos. ¿Qué te gustaría saber?';
}

function ia_respuesta_fallback_sin_consulta($pregunta) {
    if (ia_pregunta_es_conversacional($pregunta)) {
        return ia_respuesta_conversacional($pregunta);
    }

    return 'No he podido interpretar esa consulta. Prueba a ser más concreto, por ejemplo: '
        . '«ventas de hoy», «clientes de la sucursal X» o «artículos en stock». '
        . 'También puedo responder a saludos o explicarte qué datos puedo consultar.';
}

function ia_chat($conexion) {
    header('Content-Type: application/json; charset=utf-8');

    if (empty($_POST['pregunta'])) {
        echo json_encode(array('ok' => false, 'error' => 'Pregunta vacía.'));
        exit;
    }

    $pregunta_raw = trim($_POST['pregunta']);
    $pregunta     = ia_pregunta_normalizar_entrada($pregunta_raw);
    $usuario_id   = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;

    // Flujos especiales configurables (grupo flujos + disparadores en BD)
    if (function_exists('ia_agent_flujo_respuesta_si_aplica')) {
        $resp_flujo = ia_agent_flujo_respuesta_si_aplica($pregunta_raw);
        if ($resp_flujo !== null && $resp_flujo !== '') {
            ia_responder_json_chat($usuario_id, $pregunta_raw, $resp_flujo);
        }
    }

    if (strlen($pregunta) < 3) {
        if (ia_pregunta_es_conversacional($pregunta_raw)) {
            ia_responder_json_chat($usuario_id, $pregunta_raw, ia_respuesta_conversacional($pregunta_raw));
        }
        echo json_encode(array('ok' => false, 'error' => 'Pregunta demasiado corta.'));
        exit;
    }

    // Saludos y mensajes sociales (no SQL)
    if (ia_pregunta_es_conversacional($pregunta_raw)) {
        ia_responder_json_chat($usuario_id, $pregunta_raw, ia_respuesta_conversacional($pregunta_raw));
    }

    // Precio del oro: tabla precios_oro (no web)
    if (ia_pregunta_es_precio_oro($pregunta)) {
        $oro = ia_respuesta_precio_oro_desde_bd($conexion, $pregunta);
        if ($oro === false) {
            echo json_encode(array('ok' => false, 'error' => 'No hay registros de precio del oro en la base de datos.'));
            exit;
        }
        ia_historial_apend_turno($usuario_id, $pregunta, $oro['raw']);
        echo json_encode(array(
            'ok'                 => true,
            'mostrar_adjuntos'   => false,
            'texto'              => $oro['texto'],
            'filas'              => array(),
            'sql'                => '',
            'sql_crudo'          => '',
            'total'              => 0,
            'cabeceras_tabla'    => array(),
        ));
        exit;
    }

    // Utilidad / beneficio / rentabilidad: multi-consulta en orden fijo
    if (ia_pregunta_es_utilidad_negocio($pregunta)) {
        $util = ia_respuesta_utilidad_negocio($conexion, $pregunta);
        if ($util === false) {
            echo json_encode(array('ok' => false, 'error' => 'No se pudo calcular la utilidad.'));
            exit;
        }
        ia_historial_apend_turno($usuario_id, $pregunta, $util['raw']);
        echo json_encode(array(
            'ok'                 => true,
            'mostrar_adjuntos'   => false,
            'texto'              => $util['texto'],
            'filas'              => array(),
            'sql'                => '',
            'sql_crudo'          => '',
            'total'              => 0,
            'cabeceras_tabla'    => array(),
        ));
        exit;
    }

    // Alta con documento (crear cliente / lote): sin SQL; solo indicaciones y botón (+)
    if (ia_pregunta_es_solo_flujo_adjunto_documentacion($pregunta)) {
        $respuesta_adjunto = "Para seguir con un cliente o un lote/empeño usando documentación (DNI, fotos, etc.), pulsa el botón (+) junto al campo de texto.\n\n"
            . "Puedes elegir «Subir foto» si ya tienes el archivo en este equipo, o «Hacer foto» para usar el móvil con el código QR.\n\n"
            . "Este asistente solo puede ejecutar consultas de lectura (SELECT) sobre datos que ya existen; no registra altas en la base de datos desde aquí.";
        ia_historial_apend_turno($usuario_id, $pregunta, $respuesta_adjunto);
        echo json_encode(array(
            'ok'                 => true,
            'mostrar_adjuntos'   => true,
            'texto'              => nl2br(htmlspecialchars($respuesta_adjunto)),
            'filas'              => array(),
            'sql'                => '',
            'sql_crudo'          => '',
            'total'              => 0,
            'cabeceras_tabla'    => array(),
        ));
        exit;
    }

    // Pregunta sobre capacidades / alcance (sin SQL ni listado de tablas ajenas al contexto)
    if (ia_pregunta_es_informativa_sin_datos($pregunta)) {
        echo json_encode(array(
            'ok'                 => true,
            'mostrar_adjuntos'   => false,
            'texto'              => ia_responder_pregunta_informativa($pregunta, $usuario_id),
            'filas'              => array(),
            'sql'                => '',
            'sql_crudo'          => '',
            'total'              => 0,
            'cabeceras_tabla'    => array(),
        ));
        exit;
    }

    $mostrar_adjuntos = ia_mensaje_sugiere_adjuntos_creacion($pregunta);

    // 1. Generar SQL con Claude (con historial)
    $sql = ia_generar_sql($pregunta, $usuario_id);
    if (!$sql) {
        $err_ia = ia_claude_ultimo_error();
        $texto_fallback = ia_respuesta_fallback_sin_consulta($pregunta_raw);
        if ($err_ia !== '') {
            $texto_fallback = 'No he podido conectar con el asistente de IA: ' . $err_ia;
        }
        ia_responder_json_chat($usuario_id, $pregunta_raw, $texto_fallback);
    }

    // 2. Seguridad: solo SELECT
    if (!ia_respuesta_parece_sql_select($sql)) {
        echo json_encode(array('ok' => false, 'error' => 'Solo se permiten consultas de lectura.'));
        exit;
    }

    $tablas_no_permitidas = ia_sql_tablas_no_permitidas($sql);
    if (!empty($tablas_no_permitidas)) {
        echo json_encode(array(
            'ok'    => false,
            'error' => 'Consulta no permitida: solo puedo usar las tablas definidas en el asistente ('
                . implode(', ', ia_tablas_permitidas_chat())
                . '). Tabla(s) rechazada(s): '
                . implode(', ', $tablas_no_permitidas),
        ));
        exit;
    }

    // 3. Ejecutar SQL
    $filas = ia_ejecutar_sql($conexion, $sql, IA_MAX_FILAS);
    if ($filas === false) {
        echo json_encode(array('ok' => false, 'error' => 'Error SQL: ' . mysqli_error($conexion)));
        exit;
    }

    // 4. Interpretar resultados con Claude
    $mostrar_tabla = ia_debe_mostrar_tabla_chat($pregunta, $sql, $filas);
    $texto = ia_interpretar_resultados($pregunta, $sql, $filas, $mostrar_tabla);

    $cabeceras_tabla = array();
    if ($mostrar_tabla && count($filas) > 0 && isset($filas[0]) && is_array($filas[0])) {
        foreach (array_keys($filas[0]) as $col) {
            $cabeceras_tabla[$col] = ia_etiqueta_columna_listado($col);
        }
    }

    echo json_encode(array(
        'ok'                 => true,
        'mostrar_adjuntos'   => $mostrar_adjuntos,
        'texto'              => $texto,
        'filas'              => $mostrar_tabla ? $filas : array(),
        'sql'                => '',
        'sql_crudo'          => $mostrar_tabla ? $sql : '',
        'total'              => count($filas),
        'cabeceras_tabla'    => $cabeceras_tabla,
    ));
    exit;
}

// ─── ACCIÓN: EXPORT EXCEL (.xlsx OOXML) ───────────────────────────────────────

/**
 * Letra de columna Excel (0 = A, 25 = Z, 26 = AA).
 */
function ia_xlsx_col_letter_from_index($index) {
    $dividend = (int) $index + 1;
    $columnName = '';
    while ($dividend > 0) {
        $modulo = ($dividend - 1) % 26;
        $columnName = chr(65 + $modulo) . $columnName;
        $dividend = (int) (($dividend - $modulo) / 26);
    }
    return $columnName;
}

/**
 * Texto apto para XML (celda); quita caracteres de control no válidos en XML 1.0.
 */
function ia_xlsx_cell_text($v) {
    if ($v === null) {
        $s = '';
    } elseif (is_bool($v)) {
        $s = $v ? '1' : '0';
    } else {
        $s = (string) $v;
    }
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
    if (function_exists('mb_strlen') && mb_strlen($s, 'UTF-8') > 32000) {
        $s = mb_substr($s, 0, 32000, 'UTF-8');
    } elseif (strlen($s) > 32000) {
        $s = substr($s, 0, 32000);
    }
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/**
 * Genera el XML de la hoja (celdas como texto en línea, compatible con Excel).
 */
function ia_xlsx_build_sheet_xml($filas) {
    $cols = array_keys($filas[0]);
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetData>';
    $rowNum = 1;
    $xml .= '<row r="' . $rowNum . '">';
    $c = 0;
    foreach ($cols as $colName) {
        $ref = ia_xlsx_col_letter_from_index($c) . $rowNum;
        $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . ia_xlsx_cell_text(ia_etiqueta_columna_listado($colName)) . '</t></is></c>';
        $c++;
    }
    $xml .= '</row>';
    $rowNum++;
    foreach ($filas as $fila) {
        $xml .= '<row r="' . $rowNum . '">';
        $c = 0;
        foreach ($cols as $col) {
            $ref = ia_xlsx_col_letter_from_index($c) . $rowNum;
            $v = isset($fila[$col]) ? $fila[$col] : '';
            $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . ia_xlsx_cell_text($v) . '</t></is></c>';
            $c++;
        }
        $xml .= '</row>';
        $rowNum++;
    }
    $xml .= '</sheetData></worksheet>';
    return $xml;
}

/**
 * Escribe un .xlsx mínimo (Zip OOXML) en la ruta indicada.
 */
function ia_xlsx_write_file($filas, $path) {
    if (!class_exists('ZipArchive')) {
        return false;
    }
    $sheet = ia_xlsx_build_sheet_xml($filas);
    $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
        . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
        . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
        . '</styleSheet>';
    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="Datos" sheetId="1" r:id="rId1"/></sheets></workbook>';
    $relsRoot = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>';
    $relsWb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>';
    $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '</Types>';

    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }
    $zip->addFromString('[Content_Types].xml', $ct);
    $zip->addFromString('_rels/.rels', $relsRoot);
    $zip->addFromString('xl/workbook.xml', $workbook);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $relsWb);
    $zip->addFromString('xl/styles.xml', $styles);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
    $zip->close();
    return true;
}

function ia_export_excel($conexion) {
    if (empty($_REQUEST['sql'])) {
        die('SQL no proporcionado.');
    }

    $sql    = trim($_REQUEST['sql']);
    $titulo = isset($_REQUEST['titulo']) ? $_REQUEST['titulo'] : 'informe';

    if (!ia_respuesta_parece_sql_select($sql)) {
        die('Solo se permiten consultas SELECT.');
    }
    $tablas_no = ia_sql_tablas_no_permitidas($sql);
    if (!empty($tablas_no)) {
        die('Consulta no permitida.');
    }

    $filas = ia_ejecutar_sql($conexion, $sql, IA_MAX_FILAS);
    if ($filas === false || count($filas) === 0) {
        die('Sin datos para exportar.');
    }

    $fecha    = date('Y-m-d');
    $filename = ia_slugify($titulo) . '_' . $fecha . '.xlsx';

    $tmp = tempnam(sys_get_temp_dir(), 'iaxlsx');
    if ($tmp === false) {
        die('No se pudo crear el archivo temporal.');
    }
    $ok = ia_xlsx_write_file($filas, $tmp);
    if (!$ok) {
        @unlink($tmp);
        die('No se pudo generar Excel (comprueba que PHP tenga ZipArchive habilitado).');
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    exit;
}

// ─── ACCIÓN: EXPORT PDF (HTML imprimible) ────────────────────────────────────

function ia_export_pdf($conexion) {
    if (empty($_REQUEST['sql'])) {
        die('SQL no proporcionado.');
    }

    $sql    = trim($_REQUEST['sql']);
    $titulo = isset($_REQUEST['titulo']) ? htmlspecialchars($_REQUEST['titulo']) : 'Informe';

    if (!ia_respuesta_parece_sql_select($sql)) {
        die('Solo se permiten consultas SELECT.');
    }
    $tablas_no = ia_sql_tablas_no_permitidas($sql);
    if (!empty($tablas_no)) {
        die('Consulta no permitida.');
    }

    $filas = ia_ejecutar_sql($conexion, $sql, IA_MAX_FILAS);
    if ($filas === false || count($filas) === 0) {
        die('Sin datos para exportar.');
    }

    $fecha = date('d/m/Y H:i');
    $cols  = array_keys($filas[0]);

    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?php echo $titulo; ?></title>
<style>
  body  { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 20px; }
  h2    { font-size: 15px; margin-bottom: 4px; }
  small { color: #888; }
  table { width: 100%; border-collapse: collapse; margin-top: 14px; }
  th    { background: #7367f0; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; }
  td    { padding: 5px 8px; border-bottom: 1px solid #e0e0e0; }
  tr:nth-child(even) td { background: #f9f9f9; }
  @media print { .no-print { display: none; } body { margin: 0; } }
</style>
</head>
<body>
  <div class="no-print" style="margin-bottom:14px;">
    <button onclick="window.print()" style="background:#7367f0;color:#fff;border:none;padding:8px 18px;border-radius:6px;cursor:pointer;font-size:12px;">
      🖨 Imprimir / Guardar PDF
    </button>
    <button onclick="window.close()" style="margin-left:8px;background:#eee;border:none;padding:8px 18px;border-radius:6px;cursor:pointer;font-size:12px;">
      Cerrar
    </button>
  </div>
  <h2><?php echo $titulo; ?></h2>
  <small>Generado el <?php echo $fecha; ?> &mdash; <?php echo count($filas); ?> registros</small>
  <table>
    <thead>
      <tr><?php foreach ($cols as $col): ?><th><?php echo htmlspecialchars(ia_etiqueta_columna_listado($col)); ?></th><?php endforeach; ?></tr>
    </thead>
    <tbody>
      <?php foreach ($filas as $fila): ?>
        <tr><?php foreach ($fila as $valor): ?><td><?php echo htmlspecialchars($valor !== null ? $valor : '—'); ?></td><?php endforeach; ?></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <script>window.onload = function() { window.print(); };</script>
</body>
</html>
    <?php
    exit;
}

// ─── FUNCIONES COMPARTIDAS ───────────────────────────────────────────────────

function ia_generar_sql($pregunta, $usuario_id) {
    // Leer historial del fichero JSON
    $historial = ia_historial_leer($usuario_id);

    $lista_tablas = implode(', ', ia_tablas_permitidas_chat());
    $contexto = ia_agent_contexto_bd_efectivo(IA_CONTEXTO_BD);
    $reglas_sql = ia_agent_prompt_o_fallback(
        'reglas_sql',
        'generar_select',
        "Tu tarea: generar SOLO consultas SELECT para MariaDB 10.1.\n"
        . 'Devuelve únicamente el SQL (un SELECT). Sin markdown, sin explicaciones, sin punto y coma final.'
    );
    $system = $contexto
        . "\n\n" . $reglas_sql
        . "\nTablas permitidas: " . $lista_tablas . '.';

    $messages = array();
    foreach ($historial as $msg) {
        if (!isset($msg['role'], $msg['content'])) {
            continue;
        }
        $role = (string) $msg['role'];
        if ($role !== 'user' && $role !== 'assistant') {
            continue;
        }
        $messages[] = array(
            'role'    => $role,
            'content' => (string) $msg['content'],
        );
    }

    $messages[] = array(
        'role'    => 'user',
        'content' => 'Pregunta: ' . $pregunta
            . "\nDevuelve SOLO el SQL (un SELECT). Tablas permitidas: " . $lista_tablas
            . '. No uses ni menciones otras tablas. Sin markdown ni explicaciones.',
    );

    $respuesta = ia_llamar_claude($messages, 600, 90, $system);
    if (!$respuesta) {
        return false;
    }

    $sql = ia_extraer_sql_de_respuesta($respuesta);
    if ($sql === '') {
        return false;
    }

    if (!empty(ia_sql_tablas_no_permitidas($sql))) {
        return false;
    }

    // Guardar pregunta y SQL en el historial
    $historial[] = array('role' => 'user', 'content' => $pregunta);
    $historial[] = array('role' => 'assistant', 'content' => $sql);

    // Limitar a últimas 10 conversaciones (20 mensajes)
    if (count($historial) > 20) {
        $historial = array_slice($historial, -20);
    }

    ia_historial_guardar($usuario_id, $historial);

    return $sql;
}

function ia_ejecutar_sql($conexion, $sql, $limite) {
    $sql_upper = strtoupper($sql);

    $es_agregado = (strpos($sql_upper, 'COUNT(') !== false
                 || strpos($sql_upper, 'SUM(')   !== false
                 || strpos($sql_upper, 'AVG(')   !== false
                 || strpos($sql_upper, 'MAX(')   !== false
                 || strpos($sql_upper, 'MIN(')   !== false);

    if (!$es_agregado && strpos($sql_upper, 'LIMIT') === false) {
        $sql .= ' LIMIT ' . (int)$limite;
    }

    mysqli_set_charset($conexion, 'utf8');
    $result = mysqli_query($conexion, $sql);
    if ($result === false) return false;

    $filas = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $filas[] = $row;
    }
    mysqli_free_result($result);

    return $filas;
}

/**
 * Detecta fila separadora tipo markdown (| --- | --- |).
 */
function ia_es_fila_separadora_tabla_md($linea) {
    $t = trim($linea);
    if ($t === '' || !isset($t[0]) || $t[0] !== '|') {
        return false;
    }
    return (bool) preg_match('/^\|(?:\s*[-:]+\s*\|)+\s*$/', $t);
}

/**
 * Elimina bloques de tabla markdown (cabecera + |---| + filas) para no duplicar la tabla HTML.
 */
function ia_strip_markdown_tables($texto) {
    if ($texto === null || $texto === '') {
        return $texto;
    }
    $lineas = preg_split('/\R/', $texto);
    $salida = array();
    $n = count($lineas);
    $i = 0;
    while ($i < $n) {
        $actual = isset($lineas[$i]) ? $lineas[$i] : '';
        $sig     = ($i + 1 < $n) ? $lineas[$i + 1] : '';
        $ta      = trim($actual);
        if ($sig !== '' && $ta !== '' && isset($ta[0]) && $ta[0] === '|'
            && !ia_es_fila_separadora_tabla_md($actual)
            && ia_es_fila_separadora_tabla_md($sig)) {
            $i += 2;
            while ($i < $n) {
                $t = trim($lineas[$i]);
                if ($t === '' || !isset($t[0]) || $t[0] !== '|') {
                    break;
                }
                if (ia_es_fila_separadora_tabla_md($lineas[$i])) {
                    break;
                }
                $i++;
            }
            continue;
        }
        $salida[] = $actual;
        $i++;
    }
    return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $salida)));
}

function ia_interpretar_resultados($pregunta, $sql, $filas, $mostrar_tabla = true) {
    $num = count($filas);

    if ($num === 0) {
        $datos_str = "La consulta no devolvió ningún resultado.";
    } elseif ($num === 1 && count($filas[0]) === 1) {
        $val = reset($filas[0]);
        $datos_str = "Resultado: " . $val;
    } else {
        $muestra   = array_slice($filas, 0, 50);
        $datos_str = "Resultados (" . $num . " filas):\n" . json_encode($muestra, JSON_UNESCAPED_UNICODE);
    }

    $instruccion_tabla = $mostrar_tabla
        ? "- Si son varios registros, haz un resumen breve e indica que los datos se muestran en la tabla.\n"
        : "- Responde SOLO con el total, conteo o importe en texto (1-3 frases). NO digas que hay tabla ni listado.\n";

    $instrucciones = ia_agent_prompt_o_fallback(
        'interpretar',
        'instrucciones',
        "Eres un asistente de análisis de datos para una joyería española.\n"
        . "Responde en español de forma clara y concisa:\n"
        . "- Si es un total o conteo, dilo con el número formateado.\n"
        . "- Usa formatos legibles: euros con €, fechas en DD/MM/YYYY.\n"
        . "- Si no hay resultados, explica brevemente por qué podría ser.\n"
        . "- PROHIBIDO: escribir SQL, SELECT, FROM, JOIN, nombres de tablas, consultas técnicas o bloques de código.\n"
        . "- No listes tablas ni digas que puedes consultar «otras tablas» de la BD.\n"
        . "- No escribas tablas en formato markdown (líneas con |).\n"
        . "- Máximo 3-4 líneas."
    );

    $prompt = $instrucciones . "\n\n"
        . 'El usuario ha preguntado: "' . $pregunta . "\"\n"
        . "Datos obtenidos (uso interno, NO lo cites ni lo copies):\n"
        . $datos_str . "\n\n"
        . $instruccion_tabla;

    $messages  = array(array('role' => 'user', 'content' => $prompt));
    $respuesta = ia_llamar_claude($messages, 400);

    if (!$respuesta) {
        $fallback = ia_formatear_respuesta_agregados($filas);
        return $fallback !== '' ? $fallback : ($mostrar_tabla ? 'Consulta ejecutada. Revisa los datos en la tabla.' : 'No pude obtener el resultado.');
    }

    $respuesta = ia_strip_markdown_tables(trim($respuesta));
    $respuesta = ia_texto_ocultar_sql($respuesta);
    if ($respuesta === '') {
        $fallback = ia_formatear_respuesta_agregados($filas);
        return $fallback !== '' ? $fallback : ($mostrar_tabla ? 'Consulta ejecutada. Revisa los datos en la tabla.' : 'No pude obtener el resultado.');
    }

    return nl2br(htmlspecialchars($respuesta));
}

/**
 * Quita un «gracias» inicial si va seguido de una pregunta real.
 */
function ia_pregunta_normalizar_entrada($pregunta) {
    $p = trim((string) $pregunta);
    if ($p === '') {
        return '';
    }
    $p = preg_replace('/^(muchas\s+|mil\s+)?gracias\s*[,.\-–—:;]?\s+/iu', '', $p);
    $p = preg_replace('/^(thanks|thank\s+you)\s*[,.\-:;]?\s+/iu', '', $p);
    return trim($p);
}

/**
 * El usuario pide totales/resumen sin tabla ni listado.
 */
function ia_pregunta_pide_sin_tabla($pregunta) {
    $p = function_exists('mb_strtolower') ? mb_strtolower(trim($pregunta), 'UTF-8') : strtolower(trim($pregunta));
    if ($p === '') {
        return false;
    }
    return (bool) preg_match(
        '/\b(solo|solamente|únicamente|unicamente|nada\s+m[aá]s|sin\s+tabla|sin\s+listado|sin\s+listados|'
        . 'no\s+me\s+muestres|no\s+muestres|no\s+quiero\s+ver|no\s+listes|solo\s+quiero\s+saber|'
        . 'solo\s+el\s+total|solo\s+dime|únicamente\s+el\s+total|solamente\s+el\s+total)\b/u',
        $p
    );
}

function ia_sql_es_solo_agregados($sql) {
    return (bool) preg_match('/\b(COUNT|SUM|AVG|MIN|MAX)\s*\(/i', (string) $sql);
}

function ia_debe_mostrar_tabla_chat($pregunta, $sql, $filas) {
    if (ia_pregunta_pide_sin_tabla($pregunta)) {
        return false;
    }
    $num = count($filas);
    if ($num === 0) {
        return false;
    }
    if ($num === 1 && ia_sql_es_solo_agregados($sql)) {
        return false;
    }
    if ($num > 1) {
        return true;
    }
    return count($filas[0]) > 1 && !ia_sql_es_solo_agregados($sql);
}

/**
 * Elimina SQL u otros restos técnicos del texto visible al usuario.
 */
function ia_texto_ocultar_sql($texto) {
    $t = (string) $texto;
    if ($t === '') {
        return '';
    }
    $t = preg_replace('/```(?:sql|mysql)?\s*[\s\S]*?```/iu', '', $t);
    $t = preg_replace('/\bSELECT\b[\s\S]*?(?=\n\n|$)/iu', '', $t);
    $t = preg_replace('/\b(FROM|JOIN|WHERE|GROUP\s+BY|ORDER\s+BY|LIMIT)\b[\s\S]*?(?=\n\n|$)/iu', '', $t);
    $t = preg_replace('/\b(consulta|query)\s+(sql|mysql)\b.*?(?=\n|$)/iu', '', $t);
    return trim(preg_replace("/\n{3,}/", "\n\n", $t));
}

/**
 * Respuesta en texto plano para una fila con totales (COUNT/SUM).
 */
function ia_formatear_respuesta_agregados($filas) {
    if (count($filas) !== 1 || !isset($filas[0]) || !is_array($filas[0])) {
        return '';
    }
    $partes = array();
    foreach ($filas[0] as $clave => $valor) {
        if ($valor === null || $valor === '') {
            continue;
        }
        $etiqueta = ia_etiqueta_columna_listado($clave);
        $txt_val  = (string) $valor;
        if (preg_match('/precio|importe|valor|total|sum/i', (string) $clave) && is_numeric($valor)) {
            $txt_val = number_format((float) $valor, 2, ',', '.') . ' €';
        } elseif (preg_match('/^[\d\.]+$/', $txt_val) && strpos($txt_val, '.') !== false && is_numeric($valor)) {
            $txt_val = number_format((float) $valor, 2, ',', '.');
        }
        $partes[] = $etiqueta . ': ' . $txt_val;
    }
    if (empty($partes)) {
        return '';
    }
    return nl2br(htmlspecialchars(implode('. ', $partes) . '.'));
}

/**
 * Mensaje que es solo agradecimiento (respuesta fija, sin SQL).
 */
function ia_pregunta_es_solo_gracias($pregunta) {
    $raw = preg_replace('/\s+/u', ' ', trim((string) $pregunta));
    $raw = preg_replace('/[!¡\.…,;:]+$/u', '', $raw);
    if ($raw === '') {
        return false;
    }

    $sin_prefijo = trim(ia_pregunta_normalizar_entrada($raw));
    $raw_cmp = function_exists('mb_strtolower') ? mb_strtolower($raw, 'UTF-8') : strtolower($raw);
    $sin_cmp = function_exists('mb_strtolower') ? mb_strtolower($sin_prefijo, 'UTF-8') : strtolower($sin_prefijo);

    // Tras quitar "gracias, …" queda otra pregunta (p. ej. "Gracias, dime el total")
    if ($sin_cmp !== '' && $sin_cmp !== $raw_cmp) {
        return false;
    }

    return (bool) preg_match(
        '/^('
        . '(ok\s+|vale\s+|muy\s+)?(muchas\s+|mil\s+)?gracias'
        . '|thank\s+you'
        . '|thanks'
        . '|ty'
        . '|te\s+agradezco'
        . '|fant[aá]stico'
        . '|perfecto'
        . '|excelente'
        . '|genial'
        . ')$/u',
        $raw_cmp
    );
}

/**
 * Mensajes donde tiene sentido mostrar adjuntar/subir foto (crear cliente, lote/empeño, etc.).
 * Debe alinearse con modal_ia_chat.php: iaChatMensajePideAdjuntosCreacion (amplio) e iaChatEsSoloFlujoAdjuntoDocumentacion (sin SQL).
 */
function ia_mensaje_sugiere_adjuntos_creacion($pregunta) {
    $p = trim((string) $pregunta);
    if ($p === '') {
        return false;
    }
    $p = function_exists('mb_strtolower') ? mb_strtolower($p, 'UTF-8') : strtolower($p);
    $patterns = array(
        '/(crear|registrar|alta de|alta |dar de alta)\s*.{0,55}(cliente|clientes)/u',
        '/(nuevo|nueva)\s*.{0,22}(cliente|clientes)/u',
        '/(cliente|clientes)\s*.{0,28}(nuevo|nueva|crear|alta)\b/u',
        '/(crear|registrar|alta de|alta |dar de alta)\s*.{0,55}(lote|lotes|empeño|empeno|empeños)/u',
        '/(nuevo|nueva)\s*.{0,22}(lote|lotes|empeño|empeno)/u',
        '/(lote|lotes|empeño|empeno)\s*.{0,28}(nuevo|nueva|crear|alta)\b/u',
    );
    foreach ($patterns as $re) {
        if (preg_match($re, $p)) {
            return true;
        }
    }
    return false;
}

function ia_pregunta_es_precio_oro($pregunta) {
    $p = function_exists('mb_strtolower') ? mb_strtolower(trim($pregunta), 'UTF-8') : strtolower(trim($pregunta));
    if (strpos($p, 'oro') === false) {
        return false;
    }
    if (strpos($p, 'precio') !== false || strpos($p, 'cotiz') !== false) {
        return true;
    }
    if (preg_match('/\b(cuánto|cuanto)\b.*\b(vale|esta|está|esta el)\b.*\boro\b/u', $p)) {
        return true;
    }
    if (preg_match('/\b(valor)\b.*\boro\b/u', $p)) {
        return true;
    }
    return false;
}

/**
 * Descarga el HTML público de la página de precios del oro.
 */
function ia_fetch_precio_oro_html() {
    if (!function_exists('curl_init')) {
        return false;
    }
    $ch = curl_init(IA_PRECIO_ORO_URL);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_HTTPHEADER     => array(
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: es-ES,es;q=0.9',
        ),
    ));
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $code < 200 || $code >= 400) {
        return false;
    }
    return $body;
}

/**
 * Resume precios del oro a partir del HTML de la joyería (Claude).
 * @return array{texto: string, raw: string}|false
 */
function ia_respuesta_precio_oro_desde_web($pregunta) {
    $html = ia_fetch_precio_oro_html();
    if ($html === false || $html === '') {
        return false;
    }
    $max = 120000;
    if (strlen($html) > $max) {
        $html = substr($html, 0, $max) . "\n<!-- HTML truncado -->";
    }
    $prompt = "Eres el asistente de una joyería en España.\n\n"
        . "Pregunta del usuario:\n\"" . $pregunta . "\"\n\n"
        . "A continuación tienes el HTML de la página: " . IA_PRECIO_ORO_URL . "\n"
        . "Extrae y resume solo información relevante sobre precios o cotizaciones del oro que figuren en el HTML (euros, gramos, quilates, etc.).\n"
        . "Si no hay datos de precio claros en el HTML, dilo sin inventar cifras.\n"
        . "Responde en español, texto breve y claro, sin tablas markdown.\n\n"
        . "HTML:\n" . $html;

    $messages = array(array('role' => 'user', 'content' => $prompt));
    $respuesta = ia_llamar_claude($messages, 1500);
    if (!$respuesta) {
        return false;
    }
    $respuesta = ia_strip_markdown_tables(trim($respuesta));
    if ($respuesta === '') {
        return false;
    }
    return array(
        'texto' => nl2br(htmlspecialchars($respuesta)),
        'raw'   => $respuesta,
    );
}

/**
 * POST accion=analizar_documento_chat: imagen_data_url (data URL) o imagen_url (/photos/archivo.jpg del mismo servidor).
 */
function ia_analizar_documento_chat_handler() {
    header('Content-Type: application/json; charset=utf-8');
    $usuario_id = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
    if ($usuario_id <= 0) {
        echo json_encode(array('ok' => false, 'error' => 'Sesión requerida.'));
        return;
    }

    $dataUrl = isset($_POST['imagen_data_url']) ? trim((string) $_POST['imagen_data_url']) : '';
    $urlAj   = isset($_POST['imagen_url']) ? trim((string) $_POST['imagen_url']) : '';

    $media_type = '';
    $b64        = '';

    if ($dataUrl !== '') {
        if (!preg_match('#^data:(image/(?:jpeg|jpg|png|gif|webp));base64,([\s\S]+)$#i', $dataUrl, $m)) {
            echo json_encode(array('ok' => false, 'error' => 'Formato de imagen no reconocido. Usa JPG, PNG, GIF o WebP.'));
            return;
        }
        $media_type = strtolower($m[1]);
        if ($media_type === 'image/jpg') {
            $media_type = 'image/jpeg';
        }
        $b64 = preg_replace('/\s+/', '', $m[2]);
    } elseif ($urlAj !== '') {
        $parsed = parse_url($urlAj);
        $path   = isset($parsed['path']) ? $parsed['path'] : '';
        if (!preg_match('#^/photos/([^/]+)$#', (string) $path, $pm)) {
            echo json_encode(array('ok' => false, 'error' => 'URL de imagen no válida.'));
            return;
        }
        $nom = rawurldecode($pm[1]);
        $bin = ia_chat_leer_binario_foto_servidor($nom);
        if ($bin === false || $bin === '') {
            echo json_encode(array('ok' => false, 'error' => 'No se encontró la imagen en el servidor.'));
            return;
        }
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $media_type = $finfo ? finfo_buffer($finfo, $bin) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
        } else {
            $media_type = 'image/jpeg';
        }
        if (!in_array($media_type, array('image/jpeg', 'image/png', 'image/gif', 'image/webp'), true)) {
            echo json_encode(array('ok' => false, 'error' => 'Tipo de archivo no admitido para análisis.'));
            return;
        }
        $b64 = base64_encode($bin);
    } else {
        echo json_encode(array('ok' => false, 'error' => 'Falta la imagen.'));
        return;
    }

    $rawBin = base64_decode($b64, true);
    if ($rawBin === false) {
        echo json_encode(array('ok' => false, 'error' => 'Datos de imagen corruptos.'));
        return;
    }
    $len = strlen($rawBin);
    if ($len < 256) {
        echo json_encode(array('ok' => false, 'error' => 'La imagen es demasiado pequeña o está vacía.'));
        return;
    }
    if ($len > 5 * 1024 * 1024) {
        echo json_encode(array('ok' => false, 'error' => 'Imagen demasiado grande (máx. 5 MB).'));
        return;
    }

    $resultado = ia_analizar_documento_identidad_vision($media_type, $b64);
    if ($resultado === false) {
        echo json_encode(array('ok' => false, 'error' => 'No se pudo analizar el documento. Inténtalo de nuevo en unos segundos.'));
        return;
    }

    $tipo = isset($resultado['tipo_documento']) ? (string) $resultado['tipo_documento'] : 'ninguno';
    $permitidos = array('dni_espanol', 'nie_espanol', 'pasaporte', 'ninguno');
    if (!in_array($tipo, $permitidos, true)) {
        $tipo = 'ninguno';
    }
    $datos = (isset($resultado['datos']) && is_array($resultado['datos'])) ? $resultado['datos'] : array();
    $motivo = isset($resultado['motivo_rechazo']) ? trim((string) $resultado['motivo_rechazo']) : '';

    $html  = ia_doc_construir_html_respuesta_chat($tipo, $datos, $motivo);
    $plano = trim(strip_tags(str_replace(array('<br>', '<br/>', '<br />'), "\n", $html)));

    ia_historial_apend_turno($usuario_id, '[Imagen de documento — análisis automático]', $plano !== '' ? $plano : $tipo);

    echo json_encode(array(
        'ok'               => true,
        'tipo_documento' => $tipo,
        'datos_documento' => $datos,
        'texto'          => $html,
        'mostrar_adjuntos' => true,
    ));
}

/**
 * Lee bytes de una foto ya guardada en /photos/ (solo nombre de archivo seguro).
 *
 * @return string|false
 */
function ia_chat_leer_binario_foto_servidor($nombre) {
    $nombre = basename((string) $nombre);
    if (!preg_match('/^[A-Za-z0-9._-]+\.[A-Za-z0-9]+$/', $nombre)) {
        return false;
    }
    $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true)) {
        return false;
    }
    $ruta = dirname(__DIR__, 2) . '/photos/' . $nombre;
    if (!is_file($ruta) || !is_readable($ruta)) {
        return false;
    }
    return file_get_contents($ruta);
}

/**
 * @return array<string,mixed>|false
 */
function ia_analizar_documento_identidad_vision($media_type, $b64) {
    $instruccion = "Eres un experto en documentos de identidad. Analiza la imagen (puede estar rotada o parcialmente visible).\n\n"
        . "Clasifica el documento en EXACTAMENTE una de estas categorías:\n"
        . "- dni_espanol: DNI nacional de España (formato actual o reciente).\n"
        . "- nie_espanol: tarjeta de identidad de extranjero en España (NIE), letra inicial X, Y o Z.\n"
        . "- pasaporte: pasaporte de cualquier país (incluido español si es libreta pasaporte).\n"
        . "- ninguno: no es ninguno de los anteriores (por ejemplo carnet de conducir solo, factura, captura genérica, imagen ilegible, otro carnet).\n\n"
        . "Responde ÚNICAMENTE con un objeto JSON válido (sin markdown, sin texto fuera del JSON) con esta estructura:\n"
        . "{\n"
        . "  \"tipo_documento\": \"dni_espanol|nie_espanol|pasaporte|ninguno\",\n"
        . "  \"confianza\": \"alta|media|baja\",\n"
        . "  \"datos\": {\n"
        . "    \"numero_documento\": \"\",\n"
        . "    \"nombre\": \"\",\n"
        . "    \"apellidos\": \"\",\n"
        . "    \"fecha_nacimiento\": \"\",\n"
        . "    \"sexo\": \"\",\n"
        . "    \"nacionalidad\": \"\",\n"
        . "    \"fecha_caducidad\": \"\",\n"
        . "    \"lugar_nacimiento\": \"\",\n"
        . "    \"observaciones\": \"\"\n"
        . "  },\n"
        . "  \"motivo_rechazo\": \"\"\n"
        . "}\n\n"
        . "Si tipo_documento es ninguno, deja los campos de datos vacíos o con cadena vacía y resume en motivo_rechazo (máximo 200 caracteres) por qué no sirve.\n"
        . "Si es dni_espanol, nie_espanol o pasaporte, rellena todos los datos que puedas leer con fidelidad; fechas en formato DD/MM/AAAA cuando sea posible; si no lees un campo, déjalo vacío.\n"
        . "Para NIE incluye letra inicial y número completo con letra de control si se ve.";

    $messages = array(
        array(
            'role'    => 'user',
            'content' => array(
                array(
                    'type'   => 'image',
                    'source' => array(
                        'type'       => 'base64',
                        'media_type' => $media_type,
                        'data'       => $b64,
                    ),
                ),
                array(
                    'type' => 'text',
                    'text' => $instruccion,
                ),
            ),
        ),
    );

    $raw = ia_llamar_claude($messages, 2000, 120);
    if (!$raw) {
        return false;
    }

    $obj = ia_modelo_extraer_json_documento($raw);
    if ($obj === null) {
        return array(
            'tipo_documento' => 'ninguno',
            'datos'          => array(),
            'motivo_rechazo' => 'No se pudo interpretar el resultado del análisis.',
        );
    }

    return $obj;
}

/**
 * @return array<string,mixed>|null
 */
function ia_modelo_extraer_json_documento($texto) {
    $texto = trim((string) $texto);
    if ($texto === '') {
        return null;
    }
    if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/iu', $texto, $m)) {
        $texto = trim($m[1]);
    }
    $d = json_decode($texto, true);
    if (is_array($d) && isset($d['tipo_documento'])) {
        return $d;
    }
    if (preg_match('/\{[\s\S]*"tipo_documento"[\s\S]*\}/u', $texto, $m2)) {
        $d = json_decode($m2[0], true);
        if (is_array($d) && isset($d['tipo_documento'])) {
            return $d;
        }
    }
    return null;
}

/**
 * HTML seguro para la burbuja del chat (solo plantillas + htmlspecialchars en valores).
 */
function ia_doc_construir_html_respuesta_chat($tipo, array $datos, $motivo_rechazo) {
    if ($tipo === 'ninguno') {
        $base = 'Esta imagen no corresponde a un documento apto para dar de alta un cliente. Solo se admiten: DNI español, NIE español o pasaporte (cualquier país). '
            . 'Sube o haz otra foto más clara del documento correcto.';
        if ($motivo_rechazo !== '') {
            $base .= ' ' . $motivo_rechazo;
        }
        return '<p class="mb-0">' . htmlspecialchars($base, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    $etiquetas_tipo = array(
        'dni_espanol' => 'DNI español',
        'nie_espanol' => 'NIE español',
        'pasaporte'   => 'Pasaporte',
    );
    $titulo = isset($etiquetas_tipo[$tipo]) ? $etiquetas_tipo[$tipo] : $tipo;

    $mapa_campos = array(
        'numero_documento' => 'Número del documento',
        'nombre'           => 'Nombre',
        'apellidos'        => 'Apellidos',
        'fecha_nacimiento' => 'Fecha de nacimiento',
        'sexo'             => 'Sexo',
        'nacionalidad'     => 'Nacionalidad',
        'fecha_caducidad'  => 'Fecha de caducidad',
        'lugar_nacimiento' => 'Lugar de nacimiento',
        'observaciones'    => 'Observaciones',
    );

    $html = '<p class="mb-2"><strong>Tipo de documento detectado:</strong> '
        . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</p>';
    $html .= '<dl class="row mb-0 small ia-doc-datos-extraidos">';
    $con_datos = false;
    foreach ($mapa_campos as $clave => $label) {
        $val = isset($datos[$clave]) ? trim((string) $datos[$clave]) : '';
        if ($val === '') {
            continue;
        }
        $con_datos = true;
        $html .= '<dt class="col-sm-4 text-muted">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</dt>';
        $html .= '<dd class="col-sm-8">' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '</dd>';
    }
    $html .= '</dl>';
    if (!$con_datos) {
        $html .= '<p class="small text-muted mb-2">No se pudieron leer datos automáticamente en la imagen. Prueba con otra foto más nítida y con buena luz.</p>';
    }
    $html .= '<p class="mb-0 mt-2"><strong>¿Son correctos estos datos del documento?</strong></p>';
    $html .= '<p class="mb-0 small text-muted">Si son correctos, responde «sí». Si no, indica qué hay que corregir.</p>';

    return $html;
}

/**
 * Añade un turno pregunta/respuesta al historial (mismo límite que ia_generar_sql).
 */
function ia_historial_apend_turno($usuario_id, $pregunta, $respuesta_asistente_plano) {
    $historial = ia_historial_leer($usuario_id);
    $historial[] = array('role' => 'user', 'content' => $pregunta);
    $historial[] = array('role' => 'assistant', 'content' => $respuesta_asistente_plano);
    if (count($historial) > 20) {
        $historial = array_slice($historial, -20);
    }
    ia_historial_guardar($usuario_id, $historial);
}

function ia_slugify($texto) {
    $texto = strtolower($texto);
    $texto = preg_replace('/[^a-z0-9]+/', '_', $texto);
    return trim($texto, '_');
}