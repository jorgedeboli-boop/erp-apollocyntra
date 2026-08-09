<?php
// Obtener parámetros
$idSucursal = isset($_GET['id']) ? intval($_GET['id']) : 0;
$nombreSucursal = isset($_GET['nombre']) ? htmlspecialchars($_GET['nombre']) : 'Sucursal';

if ($idSucursal === 0) {
    header('Location: index.php');
    exit;
}

// Variables para validación
$puedeCerrar = false;
$mensajeError = '';
$aperturasHoy = 0;
$ultimaApertura = null;
$tieneCierrePosterior = false;
$totalCaja = 0;

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Nombre de la tabla de movimientos
    $tableName = "movimientos_de_caja_" . $idSucursal;
    
    // Verificar si la tabla existe
    $tableCheck = mysqli_query($conexion, "SHOW TABLES LIKE '$tableName'");
    
    if (mysqli_num_rows($tableCheck) > 0) {
        // Buscar aperturas del día actual ordenadas por ID descendente (más reciente primero)
        $queryAperturas = "SELECT id_movimientos, fecha_apunte, hora_de_apunte, entrada 
                          FROM $tableName 
                          WHERE grupos = 'CAJA INICIO' 
                          AND fecha_apunte = CURDATE() 
                          ORDER BY id_movimientos DESC";
        $resultAperturas = mysqli_query($conexion, $queryAperturas);
        
        if ($resultAperturas) {
            $aperturasHoy = mysqli_num_rows($resultAperturas);
            
            if ($aperturasHoy > 0) {
                // Buscar la apertura que no tiene cierre posterior (apertura activa)
                while ($rowApertura = mysqli_fetch_assoc($resultAperturas)) {
                    $idApertura = $rowApertura['id_movimientos'];
                    
                    // Verificar si hay un cierre después de esta apertura (por ID mayor)
                    $queryCierrePost = "SELECT COUNT(*) as tiene_cierre 
                                       FROM $tableName 
                                       WHERE cierre_caja = 'true' 
                                       AND fecha_apunte = CURDATE() 
                                       AND id_movimientos > ?";
                    $stmtCierrePost = mysqli_prepare($conexion, $queryCierrePost);
                    mysqli_stmt_bind_param($stmtCierrePost, 'i', $idApertura);
                    mysqli_stmt_execute($stmtCierrePost);
                    $resultCierrePost = mysqli_stmt_get_result($stmtCierrePost);
                    $rowCierrePost = mysqli_fetch_assoc($resultCierrePost);
                    mysqli_stmt_close($stmtCierrePost);
                    
                    // Si no hay cierre posterior, esta es la apertura activa
                    if ($rowCierrePost['tiene_cierre'] == 0) {
                        $ultimaApertura = [
                            'id' => $idApertura,
                            'fecha' => $rowApertura['fecha_apunte'],
                            'hora' => $rowApertura['hora_de_apunte'],
                            'importe' => floatval($rowApertura['entrada'])
                        ];
                        $tieneCierrePosterior = false;
                        break;
                    } else {
                        $tieneCierrePosterior = true;
                    }
                }
                
                // Si encontramos una apertura activa (sin cierre posterior), se puede cerrar
                if ($ultimaApertura !== null && !$tieneCierrePosterior) {
                    $puedeCerrar = true;
                    
                    // Calcular el total de caja (entradas - salidas desde la última apertura por ID)
                    $idAperturaActiva = $ultimaApertura['id'];
                    
                    $queryTotalCaja = "SELECT 
                                        COALESCE(SUM(entrada), 0) as total_entradas,
                                        COALESCE(SUM(salida), 0) as total_salidas
                                      FROM $tableName 
                                      WHERE fecha_apunte = CURDATE() 
                                      AND id_movimientos >= ?";
                    $stmtTotalCaja = mysqli_prepare($conexion, $queryTotalCaja);
                    mysqli_stmt_bind_param($stmtTotalCaja, 'i', $idAperturaActiva);
                    mysqli_stmt_execute($stmtTotalCaja);
                    $resultTotalCaja = mysqli_stmt_get_result($stmtTotalCaja);
                    $rowTotalCaja = mysqli_fetch_assoc($resultTotalCaja);
                    mysqli_stmt_close($stmtTotalCaja);
                    
                    $totalCaja = floatval($rowTotalCaja['total_entradas']) - floatval($rowTotalCaja['total_salidas']);
                    
                } else {
                    $mensajeError = 'No se puede cerrar la caja. Todas las aperturas del día ya tienen cierre.';
                }
            } else {
                $mensajeError = 'No se puede cerrar la caja. No hay ninguna apertura registrada el día de hoy.';
            }
        }
    } else {
        $mensajeError = 'No existe tabla de movimientos para esta sucursal.';
    }
    
    mysqli_close($conexion);
    
} catch (Exception $e) {
    $mensajeError = 'Error al verificar estado de caja: ' . $e->getMessage();
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
?>

<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom card-header-forms">
                    <h4 class="card-title mb-0">Cierre de Caja - <?php echo $nombreSucursal; ?></h4>
                    <small class="text-muted">ID Sucursal: <?php echo $idSucursal; ?></small>
                    <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='estados_cajas.php'">
                        <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Estados de Cajas
                    </button>
                </div>
                <div class="card-body pt-5">
                        
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <div class="text-end">
                                    <button type="button" class="btn btn-primary waves-effect waves-light" id="btnGuardarArqueo" disabled>
                                        <i class="icon-base ri ri-check-line me-2"></i>
                                        Guardar Arqueo
                                    </button>
                                    <button type="button" class="btn btn-success waves-effect waves-light" id="btnCerrarCaja" style="display: none;">
                                        <i class="icon-base ri ri-lock-line me-2"></i>
                                        Cerrar Caja
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-8">
                            <div class="card">
                <div class="card-content">
                    <div class="table-responsive text-nowrap">
                        <table class="table table-borderless" id="table_arqueo">
                            <thead>
                                <tr>
                                    <th>Efectivo</th>
                                    <th class="text-center">Unidades</th>
                                    <th class="text-center">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Billetes de 500 €</strong></td>
                                    <td class="text-center"><input type="number" class="unidades" id="billete500" data-valor="500" data-tipo="billete" value="0" min="0" tabindex="1"></td>
                                    <td class="text-center"><div class="total-linea-display">0.00 €</div></td>
                                </tr>
                                <tr>
                                    <td><strong>Billetes de 200 €</strong></td>
                                    <td class="text-center"><input type="number" class="unidades" id="billete200" data-valor="200" data-tipo="billete" value="0" min="0" tabindex="2"></td>
                                    <td class="text-center"><div class="total-linea-display">0.00 €</div></td>
                                </tr>
                                <tr>
                                    <td><strong>Billetes de 100 €</strong></td>
                                    <td class="text-center"><input type="number" class="unidades" id="billete100" data-valor="100" data-tipo="billete" value="0" min="0" tabindex="3"></td>
                                    <td class="text-center"><div class="total-linea-display">0.00 €</div></td>
                                </tr>
                                <tr>
                                    <td><strong>Billetes de 50 €</strong></td>
                                    <td class="text-center"><input type="number" class="unidades" id="billete50" data-valor="50" data-tipo="billete" value="0" min="0" tabindex="4"></td>
                                    <td class="text-center"><div class="total-linea-display">0.00 €</div></td>
                                </tr>
                                <tr>
                                    <td><strong>Billetes de 20 €</strong></td>
                                    <td class="text-center"><input type="number" class="unidades" id="billete20" data-valor="20" data-tipo="billete" value="0" min="0" tabindex="5"></td>
                                    <td class="text-center"><div class="total-linea-display">0.00 €</div></td>
                                </tr>
                                <tr>
                                    <td><strong>Billetes de 10 €</strong></td>
                                    <td class="text-center"><input type="number" class="unidades" id="billete10" data-valor="10" data-tipo="billete" value="0" min="0" tabindex="6"></td>
                                    <td class="text-center"><div class="total-linea-display">0.00 €</div></td>
                                </tr>
                                <tr>
                                    <td><strong>Billetes de 5 €</strong></td>
                                    <td class="text-center"><input type="number" class="unidades" id="billete5" data-valor="5" data-tipo="billete" value="0" min="0" tabindex="7"></td>
                                    <td class="text-center"><div class="total-linea-display">0.00 €</div></td>
                                </tr>
                                <tr>
                                    <td><strong>Monedas de 2 €</strong></td>
                                    <td class="text-center"><input type="number" class="unidades" id="moneda2" data-valor="2" data-tipo="moneda" value="0" min="0" tabindex="8"></td>
                                    <td class="text-center"><div class="total-linea-display">0.00 €</div></td>
                                </tr>
                                <tr>
                                    <td><strong>Monedas de 1 €</strong></td>
                                    <td class="text-center"><input type="number" class="unidades" id="moneda1" data-valor="1" data-tipo="moneda" value="0" min="0" tabindex="9"></td>
                                    <td class="text-center"><div class="total-linea-display">0.00 €</div></td>
                                </tr>
                                <tr>
                                    <td><strong>Monedas de 0.50 €</strong></td>
                                    <td class="text-center"><input type="number" class="unidades" id="moneda50cent" data-valor="0.50" data-tipo="centimo" value="0" min="0" tabindex="10"></td>
                                    <td class="text-center"><div class="total-linea-display">0.00 €</div></td>
                                </tr>
                                <tr>
                                    <td><strong>Monedas de 0.20 €</strong></td>
                                    <td class="text-center"><input type="number" class="unidades" id="moneda20cent" data-valor="0.20" data-tipo="centimo" value="0" min="0" tabindex="11"></td>
                                    <td class="text-center"><div class="total-linea-display">0.00 €</div></td>
                                </tr>
                                <tr>
                                    <td><strong>Monedas de 0.10 €</strong></td>
                                    <td class="text-center"><input type="number" class="unidades" id="moneda10cent" data-valor="0.10" data-tipo="centimo" value="0" min="0" tabindex="12"></td>
                                    <td class="text-center"><div class="total-linea-display">0.00 €</div></td>
                                </tr>
                                <tr>
                                    <td><strong>Monedas de 0.05 €</strong></td>
                                    <td class="text-center"><input type="number" class="unidades" id="moneda5cent" data-valor="0.05" data-tipo="centimo" value="0" min="0" tabindex="13"></td>
                                    <td class="text-center"><div class="total-linea-display">0.00 €</div></td>
                                </tr>
                                <tr>
                                    <td><strong>Monedas de 0.02 €</strong></td>
                                    <td class="text-center"><input type="number" class="unidades" id="moneda2cent" data-valor="0.02" data-tipo="centimo" value="0" min="0" tabindex="14"></td>
                                    <td class="text-center"><div class="total-linea-display">0.00 €</div></td>
                                </tr>
                                <tr>
                                    <td><strong>Monedas de 0.01 €</strong></td>
                                    <td class="text-center"><input type="number" class="unidades" id="moneda1cent" data-valor="0.01" data-tipo="centimo" value="0" min="0" tabindex="15"></td>
                                    <td class="text-center"><div class="total-linea-display">0.00 €</div></td>
                                </tr>
                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
                            </div>
                            <div class="col-md-4">
                            <div class="totales-column">
                <!-- Efectivo -->
                <div class="total-box">
                    <div class="total-value" id="totalEfectivo" >0.00 €</div>
                    <div class="total-label">Efectivo</div>
                </div>

                <!-- Diferencia -->
                <div class="total-box" id="boxDiferencia">
                <div class="total-value" id="totalDiferencia">0.00 €</div> 
                    <div class="total-label">Diferencia</div>
                </div>

                <!-- Caja -->
                <div class="total-box">
                     <div class="total-value" id="totalCaja"><?php echo number_format($totalCaja, 2, '.', ''); ?> €</div>
                     <div class="total-label">Caja</div>
                 </div>
                     <input type="hidden" id="inputCaja" value="<?php echo $totalCaja; ?>">
                
            </div>
                            </div>
                        </div>
                    
                </div>
            </div>

            <!-- DataTable de Histórico de Cierres -->
            <div class="card mt-4">
                <div class="card-header border-bottom card-header-forms">
                    <h5 class="card-title mb-0">Histórico de Cierres de Caja</h5>
                    
                    <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-5 gap-md-0 mt-0">
                        <div class="col-md-6">
                            <input type="text" id="rangeFechasCierres" class="form-control flatpickr-input" placeholder="Seleccionar rango de fechas">
                            <input type="hidden" name="filtro_fecha_desde_cierres" id="filtro_fecha_desde_cierres">
                            <input type="hidden" name="filtro_fecha_hasta_cierres" id="filtro_fecha_hasta_cierres">
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gx-1 gap-1">
                                <button type="button" class="btn btn-primary" id="filtro_hoy_cierres">Hoy</button>
                                <button type="button" class="btn btn-primary" id="filtro_mes_cierres">Mes</button>
                                <button type="button" class="btn btn-primary active" id="filtro_todos_cierres">Todos</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-datatable table-responsive">
                    <table class="datatables-cierres table border-top" id="dt_cierres_table">
                        <thead>
                            <tr>
                                <th>Nº Arqueo</th>
                                <th>Fecha de Arqueo</th>
                                <th>Caja</th>
                                <th>Efectivo</th>
                                <th>Diferencia</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- / Content -->
