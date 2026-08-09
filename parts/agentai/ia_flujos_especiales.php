<?php
/**
 * Flujos especiales del chat IA: precio oro (precios_oro) y utilidad/beneficio multi-consulta.
 */

function ia_fmt_euros($n, $decimales = 2) {
    return number_format((float) $n, $decimales, ',', '.') . ' €';
}

function ia_fmt_gramos($n, $decimales = 2) {
    return number_format((float) $n, $decimales, ',', '.') . ' g';
}

function ia_fmt_fecha_hora_es($datetime) {
    $ts = strtotime((string) $datetime);
    if ($ts === false) {
        return (string) $datetime;
    }
    return date('d/m/Y H:i', $ts);
}

/**
 * Detecta kilate pedido (18, 24, …). null = todos.
 */
function ia_precio_oro_detectar_kilate($pregunta) {
    $p = function_exists('mb_strtolower') ? mb_strtolower(trim($pregunta), 'UTF-8') : strtolower(trim($pregunta));
    if (preg_match('/\b(24|22|21|20|18|16|14|10)\s*(k|kt|kilates?|quilates?)\b/u', $p, $m)) {
        return (int) $m[1];
    }
    if (preg_match('/\b(kilates?|quilates?)\s*(24|22|21|20|18|16|14|10)\b/u', $p, $m)) {
        return (int) $m[2];
    }
    return null;
}

/**
 * 'hoy' | 'ayer' | 'ultimo'
 * - hoy / genérico: día de hoy (CURDATE)
 * - ayer: día anterior al último día con registro en precios_oro
 */
function ia_precio_oro_detectar_dia($pregunta) {
    $p = function_exists('mb_strtolower') ? mb_strtolower(trim($pregunta), 'UTF-8') : strtolower(trim($pregunta));
    if (preg_match('/\bayer\b/u', $p)) {
        return 'ayer';
    }
    if (preg_match('/\bhoy\b/u', $p)) {
        return 'hoy';
    }
    return 'hoy';
}

function ia_precio_oro_mapa_campos() {
    return array(
        24 => 'precio_gramo_24k',
        22 => 'precio_gramo_22k',
        21 => 'precio_gramo_21k',
        20 => 'precio_gramo_20k',
        18 => 'precio_gramo_18k',
        16 => 'precio_gramo_16k',
        14 => 'precio_gramo_14k',
        10 => 'precio_gramo_10k',
    );
}

/**
 * Obtiene la fila de precios_oro según día (hoy / ayer relativo al último registro).
 * @return array|false
 */
function ia_precios_oro_obtener_fila($conexion, $modo_dia) {
    if ($modo_dia === 'ayer') {
        $resUlt = mysqli_query($conexion, 'SELECT MAX(DATE(fecha_registro)) AS d FROM precios_oro');
        $rowUlt = $resUlt ? mysqli_fetch_assoc($resUlt) : null;
        if ($resUlt) {
            mysqli_free_result($resUlt);
        }
        if (!$rowUlt || empty($rowUlt['d']) || $rowUlt['d'] === '0000-00-00') {
            return false;
        }
        $stmt = mysqli_prepare(
            $conexion,
            'SELECT * FROM precios_oro
             WHERE DATE(fecha_registro) = DATE_SUB(?, INTERVAL 1 DAY)
             ORDER BY fecha_registro DESC, id DESC
             LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $d = $rowUlt['d'];
        mysqli_stmt_bind_param($stmt, 's', $d);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $fila = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        return $fila ?: false;
    }

    // Hoy
    $stmt = mysqli_prepare(
        $conexion,
        'SELECT * FROM precios_oro
         WHERE DATE(fecha_registro) = CURDATE()
         ORDER BY fecha_registro DESC, id DESC
         LIMIT 1'
    );
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $fila = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        if ($fila) {
            return $fila;
        }
    }

    // Si no hay registro de hoy: el de fecha_registro más alta
    $res = mysqli_query(
        $conexion,
        'SELECT * FROM precios_oro ORDER BY fecha_registro DESC, id DESC LIMIT 1'
    );
    $fila = $res ? mysqli_fetch_assoc($res) : null;
    if ($res) {
        mysqli_free_result($res);
    }
    return $fila ?: false;
}

/**
 * @return array{texto: string, raw: string}|false
 */
function ia_respuesta_precio_oro_desde_bd($conexion, $pregunta) {
    $modo = ia_precio_oro_detectar_dia($pregunta);
    $kilate = ia_precio_oro_detectar_kilate($pregunta);
    $fila = ia_precios_oro_obtener_fila($conexion, $modo);
    if (!$fila) {
        return false;
    }

    $mapa = ia_precio_oro_mapa_campos();
    $fechaTxt = ia_fmt_fecha_hora_es($fila['fecha_registro'] ?? '');
    $lineas = array();
    $lineas[] = 'Precio del oro (registro ' . $fechaTxt . '):';

    if ($kilate !== null && isset($mapa[$kilate])) {
        $campo = $mapa[$kilate];
        $val = isset($fila[$campo]) ? (float) $fila[$campo] : 0;
        $lineas[] = $kilate . ' kilates: ' . ia_fmt_euros($val, 2) . ' / gramo';
    } else {
        foreach ($mapa as $k => $campo) {
            $val = isset($fila[$campo]) ? (float) $fila[$campo] : 0;
            $lineas[] = $k . ' kilates: ' . ia_fmt_euros($val, 2) . ' / g';
        }
    }

    $raw = implode("\n", $lineas);
    return array(
        'texto' => nl2br(htmlspecialchars($raw)),
        'raw'   => $raw,
    );
}

function ia_pregunta_es_utilidad_negocio($pregunta) {
    $p = function_exists('mb_strtolower') ? mb_strtolower(trim($pregunta), 'UTF-8') : strtolower(trim($pregunta));
    if ($p === '') {
        return false;
    }
    // Evitar choques con precio del oro
    if (strpos($p, 'precio') !== false && strpos($p, 'oro') !== false) {
        return false;
    }

    /*
     |--------------------------------------------------------------------------
     | PATRONES UTILIDAD / BENEFICIO / GANANCIA / RENTABILIDAD
     | Añade aquí nuevas formas de preguntar del usuario.
     | Si solo dice la palabra (sin sucursal) → se calcula de TODAS las sucursales.
     |--------------------------------------------------------------------------
     | Ejemplos que deben entrar en este flujo:
     | - utilidad | ganancia | beneficio | rentabilidad
     | - … de la sucursal X
     | - … de todas las sucursales | … de todo
     | - … de marzo del 2025  (todas las sucursales + ese mes)
     */

    // Palabra clave sola o con cualquier complemento → entra al flujo
    $clavesDirectas = '/\b('
        . 'utilidad|utilidades|utilidad\s+neta|utilidades\s+netas|'
        . 'beneficio|beneficios|beneficio\s+neto|beneficios\s+netos|'
        . 'ganancia|ganancias|ganancia\s+neta|ganancias\s+netas|'
        . 'rentabilidad|rentabilidades|rentable|rentables|rentabildiad|'
        . 'margen\s+de\s+beneficio|margen\s+beneficio|'
        . 'resultado\s+neto|resultado\s+econ[oó]mico|resultado\s+del\s+negocio|'
        . 'desglose\s+de\s+utilidad|desglose\s+de\s+beneficio|desglose\s+de\s+ganancia'
        . ')\b/u';
    if (preg_match($clavesDirectas, $p)) {
        return true;
    }

    $meses = 'enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre';
    $claveNegocio = '(utilidad|utilidades|beneficio|beneficios|ganancia|ganancias|rentabilidad|rentabilidades|rentabildiad)';
    $contextoPeriodoOLugar = '/\b('
        . 'sucursal|sucursales|tienda|tiendas|negocio|central|todo|todas|'
        . 'mes|meses|a[nñ]o|a[nñ]os|hoy|ayer|actual|pasado|pasados|pasada|'
        . 'entre|desde|hasta|este|esta|ultimo|última|ultimos|últimas|'
        . 'semana|trimestre|semestre|'
        . $meses
        . ')\b/u';
    $preguntaCuanto = '/\b('
        . 'cu[aá]l|cu[aá]les|cu[aá]nto|cu[aá]nta|cu[aá]ntos|cu[aá]ntas|'
        . 'dime|ind[ií]came|mu[eé]strame|ens[eé][nñ]ame|'
        . 'calcula|calc[uú]lame|sacar|s[aá]came|hazme|dame|quiero|necesito|'
        . 'ver|mira|m[ií]rame|consultar|consulta|saber|decirme'
        . ')\b/u';
    $tieneFecha = preg_match('/\b\d{1,2}[\/\-.]\d{1,2}([\/\-.]\d{2,4})?\b|\b\d{4}-\d{2}-\d{2}\b/u', $p);

    // Formas verbales de ganar: ¿cuánto hemos ganado?, ¿cuánto gana la tienda?...
    if (preg_match('/\b(ganado|ganamos|gan[eé]|gana|ganan|ganar)\b/u', $p)
        && (preg_match($contextoPeriodoOLugar, $p) || preg_match($preguntaCuanto, $p) || $tieneFecha)
    ) {
        if (preg_match('/\b(sucursal|sucursales|tienda|tiendas|negocio|mes|a[nñ]o|beneficio|utilidad|euros?|€|dinero|caja|todo|todas)\b/u', $p)
            || preg_match($preguntaCuanto, $p)
        ) {
            return true;
        }
    }

    // Frases explícitas (añade las tuyas al final de este array)
    $frases = array(
        // Sucursal concreta / todas / todo / solo la palabra + periodo
        '/' . $claveNegocio . '\s+de\s+la\s+sucursal\b/u',
        '/' . $claveNegocio . '\s+de\s+las?\s+sucursales?\b/u',
        '/' . $claveNegocio . '\s+de\s+todas(\s+las)?\s+sucursales\b/u',
        '/' . $claveNegocio . '\s+de\s+todas(\s+las)?\s+tiendas\b/u',
        '/' . $claveNegocio . '\s+de\s+todo\b/u',
        '/' . $claveNegocio . '\s+de\s+(' . $meses . ')\b/u',
        '/' . $claveNegocio . '\s+del?\s+mes\b/u',
        '/' . $claveNegocio . '\s+del?\s+a[nñ]o\b/u',
        // Otras formas frecuentes
        '/cu[aá]nto\s+hemos\s+ganado/u',
        '/cu[aá]nto\s+hemos\s+sacado/u',
        '/cu[aá]nto\s+sacamos/u',
        '/cu[aá]nto\s+queda\s+de\s+beneficio/u',
        '/cu[aá]nto\s+nos\s+queda/u',
        '/cu[aá]nto\s+entra\s+de\s+beneficio/u',
        '/cu[aá]nto\s+dinero\s+hemos\s+ganado/u',
        '/cu[aá]l\s+es\s+(la\s+)?' . $claveNegocio . '/u',
        '/c[oó]mo\s+van\s+(los\s+)?(beneficios|utilidades|ganancias)/u',
        '/c[oó]mo\s+va\s+(la\s+)?' . $claveNegocio . '/u',
        '/qu[eé]\s+' . $claveNegocio . '\s+(tenemos|hay|tuvimos|sacamos)/u',
        '/dame\s+(la\s+|el\s+)?' . $claveNegocio . '/u',
        '/s[aá]came\s+(la\s+|el\s+)?' . $claveNegocio . '/u',
        '/calcula(me)?\s+(la\s+|el\s+)?' . $claveNegocio . '/u',
        '/quiero\s+(saber\s+)?(la\s+|el\s+)?' . $claveNegocio . '/u',
        '/necesito\s+(la\s+|el\s+)?' . $claveNegocio . '/u',
        '/resultado\s+(de\s+la\s+)?(tienda|sucursal|mes|negocio)/u',
        '/balance\s+(de\s+)?' . $claveNegocio . '/u',
        '/lo\s+que\s+hemos\s+ganado/u',
        '/lo\s+que\s+ganamos/u',
        '/cu[aá]nto\s+beneficio\s+(hay|tenemos|tuvimos|sacamos)/u',
        '/cu[aá]nta\s+utilidad\s+(hay|tenemos|tuvimos|sacamos)/u',
        '/cu[aá]nta\s+ganancia\s+(hay|tenemos|tuvimos|sacamos)/u',
    );
    foreach ($frases as $re) {
        if (preg_match($re, $p)) {
            return true;
        }
    }

    return false;
}

/**
 * @return array<string, int>
 */
function ia_utilidad_mapa_meses() {
    return array(
        'enero' => 1,
        'ene' => 1,
        'febrero' => 2,
        'feb' => 2,
        'marzo' => 3,
        'mar' => 3,
        'abril' => 4,
        'abr' => 4,
        'mayo' => 5,
        'junio' => 6,
        'jun' => 6,
        'julio' => 7,
        'jul' => 7,
        'agosto' => 8,
        'ago' => 8,
        'septiembre' => 9,
        'setiembre' => 9,
        'sep' => 9,
        'sept' => 9,
        'octubre' => 10,
        'oct' => 10,
        'noviembre' => 11,
        'nov' => 11,
        'diciembre' => 12,
        'dic' => 12,
    );
}

/**
 * @param int $mes
 * @return string
 */
function ia_utilidad_nombre_mes($mes) {
    $nombres = array(
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',
    );
    $mes = (int) $mes;
    return isset($nombres[$mes]) ? $nombres[$mes] : '';
}

/**
 * Extrae un año (YYYY) de un trozo de texto, o null.
 *
 * @param string $texto
 * @return int|null
 */
function ia_utilidad_extraer_anio($texto) {
    if (preg_match('/\b(20\d{2})\b/', (string) $texto, $m)) {
        return (int) $m[1];
    }
    return null;
}

/**
 * Parsea un trozo de texto a Y-m-d. Acepta dd/mm/yyyy, yyyy-mm-dd, "15 de marzo [de 2025]".
 *
 * @param string   $texto
 * @param int|null $anioDefault
 * @return string|null
 */
function ia_utilidad_parse_fecha_texto($texto, $anioDefault = null) {
    $texto = trim((string) $texto);
    $texto = preg_replace('/^(el|la|del?|al?)\s+/ui', '', $texto);
    $texto = trim((string) $texto);
    if ($texto === '') {
        return null;
    }

    $anioDefault = $anioDefault !== null ? (int) $anioDefault : (int) date('Y');
    $mapa = ia_utilidad_mapa_meses();

    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $texto, $m)) {
        $y = (int) $m[1];
        $mo = (int) $m[2];
        $d = (int) $m[3];
        if (checkdate($mo, $d, $y)) {
            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }
        return null;
    }

    if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $texto, $m)) {
        $d = (int) $m[1];
        $mo = (int) $m[2];
        $y = (int) $m[3];
        if (checkdate($mo, $d, $y)) {
            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }
        return null;
    }

    if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})$/', $texto, $m)) {
        $d = (int) $m[1];
        $mo = (int) $m[2];
        if (checkdate($mo, $d, $anioDefault)) {
            return sprintf('%04d-%02d-%02d', $anioDefault, $mo, $d);
        }
        return null;
    }

    $mesesAlt = implode('|', array_keys($mapa));
    if (preg_match('/^(\d{1,2})\s+de\s+(' . $mesesAlt . ')(?:\s+(?:de\s+)?(\d{4}))?$/ui', $texto, $m)) {
        $d = (int) $m[1];
        $clave = function_exists('mb_strtolower') ? mb_strtolower($m[2], 'UTF-8') : strtolower($m[2]);
        $mo = isset($mapa[$clave]) ? (int) $mapa[$clave] : 0;
        $y = !empty($m[3]) ? (int) $m[3] : $anioDefault;
        if ($mo > 0 && checkdate($mo, $d, $y)) {
            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }
        return null;
    }

    return null;
}

/**
 * Formatea dd/mm/YYYY para etiquetas.
 *
 * @param string $ymd
 * @return string
 */
function ia_utilidad_fmt_fecha_etiqueta($ymd) {
    $ts = strtotime($ymd);
    if ($ts === false) {
        return $ymd;
    }
    return date('d/m/Y', $ts);
}

/**
 * @return array{desde: string, hasta: string, etiqueta: string}
 */
function ia_utilidad_detectar_periodo($pregunta) {
    $p = function_exists('mb_strtolower') ? mb_strtolower(trim($pregunta), 'UTF-8') : strtolower(trim($pregunta));
    $hoy = date('Y-m-d');
    $anioActual = (int) date('Y');
    $mesActual = (int) date('n');
    $mapa = ia_utilidad_mapa_meses();
    $mesesAlt = implode('|', array_keys($mapa));

    // 1) Rango entre dos fechas
    $patronesRango = array(
        '/\bentre\s+(?:el\s+)?(.+?)\s+y\s+(?:el\s+)?(.+?)(?=\s*$|\s+[¿?]|\s+de\s+la\s|\s+en\s+la\s|\s+para\s|\s+por\s)/u',
        '/\bentre\s+(?:el\s+)?(.+?)\s+y\s+(?:el\s+)?(.+)$/u',
        '/\bdel?\s+(?:d[ií]a\s+)?(?:el\s+)?(.+?)\s+al?\s+(?:d[ií]a\s+)?(?:el\s+)?(.+?)(?=\s*$|\s+[¿?]|\s+de\s+la\s|\s+en\s+la\s)/u',
        '/\bdel?\s+(?:d[ií]a\s+)?(?:el\s+)?(.+?)\s+al?\s+(?:d[ií]a\s+)?(?:el\s+)?(.+)$/u',
        '/\bdesde\s+(?:el\s+)?(.+?)\s+hasta\s+(?:el\s+)?(.+?)(?=\s*$|\s+[¿?])/u',
        '/\bdesde\s+(?:el\s+)?(.+?)\s+hasta\s+(?:el\s+)?(.+)$/u',
    );
    foreach ($patronesRango as $pat) {
        if (!preg_match($pat, $p, $mRango)) {
            continue;
        }
        $t1 = trim($mRango[1]);
        $t2 = trim($mRango[2]);
        $anioRango = ia_utilidad_extraer_anio($t1 . ' ' . $t2);
        $anioParaFechas = $anioRango !== null ? $anioRango : $anioActual;

        $f1 = ia_utilidad_parse_fecha_texto($t1, $anioParaFechas);
        $f2 = ia_utilidad_parse_fecha_texto($t2, $anioParaFechas);
        if ($f1 !== null && $f2 !== null) {
            $desde = min($f1, $f2);
            $hasta = max($f1, $f2);
            return array(
                'desde' => $desde,
                'hasta' => $hasta,
                'etiqueta' => 'del ' . ia_utilidad_fmt_fecha_etiqueta($desde) . ' al ' . ia_utilidad_fmt_fecha_etiqueta($hasta),
            );
        }
        // Rango entre dos meses: "entre enero y marzo [de 2025]"
        if (preg_match('/^(' . $mesesAlt . ')(?:\s+(?:de\s+)?(\d{4}))?$/u', $t1, $mm1)
            && preg_match('/^(' . $mesesAlt . ')(?:\s+(?:de\s+)?(\d{4}))?$/u', $t2, $mm2)) {
            $mo1 = (int) $mapa[$mm1[1]];
            $mo2 = (int) $mapa[$mm2[1]];
            $y1 = !empty($mm1[2]) ? (int) $mm1[2] : null;
            $y2 = !empty($mm2[2]) ? (int) $mm2[2] : null;
            if ($y1 === null && $y2 === null) {
                $y1 = $anioActual;
                $y2 = $anioActual;
            } elseif ($y1 === null) {
                $y1 = $y2;
            } elseif ($y2 === null) {
                $y2 = $y1;
            }
            $desde = sprintf('%04d-%02d-01', $y1, $mo1);
            $hasta = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $y2, $mo2)));
            if ($desde > $hasta) {
                $desde = sprintf('%04d-%02d-01', $y2, $mo2);
                $hasta = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $y1, $mo1)));
                $etiqDesde = ia_utilidad_nombre_mes($mo2) . ' ' . $y2;
                $etiqHasta = ia_utilidad_nombre_mes($mo1) . ' ' . $y1;
            } else {
                $etiqDesde = ia_utilidad_nombre_mes($mo1) . ' ' . $y1;
                $etiqHasta = ia_utilidad_nombre_mes($mo2) . ' ' . $y2;
            }
            return array(
                'desde' => $desde,
                'hasta' => $hasta,
                'etiqueta' => 'de ' . $etiqDesde . ' a ' . $etiqHasta,
            );
        }
    }

    // 2) Fecha concreta (una sola)
    if (preg_match('/\b(\d{4}-\d{2}-\d{2})\b/u', $p, $mFecha)
        || preg_match('/\b(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{4})\b/u', $p, $mFecha)
        || preg_match('/\b(\d{1,2}\s+de\s+(?:' . $mesesAlt . ')(?:\s+(?:de\s+)?\d{4})?)\b/u', $p, $mFecha)) {
        $f = ia_utilidad_parse_fecha_texto($mFecha[1], $anioActual);
        if ($f !== null) {
            return array(
                'desde' => $f,
                'hasta' => $f,
                'etiqueta' => 'el ' . ia_utilidad_fmt_fecha_etiqueta($f),
            );
        }
    }

    // 3) Mes concreto por nombre ("enero", "marzo 2025", "mes de febrero")
    // Excluir abreviaturas cortas ambiguas salvo que vayan como mes explícito
    $mesesLargos = 'enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre';
    if (preg_match('/\b(?:mes\s+de\s+|en\s+(?:el\s+mes\s+de\s+)?|de\s+|del\s+)?(' . $mesesLargos . ')(?:\s+(?:de\s+)?(\d{4}))?\b/u', $p, $mMes)) {
        $clave = $mMes[1];
        $mo = (int) $mapa[$clave];
        if (!empty($mMes[2])) {
            $y = (int) $mMes[2];
        } else {
            // Si el mes aún no ha llegado este año, asumir el del año anterior
            $y = ($mo > $mesActual) ? ($anioActual - 1) : $anioActual;
        }
        $desde = sprintf('%04d-%02d-01', $y, $mo);
        $hasta = date('Y-m-t', strtotime($desde));
        if ($y === $anioActual && $mo === $mesActual && $hasta > $hoy) {
            $hasta = $hoy;
        }
        return array(
            'desde' => $desde,
            'hasta' => $hasta,
            'etiqueta' => ia_utilidad_nombre_mes($mo) . ' de ' . $y,
        );
    }

    // 4) Hoy / ayer
    if (preg_match('/\bhoy\b/u', $p)) {
        return array('desde' => $hoy, 'hasta' => $hoy, 'etiqueta' => 'hoy');
    }
    if (preg_match('/\bayer\b/u', $p)) {
        $ayer = date('Y-m-d', strtotime('-1 day'));
        return array('desde' => $ayer, 'hasta' => $ayer, 'etiqueta' => 'ayer');
    }

    // 5) Año pasado / año actual / año N / este año
    if (preg_match('/\ba[nñ]o\s+pasado\b|\bel\s+a[nñ]o\s+pasado\b/u', $p)) {
        $y = $anioActual - 1;
        return array(
            'desde' => $y . '-01-01',
            'hasta' => $y . '-12-31',
            'etiqueta' => 'el año ' . $y,
        );
    }
    if (preg_match('/\beste\s+a[nñ]o\b|\ba[nñ]o\s+actual\b|\bel\s+a[nñ]o\s+actual\b/u', $p)) {
        return array(
            'desde' => $anioActual . '-01-01',
            'hasta' => $hoy,
            'etiqueta' => 'este año (' . $anioActual . ')',
        );
    }
    if (preg_match('/\ba[nñ]o\s+(\d{4})\b|\ben\s+el\s+a[nñ]o\s+(\d{4})\b|\ben\s+(\d{4})\b/u', $p, $mAnio)) {
        $y = (int) (!empty($mAnio[1]) ? $mAnio[1] : (!empty($mAnio[2]) ? $mAnio[2] : $mAnio[3]));
        if ($y >= 2000 && $y <= ($anioActual + 1)) {
            $hasta = ($y === $anioActual) ? $hoy : ($y . '-12-31');
            return array(
                'desde' => $y . '-01-01',
                'hasta' => $hasta,
                'etiqueta' => 'el año ' . $y,
            );
        }
    }

    // 6) Mes pasado / mes actual / este mes
    if (preg_match('/\bmes\s+pasado\b|\bel\s+mes\s+pasado\b/u', $p)) {
        $desde = date('Y-m-01', strtotime('first day of last month'));
        $hasta = date('Y-m-t', strtotime('last day of last month'));
        return array(
            'desde' => $desde,
            'hasta' => $hasta,
            'etiqueta' => 'el mes pasado (' . ia_utilidad_nombre_mes((int) date('n', strtotime($desde))) . ' ' . date('Y', strtotime($desde)) . ')',
        );
    }
    if (preg_match('/\beste\s+mes\b|\bmes\s+actual\b|\bel\s+mes\s+actual\b/u', $p)) {
        return array(
            'desde' => date('Y-m-01'),
            'hasta' => $hoy,
            'etiqueta' => 'este mes (' . ia_utilidad_nombre_mes($mesActual) . ' ' . $anioActual . ')',
        );
    }

    // Por defecto: mes actual
    return array(
        'desde' => date('Y-m-01'),
        'hasta' => $hoy,
        'etiqueta' => 'este mes (' . ia_utilidad_nombre_mes($mesActual) . ' ' . $anioActual . ')',
    );
}

/**
 * @return array{ids: int[], etiqueta: string}
 */
function ia_utilidad_detectar_sucursales($conexion, $pregunta) {
    $p = function_exists('mb_strtolower') ? mb_strtolower(trim($pregunta), 'UTF-8') : strtolower(trim($pregunta));

    $res = mysqli_query($conexion, 'SELECT id_sucursal, nombre_sucursal, nombre_corto FROM sucursal ORDER BY nombre_sucursal ASC');
    $todas = array();
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $todas[] = $row;
        }
        mysqli_free_result($res);
    }

    $idsAll = array();
    foreach ($todas as $row) {
        $idsAll[] = (int) $row['id_sucursal'];
    }
    $etiquetaTodas = 'todas las sucursales';

    // Explícito: todas / todo / todas las tiendas → no buscar nombre concreto
    if (preg_match('/\b(todas(\s+las)?\s+sucursales|todas(\s+las)?\s+tiendas|de\s+todo|en\s+todo|todas)\b/u', $p)) {
        return array('ids' => $idsAll, 'etiqueta' => $etiquetaTodas);
    }

    $ids = array();
    $nombres = array();
    foreach ($todas as $row) {
        $nom = function_exists('mb_strtolower')
            ? mb_strtolower(trim((string) $row['nombre_sucursal']), 'UTF-8')
            : strtolower(trim((string) $row['nombre_sucursal']));
        $corto = function_exists('mb_strtolower')
            ? mb_strtolower(trim((string) (isset($row['nombre_corto']) ? $row['nombre_corto'] : '')), 'UTF-8')
            : strtolower(trim((string) (isset($row['nombre_corto']) ? $row['nombre_corto'] : '')));
        if ($nom !== '' && strpos($p, $nom) !== false) {
            $ids[] = (int) $row['id_sucursal'];
            $nombres[] = $row['nombre_sucursal'];
            continue;
        }
        if ($corto !== '' && strlen($corto) >= 3 && strpos($p, $corto) !== false) {
            $ids[] = (int) $row['id_sucursal'];
            $nombres[] = $row['nombre_sucursal'];
        }
    }

    // Sucursal concreta mencionada
    if (!empty($ids)) {
        return array(
            'ids' => array_values(array_unique($ids)),
            'etiqueta' => count($nombres) === 1 ? $nombres[0] : implode(', ', $nombres),
        );
    }

    // Sin sucursal en la pregunta (solo «utilidad», «beneficio de marzo»…) → todas
    return array('ids' => $idsAll, 'etiqueta' => $etiquetaTodas);
}

function ia_utilidad_placeholders_in($n) {
    return implode(',', array_fill(0, max(1, (int) $n), '?'));
}

function ia_utilidad_bind_ids_types($ids) {
    return str_repeat('i', count($ids));
}

/**
 * Ejecuta el cálculo de utilidad en el orden indicado (varias consultas).
 * @return array{texto: string, raw: string}|false
 */
function ia_respuesta_utilidad_negocio($conexion, $pregunta) {
    $periodo = ia_utilidad_detectar_periodo($pregunta);
    $sucu = ia_utilidad_detectar_sucursales($conexion, $pregunta);
    $ids = isset($sucu['ids']) ? $sucu['ids'] : array();
    if (empty($ids)) {
        return array(
            'texto' => nl2br(htmlspecialchars('No he encontrado sucursales para calcular la utilidad.')),
            'raw'   => 'No he encontrado sucursales para calcular la utilidad.',
        );
    }

    $desde = $periodo['desde'];
    $hasta = $periodo['hasta'];
    $in = ia_utilidad_placeholders_in(count($ids));
    $typesIds = ia_utilidad_bind_ids_types($ids);

    // 1) Gastos
    $sql1 = "SELECT COALESCE(SUM(total_gasto), 0) AS total
             FROM gastos
             WHERE DATE(fecha_gasto) BETWEEN ? AND ?
               AND sucursal_gasto IN ($in)
               AND estado_gasto != 'cancelado'";
    $p1 = ia_utilidad_scalar_sum($conexion, $sql1, 'ss' . $typesIds, array_merge(array($desde, $hasta), $ids));

    // 2) Renovaciones cobradas sin IVA (importe / 1.21)
    $sql2 = "SELECT COALESCE(SUM(importe_renovacion), 0) AS total
             FROM historico_renovaciones_gobal
             WHERE estado_historico = 'Renovado'
               AND DATE(fecha_renovacion) BETWEEN ? AND ?
               AND sucursal_id IN ($in)";
    $renovBruto = ia_utilidad_scalar_sum($conexion, $sql2, 'ss' . $typesIds, array_merge(array($desde, $hasta), $ids));
    $p2 = $renovBruto / 1.21;

    // 3) Ventas (importe)
    $sql3 = "SELECT COALESCE(SUM(precio), 0) AS total
             FROM ventas
             WHERE DATE(fecha) BETWEEN ? AND ?
               AND id_sucursal IN ($in)
               AND estado != 'anulada'";
    $p3 = ia_utilidad_scalar_sum($conexion, $sql3, 'ss' . $typesIds, array_merge(array($desde, $hasta), $ids));

    // 4) Coste artículos vendidos
    $sql4 = "SELECT COALESCE(SUM(precio_coste), 0) AS total
             FROM articulos_venta
             WHERE DATE(fecha_vendido) BETWEEN ? AND ?
               AND estado = 'vendido'
               AND id_sucursal_destino IN ($in)";
    $p4 = ia_utilidad_scalar_sum($conexion, $sql4, 'ss' . $typesIds, array_merge(array($desde, $hasta), $ids));

    // 5) Compras (lotes compra_opcion=no): peso + precio_compra
    $sql5 = "SELECT COALESCE(SUM(peso), 0) AS total_peso,
                    COALESCE(SUM(precio_compra), 0) AS total_euros
             FROM lotes_joyeria
             WHERE compra_opcion = 'no'
               AND DATE(fecha_compra) BETWEEN ? AND ?
               AND sucursal IN ($in)
               AND estado_lote != 'anulado'";
    $row5 = ia_utilidad_fetch_assoc($conexion, $sql5, 'ss' . $typesIds, array_merge(array($desde, $hasta), $ids));
    $p5_peso = $row5 ? (float) $row5['total_peso'] : 0.0;
    $p5_euros = $row5 ? (float) $row5['total_euros'] : 0.0;

    // 6) Valor metal: ley 0.725 × fixing 24k × gramos comprados
    //    gramos_fino = gramos × 0.725
    //    precio_fino = 0.725 × precio_fixing (precio_gramo_24k)
    //    valor_metal = precio_fino × gramos comprados
    $leyFina = 0.725;
    $sql6 = 'SELECT precio_gramo_24k, fecha_registro
             FROM precios_oro
             ORDER BY fecha_registro DESC, id DESC
             LIMIT 1';
    $res6 = mysqli_query($conexion, $sql6);
    $row6 = $res6 ? mysqli_fetch_assoc($res6) : null;
    if ($res6) {
        mysqli_free_result($res6);
    }
    $precioFixing = $row6 ? (float) $row6['precio_gramo_24k'] : 0.0;
    $gramosFino = $p5_peso * $leyFina;
    $precioFino = $leyFina * $precioFixing;
    $p6 = $precioFino * $p5_peso;

    // 7) Ingresos / valor
    $p7 = $p6 + $p3 + $p2;

    // 8) Costes
    $p8 = $p5_euros + $p4 + $p1;

    // 9) Utilidad
    $p9 = $p7 - $p8;

    $etiqueta = $sucu['etiqueta'];
    $periodoEt = $periodo['etiqueta'];

    $raw = 'La utilidad de ' . $etiqueta . ' (' . $periodoEt . ') es de ' . ia_fmt_euros($p9) . ".\n\n"
        . "Desglose:\n"
        . '1) Gastos: ' . ia_fmt_euros($p1) . "\n"
        . '2) Renovaciones sin IVA (bruto ' . ia_fmt_euros($renovBruto) . ' / 1,21): ' . ia_fmt_euros($p2) . "\n"
        . '3) Ventas (importe): ' . ia_fmt_euros($p3) . "\n"
        . '4) Coste artículos vendidos: ' . ia_fmt_euros($p4) . "\n"
        . '5) Compras metal: peso ' . ia_fmt_gramos($p5_peso) . ', pagado ' . ia_fmt_euros($p5_euros) . "\n"
        . '6) Valor metal: gramos fino ' . ia_fmt_gramos($gramosFino)
        . ' (×0,725), precio fino ' . ia_fmt_euros($precioFino, 2) . '/g'
        . ' (0,725 × fixing ' . ia_fmt_euros($precioFixing, 2) . '/g),'
        . ' importe ' . ia_fmt_euros($precioFino, 2) . ' × ' . ia_fmt_gramos($p5_peso)
        . ' = ' . ia_fmt_euros($p6) . "\n"
        . '7) Valor/ingresos (6+3+2): ' . ia_fmt_euros($p7) . "\n"
        . '8) Costes (compras+coste artículos+gastos): ' . ia_fmt_euros($p8) . "\n"
        . '9) Utilidad (7−8): ' . ia_fmt_euros($p9);

    return array(
        'texto' => nl2br(htmlspecialchars($raw)),
        'raw'   => $raw,
    );
}

function ia_utilidad_scalar_sum($conexion, $sql, $types, $params) {
    $row = ia_utilidad_fetch_assoc($conexion, $sql, $types, $params);
    if (!$row || !isset($row['total'])) {
        return 0.0;
    }
    return (float) $row['total'];
}

function ia_utilidad_fetch_assoc($conexion, $sql, $types, $params) {
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return null;
    }
    // bind dinámico
    $refs = array();
    $refs[] = $types;
    foreach ($params as $k => $v) {
        $refs[] = &$params[$k];
    }
    call_user_func_array(array($stmt, 'bind_param'), $refs);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return $row;
}
