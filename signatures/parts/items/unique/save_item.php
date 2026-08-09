<?php
// Incluir archivos necesarios
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Verificar que sea una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['error' => 'Petición inválida']);
    exit;
}

/**
 * Función para calcular la siguiente posición disponible en el menú
 * Busca en el rango de 1 a 200 la primera posición disponible
 */
function calcular_siguiente_posicion_menu($conexion) {
    // Buscar la primera posición disponible en el rango 1-200
    for ($pos = 1; $pos <= 200; $pos++) {
        $query = "SELECT COUNT(*) as total FROM itemsSections WHERE position_menu = ? AND in_menu = 'true'";
        $stmt = mysqli_prepare($conexion, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $pos);
            mysqli_stmt_execute($stmt);
            
            // Compatible con PHP 7.0
            mysqli_stmt_store_result($stmt);
            mysqli_stmt_bind_result($stmt, $total);
            mysqli_stmt_fetch($stmt);
            
            mysqli_stmt_close($stmt);
            
            // Si no hay items en esta posición, está disponible
            if ($total == 0) {
                return $pos;
            }
        }
    }
    
    // Si no se encuentra ninguna posición disponible, retornar 201 (fuera del rango)
    return 201;
}

/**
 * Función para crear los archivos raíz de un módulo CRUD
 * Crea los archivos principales en el directorio raíz
 */
function crear_archivos_raiz_crud($itemName, $nombre_singular) {
    // 1. Archivo principal (listar) - ej: usuarios.php
    $archivo_principal = "../../../" . $itemName . ".php";
    if (!file_exists($archivo_principal)) {
        $contenido_principal = '<?php
require_once \'include/session.php\';
require_once \'parts/universal/main_files.php\';
?>';
        file_put_contents($archivo_principal, $contenido_principal);
        error_log("Archivo principal creado: " . $archivo_principal);
    }
    
    // 2. Archivo crear - ej: crear_usuario.php
    $archivo_crear = "../../../crear_" . $nombre_singular . ".php";
    if (!file_exists($archivo_crear)) {
        $contenido_crear = '<?php
require_once \'include/session.php\';
require_once \'parts/universal/main_files.php\';
?>';
        file_put_contents($archivo_crear, $contenido_crear);
        error_log("Archivo crear creado: " . $archivo_crear);
    }
    
    // 3. Archivo editar - ej: editar_usuario.php
    $archivo_editar = "../../../editar_" . $nombre_singular . ".php";
    if (!file_exists($archivo_editar)) {
        $contenido_editar = '<?php
require_once \'include/session.php\';
require_once \'parts/universal/main_files.php\';
?>';
        file_put_contents($archivo_editar, $contenido_editar);
        error_log("Archivo editar creado: " . $archivo_editar);
    }
    
    // 4. Archivo singular - ej: usuario.php
    $archivo_singular = "../../../" . $nombre_singular . ".php";
    if (!file_exists($archivo_singular)) {
        $contenido_singular = '<?php
require_once \'include/session.php\';
require_once \'parts/universal/main_files.php\';
?>';
        file_put_contents($archivo_singular, $contenido_singular);
        error_log("Archivo singular creado: " . $archivo_singular);
    }
    
    error_log("Archivos raíz CRUD creados para: $itemName (singular: $nombre_singular)");
    
    // 5. Crear archivo de documentación de estructura CRUD específica
    crear_documentacion_estructura_crud($itemName, $nombre_singular);
    
    // 6. Procesar archivo SQL si se subió uno
    procesar_archivo_sql($itemName, $nombre_singular);
}

/**
 * Función para procesar y guardar el archivo SQL subido
 * Guarda el archivo SQL en la carpeta del módulo y actualiza la documentación
 */
function procesar_archivo_sql($itemName, $nombre_singular) {
    // Verificar si se subió un archivo SQL
    if (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
        error_log("No se subió archivo SQL o hubo error en la subida");
        return;
    }
    
    $archivo_sql = $_FILES['sql_file'];
    
    // Validar que sea un archivo SQL
    $extension = strtolower(pathinfo($archivo_sql['name'], PATHINFO_EXTENSION));
    if ($extension !== 'sql') {
        error_log("El archivo no es un archivo SQL válido");
        return;
    }
    
    // Crear la carpeta del módulo si no existe
    $carpeta_modulo = "../../../parts/" . $itemName;
    if (!is_dir($carpeta_modulo)) {
        mkdir($carpeta_modulo, 0755, true);
    }
    
    // Generar nombre del archivo SQL
    $nombre_archivo_sql = $itemName . "_table.sql";
    $ruta_archivo_sql = $carpeta_modulo . "/" . $nombre_archivo_sql;
    
    // Mover el archivo subido a la carpeta del módulo
    if (move_uploaded_file($archivo_sql['tmp_name'], $ruta_archivo_sql)) {
        error_log("Archivo SQL guardado: " . $ruta_archivo_sql);
        
        // Actualizar la documentación para incluir la referencia al archivo SQL
        actualizar_documentacion_con_sql($itemName, $nombre_singular, $nombre_archivo_sql);
    } else {
        error_log("Error al guardar el archivo SQL");
    }
}

/**
 * Función para actualizar la documentación con la referencia al archivo SQL
 */
function actualizar_documentacion_con_sql($itemName, $nombre_singular, $nombre_archivo_sql) {
    $archivo_doc = "../../../parts/" . $itemName . "/ESTRUCTURA_CRUD.md";
    
    if (file_exists($archivo_doc)) {
        $contenido = file_get_contents($archivo_doc);
        
        // Agregar sección de archivo SQL después de la sección de archivos raíz
        $seccion_sql = "

## Archivo SQL de la Tabla
El módulo incluye el archivo SQL con la estructura de la tabla MySQL:

### Archivo SQL:
- **Nombre**: `" . $nombre_archivo_sql . "`
- **Ubicación**: `parts/" . $itemName . "/" . $nombre_archivo_sql . "`
- **Descripción**: Estructura de la tabla MySQL para el módulo **" . ucfirst($itemName) . "**

### Uso del Archivo SQL:
Este archivo contiene la definición completa de la tabla de base de datos, incluyendo:
- Estructura de la tabla (columnas, tipos de datos, restricciones)
- Índices y claves primarias
- Relaciones con otras tablas (foreign keys)
- Valores por defecto y restricciones

### Importar la Tabla:
Para crear la tabla en la base de datos, ejecuta el contenido del archivo SQL:
```sql
-- Ejecutar el contenido de " . $nombre_archivo_sql . "
-- en tu cliente MySQL o herramienta de administración de base de datos
```";
        
        // Insertar la sección después de la sección de archivos raíz
        $posicion = strpos($contenido, "## Estructura de Carpetas en parts/");
        if ($posicion !== false) {
            $contenido_actualizado = substr($contenido, 0, $posicion) . $seccion_sql . "\n\n" . substr($contenido, $posicion);
            file_put_contents($archivo_doc, $contenido_actualizado);
            error_log("Documentación actualizada con referencia al archivo SQL");
        }
    }
}

/**
 * Función para crear la documentación de estructura CRUD específica del módulo
 * Crea un archivo ESTRUCTURA_CRUD.md en la carpeta del módulo
 */
function crear_documentacion_estructura_crud($itemName, $nombre_singular) {
    // Crear la carpeta del módulo si no existe
    $carpeta_modulo = "../../../parts/" . $itemName;
    if (!is_dir($carpeta_modulo)) {
        mkdir($carpeta_modulo, 0755, true);
    }
    
    // Crear archivo de documentación
    $archivo_doc = $carpeta_modulo . "/ESTRUCTURA_CRUD.md";
    $contenido_doc = "# Estructura CRUD - " . ucfirst($itemName) . "

## Descripción
Este documento describe la estructura de archivos del módulo **" . ucfirst($itemName) . "** generada automáticamente por el sistema CRUD.

## Archivos Raíz (Directorio Principal)
Los siguientes archivos se crean automáticamente en el directorio raíz:

### Archivos del módulo **" . $itemName . "**:
```
" . $itemName . ".php                    # Archivo principal (listar)
crear_" . $nombre_singular . ".php              # Crear " . $nombre_singular . "
editar_" . $nombre_singular . ".php             # Editar " . $nombre_singular . "
" . $nombre_singular . ".php                    # Vista singular
```

### Contenido de cada archivo raíz:
```php
<?php
require_once 'include/session.php';
require_once 'parts/universal/main_files.php';
?>
```

## Estructura de Carpetas en parts/
```
parts/" . $itemName . "/
├── main/                     # Vista principal con estadísticas
│   ├── content.php
│   ├── css.php
│   └── javascript.php
├── crear/                    # Formulario de creación
│   ├── content.php
│   ├── css.php
│   ├── javascript.php
│   └── procesar_nuevo_" . $nombre_singular . ".php
├── editar/                   # Formulario de edición
│   ├── content.php
│   ├── css.php
│   ├── javascript.php
│   └── procesar_editar_" . $nombre_singular . ".php
└── listar/                   # Listado con DataTables
    ├── content.php
    ├── css.php
    ├── javascript.php
    ├── load_list.php
    └── tables-datatables-load.js
```

## Convenciones de Nomenclatura

### Archivos Raíz:
- **Módulo principal**: `" . $itemName . ".php`
- **Crear**: `crear_" . $nombre_singular . ".php`
- **Editar**: `editar_" . $nombre_singular . ".php`
- **Vista singular**: `" . $nombre_singular . ".php`

### Carpetas en parts/:
- **Carpeta principal**: `parts/" . $itemName . "/`
- **Subcarpetas**: `main/`, `crear/`, `editar/`, `listar/`

### Archivos de Procesamiento:
- **Crear**: `procesar_nuevo_" . $nombre_singular . ".php`
- **Editar**: `procesar_editar_" . $nombre_singular . ".php`

## Funcionalidad del Módulo

### Al acceder al módulo **" . $itemName . "**:
1. **Listar**: Muestra todos los registros en una tabla DataTables
2. **Crear**: Formulario para agregar nuevos " . $nombre_singular . "s
3. **Editar**: Formulario para modificar " . $nombre_singular . "s existentes
4. **Main**: Vista principal con estadísticas y resumen

### Archivos generados automáticamente:
- **content.php**: Estructura HTML básica con Bootstrap
- **css.php**: Comentario para CSS personalizado
- **javascript.php**: Comentario para JavaScript personalizado
- **load_list.php**: Carga de datos para DataTables (solo en listar/)
- **tables-datatables-load.js**: Configuración de DataTables (solo en listar/)

## Adaptación del Módulo
Los archivos generados automáticamente deben ser adaptados según las necesidades específicas del módulo **" . $itemName . "**:
- Definir la tabla de base de datos correspondiente
- Configurar los campos del formulario
- Personalizar la lógica de negocio
- Ajustar la interfaz de usuario

## Notas Técnicas
- Los archivos raíz solo contienen las inclusiones básicas
- La lógica específica se implementa en los archivos dentro de `parts/" . $itemName . "/`
- Los archivos de procesamiento (`procesar_*.php`) manejan las operaciones de base de datos

---
*Documento generado automáticamente por el sistema CRUD*
*Fecha de creación: " . date('Y-m-d H:i:s') . "*";

    file_put_contents($archivo_doc, $contenido_doc);
    error_log("Documentación CRUD creada: " . $archivo_doc);
}

/**
 * Función para crear la estructura de archivos para un item
 * Crea el archivo raíz y la carpeta con archivos básicos
 */
function crear_estructura_archivos($url_item, $typ_item_insert, $crear_archivos, $fhater) {
    // Crear archivo raíz solo si no existe
    $file_raiz_path = "../../../".$url_item.".php";
    if (!file_exists($file_raiz_path)) {
        $contenido_file_raiz = '<?php require_once \'include/session.php\'; require_once \'parts/universal/main_files.php\'; ?>';
        $file_raiz = fopen($file_raiz_path, "w");
        if ($file_raiz) {
            fwrite($file_raiz, $contenido_file_raiz);
            fclose($file_raiz);
            error_log("Archivo raíz creado: " . $file_raiz_path);
        }
    }

    // Solo crear estructura de archivos si se especifica
    if ($crear_archivos == 'true') {
        // Determinar la ruta de la carpeta
        if ($fhater != 'false' && !empty($fhater)) {
            $url_definida = '../../' . $fhater . '/'.$typ_item_insert;
        } else {
            $url_definida = '../../' . $url_item . '/'.$typ_item_insert;
        }


        if($typ_item_insert == "blank_page"){

            if ($fhater != 'false' && !empty($fhater)) {
                // Crear archivo raíz solo si no existe y par ablank page en universal para incluir items en la navbar
                $file_raiz_path_extras_nav_bar = "../../universal/extras_nav_bar_".$fhater.".php";
                if (!file_exists($file_raiz_path_extras_nav_bar)) {
                    $contenido_file_raiz_nav_bar = '<!-- AQUI PUEDE INCLUIR imtes  ala nav bar superior customizados para '.$url_item.' --> ';
                    $file_raiz_nav_bar = fopen($file_raiz_path_extras_nav_bar, "w");
                    if ($file_raiz_nav_bar) {
                        fwrite($file_raiz_nav_bar, $contenido_file_raiz_nav_bar);
                        fclose($file_raiz_nav_bar);
                        error_log("Archivo raíz creado: " . $file_raiz_path_extras_nav_bar);
                    }
                }
            } else {
                
                // Crear archivo raíz solo si no existe y par ablank page en universal para incluir items en la navbar
                $file_raiz_path_extras_nav_bar = "../../universal/extras_nav_bar_".$url_item.".php";
                if (!file_exists($file_raiz_path_extras_nav_bar)) {
                    $contenido_file_raiz_nav_bar = '<!-- AQUI PUEDE INCLUIR imtes  ala nav bar superior customizados para '.$url_item.' --> ';
                    $file_raiz_nav_bar = fopen($file_raiz_path_extras_nav_bar, "w");
                    if ($file_raiz_nav_bar) {
                        fwrite($file_raiz_nav_bar, $contenido_file_raiz_nav_bar);
                        fclose($file_raiz_nav_bar);
                        error_log("Archivo raíz creado: " . $file_raiz_path_extras_nav_bar);
                    }
                }

            }

            

        }
        
        // Crear la carpeta si no existe
        if (!is_dir($url_definida)) {
            if (mkdir($url_definida, 0755, true)) {
                error_log("Carpeta específica creada: " . $url_definida);
            } else {
                error_log("Error al crear carpeta: " . $url_definida);
                return false;
            }
        }

        $apertura_content = '<!-- Content -->
        <div class="container-fluid flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-12">
            <h4 class="mb-1">' . ucfirst($typ_item_insert) . ' ' . ucfirst($url_item) . '</h4>
            <p class="mb-4">
                Contenido para ' . $typ_item_insert . ' de ' . $url_item . '.
            </p>
            </div>
        </div>
        </div>
        <!-- / Content -->';

        // Crear content.php
        $content_path = $url_definida."/content.php";
        if (!file_exists($content_path)) {
            $content = fopen($content_path, "w");
            if ($content) {
                fwrite($content, $apertura_content);
                fclose($content);
                error_log("content.php creado en: " . $content_path);
            }
        }
        
        // Crear css.php
        $css_path = $url_definida."/css.php";
        if (!file_exists($css_path)) {
            $css = fopen($css_path, "w");
            if ($css) {
                fwrite($css, '<!-- CSS CUSTOM '.$url_item.' - '.$typ_item_insert.'  -->');
                fclose($css);
                error_log("css.php creado en: " . $css_path);
            }
        }

        // Crear javascript.php
        $javascript_path = $url_definida."/javascript.php";
        if (!file_exists($javascript_path)) {
            $javascript = fopen($javascript_path, "w");
            if ($javascript) {
                fwrite($javascript, '<!-- JAVASCRIPT CUSTOM '.$url_item.' - '.$typ_item_insert.'  -->');
                fclose($javascript);
                error_log("javascript.php creado en: " . $javascript_path);
            }
        }
    } else {
        // Solo crear carpeta principal si no existe
        $url_definida = '../../' . $url_item;
        if (!is_dir($url_definida)) {
            if (mkdir($url_definida, 0755, true)) {
                error_log("Carpeta principal creada: " . $url_definida);
            } else {
                error_log("Error al crear carpeta principal: " . $url_definida);
                return false;
            }
        }
    }
    
    return true;
}

try {
    // Log para debugging
    error_log("=== DEBUG SAVE ITEM ===");
    error_log("POST data: " . print_r($_POST, true));
    
    // Obtener datos del formulario
    $itemName = trim($_POST['itemName'] ?? '');
    $itemnameText = trim($_POST['itemnameText'] ?? '');
    $nombre_singular = trim($_POST['nombre_singular'] ?? '');
    $nombre_singular_text = trim($_POST['nombre_singular_text'] ?? '');
    $typ_item = trim($_POST['typ_item'] ?? '');
    $fhater_item = trim($_POST['fhater_item'] ?? '');
    $fhater_menu = trim($_POST['fhater_menu'] ?? '0');
    $state_item = $_POST['state_item'] ?? 'true';
    $in_menu = $_POST['in_menu'] ?? 'true';
    $url_item = trim($_POST['url_item'] ?? '');
    $icon_menu = trim($_POST['icon_menu'] ?? '');
    $position_menu = intval($_POST['position_menu'] ?? 1);
    $tabla_mysql_name = trim($_POST['tabla_mysql_name'] ?? '');
    $section_activa = trim($_POST['section_activa'] ?? 'central_section');
    $columnas_section = ['sucursal_section', 'central_section', 'recepcion_lotes_section', 'auditoria_section'];
    if (!in_array($section_activa, $columnas_section, true)) {
        $section_activa = 'central_section';
    }
    $sucursal_section_parset_item = 'false';
    $central_section_parset_item = 'false';
    $recepcion_lotes_section_parset_item = 'false';
    $auditoria_section_parset_item = 'false';
    if ($section_activa === 'sucursal_section') {
        $sucursal_section_parset_item = 'true';
    } elseif ($section_activa === 'central_section') {
        $central_section_parset_item = 'true';
    } elseif ($section_activa === 'recepcion_lotes_section') {
        $recepcion_lotes_section_parset_item = 'true';
    } elseif ($section_activa === 'auditoria_section') {
        $auditoria_section_parset_item = 'true';
    }
    $item_root_parset_item = trim($_POST['item_root'] ?? 'false');
    
    // Determinar si es un item CRUD basado en el tipo
    $item_crud = ($typ_item === 'crud') ? 'true' : 'false';
    $id = trim($_POST['id'] ?? '');
    
    // Log de datos procesados
    error_log("Datos procesados:");
    error_log("itemName: '$itemName'");
    error_log("itemnameText: '$itemnameText'");
    error_log("nombre_singular: '$nombre_singular'");
    error_log("nombre_singular_text: '$nombre_singular_text'");
    error_log("typ_item: '$typ_item'");
    error_log("fhater_item: '$fhater_item'");
    error_log("fhater_menu: '$fhater_menu'");
    error_log("state_item: '$state_item'");
    error_log("in_menu: '$in_menu'");
    error_log("url_item: '$url_item'");
    error_log("icon_menu: '$icon_menu'");
    error_log("position_menu: $position_menu");
    error_log("tabla_mysql_name: $tabla_mysql_name");
    error_log("sucursal_section_parset_item: $sucursal_section_parset_item");
    error_log("item_crud: '$item_crud'");
    error_log("id: '$id'");
    
    // Validar datos
    if (empty($itemName)) {
        throw new Exception('El nombre del item es obligatorio');
    }

    if (empty($itemnameText)) {
        throw new Exception('El nombre del item a mostrar en el menú es obligatorio');
    }
    
    if (empty($typ_item)) {
        throw new Exception('El tipo de item es obligatorio');
    }
    
    // Validar que el tipo sea válido
    $tipos_validos = ['unique', 'main', 'listar', 'editar', 'crear', 'delete', 'menu', 'crud', 'acces_special', 'edit', 'blank_page'];
    if (!in_array($typ_item, $tipos_validos)) {
        throw new Exception('El tipo de item no es válido');
    }
    
    // Validar que in_menu sea válido
    if (!in_array($in_menu, ['true', 'false'])) {
        throw new Exception('El valor de in_menu no es válido');
    }
    
    // Validar que position_menu sea un número válido
    if ($position_menu < 1) {
        throw new Exception('La posición en el menú debe ser mayor a 0');
    }

    // Después de obtener los datos del formulario
    if (empty($url_item)) {
        throw new Exception('La URL del item es obligatoria');
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    error_log("Conexión a BD exitosa");
    
    // Si in_menu es 'true', calcular la siguiente posición disponible en el rango 1-200
    if ($in_menu === 'true') {
        $position_menu = calcular_siguiente_posicion_menu($conexion);
        error_log("Posición calculada automáticamente: $position_menu");
    }
    
    // Verificar si es una inserción o actualización
    if (empty($id)) {
        // Si el tipo es 'crud', cambiar a 'listar' para el insert principal (mantiene nombre en plural)
        $typ_item_insert = ($typ_item === 'crud') ? 'listar' : $typ_item;
        
        // INSERT - Nuevo item
        $query = "INSERT INTO itemsSections (itemName, itemnameText, typ_item, fhater_item, fhater_menu, state_item, in_menu, url_item, icon_menu, position_menu, tabla_mysql_name, sucursal_section, central_section, recepcion_lotes_section, auditoria_section, item_root) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        error_log("Query INSERT: $query");
        
        $stmt = mysqli_prepare($conexion, $query);
        
        if (!$stmt) {
            error_log("Error en prepare: " . mysqli_error($conexion));
            throw new Exception('Error al preparar la consulta: ' . mysqli_error($conexion));
        }
        
        error_log("Prepare exitoso, ejecutando bind_param");
        mysqli_stmt_bind_param($stmt, 'sssssssssissssss', $itemName, $itemnameText, $typ_item_insert, $fhater_item, $fhater_menu, $state_item, $in_menu, $url_item, $icon_menu, $position_menu, $tabla_mysql_name, $sucursal_section_parset_item, $central_section_parset_item, $recepcion_lotes_section_parset_item, $auditoria_section_parset_item, $item_root_parset_item);
        error_log("bind_param exitoso, ejecutando execute");
        $resultado = mysqli_stmt_execute($stmt);
        
        if (!$resultado) {
            error_log("Error en execute: " . mysqli_stmt_error($stmt));
            throw new Exception('Error al insertar: ' . mysqli_stmt_error($stmt));
        }
        
        error_log("Execute exitoso, item insertado");
        
        $nuevo_id = mysqli_insert_id($conexion);
        $mensaje = 'Item creado correctamente';
        $tipo = 'insert';
        
        // Crear estructura de archivos para el item principal
        if ($typ_item_insert != 'crud' && $typ_item_insert != 'menu' && $typ_item_insert != 'acces_special' && $typ_item_insert != 'delete' && $typ_item_insert != 'edit') {
            crear_estructura_archivos($url_item, $typ_item_insert, 'false', 'false');
            crear_estructura_archivos($url_item, $typ_item_insert, 'true', 'false');
        }

        // Lógica CRUD - Si el tipo original es 'crud', crear los 4 items CRUD
        if ($typ_item === 'crud') {
            error_log("Creando estructura CRUD para item: $itemName");
            
            // Obtener nombre en singular del formulario o generar automáticamente
            if (!empty($nombre_singular)) {
                error_log("Usando nombre singular del formulario: $nombre_singular");
            } else {
                // Generar automáticamente (quitar 's' del final si existe)
                $nombre_singular = rtrim($itemName, 's');
                if (empty($nombre_singular)) {
                    $nombre_singular = $itemName; // Si solo tenía una letra, mantener el original
                }
                error_log("Nombre singular generado automáticamente: $nombre_singular");
            }
            
            // Crear archivos raíz automáticamente
            crear_archivos_raiz_crud($itemName, $nombre_singular);
                        
            // 2) INSERT - typ_item = 'main', state_item = 'true', nombre en singular, fhater_item = id del primero
            $query_crud2 = "INSERT INTO itemsSections (itemName, itemnameText, typ_item, fhater_item, state_item, url_item, sucursal_section, central_section, recepcion_lotes_section, auditoria_section) VALUES (?, ?, 'main', ?, 'true', ?, ?, ?, ?, ?)";
            $stmt_crud2 = mysqli_prepare($conexion, $query_crud2);
            if ($stmt_crud2) {
                mysqli_stmt_bind_param($stmt_crud2, 'ssisssss', $itemName, $nombre_singular_text, $nuevo_id, $nombre_singular, $sucursal_section_parset_item, $central_section_parset_item, $recepcion_lotes_section_parset_item, $auditoria_section_parset_item);
                mysqli_stmt_execute($stmt_crud2);
                $id_main = mysqli_insert_id($conexion);
                mysqli_stmt_close($stmt_crud2);
                error_log("Item CRUD 2 (main) creado con ID: $id_main");
                crear_estructura_archivos($nombre_singular, 'main', 'true', $itemName);
            }
            
            // 3) INSERT - typ_item = 'editar', state_item = 'true', nombre en singular + prefijo "editar_", fhater_item = id del primero
            $query_crud3 = "INSERT INTO itemsSections (itemName, itemnameText, typ_item, fhater_item, state_item, url_item, sucursal_section, central_section, recepcion_lotes_section, auditoria_section) VALUES (?, ?, 'editar', ?, 'true', ?, ?, ?, ?, ?)";
            $stmt_crud3 = mysqli_prepare($conexion, $query_crud3);
            if ($stmt_crud3) {
                $nombre_editar = "editar_" . $nombre_singular;
                $nombre_editar_text = "Editar " . $nombre_singular_text;
                mysqli_stmt_bind_param($stmt_crud3, 'ssisssss', $itemName, $nombre_editar_text, $nuevo_id, $nombre_editar, $sucursal_section_parset_item, $central_section_parset_item, $recepcion_lotes_section_parset_item, $auditoria_section_parset_item);
                mysqli_stmt_execute($stmt_crud3);
                $id_editar = mysqli_insert_id($conexion);
                mysqli_stmt_close($stmt_crud3);
                error_log("Item CRUD 3 (editar) creado con ID: $id_editar");
                crear_estructura_archivos($nombre_editar, 'editar', 'true', $itemName);
            }
            
            // 4) INSERT - typ_item = 'crear', state_item = 'true', nombre en singular + prefijo "crear_", fhater_item = id del primero
            $query_crud4 = "INSERT INTO itemsSections (itemName, itemnameText, typ_item, fhater_item, state_item, url_item, sucursal_section, central_section, recepcion_lotes_section, auditoria_section) VALUES (?, ?, 'crear', ?, 'true', ?, ?, ?, ?, ?)";
            $stmt_crud4 = mysqli_prepare($conexion, $query_crud4);
            if ($stmt_crud4) {
                $nombre_crear = "crear_" . $nombre_singular;
                $nombre_crear_text = "Crear " . $nombre_singular_text;
                mysqli_stmt_bind_param($stmt_crud4, 'ssisssss', $itemName, $nombre_crear_text, $nuevo_id, $nombre_crear, $sucursal_section_parset_item, $central_section_parset_item, $recepcion_lotes_section_parset_item, $auditoria_section_parset_item);
                mysqli_stmt_execute($stmt_crud4);
                $id_crear = mysqli_insert_id($conexion);
                mysqli_stmt_close($stmt_crud4);
                error_log("Item CRUD 4 (crear) creado con ID: $id_crear");
                crear_estructura_archivos($nombre_crear, 'crear', 'true', $itemName);
            }
            
            error_log("Estructura CRUD completa creada para item: $itemName");
        }
        
    } else {
        // UPDATE - Actualizar item existente
        $query = "UPDATE itemsSections SET 
        itemName = ?,
        itemnameText = ?, 
        typ_item = ?, 
        fhater_item = ?, 
        fhater_menu = ?, 
        state_item = ?, 
        in_menu = ?, 
        url_item = ?, 
        icon_menu = ?, 
        position_menu = ?,
        tabla_mysql_name = ?,
        sucursal_section = ?,
        central_section = ?,
        recepcion_lotes_section = ?,
        auditoria_section = ?,
        item_root = ?
        WHERE id_type_Item = ?
        ";
        error_log("Query UPDATE: $query");
        
        $stmt = mysqli_prepare($conexion, $query);
        
        if (!$stmt) {
            error_log("Error en prepare UPDATE: " . mysqli_error($conexion));
            throw new Exception('Error al preparar la consulta: ' . mysqli_error($conexion));
        }
        
        error_log("Prepare UPDATE exitoso, ejecutando bind_param");
        mysqli_stmt_bind_param($stmt, 'sssssssssissssssi', $itemName, $itemnameText, $typ_item, $fhater_item, $fhater_menu, $state_item, $in_menu, $url_item, $icon_menu, $position_menu, $tabla_mysql_name, $sucursal_section_parset_item, $central_section_parset_item, $recepcion_lotes_section_parset_item, $auditoria_section_parset_item, $item_root_parset_item, $id);
        error_log("bind_param UPDATE exitoso, ejecutando execute");
        $resultado = mysqli_stmt_execute($stmt);
        
        if (!$resultado) {
            error_log("Error en execute UPDATE: " . mysqli_stmt_error($stmt));
            throw new Exception('Error al actualizar: ' . mysqli_stmt_error($stmt));
        }
        
        error_log("Execute UPDATE exitoso, item actualizado");
        
        $nuevo_id = $id;
        $mensaje = 'Item actualizado correctamente';
        $tipo = 'update';
    }
    
    // Cerrar statement y conexión
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conexion);
    
    error_log("Proceso completado exitosamente. Tipo: $tipo, ID: $nuevo_id");
    
    // Devolver respuesta JSON exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'tipo' => $tipo,
        'id' => $nuevo_id,
        'itemName' => $itemName,
        'itemnameText' => $itemnameText
    ]);
    
} catch (Exception $e) {
    // En caso de error
    error_log("ERROR en save_item: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>
