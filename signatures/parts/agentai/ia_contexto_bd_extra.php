<?php
/**
 * Contexto adicional de tablas para el asistente IA (ajax_ia_chat.php).
 * Bloques A–E: lotes, envíos/metal, caja/gastos, ventas/auditorías, geo/logs.
 * Solo lectura SELECT; tablas globales consolidadas (no lotes_{id} ni articulos_{id}).
 */

function ia_tablas_permitidas_extra() {
    return array(
        // A
        'lotes_joyeria',
        'articulos_lotes',
        'historico_renovaciones_gobal',
        'adelantos_capital',
        'trazabilidad_lotes',
        'trazabilidad_articulos',
        'trazabilidad_articulos_venta',
        'cambios_estados_lotes',
        // B
        'envios',
        'informe_metal',
        'proformas',
        'proveedores',
        'precios_oro',
        'precios_proveedor',
        'precios_oro_proveedores',
        // C
        'movimientos_de_caja_global',
        'movimientos_tarjeta',
        'movimientos_transferencia',
        'movimientos_bizum',
        'gastos',
        'gastos_fijos',
        'rel_gastos_forma_pago',
        'autorizaciones_gastos',
        'formas_de_pago',
        'grupos_movimientos',
        // D
        'facturas',
        'facturas_rectificativas',
        'facturas_rel_articulos',
        'facturas_rel_renovaciones',
        'devoluciones',
        'ventas_plazos',
        'traspasos',
        'rel_articulos_traspaso',
        'reporte_ventas',
        'informe_diario',
        'auditorias_tiendas',
        'rel_art_auditoria',
        'rel_lote_auditoria',
        'autorizaciones_descuento_articulo_venta',
        'autorizaciones_devoluciones',
        'autorizaciones_porcentajes_ventas',
        'historico_precio_articulos_venta',
        'rel_articulos_estados',
        'empresas',
        // E
        'paises',
        'provincias',
        'poblacion',
        'nacionalidades',
        'usersActions',
        'usersConexions',
        'relItemsLevel',
    );
}

function ia_contexto_bd_extra_texto() {
    return "

══════════════════════════════════════════════════════════════
AMPLIACIÓN DE ESQUEMA (bloques A–E) — TPV compra/venta oro y casa de empeños
══════════════════════════════════════════════════════════════

ARQUITECTURA (OBLIGATORIO):
- La app escribe en tablas por sucursal (lotes_{id}, articulos_{id}, historico_renovaciones_{id}, movimientos_de_caja_{id}).
- Triggers consolidan a tablas GLOBALES. Para consultas del asistente USA SIEMPRE las globales listadas aquí.
- NUNCA uses nombres dinámicos tipo lotes_3, articulos_12, movimientos_de_caja_5, etc.
- Empeño = lotes_joyeria.compra_opcion = 'si'. Compra directa = compra_opcion = 'no'.
- tipo_de_lote es el METAL ('oro','plata','acero'), NO el tipo de operación.
- Clave de negocio de un lote en sucursal: (id_lote, sucursal). PK global: lotes_joyeria.identificador.
- JOIN artículos de lote: articulos_lotes.id_lote_articulos = lotes_joyeria.id_lote AND articulos_lotes.sucursal_articulo = lotes_joyeria.sucursal
- JOIN renovaciones (SOLO si necesitas datos del lote: cliente, metal, estado_lote, etc.): historico_renovaciones_gobal.lote = lotes_joyeria.id_lote AND historico_renovaciones_gobal.sucursal_id = lotes_joyeria.sucursal
- Totales/conteos/sumas de renovaciones (importe, IVA, año, estado): consulta SOLO historico_renovaciones_gobal. NO hagas JOIN a lotes_joyeria si no usas columnas del lote.
- JOIN adelantos: adelantos_capital.id_lote_adelanto = lotes_joyeria.id_lote AND adelantos_capital.sucursal_adelanto = lotes_joyeria.sucursal
- Nota: el nombre historico_renovaciones_gobal lleva typo (gobal). Úsalo así.

──────────────────────────────────────────────────────────────
A) LOTES + ARTÍCULOS DE LOTE + RENOVACIONES + ADELANTOS + TRAZABILIDAD
──────────────────────────────────────────────────────────────

TABLA: lotes_joyeria  (lotes de compra/empeño consolidados)
- identificador INT PK AUTO_INCREMENT — ID global del lote (usar en listados como Nº de lote global)
- id_lote INT — nº de lote dentro de la sucursal
- cliente INT → FK clientes.id_cliente
- tipo_de_lote VARCHAR — metal: oro / plata / acero
- peso DECIMAL(7,2) — peso neto
- peso_bruto DECIMAL(7,2)
- merma / merma_real DECIMAL
- precio_compra DECIMAL — capital entregado al cliente
- precio_recompra DECIMAL — lo que debe pagar el cliente para retirar (empeño); interés € ≈ precio_recompra - precio_compra
- intereses_lote INT — % intereses del lote
- fecha_compra DATE
- fecha_vencimiento DATE — vencimiento del empeño (relevante si compra_opcion='si')
- compra_opcion ENUM('no','si') — 'si'=empeño, 'no'=compra
- comprado_por VARCHAR/INT — usuario que compró (suele ser id usuario)
- metodo_pago VARCHAR DEFAULT 'efectivo'
- estado_lote VARCHAR — valores habituales: compra, enfecha, vencido, retirado, perdido, liberado, intervenido, fundido, enviado, anulado
- liberado VARCHAR(2) — 'si'/'no' (retención legal de compras; solo liberados se envían a central)
- fecha_liberacion / fecha_liberado DATE
- lote_perdible ENUM('true','false') — si un empeño vencido puede pasar a perdido por cron
- estado_envio ENUM('false','pendiente_enviar','auditando_central','enviado_central','recibido_central','devuelto_central','recibido_sucursal','envio_cancelado','lote_faltante','correguido','lote_auditado') — typo 'correguido' es real
- ubicacion_lote ENUM('sucursal','central','en_transporte')
- envio_numero INT → FK envios.id_envio (0 o vacío si no enviado)
- sucursal INT → FK sucursal.id_sucursal
- cantidad_articulos INT
- intervenido ENUM('false','true'), fecha_intervenido, intervenido_por, observaciones_intervenido
- lote_fundir ENUM('false','true'), fecha_fundido, comentarios_fundicion
- semana_numero / anyo_semana INT — semana de compra
- numero_semana_empenio_perdido / year_empenio_perdido — semana/año al perderse el empeño
- observaciones_lote TEXT
- anulado_por / fecha_anulado — si anulado
Flujo empeño: enfecha → (renovar mantiene enfecha) → vencido → retirar(retirado) o perdido → pendiente_enviar → envío central.
Flujo compra: compra → liberado='si' → pendiente_enviar → envío central.

TABLA: articulos_lotes  (joyas dentro de un lote; consolidado)
- identificador_articulo INT PK
- id_articulo INT — nº artículo en la sucursal
- id_lote_articulos INT — = lotes_joyeria.id_lote
- sucursal_articulo INT — = lotes_joyeria.sucursal
- identificador_lote INT — a veces = lotes_joyeria.identificador (preferir join por id_lote+sucursal)
- unidades INT
- descripcion_articulo TEXT
- ley VARCHAR — quilates/ley
- tipo_de_articulo VARCHAR — Oro/Plata/...
- peso_articulo / peso_real / peso_bruto / peso_bruto_real DECIMAL
- merma / merma_real DECIMAL
- precio_compra_articulo DECIMAL
- precio_venta_articulo DECIMAL, articulo_venta VARCHAR(2) — si se aparta a venta en vez de fundir
- estado_articulo VARCHAR — enfecha, pendiente_enviar, enviado_central, retirado, mermado, vendido, vendido_web, liberado, noliberado...
- articulo_auditado ENUM('false','true')
- categoria_articulo INT
- rel_id_proforma INT, rel_proforma_state ENUM('false','true')
- precio_fundicion / rentabilidad / total_gramos_fundicion / total_pagado_fundicion DECIMAL
- rel_numero_semana INT
NO confundir con articulos_venta (stock de tienda para vender al público).

TABLA: historico_renovaciones_gobal  (cuotas/renovaciones de empeño; typo gobal)
- id_renovaciones INT PK
- rel_id_renovacion INT — id en tabla de sucursal
- lote INT — = lotes_joyeria.id_lote
- sucursal_id INT — = lotes_joyeria.sucursal
- fecha_renovacion DATE — cuándo se cobró (si pagada)
- proximo_vencimiento DATE
- importe_renovacion DECIMAL — interés de la cuota (≈ precio_recompra - precio_compra)
- estado_historico VARCHAR — mezcla mayúsculas: 'enfecha' (vigente), 'Renovado', 'Vencido', 'Retirado', 'Perdido', 'Pendiente'
- forma_de_pago ENUM('pendiente','efectivo','tarjeta','transferencia','bizum')
- fecha_insert / fecha_vencido / fecha_perdido DATE
- nombre_foto TEXT — comprobante
Una renovación = pago del interés mensual del empeño (no es retirar el lote).
Totales: SUM(importe_renovacion) / COUNT(*) solo sobre esta tabla. Ejemplo año pasado cobradas:
SELECT SUM(importe_renovacion) AS total, SUM(importe_renovacion)/1.21 AS base_sin_iva
FROM historico_renovaciones_gobal
WHERE estado_historico = 'Renovado' AND YEAR(fecha_renovacion) = YEAR(CURDATE()) - 1
(sin JOIN a lotes_joyeria).

TABLA: adelantos_capital  (aumentar capital de un empeño existente)
- id_adelanto_capital INT PK
- id_lote_adelanto INT — = lotes_joyeria.id_lote
- sucursal_adelanto INT
- cliente_adelanto INT → clientes.id_cliente
- usuario_adelanto INT → usuarios.id_usuario
- fecha_adelanto DATETIME
- importe_adelanto DECIMAL — dinero extra entregado al cliente
- capital_antiguo / nuevo_capital DECIMAL
- precio_recompra_antiguo / nuevo_precio_recompra DECIMAL
- forma_de_pago VARCHAR
- nombre_foto TEXT — comprobante
- gastos_adelantos DECIMAL
Nuevo importe renovación ≈ nuevo_precio_recompra - nuevo_capital.

TABLA: trazabilidad_lotes  (log de acciones sobre lotes)
- id_trazabilidad INT PK
- id_lote INT — suele ser id_lote de sucursal (interpretar con sucursal_accion)
- fecha_accion DATETIME
- usuario_accion INT → usuarios.id_usuario (1 a menudo = cron/sistema)
- accion_trazabilidad ENUM — editado, compra, empeno, intervenido, vencido, liberado, perdido, renovado, pendiente_enviar, enviado_a_central, recibido_central, lote_auditado, adelanto_capital, etc.
- comentarios_accion TEXT
- sucursal_accion INT → sucursal.id_sucursal
- codigo_envio VARCHAR(6), envio_id INT

TABLA: trazabilidad_articulos  (log artículos de lote)
- id_trazabilidad_articulo INT PK
- id_lote INT, id_articulo INT
- fecha_accion DATETIME, usuario_accion INT, sucursal_accion INT
- accion_trazabilidad ENUM — creado, editado, compra, empeno, enviado_fundir, fundido, pasado_stock, incluido_proforma, auditado_sucursal, etc.
- comentarios_accion TEXT, envio_id INT, proforma_id_rel INT, id_articulo_venta INT

TABLA: trazabilidad_articulos_venta  (log artículos de venta / stock tienda)
- id_trazabilidad_articulo INT PK
- id_venta / identificador_venta INT
- id_articulo INT → articulos_venta.id
- fecha_accion DATETIME, usuario_accion INT, sucursal_accion INT
- accion_trazabilidad ENUM — creado, editado, vendido, devuelto, mermado, traspaso_enviado, traspaso_recibido, publicadoweb, reservado, enventa, etc.

TABLA: cambios_estados_lotes  (transición estado anterior → actual)
- id_cambio_estado INT PK
- id_lote INT
- fecha_cambio DATETIME, usuario_cambio INT, sucursal_accion INT
- accion_cambio_antiguo / accion_cambio_actual ENUM — editado, compra, empeno, intervenido, vencido, liberado, perdido, renovado, enfecha, etc.
- comentarios_accion TEXT, codigo_envio, envio_id

──────────────────────────────────────────────────────────────
B) ENVÍOS + INFORME METAL + PROFORMAS + PROVEEDORES + PRECIOS
──────────────────────────────────────────────────────────────

TABLA: envios  (envío semanal sucursal → central)
- id_envio INT PK
- sucursal_remitente INT → sucursal.id_sucursal
- estado_envio ENUM('enviado_central','recibido_central','envio_cancelado','pendiente_envio','envio_rechazado','envio_auditado','auditando_envio')
- fecha_envio / fecha_recepcion DATETIME
- cantidad_lotes / cantidad_articulos / cantidad_compras / cantidad_empenios INT
- peso_bruto_oro_lotes / peso_neto_oro_lotes / merma_oro / total_abonado_oro / media_oro DECIMAL
- peso_bruto_plata_lotes / peso_neto_plata_lotes / merma_plata / total_abonado_plata / media_plata DECIMAL
- total_renovaciones / total_retiradas DECIMAL, cantidad_renovaciones / cantidad_retiradas INT
- semana_numero INT, semanas_enviadas TEXT
- desde_fecha / hasta_fecha / fecha_compra_desde / fecha_compra_hasta DATE
- enviado_por / recibido_por INT → usuarios
- observaciones_envio / motivo_rechazo_central_envio / comentario_fundicion TEXT
- empresa_id_envio INT → empresas.id_empresa
- rel_proforma_id INT, rel_proforma_state ENUM('false','true')
- traspaso_id_rel INT — traspaso auto si hay artículos a venta

TABLA: informe_metal  (resumen metal al auditar un envío)
- id_informe INT PK
- envio_informe_metal INT → envios.id_envio
- sucursal_informe INT, empresa_informe_metal INT, usuario_informe_metal INT
- fecha_informe DATE, hora_informe_metal TIME
- semana_informe_metal INT, fecha_desde_informe_metal / fecha_hasta_informe_metal DATE
- peso_bruto_oro / peso_neto_oro / merma_oro / gramos_fundir_oro / total_fundir_oro DECIMAL
- peso_bruto_oro_empenos / peso_neto_oro_empenos / merma_oro_empenos DECIMAL
- peso_bruto_plata / peso_neto_plata / merma_plata / gramos_fundir_plata DECIMAL
- total_gramos_stock DECIMAL, articulos_stock INT — apartado a venta
- comentarios_fundicion_proforma TEXT, semanas_enviadas TEXT

TABLA: proformas  (venta de metal a fundición)
- id_proforma INT PK, proforma_numero INT
- proveedor_proforma INT → proveedores.id_proveedor (suele fundicion='true')
- empresa_proforma INT → empresas.id_empresa
- estado_proforma ENUM('pendiente','generada','enviada','liquidada','cancelada','editando')
- tipo_metal_proforma ENUM('Oro','Plata')
- precio_gramo_proforma / importe_proforma / adelanto_proforma / importe_pagado DECIMAL
- total_gramos_enviados / total_gramos_proforma / total_fino DECIMAL
- rentabilidad_proforma / coste_proforma / beneficio_fundicion DECIMAL
- forma_de_pago INT → formas_de_pago.id_forma_de_pago
- envio_rel_id INT, rel_semana_numero INT, year_rel YEAR
- fecha_creacion DATETIME, fecha_proforma / fecha_envio / fecha_pago / fecha_factura DATE
- usuario_genera_proforma / usuario_envia_proforma / cancelada_por INT
- observacion_proforma TEXT, semanas_proforma TEXT, solo_una_semana ENUM

TABLA: proveedores
- id_proveedor INT PK
- nombre_proveedor VARCHAR, cif_proveedor VARCHAR
- fundicion ENUM('false','true') — si es fundición de metal
- fundicion_multi_kilates ENUM('false','true')
- forma_pago_proveedor ENUM('efectivo','tarjeta','transferencia','domiciliacion','bizum')
- email_proveedor, telefono_proveedor, direccion_proveedor, poblacion_proveedor, provincia_proveedor, pais_proveedor
- rel_id_provincia / rel_id_pais / rel_id_poblacion INT
- creado_por INT, fecha_creacion_proveedor DATE

TABLA: precios_oro  (cotización oro en BD)
- id INT PK
- fecha_registro DATETIME — usar siempre el de fecha_registro más alta del día pedido
- metal VARCHAR, currency VARCHAR
- precio_onza / precio_apertura / precio_max / precio_min / variacion / variacion_pct DECIMAL
- precio_gramo_24k / 22k / 21k / 20k / 18k / 16k / 14k / 10k DECIMAL — €/gramo
- timestamp_api INT
REGLA PRECIO ORO (la atiende el servidor, no generes SQL):
- «precio del oro» / «hoy» → registro de CURDATE() con MAX(fecha_registro); si no hay, el global más reciente
- «ayer» → día anterior al MAX(DATE(fecha_registro)) de la tabla
- Por defecto devolver TODOS los kilates en €/g; si piden 18 kilates (u otro) solo ese
- Respuesta en euros por gramo

FLUJO UTILIDAD / BENEFICIO / RENTABILIDAD / GANANCIA (servidor multi-consulta; NO un solo SELECT):
Ejemplos de cómo puede preguntar el usuario (todas van a este flujo):
- utilidad | ganancia | beneficio | rentabilidad  → TODAS las sucursales (mes actual)
- … de la sucursal X  → solo esa sucursal
- … de todas las sucursales | … de todo  → todas
- … de marzo del 2025  → todas las sucursales en ese mes
- utilidad / utilidades / utilidad neta; beneficio / beneficios; ganancia / ganancias; rentabilidad
- «cuánto hemos ganado», «dame el beneficio», «cómo van los beneficios», «resultado neto», etc.
Periodo (obligatorio filtrar por fechas; el servidor lo detecta de la pregunta):
- Por defecto: mes actual (del día 1 a hoy)
- Mes actual / este mes; mes pasado; mes concreto (enero, febrero… + año opcional)
- Fecha concreta (dd/mm/yyyy, yyyy-mm-dd, «15 de marzo»)
- Hoy / ayer
- Año actual / este año; año pasado; año N (p. ej. 2024)
- Rango: entre dos fechas, del X al Y, desde X hasta Y (también entre dos meses)
1) SUM(gastos.total_gasto) DATE(fecha_gasto) en periodo + sucursal(es), excluye cancelado
2) SUM(historico_renovaciones_gobal.importe_renovacion) estado Renovado → sin IVA = total/1.21
3) SUM(ventas.precio) ventas no anuladas
4) SUM(articulos_venta.precio_coste) estado=vendido por fecha_vendido
5) lotes_joyeria compra_opcion='no': SUM(peso) y SUM(precio_compra)
6) Valor metal (ley 0,725): gramos_fino = peso×0,725; precio_fino = 0,725×precios_oro.precio_gramo_24k (fixing, último registro); valor_metal = precio_fino × peso comprado
7) valor = paso6 + paso3 + paso2
8) costes = precio_compra(paso5) + paso4 + paso1
9) utilidad = paso7 − paso8
10) Texto: «La utilidad de … (periodo) es de X €» + desglose, sin tabla
NO generes SQL para utilidad/beneficio/rentabilidad/ganancia: el servidor calcula el periodo y responde en texto.

TABLA: precios_proveedor  (precios por ley/proveedor)
- id_precios_proveedor INT PK
- id_proveedor_rel INT → proveedores.id_proveedor
- ley_rel INT, value_rel VARCHAR
- precio_adelanto_rel / precio_final_rel DECIMAL
- fecha_creacion DATE

TABLA: precios_oro_proveedores  (precio gramo fundición por proveedor; la usa la app en proformas)
- id INT PK (si existe en BD)
- proveedor_id INT → proveedores.id_proveedor
- precio_gramo_24k DECIMAL, metal VARCHAR
- fecha_standby DATE, timestamp_api INT, usuario_accion INT
Último precio proveedor: WHERE proveedor_id = ? ORDER BY id DESC LIMIT 1

──────────────────────────────────────────────────────────────
C) CAJA / MOVIMIENTOS + GASTOS + AUTORIZACIONES
──────────────────────────────────────────────────────────────

TABLA: movimientos_de_caja_global  (libro de caja efectivo consolidado)
- id_movimientos INT PK
- fecha_apunte DATE, hora_de_apunte TIME
- grupos VARCHAR — concepto de grupo (catálogo grupos_movimientos)
- concepto VARCHAR
- entrada / salida DECIMAL
- usuario VARCHAR — nombre/login (no siempre id)
- apertura_caja / cierre_caja ENUM('false','true') — CAJA INICIO / CAJA FINAL
- sucursal_id_movimiento INT → sucursal.id_sucursal
- id_movimientos_sucursal INT
Para totales de efectivo usa esta tabla (no movimientos_de_caja_{id}).

TABLA: movimientos_tarjeta  (cobros/pagos con tarjeta)
- id INT PK, id_venta INT, id_lote INT, sucursal INT → sucursal.id_sucursal
- descripcion VARCHAR, grupos VARCHAR, fecha TIMESTAMP, usuario VARCHAR
- importe DECIMAL, salida DECIMAL

TABLA: movimientos_transferencia  (cobros/pagos por transferencia)
- id INT PK, id_venta INT, id_lote INT, sucursal INT
- descripcion VARCHAR, grupos VARCHAR, fecha TIMESTAMP, usuario VARCHAR
- entrada DECIMAL, salida DECIMAL

TABLA: movimientos_bizum  (cobros/pagos Bizum)
- id INT PK, id_venta INT, id_lote INT, sucursal INT
- descripcion VARCHAR, grupos VARCHAR, fecha TIMESTAMP, usuario VARCHAR
- importe DECIMAL, salida DECIMAL
Pagos no efectivo de ventas, renovaciones, retiradas, etc.

TABLA: gastos
- id_gasto INT PK
- sucursal_gasto INT, empresa_gasto INT → empresas, proveedor_gasto INT → proveedores
- fecha_gasto / fecha_factura_gasto DATE, fecha_pago_gasto DATETIME
- base_impobile / iva_total / irpf / total_gasto DECIMAL (ojo: base_impobile typo real)
- estado_gasto ENUM('pendiente','pagado','cancelado')
- forma_pago_gasto INT → formas_de_pago
- tipo_de_gasto INT, tipo_iva INT
- descripcion_gasto TEXT, numero_factura_proveedor VARCHAR
- usuario_creacion_gasto / usuario_pago_gasto INT
- creado_desde ENUM('Central','Sucursal','Cron','Agente','pyton')
- origen_gasto_variable ENUM('manual','gasto_fijo'), rel_id_gasto_fijo INT
- gasto_tipo ENUM('sucursal','empresa')

TABLA: gastos_fijos  (plantillas recurrentes; el cron genera filas en gastos)
- id_gasto_fijo INT PK
- sucursal_gasto_fijo / empresa_gasto_fijo / proveedor_gasto_fijo INT
- periodo_gasto_fijo ENUM('indefinido','diario','semanal','quincenal','mensual','trimestral','semestral','anual','bianual')
- estado_gasto_fijo ENUM('true','false') — 'true'=activo
- base_impobile_fijo / iva_total_fijo / irpf_fijo / total_gasto_fijo DECIMAL
- fecha_inicio_gasto_fijo / fecha_vencimiento_gasto_fijo / fecha_alta_gasto_fijo DATE
- descripcion_gasto_fijo TEXT, forma_pago_gasto_fijo INT, tipo_de_gasto_fijo INT
- gasto_tipo ENUM('empresa','sucursal')

TABLA: rel_gastos_forma_pago
- id_rel_forma_pago INT PK
- gasto_id INT → gastos.id_gasto
- forma_de_pago_id INT → formas_de_pago
- numero_forma_pago VARCHAR — cuenta/tarjeta
- fecha_rel DATE, empresa_id_rel INT
- gasto_fijo ENUM('false','true'), gasto_fijo_id INT

TABLA: autorizaciones_gastos  (código 6 dígitos para salida de caja)
- id INT PK, sucursal INT, usuario VARCHAR, codigo VARCHAR(6)
- estado ENUM('pendiente','autorizada','usada','nousada')
- fecha TIMESTAMP, fecha_uso TIMESTAMP
- grupo / concepto VARCHAR, salida DECIMAL
- id_apunte INT, id_gasto_parset INT, imagen VARCHAR

TABLA: formas_de_pago
- id_forma_de_pago INT, nombre_forma_de_pago VARCHAR, fecha_creacion DATE

TABLA: grupos_movimientos
- id_grupo INT PK, nombre_grupo VARCHAR, tipo_grupo VARCHAR
Catálogo de grupos de apuntes de caja (ej. Renovar empeños, CAJA INICIO).

──────────────────────────────────────────────────────────────
D) VENTAS: FACTURAS, DEVOLUCIONES, PLAZOS, TRASPASOS, REPORTE, AUDITORÍAS
──────────────────────────────────────────────────────────────

TABLA: empresas
- id_empresa INT PK, nombre_empresa VARCHAR, cif_empresa VARCHAR
- region_regimen ENUM('false','Verifactu','TicketBAIBizkaia','TicketBAIAlava','TicketBAIGipuzkua','General')
- tipo_api ENUM('test','produccion'), factura_digital ENUM('false','true')
- email_empresa, telefono_empresa, direccion_empresa, poblacion_empresa, provincia_empresa, pais_empresa
- rel_id_provincia / rel_id_poblacion / rel_id_pais INT
- cuenta_corriente_empresa, webempresa, textos de factura/contrato
sucursal.empresa_id → empresas.id_empresa

TABLA: facturas
- id_factura INT PK
- id_sucursal INT, prefijo_factura VARCHAR, numero_factura INT
- cliente_factura INT → clientes, facturado_por INT → usuarios
- estado_factura ENUM('nopagada','pagada','anulada')
- tipo_pago_factura VARCHAR, total_factura DECIMAL
- fecha_factura DATE, hora_factura TIME
- tipo_factura ENUM('articulos','renovaciones','oro_inversion')
- rel_id_venta / rel_id_lote / rel_id_renovacion INT
- factura_regimen ENUM(...), rel_id_empresa INT, id_rel_factura_fiskaly INT
- factura_simplificada ENUM('false','true'), fecha_anulacion DATETIME

TABLA: facturas_rectificativas  (abonos; enlaza a factura original)
- mismos campos base que facturas + factura_original / rel_id_factura INT
- motivo_rectificado VARCHAR, fecha_factura_original DATE, prefijo_factura_original VARCHAR

TABLA: facturas_rel_articulos
- id_rel_fac_art INT PK
- id_rel_factura INT → facturas.id_factura
- id_rel_articulo INT, id_rel_sucursal INT
- precio_rel_articulo DECIMAL, fecha_factura DATETIME

TABLA: facturas_rel_renovaciones
- id_rel_fac_art INT PK
- id_rel_factura INT, id_rel_renovacion INT, id_rel_sucursal INT
- precio_rel_renovacion / precio_venta_sin_iva DECIMAL
- descripcion_renovacion TEXT, fecha_factura DATETIME

TABLA: devoluciones
- id_devolucion INT PK
- id_venta_original INT → ventas.id
- articulo_devolucion INT → articulos_venta.id
- cliente_devolucion INT, sucursal_devolucion INT, usuario_devolucion INT
- importe_devolucion DECIMAL, forma_de_pago_devolucion VARCHAR
- estado_devolucion ENUM('creada','hecha','cancelada')
- motivo_devolucion TEXT
- codigo_autorizacion VARCHAR(6), estado_autorizacion ENUM('pendiente','autorizada','usada','nousada')
- devolucion_web ENUM('false','true'), factura_rel_id INT
- tipo_factura ENUM('factura','simplificada')
- fecha_devolucion DATE, hora_devolucion TIME, empresa_devolucion INT

TABLA: ventas_plazos  (cuotas de ventas a plazos; cabecera en ventas con venta_plazos='si')
- id INT PK, id_venta INT → ventas.id
- estado VARCHAR — 'Pendiente', 'Pagado', etc.
- fecha_creado TIMESTAMP, fecha_vencimiento DATE, fecha_cobrado TIMESTAMP, fecha_vencido DATE, fecha_anulado DATE
- importe DECIMAL, metodo_pago VARCHAR, comprobante_plazo VARCHAR
- cantidad_contado / tarjeta / transferencia / bizum DECIMAL
- rel_id_venta_a_plazos INT

TABLA: traspasos  (traspaso de artículos de venta entre sucursales)
- id_traspaso INT PK (compuesto con sucursal_traspaso en PK física)
- sucursal_traspaso INT origen, sucursal_destino INT
- estado_traspaso ENUM('PENDIENTEENVIO','TRASPASADO','PENDIENTEDERECIBIR','ANULADO')
- fecha_traspaso / fecha_envio_traspaso / fecha_recepcion_traspaso DATE
- total_articulos_traspaso INT, skus_traspaso TEXT
- creado_por / enviado_por / recibido_por / anulado_por INT
- traspaso_web ENUM, traspaso_envio ENUM — auto desde envío a central
- envio_numero_rel INT, observaciones_traspaso TEXT

TABLA: rel_articulos_traspaso
- id_rel_traspaso INT PK
- id_articulo_rel INT → articulos_venta.id
- id_traspaso_rel INT → traspasos.id_traspaso
- sucursal_origen_rel / sucursal_destino_rel INT
- fecha_creacion_rel / fecha_traspasado / fecha_recibido DATE

TABLA: reporte_ventas  (reporting desnormalizado por artículo vendido)
- id_reporte_ventas INT PK
- id_articulo INT, id_sucursal_venta INT, nombre_sucursal_venta VARCHAR
- descripcion_articulo VARCHAR, id_venta_rel / identificador_venta INT
- precio_articulo / peso_articulo / coste_articulo_venta DECIMAL
- articulo_web ENUM, tipo_metal_articulo ENUM('oro','plata')
- venta_plazos ENUM('no','si'), numero_plazos INT
- tipo_pago ENUM('contado','bizum','combinado','transferencia','tarjeta')
- cantidad_contado / tarjeta / transferencia / bizum DECIMAL
- fecha_venta DATE, usuario_venta INT
- factura_id_rel INT, numero_factura_venta INT, prefijo_factura VARCHAR

TABLA: informe_diario  (resumen diario por sucursal; se genera ~21:30 cada día)
PREFERIR ESTA TABLA para totales, sumas, medias e informes agregados de un día, semana, mes o rango
(ventas, compras oro/plata, empeños, renovaciones, caja, gastos, stock valorizado, etc.).
Un registro = una sucursal + un día (fecha_informe). Agregar con SUM()/AVG() y filtrar por fechas.
NO uses esta tabla para listados de operaciones individuales (esas van a ventas, lotes_joyeria, etc.).
NO uses esta tabla para el flujo utilidad/beneficio/rentabilidad/ganancia (eso lo calcula el servidor aparte).
- id_informe INT PK AUTO_INCREMENT
- fecha_informe DATE — día del resumen (filtrar siempre por esta columna)
- year_rel YEAR(4), semana_numero INT — año/semana del informe
- fecha_generado DATE, hora_generado TIME — cuándo se generó el cron
- sucursal_informe INT → FK sucursal.id_sucursal
- empresa_informe_id INT, usuario_genera_informe INT
- estado_informe ENUM('abierto','finalizado','cancelado') — usa estado_informe='finalizado' en totales
- estado_cron_informe ENUM('inicializado_cron','finalizado_cron')
- caja_cerrada ENUM('false','true')
- ultima_actualizacion DATETIME
Caja / movimientos del día:
- total_caja_entradas / total_caja_salidas DECIMAL(15,2)
- total_operaciones_tarjeta DECIMAL(15,2)
- total_operaciones_trasnferencia_salida / total_operaciones_trasnferencia_entrada DECIMAL(15,2) — typo real «trasnferencia»
- total_operaciones_bizum DECIMAL(15,2)
- total_entradas / total_salidas DECIMAL(15,2)
- total_gastos DECIMAL(15,2)
Compras oro / plata:
- total_lotes_compra_oro INT, total_gramos_compra_oro / total_euros_lotes_compra_oro DECIMAL(15,2)
- media_pagado_oro_compra / media_pagado_plata_compra DECIMAL(15,2)
- total_lotes_compra_plata INT, total_gramos_compra_plata / total_euros_lotes_compra_plata DECIMAL(15,2)
- total_piedras_compradas INT
Empeños (todos / oro / plata):
- total_lotes_empenios INT, total_gramos_empenios / total_euros_lotes_empenios DECIMAL(15,2)
- media_pagado_empenyo / media_pagado_oro_empenyo / media_pagado_plata_empenyo DECIMAL(15,2)
- total_lotes_empenios_oro INT, total_gramos_empenios_oro / total_euros_lotes_empenios_oro DECIMAL(15,2)
- total_lotes_empenios_plata INT, total_gramos_empenios_plata / total_euros_lotes_empenios_plata DECIMAL(15,2)
Empeños retirados / vencidos / perdidos / intervenidos:
- total_empenyos_retirados INT, total_euros_empenyos_retirados / total_gramos_empenios_retirados DECIMAL(15,2)
- total_empenyos_vencidos INT, total_euros_empenyos_vencidos / total_gramos_empenios_vencidos DECIMAL(15,2)
- total_empenyos_perdidos INT, total_euros_empenios_perdidos / total_gramos_empenyos_perdidos DECIMAL(15,2)
- total_contratos_intervenidos INT, total_euros_contratos_intervenidos / total_gramos_contratos_intervenidos DECIMAL(15,2)
Renovaciones / devoluciones / reparaciones:
- total_renovaciones INT, total_euros_renovaciones DECIMAL(15,2)
- total_devoluciones INT, total_euros_devoluciones DECIMAL(15,2)
- total_reparaciones INT, toral_repapraciones_euro DECIMAL(15,2) — typo real «toral_repapraciones_euro»
Ventas (tienda + web + plazos + forma de pago):
- total_ventas INT, total_media_ventas / total_gramos_ventas / total_euros_ventas DECIMAL(15,2)
- total_ventas_plazo INT, total_ventas_plazo_euro DECIMAL(15,2)
- ventas_web INT, total_euros_ventas_web / total_media_ventas_web DECIMAL(15,2)
- ventas_tarjeta INT, ventas_tarjeta_euros DECIMAL(15,2)
- ventas_contado INT, ventas_contado_euros DECIMAL(15,2)
- ventas_transferencia INT, ventas_transferencia_euros DECIMAL(15,2)
- ventas_bizum INT, ventas_bizum_euros DECIMAL(15,2)
- total_coste_art_venta / total_beneficio_ventas DECIMAL(15,2)
Stock / beneficio tienda / precio oro / ajustes:
- stock_articulos INT, stock_valorizado_eruo DECIMAL(15,2) — typo real «eruo»
- coste_stock_valorizado DECIMAL(15,2)
- beneficio_tienda / matriz_beneficio_tienda DECIMAL(15,2)
- precio_oro DECIMAL(15,2), ajustes_de_lotes DECIMAL(15,2)
Ejemplos:
- Totales ventas mes actual todas las sucursales:
  SELECT SUM(total_euros_ventas) AS total_euros, SUM(total_ventas) AS num_ventas
  FROM informe_diario WHERE estado_informe='finalizado'
  AND MONTH(fecha_informe)=MONTH(CURDATE()) AND YEAR(fecha_informe)=YEAR(CURDATE())
- Totales por sucursal en un día: JOIN sucursal s ON s.id_sucursal = informe_diario.sucursal_informe,
  filtrar fecha_informe = 'YYYY-MM-DD', GROUP BY s.nombre_sucursal
- Si piden «hoy» y aún no hay fila de CURDATE() (cron ~21:30), usa el último fecha_informe disponible
  o indica que el resumen de hoy aún no se ha generado
- Semana: semana_numero + year_rel; o DATE ranges sobre fecha_informe

TABLA: auditorias_tiendas
- id_auditoria INT PK
- sucursal_auditoria INT, usuario_auditoria INT (auditor), usuario_auditado INT
- estado_auditoria ENUM('Auditando','Finalizada','Cancelada')
- resultado_auditoria ENUM('false','Negativa','Positiva')
- fecha_inicio / fecha_finalizar / fecha_cancelada DATETIME
- total_articulos_auditar / auditados / faltantes / existentes INT
- articulos_stock / stock_faltantes / articulos_empenos / art_empenios_faltantes INT
- articulos_liberados / liberados_faltantes INT
- motivo_cancela_auditoria / comentarios_auditoria TEXT

TABLA: rel_art_auditoria  (checklist artículo en auditoría)
- id_rel_art_aud INT PK
- rel_id_auditoria INT → auditorias_tiendas.id_auditoria
- rel_articulo INT, rel_id_lote INT, rel_id_venta INT
- tipo_articulo ENUM('compra','empenio','venta')
- estado_articulo ENUM — liberado, stock, enfecha, vencido, perdido, reservado, vendido, fundido, etc.
- estado_auditoria ENUM('auditando','existente','faltante','cancelado')
- precio_compra / precio_venta DECIMAL, descripcion_articulo TEXT
- sucursal_irel_art_aud INT, fecha_rel_art_aud DATETIME

TABLA: rel_lote_auditoria  (snapshot lotes en auditoría)
- identificador INT PK, id_lote INT, sucursal INT
- rel_id_auditoria INT, estado_auditoria ENUM('pendiente','auditando','faltantes','existente','cancelado')
- compra_opcion ENUM, estado_lote VARCHAR, estado_envio ENUM, liberado VARCHAR
- peso / peso_bruto / precio_compra / precio_recompra DECIMAL
- tipo_de_lote VARCHAR, cantidad_articulos INT, fecha_compra / fecha_perdido / fecha_alta_auditoria DATE

TABLA: autorizaciones_descuento_articulo_venta
- id INT PK, sucursal INT, usuario VARCHAR, codigo VARCHAR(6), id_articulo INT
- estado ENUM('pendiente','autorizada','usada','nousada')
- precio_original / precio_nuevo DECIMAL, fecha TIMESTAMP

TABLA: autorizaciones_devoluciones
- id_autorizacion INT PK
- sucursal_autorizacion INT, usuario_autorizacion VARCHAR, codigo_autorizacion VARCHAR(6)
- estado_autorizacion ENUM('pendiente','autorizada','usada','nousada')
- sku_articulo_devolucion INT, venta_id INT, rel_id_devolucion INT, fecha_autorizacion DATE

TABLA: autorizaciones_porcentajes_ventas  (autorizar cambio % intereses venta a plazos)
- id INT PK, sucursal INT, usuario VARCHAR, codigo VARCHAR(6), id_articulo TEXT
- estado ENUM('pendiente','autorizada','usada','nousada')
- intereses_originales / intereses_nuevos / precio_original / precio_nuevo DECIMAL

TABLA: historico_precio_articulos_venta
- id_rel_historico INT PK
- rel_sku_historico INT → articulos_venta.id
- precio_anterior / precio_actual DECIMAL
- actualizado_por INT, sucursal INT, fecha_actualizacion DATE
- tipo_registro ENUM('update','create')

TABLA: rel_articulos_estados  (estado/histórico global por artículo para informes/envíos)
- id_articulo_rel INT PK
- rel_id_articulo INT, rel_id_sucursal INT, rel_id_lote INT
- ley VARCHAR, tipo_de_articulo VARCHAR, peso_articulo DECIMAL
- precio_compra_articulo / precio_venta / precio_coste_venta / rentabilidad_venta DECIMAL
- estado_articulo VARCHAR
- articulo_empeno ENUM('false','true') — distingue empeño vs compra en informe metal
- articulo_auditado ENUM, rel_id_envio INT, rel_id_empresa INT
- rel_id_proforma / rel_proforma_state / rel_id_item_proforma
- rel_id_articulo_venta / rel_id_sucursal_venta / rel_id_venta INT
- rel_numero_semana / year_rel, rel_numero_semana_empenio_perdido / year_rel_empenio_perdido
- tipo_iva_articulo ENUM, system_codigo_regimen ENUM('REBU','INVERSION','GENERAL')
- articulo_web ENUM, fecha_mermado DATE, motivo_merma VARCHAR

──────────────────────────────────────────────────────────────
E) GEO + USUARIOS / LOGS / PERMISOS
──────────────────────────────────────────────────────────────

TABLA: paises
- id_country INT PK, iso VARCHAR(2), codeLocale VARCHAR(5), symboloCurrency VARCHAR(10), nombre_pais VARCHAR

TABLA: provincias
- id_province INT PK, nombreProvince VARCHAR, provinciaseo VARCHAR, provincia3 CHAR(3)
- id_rel_country INT → paises.id_country

TABLA: poblacion
- idpoblacion INT PK, idprovincia INT → provincias.id_province
- poblacion VARCHAR, postal VARCHAR, latitud / longitud DECIMAL
- rel_id_country INT → paises.id_country

TABLA: nacionalidades
- id INT PK, nombre_nacionalidad VARCHAR, country_id_rel INT → paises.id_country

Ampliación privilegios_usuarios (ya existe en contexto base):
- root_section / super_administrador / auditoria_section / recepcion_lotes_section / central_section ENUM('false','true')

TABLA: relItemsLevel  (permisos menú ↔ privilegio)
- id_rel INT PK
- relIdItems INT — ítem de menú
- relIdUsersLevel INT → privilegios_usuarios.id_privilegios

TABLA: usersActions  (log de acciones de usuario)
- idUserAction INT PK
- userId INT → usuarios.id_usuario
- dateAction DATETIME
- relidlistActions INT, relItemAction INT
- logTxt TEXT, urlAction TEXT
- sucursalIdUserAction INT, ipNumberUser VARCHAR, userAgent VARCHAR

TABLA: usersConexions  (log de sesiones/conexiones)
- idUserConexion INT PK
- userId INT → usuarios.id_usuario
- dateConexion DATETIME, state_connection ENUM('false','true')
- companyIdUserConexion INT
- groupId INT, logTxt TEXT, tokensessioncontrol VARCHAR
- ipNumberUser VARCHAR, userAgent VARCHAR
- locationLong / locationLat VARCHAR

RELACIONES EXTRA (JOINs típicos):
- lotes_joyeria.cliente = clientes.id_cliente
- lotes_joyeria.sucursal = sucursal.id_sucursal
- articulos_lotes: id_lote_articulos+sucursal_articulo = lotes_joyeria.id_lote+sucursal
- historico_renovaciones_gobal: lote+sucursal_id = lotes_joyeria.id_lote+sucursal
- adelantos_capital: id_lote_adelanto+sucursal_adelanto = lotes_joyeria.id_lote+sucursal
- envios.sucursal_remitente = sucursal.id_sucursal
- lotes_joyeria.envio_numero = envios.id_envio
- informe_metal.envio_informe_metal = envios.id_envio
- proformas.proveedor_proforma = proveedores.id_proveedor
- proformas.empresa_proforma = empresas.id_empresa
- gastos.proveedor_gasto = proveedores.id_proveedor
- gastos.sucursal_gasto = sucursal.id_sucursal
- facturas.cliente_factura = clientes.id_cliente
- facturas.rel_id_venta = ventas.id
- devoluciones.id_venta_original = ventas.id
- ventas_plazos.id_venta = ventas.id
- traspasos.sucursal_traspaso / sucursal_destino = sucursal.id_sucursal
- reporte_ventas.id_venta_rel ≈ ventas / identificador_venta
- informe_diario.sucursal_informe = sucursal.id_sucursal
- usersActions.userId = usuarios.id_usuario
- usersConexions.userId = usuarios.id_usuario
- direcciones proveedores: type_direccion='proveedores' AND rel_id_item = proveedores.id_proveedor
- direcciones empresas: type_direccion='empresas' AND rel_id_item = empresas.id_empresa

REGLAS EXTRA:
- Empeños activos típicos: compra_opcion='si' AND estado_lote IN ('enfecha','vencido')
- Compras: compra_opcion='no'
- Lotes pendientes de envío: estado_envio='pendiente_enviar'
- Renovaciones cobradas: estado_historico='Renovado'; vigentes: estado_historico='enfecha'
- TOTALES / SUMAS / INFORMES AGREGADOS (día, semana, mes, año, rango): preferir informe_diario
  (SUM de columnas del resumen) con estado_informe='finalizado'. Es más rápido y coherente con el cron diario.
  Solo agrega desde tablas operativas (ventas, lotes_joyeria, historico_renovaciones_gobal, …) si piden
  detalle por operación, un desglose que no exista en informe_diario, o el día de hoy aún sin generar.
- JOINs: solo cuando necesites columnas de la otra tabla. Totales/agregados de una sola entidad = una sola tabla
  (totales diarios → informe_diario; renovaciones detalle → historico_renovaciones_gobal; coste vendidos detalle → articulos_venta; etc.).
  Un JOIN innecesario en tablas grandes ralentiza y puede excluir filas
- No inventes tablas por sucursal (lotes_N, articulos_N, ...)
- En listados de lotes incluye sucursal.nombre_sucursal y preferible identificador + id_lote
- Códigos de autorización (6 dígitos): puedes consultar estado/fechas; no inventes códigos
- Para caja efectivo detalle: movimientos_de_caja_global; para totales diarios de caja/tarjeta/bizum/transferencia: informe_diario
- Excluye clientes delete_state='true' cuando joins a clientes
- Respeta typos reales de columnas de informe_diario: trasnferencia, stock_valorizado_eruo, toral_repapraciones_euro
";
}
