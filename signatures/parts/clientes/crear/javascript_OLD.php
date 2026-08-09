<!-- JAVASCRIPT CUSTOM CREAR LOTE -->
<script src="js/botones_signature.js"></script>
<script>
/**
 * Obtener el valor de intereses de una sucursal
 */
function obtenerInteresesSucursal(idSucursal) {
    fetch('parts/lotes/crear/get_intereses_sucursal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_sucursal=' + idSucursal
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar campo hidden de intereses
            const campoIntereses = document.getElementById('intereses');
            if (campoIntereses) {
                campoIntereses.value = data.intereses;
            }
            
            // Actualizar campo porcentaje_recompra con el mismo valor
            const campoPorcentajeRecompra = document.getElementById('porcentaje_recompra');
            if (campoPorcentajeRecompra) {
                campoPorcentajeRecompra.value = data.intereses;
                // Recalcular precio de recompra cuando cambie el porcentaje
                if (typeof calcularPrecioRecompra === 'function') {
                    calcularPrecioRecompra();
                }
            }
        } else {
            console.error('Error al obtener intereses:', data.error);
            // En caso de error, mantener el valor por defecto
            const campoIntereses = document.getElementById('intereses');
            if (campoIntereses) {
                campoIntereses.value = 'false';
            }
            const campoPorcentajeRecompra = document.getElementById('porcentaje_recompra');
            if (campoPorcentajeRecompra) {
                campoPorcentajeRecompra.value = '0';
            }
        }
    })
    .catch(error => {
        console.error('Error al conectar con el servidor:', error);
        // En caso de error, mantener el valor por defecto
        const campoIntereses = document.getElementById('intereses');
        if (campoIntereses) {
            campoIntereses.value = 'false';
        }
        const campoPorcentajeRecompra = document.getElementById('porcentaje_recompra');
        if (campoPorcentajeRecompra) {
            campoPorcentajeRecompra.value = '0';
        }
    });
}

/**
 * Obtener el estado de SMS de una sucursal
 */
function obtenerSMSStateSucursal(idSucursal) {
    fetch('parts/lotes/crear/get_sms_state_sucursal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_sucursal=' + idSucursal
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar campo hidden de active_code_autorization
            const campoActiveCode = document.getElementById('active_code_autorization');
            const contenedor_btn_solicitar_autorizacion_sms = document.getElementById('contenedor_btn_solicitar_autorizacion_sms');
            
            if (campoActiveCode) {
                campoActiveCode.value = data.sms_state;
            }

            if(data.sms_state === "true") {
                contenedor_btn_solicitar_autorizacion_sms.classList.remove('d-none');
                document.getElementById('telefono').setAttribute('required', 'required');
            } else {
                contenedor_btn_solicitar_autorizacion_sms.classList.add('d-none');
                document.getElementById('telefono').removeAttribute('required', 'required');
            }

        } else {
            console.error('Error al obtener estado SMS:', data.error);
            // En caso de error, mantener el valor por defecto
            const campoActiveCode = document.getElementById('active_code_autorization');
            if (campoActiveCode) {
                campoActiveCode.value = 'false';
            }
        }
    })
    .catch(error => {
        console.error('Error al conectar con el servidor:', error);
        // En caso de error, mantener el valor por defecto
        const campoActiveCode = document.getElementById('active_code_autorization');
        if (campoActiveCode) {
            campoActiveCode.value = 'false';
        }
    });
}

/**
 * Obtener el estado de SMS empeño de una sucursal
 */
function obtenerSMSStateEmpenyoSucursal(idSucursal) {
    fetch('parts/lotes/crear/get_sms_state_empenyo_sucursal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_sucursal=' + idSucursal
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar campo hidden de active_code_empenyo_autorization
            const campoActiveCodeEmpenyo = document.getElementById('active_code_empenyo_autorization');
            if (campoActiveCodeEmpenyo) {
                campoActiveCodeEmpenyo.value = data.sms_state_empenyo;
            }
        } else {
            console.error('Error al obtener estado SMS empeño:', data.error);
            // En caso de error, mantener el valor por defecto
            const campoActiveCodeEmpenyo = document.getElementById('active_code_empenyo_autorization');
            if (campoActiveCodeEmpenyo) {
                campoActiveCodeEmpenyo.value = 'false';
            }
        }
    })
    .catch(error => {
        console.error('Error al conectar con el servidor:', error);
        // En caso de error, mantener el valor por defecto
        const campoActiveCodeEmpenyo = document.getElementById('active_code_empenyo_autorization');
        if (campoActiveCodeEmpenyo) {
            campoActiveCodeEmpenyo.value = 'false';
        }
    });
}

/**
 * Obtener el estado de SMS según tipo de pago de una sucursal
 */
function obtenerSMSStateTipoPagoSucursal(idSucursal, tipoPago, campoId) {
    fetch('parts/lotes/crear/get_sms_state_tipo_pago_sucursal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_sucursal=' + idSucursal + '&tipo_pago=' + tipoPago
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar campo hidden correspondiente
            const campo = document.getElementById(campoId);
            if (campo) {
                campo.value = data.sms_state;
            }
        } else {
            console.error('Error al obtener estado SMS tipo pago:', data.error);
            // En caso de error, mantener el valor por defecto
            const campo = document.getElementById(campoId);
            if (campo) {
                campo.value = 'false';
            }
        }
    })
    .catch(error => {
        console.error('Error al conectar con el servidor:', error);
        // En caso de error, mantener el valor por defecto
        const campo = document.getElementById(campoId);
        if (campo) {
            campo.value = 'false';
        }
    });
}

/**
 * Obtener el siguiente número de lote de la tabla lotes_$id_sucursal
 */
function obtenerNumeroLote(idSucursal) {
    fetch('parts/lotes/crear/get_numero_lote.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_sucursal=' + idSucursal
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Crear y establecer la opción en el select2 de id_lote
            const selectLote = $('#id_lote');
            // Limpiar opciones existentes
            selectLote.empty();
            $('#id_lote').select2({
                allowClear: false  // Esto quita el botón X
            });
            // Crear nueva opción con el número de lote
            const newOption = new Option(data.numero_lote, data.numero_lote, true, true);
            selectLote.append(newOption).trigger('change');
            
            // Actualizar span en el título
            document.getElementById('numero_lote').textContent = '(Nº ' + data.numero_lote + ')';
            
            // Verificar estado del botón
            verificarEstadoBotonCrear();
        } else {
            Swal.fire({
                title: 'Error',
                text: data.error || 'Error al obtener número de lote',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error',
            text: 'Error al conectar con el servidor',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
    });
}

/**
 * Verificar si hay lotes en cuarentena con estado 'arribado'
 */
function verificarLotesCuarentena(idSucursal) {
    fetch('parts/lotes/crear/verificar_lotes_cuarentena.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_sucursal=' + idSucursal
    })
    .then(response => response.json())
    .then(data => {
        const numeros_lotes = document.getElementById('numeros_lotes');
        const tituloAlertErrorSucursal = document.getElementById('titulo_alert_error_sucursal');
        const selectLote = $('#id_lote');
        
        if (data.success) {
            if (data.hay_lotes) {
                // Mostrar el div si hay lotes
                var lotes_disponibles = data.lotes_disponibles;
                
                if (numeros_lotes) {
                    numeros_lotes.classList.remove('d-none');
                    tituloAlertErrorSucursal.textContent = 'Alerta, lo siguientes números de lotes estan disponibles: ' + lotes_disponibles;
                    numeros_lotes.style.display = 'block';
                }
                
                // Inyectar los lotes disponibles como opciones en el select2 (después del que ya existe)
                if (lotes_disponibles && lotes_disponibles.trim() !== '') {
                    // Dividir la cadena por comas y crear opciones
                    const lotesArray = lotes_disponibles.split(',').map(lote => lote.trim());
                    lotesArray.forEach(function(lote) {
                        if (lote) {
                            // Verificar que la opción no exista ya
                            if (selectLote.find('option[value="' + lote + '"]').length === 0) {
                                const newOption = new Option(lote, lote, false, false);
                                selectLote.append(newOption);
                            }
                        }
                    });
                    
                    // Actualizar select2
                    selectLote.trigger('change');
                }
            } else {
                // Ocultar el div si no hay lotes
                if (numeros_lotes) {
                    numeros_lotes.classList.add('d-none');
                    numeros_lotes.style.display = 'none';
                }
            }
        } else {
            // En caso de error, ocultar el div
            if (numeros_lotes) {
                numeros_lotes.classList.add('d-none');
                numeros_lotes.style.display = 'none';
            }
        }
    })
    .catch(error => {
        console.error('Error al verificar lotes en cuarentena:', error);
        // En caso de error, ocultar el div
        const numeros_lotes = document.getElementById('alert_error_sucursal');
        if (numeros_lotes) {
            numeros_lotes.classList.add('d-none');
            numeros_lotes.style.display = 'none';
        }
    });
}

/**
 * Obtener nombre de sucursal por ID
 */
function obtenerNombreSucursal(idSucursal) {
    return fetch('parts/clientes/listar/get_sucursales.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const sucursal = data.sucursales.find(s => s.id_sucursal == idSucursal);
                return sucursal ? sucursal.nombre_sucursal : '';
            }
            return '';
        })
        .catch(error => {
            console.error('Error al obtener nombre de sucursal:', error);
            return '';
        });
}

/**
 * Mostrar/ocultar sección de datos del cliente y del lote según sucursal seleccionada
 */
function mostrarDatosCliente() {
    const inputSucursal = document.getElementById('sucursal_lote');
    const divDatosCliente = document.getElementById('datos_cliente');
    const divDatosLote = document.getElementById('datos_lote');
    const nombreSucursal = document.getElementById('nombre_sucursal');
    
    if (inputSucursal && inputSucursal.value) {
        const idSucursal = inputSucursal.value;
        
        // Obtener nombre de la sucursal
        obtenerNombreSucursal(idSucursal).then(textoSucursal => {
            if (textoSucursal) {
                nombreSucursal.textContent = textoSucursal;
            }
        });
        
        // Obtener siguiente número de lote
        obtenerNumeroLote(idSucursal);
        
        // Obtener valor de intereses de la sucursal
        obtenerInteresesSucursal(idSucursal);
        
        // Verificar si hay lotes en cuarentena
        verificarLotesCuarentena(idSucursal);
        
        // Obtener estado de SMS de la sucursal
        obtenerSMSStateSucursal(idSucursal);
        
        // Obtener estado de SMS empeño de la sucursal
        obtenerSMSStateEmpenyoSucursal(idSucursal);
        
        // Obtener estado de SMS según tipo de pago (contado)
        obtenerSMSStateTipoPagoSucursal(idSucursal, 'sms_contado', 'active_sendTipoPago_contado');
        
        // Obtener estado de SMS según tipo de pago (otros métodos)
        obtenerSMSStateTipoPagoSucursal(idSucursal, 'sms_otros_metodos_pago', 'active_sendTipoPago_otros');
        
        // Mostrar datos del cliente y del lote
        document.getElementById('datos_cliente').classList.remove('formulario-borroso');
        document.getElementById('datos_lote').classList.remove('formulario-borroso');
        document.getElementById('numeros_lotes').classList.remove('formulario-borroso');
        // Mostrar fecha de liberación al mostrar el formulario
        if (typeof mostrarFechaLiberacionGlobal === 'function') {
            mostrarFechaLiberacionGlobal();
        }
        
        // Verificar estado del botón
        verificarEstadoBotonCrear();
    } else {
        nombreSucursal.textContent = '';
        document.getElementById('numero_lote').textContent = '';
        document.getElementById('id_lote').value = '';
        document.getElementById('datos_cliente').classList.add('formulario-borroso');
        document.getElementById('datos_lote').classList.add('formulario-borroso');
        document.getElementById('numeros_lotes').classList.add('formulario-borroso');
        // Resetear valor de intereses
        const campoIntereses = document.getElementById('intereses');
        if (campoIntereses) {
            campoIntereses.value = 'false';
        }
        
        // Resetear valor de porcentaje_recompra
        const campoPorcentajeRecompra = document.getElementById('porcentaje_recompra');
        if (campoPorcentajeRecompra) {
            campoPorcentajeRecompra.value = '0';
        }
        
        // Resetear valor de active_code_autorization
        const campoActiveCode = document.getElementById('active_code_autorization');
        if (campoActiveCode) {
            campoActiveCode.value = 'false';
        }
        
        // Resetear valor de active_code_empenyo_autorization
        const campoActiveCodeEmpenyo = document.getElementById('active_code_empenyo_autorization');
        if (campoActiveCodeEmpenyo) {
            campoActiveCodeEmpenyo.value = 'false';
        }
        
        // Resetear valor de active_sendTipoPago_contado
        const campoActiveSendContado = document.getElementById('active_sendTipoPago_contado');
        if (campoActiveSendContado) {
            campoActiveSendContado.value = 'false';
        }
        
        // Resetear valor de active_sendTipoPago_otros
        const campoActiveSendOtros = document.getElementById('active_sendTipoPago_otros');
        if (campoActiveSendOtros) {
            campoActiveSendOtros.value = 'false';
        }
        
        // Verificar estado del botón
        verificarEstadoBotonCrear();
    }
}

/**
 * Manejar responsive del card del formulario
 */
function manejarResponsiveCard() {
    const cardElement = document.getElementById('card-form-custom-id');
    if (!cardElement) return;
    
    const mediaQuery = window.matchMedia('(max-width: 620px)');
    
    function handleScreenChange(e) {
        if (e.matches) {
            // Pantalla menor a 620px - quitar clases
            cardElement.classList.remove('card', 'card-form-custom');
        } else {
            // Pantalla mayor a 620px - agregar clases
            if (!cardElement.classList.contains('card')) {
                cardElement.classList.add('card');
            }
            if (!cardElement.classList.contains('card-form-custom')) {
                cardElement.classList.add('card-form-custom');
            }
        }
    }
    
    // Ejecutar al cargar
    handleScreenChange(mediaQuery);
    
    // Escuchar cambios de tamaño
    mediaQuery.addListener(handleScreenChange);
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {

    document.getElementById('datos_cliente').classList.add('formulario-borroso');
    document.getElementById('datos_lote').classList.add('formulario-borroso');
    document.getElementById('numeros_lotes').classList.add('formulario-borroso');
    // Manejar responsive del card
    manejarResponsiveCard();
    
    // Resetear campos al cargar la página (excepto sucursal que viene por POST)
    document.getElementById('id_lote').value = '';
    document.getElementById('numero_lote').textContent = '';
    document.getElementById('nombre_sucursal').textContent = '';
    
    // Verificar si hay sucursal en el input hidden y mostrar datos automáticamente
    const inputSucursal = document.getElementById('sucursal_lote');
    if (inputSucursal && inputSucursal.value) {
        mostrarDatosCliente();
    }
    
    // Inicializar Select2 para campos de cliente
    $('#tipo_identificacion').select2({
        placeholder: 'Seleccionar...',
        allowClear: true
    });
    
    $('#nacionalidad').select2({
        placeholder: 'Seleccionar...',
        allowClear: true
    });

    $('#tipo_identificacion').on('change', function () {
        const idTipo = String($(this).val() || '');
        const $nac = $('#nacionalidad');
        // DNI (1), CIF (3), pasaporte español (4) → nacionalidad Española (54)
        if (idTipo === '1' || idTipo === '3' || idTipo === '4') {
            if ($nac.find('option[value="54"]').length) {
                $nac.val('54').trigger('change');
            }
        } else if (idTipo === '2' || idTipo === '5') {
            // NIE (2) u Otros (5) → vaciar nacionalidad
            $nac.val('').trigger('change');
        }
    });
    
    $('#sexo').select2({
        placeholder: 'Seleccionar...',
        allowClear: true
    });

    $('#sexo, #nacionalidad, #tipo_identificacion').off('change.verifBtnLote select2:select.verifBtnLote select2:clear.verifBtnLote');
    $('#sexo, #nacionalidad, #tipo_identificacion').on(
        'change.verifBtnLote select2:select.verifBtnLote select2:clear.verifBtnLote',
        function () {
            verificarEstadoBotonCrear();
        }
    );
    
    // Configurar validaciones de cliente
    configurarValidacion();
    configurarValidacionesEspeciales();
    
    // Configurar opción de compra
    configurarOpcionCompra();
    
    // Configurar cálculo de precio de recompra
    configurarCalculoPrecioRecompra();
    
    // Configurar modal de editar porcentaje
    configurarModalEditarPorcentaje();
    
    // Configurar modal de autorización SMS
    configurarModalAutorizacionSMS();
    
    // Configurar manejo del submit del formulario
    configurarSubmitFormulario();
    
    // Configurar botón de comprobar código SMS
    const btnCheckCodeSMS = document.getElementById('btn_check_code_sms');
    if (btnCheckCodeSMS) {
        btnCheckCodeSMS.addEventListener('click', function(e) {
            e.preventDefault();
            mostrarLoaderUniversal("Comprobando código SMS");
            checkCodeSMS();
        });
    }
    
    // Verificar estado del botón al cambiar cualquier campo
    verificarEstadoBotonCrear();
});

/**
 * Configurar validación del formulario
 */
function configurarValidacion() {
    const form = document.getElementById('formCrearLote');
    
    // Validación en tiempo real
    form.addEventListener('input', function(event) {
        const field = event.target;
        if (field.hasAttribute('required')) {
            validarCampo(field);
        }
        // Verificar estado del botón después de cada input
        verificarEstadoBotonCrear();
    });
    
    // Validación al cambiar select
    form.addEventListener('change', function(event) {
        const field = event.target;
        if (field.hasAttribute('required')) {
            validarCampo(field);
        }
        // Verificar estado del botón después de cada cambio
        verificarEstadoBotonCrear();
    });
}

/**
 * Comprobar condiciones de SMS antes de enviar el formulario
 * @return bool True si se puede enviar, false si no
 */
function comprobarSmsSend() {
    // Obtener valores de los inputs hidden
    const activeCodeAutorization = document.getElementById('active_code_autorization');
    const activeCodeEmpenyoAutorization = document.getElementById('active_code_empenyo_autorization');
    const activeSendTipoPagoContado = document.getElementById('active_sendTipoPago_contado');
    const activeSendTipoPagoOtros = document.getElementById('active_sendTipoPago_otros');
    const not_mobile_get = document.getElementById('not_mobile_get');
    
    // Obtener valores de los radio buttons
    const opcionCompra = document.querySelector('input[name="opcion_compra"]:checked');
    const metodoPago = document.querySelector('input[name="metodo_pago"]:checked');
    
    // Obtener los valores
    const valorActiveCodeAutorization = activeCodeAutorization ? activeCodeAutorization.value : 'false';
    const valorActiveCodeEmpenyoAutorization = activeCodeEmpenyoAutorization ? activeCodeEmpenyoAutorization.value : 'false';
    const valorActiveSendTipoPagoContado = activeSendTipoPagoContado ? activeSendTipoPagoContado.value : 'false';
    const valorActiveSendTipoPagoOtros = activeSendTipoPagoOtros ? activeSendTipoPagoOtros.value : 'false';
    const valorOpcionCompra = opcionCompra ? opcionCompra.value : '';
    const valorMetodoPago = metodoPago ? metodoPago.value : '';
    const valornot_mobile_get = not_mobile_get ? not_mobile_get.value : 'false';

    // Mostrar valores en consola
    console.log(JSON.stringify({
        active_code_autorization: valorActiveCodeAutorization,
        active_code_empenyo_autorization: valorActiveCodeEmpenyoAutorization,
        active_sendTipoPago_contado: valorActiveSendTipoPagoContado,
        active_sendTipoPago_otros: valorActiveSendTipoPagoOtros,
        opcion_compra: valorOpcionCompra,
        metodo_pago: valorMetodoPago,
        not_mobile_get: valornot_mobile_get
    }, null, 2));

    // AQUI SE AGREGARA LA LOGICA
    // Inicializar active_metod_pago por defecto
    let active_metod_pago = 'false';
    
    if (valorMetodoPago === "efectivo") {
        if (valorActiveSendTipoPagoContado === "true") {
            active_metod_pago = "true";
        }
    } else if (valorMetodoPago === "transferencia") {
        if (valorActiveSendTipoPagoOtros === "true") {
            active_metod_pago = "true";
        }
    }

    if (valorActiveCodeAutorization === "true" && valorOpcionCompra === "no") {
        if (active_metod_pago === "true") {
            setTimeout(function () {
                open_sms('compra', valornot_mobile_get);
            }, 1000);
            return false; // Prevenir envío mientras se procesa SMS
        } else {
            return true; // Permitir envío si no requiere SMS
        }
    } else if (valorActiveCodeEmpenyoAutorization === "true" && valorOpcionCompra === "si") {
        if (active_metod_pago === "true") {
            setTimeout(function () {
                open_sms('empenyo', valornot_mobile_get);
            }, 1000);
            return false; // Prevenir envío mientras se procesa SMS
        } else {
            return true; // Permitir envío si no requiere SMS
        }
    } else {
        return true; // Permitir envío en otros casos
    }
}

function open_sms(tipo_operacion, valornot_mobile_get) {
    if (valornot_mobile_get === "true") {
        console.log("orden: notSMS");
        mostrarLoaderUniversal("Solicitando autorización...");
        requestAuthorizationNotSMS(tipo_operacion);
    } else {
        console.log("orden: sendSMS");
        mostrarLoaderUniversal("Enviando SMS");
        sendSMS(tipo_operacion);
    }
}

function sendSMS(tipo_operacion) {
    var idlote_sms = document.getElementById('id_lote').value;
    var importe_sms = document.getElementById('precio_compra').value;
    var movil_sms = document.getElementById('telefono').value;

    datos_sms = {
        "idlote_sms": idlote_sms,
        "importe_sms": importe_sms,
        "movil_sms": movil_sms
    };

    $.ajax({
        url: 'https://goldservice.matermedia.app/sms.php',
        type: 'POST',
        dataType: 'json',
        data: datos_sms,
        success: function(data) {
            var state_sms = data.statuqouo;
            var codigo_sms = data.codigo_sms;
            
            if (state_sms === "ok") {
                insertLogSMS(codigo_sms, tipo_operacion);
                ocultarLoaderUniversal();
                
                var codigoSms = document.getElementById('codigo_sms');
                if (codigoSms) {
                    codigoSms.value = '';
                }
                
                // Abrir modal usando Bootstrap 5
                const modalSmsCode = new bootstrap.Modal(document.getElementById('sms_code'));
                modalSmsCode.show();
                
                // Focus en el input después de que el modal se muestre
                var smsCodeModal = document.getElementById('sms_code');
                if (smsCodeModal) {
                    smsCodeModal.addEventListener('shown.bs.modal', function () {
                        var codigoSmsInput = document.getElementById('codigo_sms');
                        if (codigoSmsInput) {
                            codigoSmsInput.focus();
                        }
                    });
                }
                
                console.log("enviado");
            } else {
                console.log("no enviado");
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al enviar SMS:', error);
        }
    });
}

function checkCodeSMS() {
    var id_sms = document.getElementById('id_sms').value;
    var codigo_sms = document.getElementById('codigo_sms').value;

     datos_sms = {
         "id_sms": id_sms,
         "codigo_sms": codigo_sms
     };

     $.ajax({
         url: 'sms/check_codigo_autorizacion.php',
         type: 'POST',
         data: datos_sms,
         success: function(data) {
             var same_code = data.same_code;
             /*ocultarLoaderUniversal();*/
            if( same_code === "ok"){
                 const modalSmsCode = bootstrap.Modal.getInstance(document.getElementById('sms_code'));
                 if (modalSmsCode) {
                     modalSmsCode.hide();
                 }
                 mostrarLoaderUniversal("Creando lote...");
                 enviarFormularioLote();
            }else{
                Swal.fire({
                    title: 'Código incorrecto',
                    text: 'El código NO es el mismo, inténtelo nuevamente',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }

         }
    });

}

// Variable global para almacenar el intervalo de polling
var pollingIntervalSMS = null;

function iniciarPollingAutorizacionSMS(id_sms, modalInstance) {
    console.log("iniciarPollingAutorizacionSMS");
    // Limpiar cualquier polling anterior
    if (pollingIntervalSMS) {
        console.log("clearInterval");
        clearInterval(pollingIntervalSMS);
        pollingIntervalSMS = null;
    }
    
    // Verificar que el modal esté abierto
    var smsCodeModal = document.getElementById('sms_code');
    if (!smsCodeModal) {
        console.log("smsCodeModal no encontrado");
        return;
    }
    
    // Función para verificar el estado
    function verificarEstadoAutorizacion() {
        console.log("verificarEstadoAutorizacion");
        // Verificar si el modal sigue abierto
        var modalElement = document.getElementById('sms_code');
        if (!modalElement || !modalElement.classList.contains('show')) {
            // Modal cerrado, detener polling
            if (pollingIntervalSMS) {
                clearInterval(pollingIntervalSMS);
                pollingIntervalSMS = null;
            }
            return;
        }
        
        // Hacer petición AJAX para verificar estado
        $.ajax({
            url: 'parts/lotes/crear/verificar_estado_autorizacion_sms.php',
            type: 'POST',
            data: {
                id_sms: id_sms
            },
            success: function(data) {
                if (data.success && data.autorizado === true) {
                    console.log("autorizado");
                    // Autorización confirmada, detener polling
                    if (pollingIntervalSMS) {
                        clearInterval(pollingIntervalSMS);
                        pollingIntervalSMS = null;
                    }
                    
                    // Cerrar modal
                    if (modalInstance) {
                        modalInstance.hide();
                    } else {
                        const modalSmsCode = bootstrap.Modal.getInstance(document.getElementById('sms_code'));
                        if (modalSmsCode) {
                            modalSmsCode.hide();
                        }
                    }
                    
                    // Continuar con la ejecución
                    mostrarLoaderUniversal("Creando lote...");
                    enviarFormularioLote();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al verificar estado de autorización:', error);
            }
        });
    }
    
    // Iniciar polling cada 3 segundos
    pollingIntervalSMS = setInterval(verificarEstadoAutorizacion, 3000);
    
    // Detener polling cuando se cierre el modal
    if (smsCodeModal) {
        smsCodeModal.addEventListener('hidden.bs.modal', function() {
            if (pollingIntervalSMS) {
                clearInterval(pollingIntervalSMS);
                pollingIntervalSMS = null;
            }
        }, { once: true });
    }
}

function requestAuthorizationNotSMS(tipo_operacion) {
    var id_lote = document.getElementById('id_lote').value;
    var precio_compra = document.getElementById('precio_compra').value;
    const inputSucursalSms = document.getElementById('sucursal_lote');
    var sucursal_sms = inputSucursalSms ? inputSucursalSms.value : '';
    if( tipo_operacion === "compra" ){
        var mensaje_sms = "PAGO DEL LOTE Nº " + id_lote + " POR EL IMPORTE " + precio_compra + "€";
    } else if( tipo_operacion === "empenyo" ){
        var mensaje_sms = "EMPENYO DEL LOTE Nº " + id_lote + " POR EL IMPORTE " + precio_compra + "€";
    }
    var datos_sms = {
        "rel_item_sms": id_lote,
        "importe_sms": precio_compra,
        "estado_sms": "false",
        "estado_codigo": "false",
        "type_item_sms": "pago_lote",
        "sucursal_sms": sucursal_sms,
        "mensaje_sms": mensaje_sms,
        "autorizado_central": "true"
    };
    
    var formData = new FormData();
    for (var key in datos_sms) {
        formData.append(key, datos_sms[key]);
    }
    
    fetch('sms/insert_codigo_solicitar.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        var statelogsms = data.statelogsms;
        var id_sms_parset = data.id_sms;
        
        var idSmsInput = document.getElementById('id_sms');
        if (idSmsInput) {
            idSmsInput.value = id_sms_parset;
        }
        
        var idAuthorization = document.getElementById('id_autorization');
        if (idAuthorization) {
            idAuthorization.textContent = id_sms_parset;
        }
        
        if (statelogsms === "ok") {
            console.log("log SMS creado");
            // Iniciar polling con setTimeout recursivo
            comprobarAutorizaciónNotSMS(id_sms_parset, id_lote);
        } else {
            console.log("log SMS no creado");
        }
    })
    .catch(error => {
        console.error('Error al insertar SMS:', error);
    });
}

function comprobarAutorizaciónNotSMS(id_sms_parset, idlote_sms){
    // Limpiar timeout anterior si existe
    if (timeoutAutorizacionSMS) {
        clearTimeout(timeoutAutorizacionSMS);
        timeoutAutorizacionSMS = null;
    }
    
    datos_sms = {
       "id_sms_parset": id_sms_parset
    };
    $.ajax({
       url: 'sms/consultar_codigo_solicitar.php',
       type: 'POST',
       data: datos_sms,
       success: function(data) {
           var statelogsms = data.statelogsms;
           var autorizado = data.autorizado;
           var id_sms_parset = data.id_sms_parset;
           if( autorizado === "true" ){
                // Limpiar timeout para detener el polling
                if (timeoutAutorizacionSMS) {
                    clearTimeout(timeoutAutorizacionSMS);
                    timeoutAutorizacionSMS = null;
                }
                ocultarLoaderUniversal();
                mostrarLoaderUniversal("Autorizado, creando lote Nº "+idlote_sms+"...");
                setTimeout(function() {
                    enviarFormularioLote();
                }, 5000);
           }else if( autorizado === "cancelada" ){
               // Limpiar timeout para detener el polling
               if (timeoutAutorizacionSMS) {
                   clearTimeout(timeoutAutorizacionSMS);
                   timeoutAutorizacionSMS = null;
               }
               ocultarLoaderUniversal();
               Swal.fire({
                   title: 'Atención!',
                   text: 'Autorización cancelada!',
                   icon: 'error',
                   confirmButtonText: 'Aceptar',
                   confirmButtonColor: '#dc3545'
               });
           }else{
               // Estado pendiente: programar otra verificación después de 5 segundos
               mostrarLoaderUniversal("Esperando autorización por SMS...");
               timeoutAutorizacionSMS = setTimeout(function() {
                   comprobarAutorizaciónNotSMS(id_sms_parset, idlote_sms);
               }, 5000);
           }
           
       }
    });
    
}

function insertLogSMS(codigo_sms, tipo_operacion) {
    var id_lote = document.getElementById('id_lote').value;
    var precio_compra = document.getElementById('precio_compra').value;
    var movil_sms = document.getElementById('telefono').value;
    var codigo_sms_parset = codigo_sms;
    const inputSucursalSms = document.getElementById('sucursal_lote');
    var sucursal_sms = inputSucursalSms ? inputSucursalSms.value : '';
    if( tipo_operacion === "compra" ){
        var mensaje_sms = "PAGO DEL LOTE Nº " + id_lote + " POR EL IMPORTE " + precio_compra + "€";
    } else if( tipo_operacion === "empenyo" ){
        var mensaje_sms = "EMPENYO DEL LOTE Nº " + id_lote + " POR EL IMPORTE " + precio_compra + "€";
    }
    var datos_sms = {
        "rel_item_sms": id_lote,
        "importe_sms": precio_compra,
        "movil_sms": movil_sms,
        "codigo_sms": codigo_sms_parset,
        "estado_sms": "true",
        "estado_codigo": "true",
        "type_item_sms": "pago_lote",
        "sucursal_sms": sucursal_sms,
        "mensaje_sms": mensaje_sms
    };
    
    var formData = new FormData();
    for (var key in datos_sms) {
        formData.append(key, datos_sms[key]);
    }
    
    fetch('sms/insert_sms.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        var statelogsms = data.statelogsms;
        var id_sms_parset = data.id_sms;
        
        var idSmsInput = document.getElementById('id_sms');
        if (idSmsInput) {
            idSmsInput.value = id_sms_parset;
        }
        
        var idAuthorization = document.getElementById('id_autorization');
        if (idAuthorization) {
            idAuthorization.textContent = id_sms_parset;
        }
        
        if (statelogsms === "ok") {
            console.log("log SMS creado");
            
            // Iniciar polling para verificar autorización automática
            // Solo si el modal está abierto
            var smsCodeModal = document.getElementById('sms_code');
            if (smsCodeModal && smsCodeModal.classList.contains('show')) {
                const modalSmsCode = bootstrap.Modal.getInstance(smsCodeModal);
                if (modalSmsCode && id_sms_parset) {
                    iniciarPollingAutorizacionSMS(id_sms_parset, modalSmsCode);
                }
            } else {
                // Si el modal aún no está abierto, esperar a que se abra
                smsCodeModal.addEventListener('shown.bs.modal', function() {
                    const modalSmsCode = bootstrap.Modal.getInstance(smsCodeModal);
                    if (modalSmsCode && id_sms_parset) {
                        iniciarPollingAutorizacionSMS(id_sms_parset, modalSmsCode);
                    }
                }, { once: true });
            }
        } else {
            console.log("log SMS no creado");
        }
        
        setInterval(function() {
            ocultarLoaderUniversal();
        }, 5000);
    })
    .catch(error => {
        console.error('Error al insertar SMS:', error);
    });
} 
       
function mostrarLoaderUniversal(texto_loader_parsed) {
    const loaderContainer = document.getElementById('loaderContainer');
    const textLoader = document.getElementById('textLoader');
    
    if (loaderContainer) {
        // Establecer opacity inicial a 0 y display a flex
        loaderContainer.style.opacity = '0';
        loaderContainer.style.display = 'flex';
        loaderContainer.style.transition = 'opacity 0.4s ease-in-out';
        
        // Forzar reflow para que la transición funcione
        void loaderContainer.offsetWidth;
        
        // Hacer fadeIn
        setTimeout(function() {
            loaderContainer.style.opacity = '1';
        }, 10);
    }
    
    if (textLoader) {
        textLoader.textContent = '' + texto_loader_parsed + '...';
    }
}


function ocultarLoaderUniversal() {
    const loaderContainer = document.getElementById('loaderContainer');
    const textLoader = document.getElementById('textLoader');
    
    if (loaderContainer) {
        // Asegurar que la transición esté configurada
        loaderContainer.style.transition = 'opacity 0.4s ease-in-out';
        
        // Hacer fadeOut
        loaderContainer.style.opacity = '0';
        
        // Ocultar después de que termine la animación
        setTimeout(function() {
            loaderContainer.style.display = 'none';
            loaderContainer.style.opacity = '1'; // Resetear para la próxima vez
        }, 400);
    }
    
    if (textLoader) {
        textLoader.textContent = '';
    }
}
/**
 * Enviar formulario de lote por AJAX
 * @param {function} [onSendFailed] Se llama si no hay redirección (error de red, validación servidor, etc.) para restaurar el botón de envío.
 */
function enviarFormularioLote(onSendFailed) {
    const form = document.getElementById('formCrearLote');
    if (!form) {
        console.error('Formulario no encontrado');
        ocultarLoaderUniversal();
        if (typeof onSendFailed === 'function') {
            onSendFailed();
        }
        return;
    }
    
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        redirect: 'follow'
    })
    .then(response => {
        if (response.ok) {
            return response.json().then(data => {
                if (data.success) {
                    // Usar la URL que viene del servidor en el JSON
                    if (data.url) {
                        if (data.firma_digital === 'true') {
                            mostrarLoaderUniversal('Solicite al cliente que firme desde el dispositivo de firma, por favor esperar...');
                            insertarSignatureDocumentCreateLote(
                                String(data.id_lote),
                                'lote',
                                String(data.precio_compra),
                                String(data.id_sucursal),
                                data.url
                            );
                        } else {
                            window.location.href = data.url;
                        }
                    } else {
                        ocultarLoaderUniversal();
                        Swal.fire({
                            title: data.message || 'Lote creado',
                            text: data.message || 'El lote se ha creado correctamente',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            window.location.href = 'lotes.php';
                        });
                    }
                } else {
                    throw new Error(data.error || data.message || 'Error al crear el lote');
                }
            });
        } else {
            throw new Error('Error en la respuesta del servidor');
        }
    })
    .catch(error => {
        if (typeof onSendFailed === 'function') {
            onSendFailed();
        }
        ocultarLoaderUniversal();
        Swal.fire({
            title: 'Error',
            text: error.message || 'Error al crear el lote. Por favor, inténtelo de nuevo.',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
    });
}

/**
 * Configurar manejo del submit del formulario
 * Deshabilita el botón y muestra loader al enviar
 */
function configurarSubmitFormulario() {
    const form = document.getElementById('formCrearLote');
    const btnCrearLote = document.getElementById('btnCrearLote');
    const loaderCrearLote = document.getElementById('loaderCrearLote');
    const iconCrearLote = document.getElementById('iconCrearLote');
    const textCrearLote = document.getElementById('textCrearLote');
    
    let formSubmitted = false;
    
    form.addEventListener('submit', function(e) {
        // Si el formulario ya se está enviando, prevenir envío múltiple
        if (formSubmitted) {
            e.preventDefault();
            return false;
        }
        
        // Validar que peso_neto, peso_bruto y precio_compra sean mayores a cero
        const pesoNeto = parseFloat(document.getElementById('peso_neto').value) || 0;
        const pesoBruto = parseFloat(document.getElementById('peso_bruto').value) || 0;
        const precioCompra = parseFloat(document.getElementById('precio_compra').value) || 0;
        
        if (pesoNeto <= 0) {
            e.preventDefault();
            Swal.fire({
                title: 'Error de validación',
                text: 'El peso neto debe ser mayor a cero',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
            return false;
        }
        
        if (pesoBruto <= 0) {
            e.preventDefault();
            Swal.fire({
                title: 'Error de validación',
                text: 'El peso bruto debe ser mayor a cero',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
            return false;
        }
        
        if (precioCompra <= 0) {
            e.preventDefault();
            Swal.fire({
                title: 'Error de validación',
                text: 'El precio de compra debe ser mayor a cero',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
            return false;
        }
        
        // Comprobar condiciones de SMS antes de enviar
        if (!comprobarSmsSend()) {
            e.preventDefault();
            formSubmitted = false; // Resetear flag para permitir reintento
            return false;
        }
        
        // Marcar como enviado
        formSubmitted = true;
        
        // Deshabilitar botón
        btnCrearLote.disabled = true;
        
        // Mostrar loader y ocultar icono
        if (loaderCrearLote) {
            loaderCrearLote.classList.remove('d-none');
        }
        if (iconCrearLote) {
            iconCrearLote.style.display = 'none';
        }
        if (textCrearLote) {
            textCrearLote.textContent = 'Creando...';
        }
        
        e.preventDefault();
        enviarFormularioLote(function restaurarEnvioLote() {
            formSubmitted = false;
            if (btnCrearLote) {
                btnCrearLote.disabled = false;
            }
            if (loaderCrearLote) {
                loaderCrearLote.classList.add('d-none');
            }
            if (iconCrearLote) {
                iconCrearLote.style.display = '';
            }
            if (textCrearLote) {
                textCrearLote.textContent = 'Crear Lote';
            }
        });
        return false;
    });
}

/**
 * Verificar estado del botón Crear Lote
 * Solo se habilita si todos los campos requeridos están completos y validados
 */
function verificarEstadoBotonCrear() {
    const btnCrearLote = document.getElementById('btnCrearLote');
    if (!btnCrearLote) return;
    
    const inputSucursalLote = document.getElementById('sucursal_lote');
    const idLote = document.getElementById('id_lote');
    
    // Verificar condición 1: Hay sucursal seleccionada
    const haySucursal = inputSucursalLote && inputSucursalLote.value !== '';
    
    // Verificar condición 2: Hay número de lote
    const hayNumeroLote = idLote && idLote.value !== '';
    
    if (!haySucursal || !hayNumeroLote) {
        btnCrearLote.disabled = true;
        return;
    }
    
    // Verificar condición 3: Todos los campos required del cliente están completos
    let camposClienteCompletos = true;
    const datosCliente = document.getElementById('datos_cliente');
    if (datosCliente && datosCliente.style.display !== 'none') {
        const camposRequired = datosCliente.querySelectorAll('[required]');
        camposRequired.forEach(function(campo) {
            // Para radio buttons, verificar que alguno esté seleccionado
            if (campo.type === 'radio') {
                const radioGroup = document.querySelectorAll('input[name="' + campo.name + '"]:checked');
                if (radioGroup.length === 0) {
                    camposClienteCompletos = false;
                }
            } else if (!campo.value || campo.value.trim() === '') {
                camposClienteCompletos = false;
            }
        });
    }
    
    if (!camposClienteCompletos) {
        btnCrearLote.disabled = true;
        return;
    }
    
    // Verificar condición 4: Todos los campos required del lote están completos
    let camposLoteCompletos = true;
    const datosLote = document.getElementById('datos_lote');
    if (datosLote && datosLote.style.display !== 'none') {
        // Verificar tipo_lote (radio button)
        const tipoLote = document.querySelector('input[name="tipo_lote"]:checked');
        if (!tipoLote) {
            camposLoteCompletos = false;
        }
        
        // Verificar cantidad_articulos
        const cantidadArticulos = document.getElementById('cantidad_articulos');
        if (!cantidadArticulos || !cantidadArticulos.value || parseFloat(cantidadArticulos.value) <= 0) {
            camposLoteCompletos = false;
        }
        
        // Verificar peso_neto (debe ser mayor a 0)
        const pesoNeto = document.getElementById('peso_neto');
        if (!pesoNeto || !pesoNeto.value || parseFloat(pesoNeto.value) <= 0) {
            camposLoteCompletos = false;
        }
        
        // Verificar peso_bruto (debe ser mayor a 0)
        const pesoBruto = document.getElementById('peso_bruto');
        if (!pesoBruto || !pesoBruto.value || parseFloat(pesoBruto.value) <= 0) {
            camposLoteCompletos = false;
        }
        
        // Verificar precio_compra (debe ser mayor a 0)
        const precioCompra = document.getElementById('precio_compra');
        if (!precioCompra || !precioCompra.value || parseFloat(precioCompra.value) <= 0) {
            camposLoteCompletos = false;
        }
        
        // Verificar opcion_compra (radio button)
        const opcionCompra = document.querySelector('input[name="opcion_compra"]:checked');
        if (!opcionCompra) {
            camposLoteCompletos = false;
        } else if (opcionCompra.value === 'si') {
            // Si opcion_compra es "si", verificar porcentaje_recompra y precio_recompra
            const porcentajeRecompra = document.getElementById('porcentaje_recompra');
            const precioRecompra = document.getElementById('precio_recompra');
            if (!porcentajeRecompra || !porcentajeRecompra.value || parseFloat(porcentajeRecompra.value) <= 0) {
                camposLoteCompletos = false;
            }
            if (!precioRecompra || !precioRecompra.value || parseFloat(precioRecompra.value) <= 0) {
                camposLoteCompletos = false;
            }
        }
        
        // Verificar metodo_pago (radio button)
        const metodoPago = document.querySelector('input[name="metodo_pago"]:checked');
        if (!metodoPago) {
            camposLoteCompletos = false;
        }
    } else {
        camposLoteCompletos = false;
    }
    
    // Habilitar o deshabilitar botón
    btnCrearLote.disabled = !(camposClienteCompletos && camposLoteCompletos);
}

/**
 * Validar un campo específico
 */
function validarCampo(field) {
    const isValid = field.checkValidity();
    const feedbackId = 'validation_feedback_' + field.id;
    let feedbackEl = document.getElementById(feedbackId);

    const mergeIdent =
        field.id === 'identificacion'
            ? field.closest('.inputgroupidentificacion, .input-group')
            : null;
    const label = mergeIdent
        ? mergeIdent.querySelector('label[for="' + field.id + '"]')
        : field.parentElement.querySelector('label[for="' + field.id + '"]');
    const container = field.parentElement;
    
    if (isValid) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');

        if (feedbackEl) {
            feedbackEl.remove();
            feedbackEl = null;
        }
        
        // Actualizar label si existe
        if (label) {
            label.classList.remove('text-danger');
            label.classList.add('text-success');
        }
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');

        const msg = (field.validationMessage && String(field.validationMessage).trim() !== '')
            ? String(field.validationMessage)
            : 'Error: campo no válido';

        if (!feedbackEl) {
            feedbackEl = document.createElement('div');
            feedbackEl.id = feedbackId;
            feedbackEl.className = 'invalid-feedback d-block';
        }
        if (mergeIdent) {
            mergeIdent.insertAdjacentElement('afterend', feedbackEl);
        } else if (label && container) {
            container.insertBefore(feedbackEl, label);
        } else if (container) {
            container.appendChild(feedbackEl);
        }
        feedbackEl.textContent = msg;
        
        // Actualizar label si existe
        if (label) {
            label.classList.remove('text-success');
            label.classList.add('text-danger');
        }
    }
    
    return isValid;
}

/**
 * Configurar validaciones especiales
 */
function configurarValidacionesEspeciales() {
    // Validación de código postal
    const codigoPostal = document.getElementById('codigo_postal');
    if (codigoPostal) {
        codigoPostal.addEventListener('input', function() {
            // Permitir números, letras y espacios/guiones (comunes en códigos postales)
            this.value = this.value.replace(/[^a-zA-Z0-9\s-]/g, '');
            
            // Convertir a mayúsculas (opcional, común en códigos postales)
            this.value = this.value.toUpperCase();
        });
    }
    
    // Validación de teléfono
    const telefono = document.getElementById('telefono');
    if (telefono) {
        let timeoutTelefono = null;
        
        telefono.addEventListener('input', function() {
            // Permitir números, espacios, +, - y paréntesis
            this.value = this.value.replace(/[^0-9\s\+\-\(\)]/g, '');
            
            // Limpiar timeout anterior
            if (timeoutTelefono) {
                clearTimeout(timeoutTelefono);
            }
            
            // Verificar si el teléfono ya existe (después de 500ms de inactividad)
            const telefonoValor = this.value.trim();
            if (telefonoValor.length >= 9) {
                timeoutTelefono = setTimeout(function() {
                    verificarTelefonoExistente(telefonoValor);
                }, 500);
            }
        });
    }
    
    // Validación de email
    const email = document.getElementById('email');
    if (email) {
        email.addEventListener('blur', function() {
            if (this.value && !this.checkValidity()) {
                this.classList.add('is-invalid');
            }
        });
    }
    
    // Configurar formato decimal para peso_neto, peso_bruto, precio_compra y merma
    function configurarFormatoDecimal(campoId) {
        const campo = document.getElementById(campoId);
        if (campo) {
            // Reemplazar coma por punto mientras escribe (solo si hay coma)
            campo.addEventListener('input', function(e) {
                if (this.value.includes(',')) {
                    this.value = this.value.replace(/,/g, '.');
                }
            });
            
            // Formatear con 2 decimales al salir del campo solo si hay un valor válido
            campo.addEventListener('blur', function() {
                const valorStr = this.value.trim();
                // Si el campo está vacío o solo tiene punto/coma, dejarlo vacío
                if (valorStr === '' || valorStr === '.' || valorStr === ',') {
                    this.value = '';
                    return;
                }
                
                const valor = parseFloat(valorStr);
                // Solo formatear si es un número válido y mayor a cero
                if (!isNaN(valor) && valor > 0) {
                    this.value = valor.toFixed(2);
                }
            });
        }
    }
    
    // Aplicar formato decimal a los campos especificados
    configurarFormatoDecimal('peso_neto');
    configurarFormatoDecimal('peso_bruto');
    configurarFormatoDecimal('precio_compra');
    configurarFormatoDecimal('merma');
    
    // Validación de peso neto vs peso bruto
    const pesoNeto = document.getElementById('peso_neto');
    const pesoBruto = document.getElementById('peso_bruto');
    
    function validarPesos(campoEditado) {
        if (pesoNeto && pesoBruto) {
            const pesoNetoValor = parseFloat(pesoNeto.value);
            const pesoBrutoValor = parseFloat(pesoBruto.value);
            
            // Solo validar si ambos campos tienen valores numéricos válidos
            if (!isNaN(pesoNetoValor) && !isNaN(pesoBrutoValor) && pesoNetoValor > 0 && pesoBrutoValor > 0) {
                if (pesoNetoValor > pesoBrutoValor) {
                    Swal.fire({
                        title: 'Error',
                        text: 'El peso neto no puede ser mayor que el peso bruto',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    // Limpiar el campo que causó el error
                    if (campoEditado === 'peso_neto') {
                        pesoNeto.value = '';
                        pesoNeto.focus();
                    } else if (campoEditado === 'peso_bruto') {
                        pesoBruto.value = '';
                        pesoBruto.focus();
                    }
                }
            }
        }
    }
    
    if (pesoNeto) {
        pesoNeto.addEventListener('blur', function() {
            validarPesos('peso_neto');
            calcularMerma();
            verificarEstadoBotonCrear();
        });
        pesoNeto.addEventListener('input', function() {
            // Solo calcular merma en tiempo real, no validar pesos
            calcularMerma();
            verificarEstadoBotonCrear();
        });
    }
    
    if (pesoBruto) {
        pesoBruto.addEventListener('blur', function() {
            validarPesos('peso_bruto');
            calcularMerma();
            verificarEstadoBotonCrear();
        });
        pesoBruto.addEventListener('input', function() {
            // Solo calcular merma en tiempo real, no validar pesos
            calcularMerma();
            verificarEstadoBotonCrear();
        });
    }
    
    // Agregar listeners a cantidad_articulos y precio_compra
    const cantidadArticulos = document.getElementById('cantidad_articulos');
    if (cantidadArticulos) {
        cantidadArticulos.addEventListener('input', verificarEstadoBotonCrear);
        cantidadArticulos.addEventListener('blur', verificarEstadoBotonCrear);
    }
    
    const precioCompra = document.getElementById('precio_compra');
    if (precioCompra) {
        precioCompra.addEventListener('input', verificarEstadoBotonCrear);
        precioCompra.addEventListener('blur', verificarEstadoBotonCrear);
    }
    
    // Agregar listeners a los radio buttons de tipo_lote
    const tipoLoteRadios = document.querySelectorAll('input[name="tipo_lote"]');
    tipoLoteRadios.forEach(function(radio) {
        radio.addEventListener('change', verificarEstadoBotonCrear);
    });
    
    // Agregar listeners a los radio buttons de metodo_pago
    const metodoPagoRadios = document.querySelectorAll('input[name="metodo_pago"]');
    metodoPagoRadios.forEach(function(radio) {
        radio.addEventListener('change', verificarEstadoBotonCrear);
    });
    
    // Calcular merma automáticamente cuando se ingresen peso neto y peso bruto
    function calcularMerma() {
        const merma = document.getElementById('merma');
        if (pesoNeto && pesoBruto && merma) {
            const pesoNetoValor = parseFloat(pesoNeto.value);
            const pesoBrutoValor = parseFloat(pesoBruto.value);
            
            // Solo calcular si ambos campos tienen valores numéricos válidos
            if (!isNaN(pesoNetoValor) && !isNaN(pesoBrutoValor) && pesoNetoValor > 0 && pesoBrutoValor > 0) {
                const mermaCalculada = pesoBrutoValor - pesoNetoValor;
                if (mermaCalculada >= 0) {
                    merma.value = mermaCalculada.toFixed(2);
                }
            }
        }
    }
    
    // Validación de merma vs peso bruto y peso neto
    const merma = document.getElementById('merma');
    
    function validarMerma() {
        if (merma) {
            const mermaValor = parseFloat(merma.value);
            
            if (!isNaN(mermaValor) && mermaValor > 0) {
                const pesoNetoValor = parseFloat(pesoNeto?.value || 0);
                const pesoBrutoValor = parseFloat(pesoBruto?.value || 0);
                
                // Validar que la merma no sea mayor que peso bruto
                if (pesoBrutoValor > 0 && mermaValor > pesoBrutoValor) {
                    Swal.fire({
                        title: 'Error',
                        text: 'La merma no puede ser mayor que el peso bruto',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    merma.value = '';
                    merma.focus();
                    return;
                }
                
                // Validar que la merma no sea mayor que peso neto
                if (pesoNetoValor > 0 && mermaValor > pesoNetoValor) {
                    Swal.fire({
                        title: 'Error',
                        text: 'La merma no puede ser mayor que el peso neto',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    merma.value = '';
                    merma.focus();
                    return;
                }
            }
        }
    }
    
    if (merma) {
        merma.addEventListener('blur', validarMerma);
    }
    
    // Validación de fecha de nacimiento
    const fNacimiento = document.getElementById('f_nacimiento');
    if (fNacimiento) {
        fNacimiento.addEventListener('change', function() {
            const fechaSeleccionada = new Date(this.value);
            const fechaActual = new Date();
            
            // Calcular edad
            let edad = fechaActual.getFullYear() - fechaSeleccionada.getFullYear();
            const mes = fechaActual.getMonth() - fechaSeleccionada.getMonth();
            if (mes < 0 || (mes === 0 && fechaActual.getDate() < fechaSeleccionada.getDate())) {
                edad--;
            }
            
            if (fechaSeleccionada > fechaActual) {
                Swal.fire({
                    title: 'Error',
                    text: 'La fecha de nacimiento no puede ser futura',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#dc3545'
                });
                this.value = '';
                this.setCustomValidity('La fecha de nacimiento no puede ser futura');
                this.classList.add('is-invalid');
            } else if (edad < 18) {
                Swal.fire({
                    title: 'Error',
                    text: 'El cliente debe ser mayor de edad (18 años)',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#dc3545'
                });
                this.value = '';
                this.setCustomValidity('Debe ser mayor de edad');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }
    
    // Validación de fecha de vencimiento
    const fVencimiento = document.getElementById('f_vencimiento');
    if (fVencimiento) {
        fVencimiento.addEventListener('change', function() {
            const fechaSeleccionada = new Date(this.value);
            const fechaActual = new Date();
            
            if (fechaSeleccionada < fechaActual) {
                Swal.fire({
                    title: 'Error',
                    text: 'La fecha de vencimiento debe ser mayor a la fecha actual',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#dc3545'
                });
                this.value = '';
                this.setCustomValidity('La fecha de vencimiento no puede ser pasada');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }
}

/**
 * Validar identificación según tipo
 */
function validarIdentificacion() {
    const tipoIdentificacion = document.getElementById('tipo_identificacion').value;
    const identificacion = document.getElementById('identificacion').value;
    
    if (!tipoIdentificacion || !identificacion) return true;
    
    // TODO: Implementar validación
    
    return true;
}

/**
 * Verificar si el teléfono ya existe en la base de datos (solo validación de duplicados)
 */
function verificarTelefonoExistente(telefono) {
    if (!telefono || telefono.length < 9) return;

    const idClienteEl = document.getElementById('id_cliente');
    let idCliente = idClienteEl && idClienteEl.value ? String(idClienteEl.value).trim() : '';
    if (idCliente === 'false') {
        idCliente = '';
    }

    $.ajax({
        url: 'parts/lotes/crear/ajax_verificar_cliente.php',
        method: 'GET',
        dataType: 'json',
        data: {
            action: 'verificar_telefono',
            valor: telefono,
            id_cliente: idCliente
        },
        success: function(response) {
            if (response.existe) {
                Swal.fire({
                    title: '¡Teléfono Duplicado!',
                    text: response.message,
                    icon: 'warning',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#f39c12'
                });
                
                // Vaciar el campo
                document.getElementById('telefono').value = '';
                document.getElementById('telefono').focus();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al verificar teléfono:', error);
        }
    });
}

/**
 * Verificar si la identificación ya existe en la base de datos
 * Si existe, autocompleta el formulario con los datos del cliente
 */
function verificarIdentificacionExistente(identificacion) {
    if (!identificacion || identificacion.length < 5) {
        console.log('Identificación muy corta o vacía');
        return;
    }
    // AQUI LOADER
    mostrarLoaderUniversal("Comprobando cliente...");
    
    console.log('Iniciando AJAX para verificar identificación:', identificacion);
    
    $.ajax({
        url: 'parts/lotes/crear/ajax_verificar_cliente.php',
        method: 'GET',
        dataType: 'json',
        data: {
            action: 'verificar_identificacion',
            valor: identificacion
        },
        success: function(response) {
            setTimeout(function() {
                
                console.log('Respuesta AJAX identificación:', response);
                
                if (response.existe) {
                    autocompletarFormularioCliente(response.cliente, response.direccion, response.datos_cliente);
                } else {
                    ocultarLoaderUniversal();
                    console.log('Identificación NO existe en BD');
                }
            }, 3000);
        },
        error: function(xhr, status, error) {
            ocultarLoaderUniversal();
            console.error('Error al verificar identificación:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
        }
    });
}

/**
 * Cargar formulario de dirección dinámicamente
 */
function cargarFormularioDireccion(tipo, callback) {
    $.ajax({
        url: 'parts/lotes/crear/load_formulario_direccion.php',
        method: 'GET',
        data: { tipo: tipo },
        success: function(html) {
            $('#container_direccion').html(html);
            
            if (typeof window.inicializarDireccionesSelect2ConAjax === 'function') {
                window.inicializarDireccionesSelect2ConAjax();
            } else if (typeof inicializarSelect2Direcciones === 'function') {
                inicializarSelect2Direcciones();
            }
            
            // Ejecutar callback si existe
            if (callback) {
                setTimeout(callback, 500);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar formulario de dirección:', error);
        }
    });
}

/**
 * Habilita o deshabilita todos los input/select del formulario de lote excepto `tipo_identificacion`.
 * No toca hidden ni submit/button. Los controles dentro de `.input-group` usan la clase `disabled`
 * del grupo (estilos en core.css). También `btnSolicitarAutorizacionSMS` y `btn_comprobar_identificacion` si existe.
 */
function aplicarDisabledFormLoteExceptoTipo(form, deshabilitar) {
    if (!form) return;
    const inputGruposAfectar = new Set();
    form.querySelectorAll('input, select').forEach(function (el) {
        if (el.id === 'tipo_identificacion') return;
        const t = (el.type || '').toLowerCase();
        if (t === 'hidden' || t === 'submit' || t === 'button' || t === 'reset') return;
        if (el.readOnly) return;
        const grupo = el.closest('.input-group-merge, .input-group');
        if (grupo && form.contains(grupo)) {
            inputGruposAfectar.add(grupo);
            return;
        }
        el.disabled = deshabilitar;
    });
    inputGruposAfectar.forEach(function (grupo) {
        grupo.classList.toggle('disabled', deshabilitar);
    });
    const btnSms = document.getElementById('btnSolicitarAutorizacionSMS');
    if (btnSms) {
        btnSms.disabled = deshabilitar;
    }
    const btnComprobarIdent = document.getElementById('btn_comprobar_identificacion');
    if (btnComprobarIdent) {
        btnComprobarIdent.disabled = deshabilitar;
    }
}

/**
 * Autocompletar formulario con datos del cliente existente
 */
function autocompletarFormularioCliente(cliente, direccion, datos_cliente) {
    console.log('Autocompletando formulario con:', cliente, direccion, datos_cliente);
    
    // Activar flag para prevenir bucle
    autocompletandoCliente = true;
    
    // Cargar formulario de dirección EDIT primero
    cargarFormularioDireccion('edit', function() {
        // Después de cargar el formulario, autocompletar datos
        autocompletarDatosCliente(cliente, direccion, datos_cliente);
        
        // Desactivar flag después de un delay
        setTimeout(function() {
            autocompletandoCliente = false;
            console.log('Autocompletado finalizado');
        }, 1000);
    });
}

/**
 * Autocompletar datos del cliente (después de cargar el formulario correcto)
 */
function autocompletarDatosCliente(cliente, direccion, datos_cliente) {
    console.log('Autocompletando datos con:', cliente, direccion, datos_cliente);

    const formLoteAuto = document.getElementById('formCrearLote');
    if (formLoteAuto) {
        aplicarDisabledFormLoteExceptoTipo(formLoteAuto, false);
    }
    
    // Poner el ID del cliente en el campo hidden
    document.getElementById('id_cliente').value = cliente.id_cliente;
    
    // Datos de identificación
    if (cliente.tipo_identificacion_id) {
        $('#tipo_identificacion').val(cliente.tipo_identificacion_id).trigger('change');
    }
    if (cliente.identificacion) {
        const campoIdentificacion = document.getElementById('identificacion');
        if (campoIdentificacion) campoIdentificacion.value = cliente.identificacion;
    }
    if (cliente.nacionalidad_id) {
        $('#nacionalidad').val(cliente.nacionalidad_id).trigger('change');
    }
    
    // Fecha de vencimiento (prioridad a datos_cliente)
    const f_vencimiento = datos_cliente?.f_vencimiento || cliente.f_vencimiento;
    if (f_vencimiento) {
        document.getElementById('f_vencimiento').value = f_vencimiento;
    }
    
    // Datos personales
    if (cliente.nombre) {
        document.getElementById('nombre').value = cliente.nombre;
    }
    if (cliente.apellido) {
        document.getElementById('apellido').value = cliente.apellido;
    }
    
    // Fecha de nacimiento (prioridad a datos_cliente)
    const f_nacimiento = datos_cliente?.f_nacimiento || cliente.f_nacimiento;
    if (f_nacimiento) {
        document.getElementById('f_nacimiento').value = f_nacimiento;
    }
    
    // Sexo (prioridad a datos_cliente)
    const sexo = datos_cliente?.sexo || cliente.sexo;
    if (sexo) {
        $('#sexo').val(sexo).trigger('change');
    }
    
    const email = datos_cliente?.email || cliente.email;
    if (email) {
        document.getElementById('email').value = email;
    }
    
    // Datos de dirección si existen
    if (direccion) {
        if (direccion.direccion) {
            const direccionField = document.getElementById('direccion');
            if (direccionField) direccionField.value = direccion.direccion;
        }
        if (direccion.codigo_postal) {
            const codigoPostalField = document.getElementById('codigo_postal');
            if (codigoPostalField) codigoPostalField.value = direccion.codigo_postal;
        }
        
        // Cargar país si existe
        if (direccion.rel_id_pais && direccion.c_pais) {
            const paisField = $('#pais');
            if (paisField.length) {
                // Crear la opción y agregarla al select
                const newOption = new Option(direccion.c_pais, direccion.rel_id_pais, true, true);
                paisField.append(newOption).trigger('change');
            }
        }
        
        // Cargar provincia si existe (con timeout para que país cargue primero)
        if (direccion.rel_id_provincia && direccion.c_provincia) {
            setTimeout(function() {
                const provinciaField = $('#c_provincia');
                if (provinciaField.length) {
                    // Crear la opción y agregarla al select
                    const newOption = new Option(direccion.c_provincia, direccion.rel_id_provincia, true, true);
                    provinciaField.append(newOption).trigger('change');
                }
            }, 500);
        }
        
        // Cargar población si existe (con timeout para que provincia cargue primero)
        if (direccion.rel_id_poblacion && direccion.c_poblacion) {
            setTimeout(function() {
                const poblacionField = $('#c_poblacion');
                if (poblacionField.length) {
                    // Crear la opción y agregarla al select
                    const newOption = new Option(direccion.c_poblacion, direccion.rel_id_poblacion, true, true);
                    poblacionField.append(newOption).trigger('change');
                }
            }, 1000);
        }
    }

    // Teléfono: solo `clientes.telefono`. Se aplica al final (tras cargar dirección/selects) para que no lo pise otro handler.
    if (cliente && cliente.telefono !== undefined && cliente.telefono !== null) {
        const telStr = String(cliente.telefono).trim();
        if (telStr !== '') {
            setTimeout(function() {
                const inpTel = document.getElementById('telefono');
                if (inpTel) {
                    inpTel.value = telStr;
                }
            }, 1250);
        }
    }

    ocultarLoaderUniversal();
    // Verificar estado del botón después de autocompletar
    setTimeout(function() {
        verificarEstadoBotonCrear();
    }, 1500);
    
    Swal.fire({
        title: 'Datos Cargados',
        text: 'El formulario se ha completado con los datos del cliente existente',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    });
}

// Flag para prevenir bucle al autocompletar
var autocompletandoCliente = false;

// Agregar validación de identificación al cambiar tipo
document.addEventListener('DOMContentLoaded', function() {
    const tipoIdentificacion = document.getElementById('tipo_identificacion');
    const identificacion = document.getElementById('identificacion');
    const formLote = document.getElementById('formCrearLote');
    
    if (tipoIdentificacion && identificacion && formLote) {
        aplicarDisabledFormLoteExceptoTipo(formLote, true);
        identificacion.placeholder = 'Primero seleccione el tipo de identificación';
        
        // Habilitar/deshabilitar campo según selección de tipo (usar jQuery para Select2)
        $(tipoIdentificacion).on('change', function() {
            if (autocompletandoCliente) {
                if (this.value) {
                    identificacion.disabled = false;
                }
                return;
            }
            
            if (this.value) {
                identificacion.disabled = false;
                identificacion.placeholder = 'Número de identificación';
                window.setTimeout(function () {
                    identificacion.focus();
                }, 50);
            } else {
                aplicarDisabledFormLoteExceptoTipo(formLote, true);
                identificacion.value = '';
                identificacion.placeholder = 'Primero seleccione el tipo de identificación';
                identificacion.classList.remove('is-valid', 'is-invalid');
            }
            validarIdentificacion();
        });

        $(tipoIdentificacion).on('select2:close', function () {
            if (autocompletandoCliente) return;
            if (!$(tipoIdentificacion).val()) return;
            window.setTimeout(function () {
                if (!identificacion.disabled) {
                    identificacion.focus();
                }
            }, 10);
        });
        
        identificacion.addEventListener('input', validarIdentificacion);
        
        // Verificar si la identificación ya existe al salir del campo (blur)
        identificacion.addEventListener('blur', function() {
            // No validar si estamos autocompletando
            if (autocompletandoCliente) {
                console.log('Autocompletando, no validar');
                return;
            }
            
            const identificacionValor = this.value.trim();
            const tipoIdValor = tipoIdentificacion.value;
            
            console.log('Blur en identificación:', identificacionValor);
            console.log('Tipo identificación:', tipoIdValor);
            
            // Si está vacío o no hay tipo seleccionado, no validar
            if (!identificacionValor || !tipoIdValor) {
                console.log('Campo vacío o sin tipo seleccionado');
                return;
            }
            
            // Validar formato con validarIdentificacionSpain si está disponible
            if (typeof validarIdentificacionSpain === 'function') {
                const resultado = validarIdentificacionSpain(tipoIdValor, identificacionValor);
                
                console.log('Resultado validación España:', resultado);
                
                // Aplicar resultado de la validación
                const campoIdentificacion = document.getElementById('identificacion');
                campoIdentificacion.setCustomValidity(resultado.valido ? '' : resultado.mensaje);
                validarCampo(campoIdentificacion);
                
                // Si no es válido, no continuar con la verificación en BD
                if (!resultado.valido) {
                    console.log('Identificación no válida según formato, no se verifica en BD');
                    return;
                }
            }
            
            // Si la validación de formato pasó, verificar si existe en BD (para autocompletar)
            if (identificacionValor.length >= 5) {
                console.log('Llamando a verificarIdentificacionExistente');
                verificarIdentificacionExistente(identificacionValor);
            } else {
                console.log('No cumple condiciones - Longitud:', identificacionValor.length);
            }
        });
    }
});

/**
 * Función global para mostrar fecha de liberación (siempre visible)
 */
function mostrarFechaLiberacionGlobal() {
    const fechaLiberacionHidden = document.getElementById('fecha_liberacion_hidden');
    const fechaLiberacionMostrada = document.getElementById('fecha_liberacion_mostrada');
    
    if (!fechaLiberacionHidden || !fechaLiberacionMostrada) return;
    
    // Calcular fecha + 14 días
    const hoy = new Date();
    const fechaLiberacion = new Date(hoy);
    fechaLiberacion.setDate(fechaLiberacion.getDate() + 14);
    
    // Formatear fecha a YYYY-MM-DD
    const year = fechaLiberacion.getFullYear();
    const month = String(fechaLiberacion.getMonth() + 1).padStart(2, '0');
    const day = String(fechaLiberacion.getDate()).padStart(2, '0');
    const fechaLibISO = `${year}-${month}-${day}`;
    
    // Formatear fecha a DD-MM-YYYY
    const fechaLibES = `${day}-${month}-${year}`;
    
    fechaLiberacionHidden.value = fechaLibISO;
    fechaLiberacionMostrada.innerHTML = '<strong>Fecha de liberación:</strong> ' + fechaLibES;
    fechaLiberacionMostrada.style.display = 'block';
}

/**
 * Calcular precio de recompra basado en precio_compra y porcentaje_recompra
 */
function calcularPrecioRecompra() {
    const precioCompra = parseFloat(document.getElementById('precio_compra').value) || 0;
    const porcentajeRecompra = parseFloat(document.getElementById('porcentaje_recompra').value) || 0;
    const precioRecompra = document.getElementById('precio_recompra');
    
    if (precioRecompra) {
        // Calcular: precio_recompra = precio_compra + (precio_compra * porcentaje_recompra / 100)
        const resultado = precioCompra + (precioCompra * porcentajeRecompra / 100);
        // Redondear hacia arriba sin decimales
        precioRecompra.value = Math.ceil(resultado);
        // Verificar estado del botón después de actualizar
        verificarEstadoBotonCrear();
    }
}

// Variables globales para almacenar datos de autorización
let idAutorizacionPorcentaje = null;
let nuevoPorcentajeRecompra = null;
let intervaloPolling = null;
// Variable global para almacenar el texto original del botón editar porcentaje
let textoOriginalBtnEditarPorcentaje = 'Editar porcentaje';
// Variable global para el timeout de polling de autorización SMS
let timeoutAutorizacionSMS = null;

/**
 * Mostrar loader de autorización
 */
function mostrarLoaderAutorizacion() {
    const loaderContainer = document.getElementById('loaderContainer');
    const textLoader = document.getElementById('textLoader');
    
    if (loaderContainer) {
        loaderContainer.style.display = 'flex';
    }
    
    if (textLoader) {
        textLoader.textContent = 'Esperando autorización...';
    }
}

/**
 * Ocultar loader de autorización
 */
function ocultarLoaderAutorizacion() {
    const loaderContainer = document.getElementById('loaderContainer');
    const textLoader = document.getElementById('textLoader');
    
    if (loaderContainer) {
        loaderContainer.style.display = 'none';
    }
    
    if (textLoader) {
        textLoader.textContent = '';
    }
    
    // Limpiar intervalo de polling
    if (intervaloPolling) {
        clearInterval(intervaloPolling);
        intervaloPolling = null;
    }
}

/**
 * Verificar estado de autorización
 */
function verificarEstadoAutorizacion() {
    if (!idAutorizacionPorcentaje) {
        console.log('No hay ID de autorización, ocultando loader');
        ocultarLoaderAutorizacion();
        return;
    }
    
    console.log('Verificando estado de autorización, ID:', idAutorizacionPorcentaje);
    
    fetch('parts/lotes/crear/verificar_estado_autorizacion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_autorizacion=' + idAutorizacionPorcentaje
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta verificación estado:', data);
        
        if (data.success) {
            // Verificar si está autorizada (comparación case-insensitive)
            const estadoAutorizada = data.estado_autorizacion && 
                                    data.estado_autorizacion.toLowerCase() === 'autorizada';
            
            console.log('Estado autorización:', data.estado_autorizacion, 'Autorizada:', estadoAutorizada);
            
            if (estadoAutorizada) {
                // Autorización aprobada
                console.log('Autorización aprobada, actualizando porcentaje');
                
                // Actualizar porcentaje de recompra con el valor de intereses_lote
                const campoPorcentajeRecompra = document.getElementById('porcentaje_recompra');
                if (campoPorcentajeRecompra && data.intereses_lote !== null && data.intereses_lote !== undefined) {
                    campoPorcentajeRecompra.value = data.intereses_lote;
                    // Recalcular precio de recompra
                    if (typeof calcularPrecioRecompra === 'function') {
                        calcularPrecioRecompra();
                    }
                }
                
                // Ocultar loader después de actualizar
                ocultarLoaderAutorizacion();
                
                // Restaurar botón de solicitar autorización
                const btnSolicitarAutorizacion = document.getElementById('btnSolicitarAutorizacion');
                const loaderSolicitarAutorizacion = document.getElementById('loaderSolicitarAutorizacion');
                if (btnSolicitarAutorizacion) {
                    btnSolicitarAutorizacion.disabled = false;
                }
                if (loaderSolicitarAutorizacion) {
                    loaderSolicitarAutorizacion.classList.add('d-none');
                }
                
                // Restaurar botón de editar porcentaje
                const btnEditarPorcentaje = document.getElementById('btnEditarPorcentaje');
                if (btnEditarPorcentaje) {
                    btnEditarPorcentaje.disabled = false;
                    // Restaurar texto original del botón
                    btnEditarPorcentaje.innerHTML = textoOriginalBtnEditarPorcentaje || 'Editar porcentaje';
                }
                
                Swal.fire({
                    title: 'Autorización Aprobada',
                    text: 'El porcentaje de recompra ha sido actualizado',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Limpiar variables
                idAutorizacionPorcentaje = null;
                nuevoPorcentajeRecompra = null;
            } else if (data.estado_autorizacion && data.estado_autorizacion.toLowerCase() === 'cancelada') {
                // Autorización cancelada
                console.log('Autorización cancelada');
                
                // Ocultar loader
                ocultarLoaderAutorizacion();
                
                // Restaurar botón de solicitar autorización
                const btnSolicitarAutorizacion = document.getElementById('btnSolicitarAutorizacion');
                const loaderSolicitarAutorizacion = document.getElementById('loaderSolicitarAutorizacion');
                if (btnSolicitarAutorizacion) {
                    btnSolicitarAutorizacion.disabled = false;
                }
                if (loaderSolicitarAutorizacion) {
                    loaderSolicitarAutorizacion.classList.add('d-none');
                }
                
                // Restaurar botón de editar porcentaje
                const btnEditarPorcentaje = document.getElementById('btnEditarPorcentaje');
                if (btnEditarPorcentaje) {
                    btnEditarPorcentaje.disabled = false;
                    // Restaurar texto original del botón
                    btnEditarPorcentaje.innerHTML = textoOriginalBtnEditarPorcentaje || 'Editar porcentaje';
                }
                
                Swal.fire({
                    title: 'Autorización Cancelada',
                    text: 'La solicitud de autorización ha sido cancelada',
                    icon: 'info',
                    confirmButtonText: 'Aceptar'
                });
                
                // Limpiar variables
                idAutorizacionPorcentaje = null;
                nuevoPorcentajeRecompra = null;
            } else {
                // No está autorizada aún, continuar polling
                console.log('Autorización pendiente, continuando polling...');
            }
        } else {
            console.error('Error al verificar estado:', data.error);
            // No ocultar loader si hay error, continuar intentando
        }
    })
    .catch(error => {
        console.error('Error al verificar estado de autorización:', error);
        // No ocultar loader si hay error de conexión, continuar intentando
    });
}

/**
 * Iniciar polling para verificar estado de autorización cada 5 segundos
 */
function iniciarPollingAutorizacion() {
    // Limpiar intervalo anterior si existe
    if (intervaloPolling) {
        clearInterval(intervaloPolling);
    }
    
    // Verificar inmediatamente
    verificarEstadoAutorizacion();
    
    // Verificar cada 5 segundos
    intervaloPolling = setInterval(function() {
        verificarEstadoAutorizacion();
    }, 5000);
}

/**
 * Configurar modal para editar porcentaje de recompra
 */
function configurarModalEditarPorcentaje() {
    const btnEditarPorcentaje = document.getElementById('btnEditarPorcentaje');
    const modalSolicitarAutorizacion = document.getElementById('modalSolicitarAutorizacion');
    
    if (btnEditarPorcentaje && modalSolicitarAutorizacion) {
        // Abrir modal de solicitar autorización al hacer clic en el botón
        btnEditarPorcentaje.addEventListener('click', function() {
            // Deshabilitar botón y mostrar loader
            btnEditarPorcentaje.disabled = true;
            // Guardar texto original en variable global
            textoOriginalBtnEditarPorcentaje = btnEditarPorcentaje.innerHTML;
            btnEditarPorcentaje.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Cargando...';
            
            // Verificar que haya sucursal y lote
            const inputSucursalLote = document.getElementById('sucursal_lote');
            const sucursalLote = inputSucursalLote ? inputSucursalLote.value : '';
            const idLote = document.getElementById('id_lote').value;
            
            if (!sucursalLote || !idLote) {
                Swal.fire({
                    title: 'Error',
                    text: 'Debe seleccionar una sucursal y tener un número de lote',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
                btnEditarPorcentaje.disabled = false;
                btnEditarPorcentaje.innerHTML = textoOriginalBtnEditarPorcentaje || 'Editar porcentaje';
                return;
            }
            
            // Abrir modal de solicitar autorización
            const modal = new bootstrap.Modal(modalSolicitarAutorizacion);
            modal.show();
            
            // Mostrar porcentaje actual
            const porcentajeActual = document.getElementById('porcentajeActualRecompra');
            const porcentajeRecompraActual = document.getElementById('porcentaje_recompra').value;
            if (porcentajeActual) {
                porcentajeActual.value = porcentajeRecompraActual || '0';
            }
            
            // Limpiar campo al abrir
            const nuevoPorcentaje = document.getElementById('nuevoPorcentajeRecompra');
            if (nuevoPorcentaje) {
                nuevoPorcentaje.value = '';
                nuevoPorcentaje.focus();
            }
            
            // Restaurar botón cuando se cierre el modal (solo si no hay polling activo)
            modalSolicitarAutorizacion.addEventListener('hidden.bs.modal', function() {
                // Solo restaurar botón y limpiar si no hay polling activo (usuario canceló sin solicitar)
                if (!intervaloPolling && !idAutorizacionPorcentaje) {
                    btnEditarPorcentaje.disabled = false;
                    btnEditarPorcentaje.innerHTML = textoOriginalBtnEditarPorcentaje || 'Editar porcentaje';
                    // Restaurar botón de solicitar autorización
                    const btnSolicitarAutorizacion = document.getElementById('btnSolicitarAutorizacion');
                    const loaderSolicitarAutorizacion = document.getElementById('loaderSolicitarAutorizacion');
                    if (btnSolicitarAutorizacion) {
                        btnSolicitarAutorizacion.disabled = false;
                    }
                    if (loaderSolicitarAutorizacion) {
                        loaderSolicitarAutorizacion.classList.add('d-none');
                    }
                    // Asegurarse de que el loader esté oculto si se canceló
                    ocultarLoaderAutorizacion();
                }
            });
        });
        
        // Botón solicitar autorización
        const btnSolicitarAutorizacion = document.getElementById('btnSolicitarAutorizacion');
        const loaderSolicitarAutorizacion = document.getElementById('loaderSolicitarAutorizacion');
        
        if (btnSolicitarAutorizacion) {
            btnSolicitarAutorizacion.addEventListener('click', function() {
                const nuevoPorcentaje = document.getElementById('nuevoPorcentajeRecompra');
                
                if (!nuevoPorcentaje || !nuevoPorcentaje.value || parseFloat(nuevoPorcentaje.value) < 0) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Debe ingresar un porcentaje válido',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }
                
                // Deshabilitar botón y mostrar loader
                btnSolicitarAutorizacion.disabled = true;
                if (loaderSolicitarAutorizacion) {
                    loaderSolicitarAutorizacion.classList.remove('d-none');
                }
                
                // Guardar nuevo porcentaje
                nuevoPorcentajeRecompra = parseFloat(nuevoPorcentaje.value);
                
                // Obtener datos necesarios
                const inputSucursalLote = document.getElementById('sucursal_lote');
                const sucursalLote = inputSucursalLote ? inputSucursalLote.value : '';
                const idLote = document.getElementById('id_lote').value;
                const porcentajeRecompraActual = document.getElementById('porcentaje_recompra').value;
                
                // Generar código de autorización vía AJAX
                fetch('parts/lotes/crear/generar_codigo_autorizacion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'sucursal_autorizacion=' + sucursalLote + 
                          '&lote_autorizacion=' + idLote + 
                          '&intereses_originales=' + porcentajeRecompraActual +
                          '&nuevo_porcentaje_recompra=' + nuevoPorcentajeRecompra
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Guardar ID de autorización en variable global
                        idAutorizacionPorcentaje = data.id_autorizacion;
                        console.log('Código generado, ID autorización:', idAutorizacionPorcentaje);
                        
                        // Cerrar modal de solicitar autorización
                        const modalSolicitar = bootstrap.Modal.getInstance(modalSolicitarAutorizacion);
                        if (modalSolicitar) {
                            modalSolicitar.hide();
                        }
                        
                        // Esperar un momento para que el modal se cierre completamente antes de mostrar loader
                        setTimeout(function() {
                            // Mostrar loader y empezar polling
                            console.log('Mostrando loader e iniciando polling');
                            mostrarLoaderAutorizacion();
                            iniciarPollingAutorizacion();
                        }, 300);
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.error || 'Error al generar código de autorización',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                        btnSolicitarAutorizacion.disabled = false;
                        if (loaderSolicitarAutorizacion) {
                            loaderSolicitarAutorizacion.classList.add('d-none');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Error al conectar con el servidor',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    btnSolicitarAutorizacion.disabled = false;
                    if (loaderSolicitarAutorizacion) {
                        loaderSolicitarAutorizacion.classList.add('d-none');
                    }
                });
            });
        }
    }
}

/**
 * Configurar cálculo automático de precio de recompra
 */
function configurarCalculoPrecioRecompra() {
    const precioCompra = document.getElementById('precio_compra');
    const porcentajeRecompra = document.getElementById('porcentaje_recompra');
    
    // Calcular cuando cambie precio_compra
    if (precioCompra) {
        precioCompra.addEventListener('input', calcularPrecioRecompra);
        precioCompra.addEventListener('change', calcularPrecioRecompra);
    }
    
    // Calcular cuando cambie porcentaje_recompra
    if (porcentajeRecompra) {
        porcentajeRecompra.addEventListener('input', calcularPrecioRecompra);
        porcentajeRecompra.addEventListener('change', calcularPrecioRecompra);
    }
    
    // Calcular inicialmente si hay valores
    calcularPrecioRecompra();
}

/**
 * Configurar opción de compra, fecha de vencimiento y fecha de liberación
 */
function configurarOpcionCompra() {
    const opcionCompraSi = document.getElementById('opcion_compra_si');
    const opcionCompraNo = document.getElementById('opcion_compra_no');
    const fechaVencimientoInput = document.getElementById('fecha_vencimiento_input');
    const fechaVencimientoMostrada = document.getElementById('fecha_vencimiento_mostrada');
    const contenedorPorcentajeRecompra = document.getElementById('contenedor_porcentaje_recompra');
    const contenedorPrecioRecompra = document.getElementById('contenedor_precio_recompra');
    
    // Función para manejar cambio de opción
    function manejarCambioOpcion() {
        console.log('manejarCambioOpcion');
        if (opcionCompraSi && opcionCompraSi.checked) {
            // Si selecciona "Si" (EMPEÑO) - Mostrar fecha de vencimiento, porcentaje y precio de recompra
            if (fechaVencimientoMostrada) {
                fechaVencimientoMostrada.style.display = 'block';
            }
            if (contenedorPorcentajeRecompra) {
                contenedorPorcentajeRecompra.style.display = 'block';
            }
            if (contenedorPrecioRecompra) {
                contenedorPrecioRecompra.style.display = 'block';
            }
        } else if (opcionCompraNo && opcionCompraNo.checked) {
            // Si selecciona "No" (COMPRA) - Ocultar fecha de vencimiento, porcentaje y precio de recompra
            if (fechaVencimientoMostrada) {
                fechaVencimientoMostrada.style.display = 'none';
            }
            if (contenedorPorcentajeRecompra) {
                contenedorPorcentajeRecompra.style.display = 'none';
            }
            if (contenedorPrecioRecompra) {
                contenedorPrecioRecompra.style.display = 'none';
            }
        }
        verificarEstadoBotonCrear();
    }
    
    // Verificar estado inicial al cargar - Ocultar por defecto
    if (fechaVencimientoMostrada) {
        fechaVencimientoMostrada.style.display = 'none';
        // Solo mostrar si "Si" está seleccionado
        if (opcionCompraSi && opcionCompraSi.checked) {
            fechaVencimientoMostrada.style.display = 'block';
        }
    }
    
    if (contenedorPorcentajeRecompra) {
        contenedorPorcentajeRecompra.style.display = 'none';
        // Solo mostrar si "Si" está seleccionado
        if (opcionCompraSi && opcionCompraSi.checked) {
            contenedorPorcentajeRecompra.style.display = 'block';
        }
    }
    
    if (contenedorPrecioRecompra) {
        contenedorPrecioRecompra.style.display = 'none';
        // Solo mostrar si "Si" está seleccionado
        if (opcionCompraSi && opcionCompraSi.checked) {
            contenedorPrecioRecompra.style.display = 'block';
        }
    }
    
    // Agregar event listeners
    if (opcionCompraSi) {
        opcionCompraSi.addEventListener('change', manejarCambioOpcion);
    }
    if (opcionCompraNo) {
        opcionCompraNo.addEventListener('change', manejarCambioOpcion);
    }
    
    // Agregar listeners a porcentaje_recompra y precio_recompra (aunque sean readonly, se actualizan programáticamente)
    const porcentajeRecompra = document.getElementById('porcentaje_recompra');
    const precioRecompra = document.getElementById('precio_recompra');
    if (porcentajeRecompra) {
        porcentajeRecompra.addEventListener('change', verificarEstadoBotonCrear);
    }
    if (precioRecompra) {
        precioRecompra.addEventListener('change', verificarEstadoBotonCrear);
    }
}

var placeholderSelect2 = 'Seleccionar...';
document.addEventListener('DOMContentLoaded', function() {
    // Placeholder universal para todos los Select2
    
    // Inicializar otros Select2 (incluyendo nacionalidad)
    var select2 = $('.select2:not(#pais):not(#c_provincia):not(#c_poblacion)');
    if (select2.length) {
        select2.each(function () {
            var $this = $(this);
            select2Focus($this);
            $this.select2({
                dropdownParent: $this.parent(),
                placeholder: placeholderSelect2,
                allowClear: true
            });
        });
    }
});

/**
 * Reinicializar Select2 para campos de dirección después de cargar dinámicamente
 */
function inicializarSelect2Direcciones() {
    if (typeof window.inicializarDireccionesSelect2ConAjax === 'function') {
        window.inicializarDireccionesSelect2ConAjax();
        return;
    }
    if ($('#pais').data('select2')) {
        $('#pais').select2('destroy');
    }
    if ($('#c_provincia').data('select2')) {
        $('#c_provincia').select2('destroy');
    }
    if ($('#c_poblacion').data('select2')) {
        $('#c_poblacion').select2('destroy');
    }
    $('#pais, #c_provincia, #c_poblacion').each(function () {
        var $this = $(this);
        if (typeof select2Focus === 'function') {
            select2Focus($this);
        }
        $this.select2({
            dropdownParent: $this.parent(),
            placeholder: placeholderSelect2,
            allowClear: true
        });
    });
}

/**
 * Configurar modal de autorización SMS
 */
function configurarModalAutorizacionSMS() {
    const btnConfirmarAutorizacionSMS = document.getElementById('btnConfirmarAutorizacionSMS');
    const btnCancelarAutorizacionSMS = document.getElementById('btnCancelarAutorizacionSMS');
    const btnSolicitarAutorizacionSMS = document.getElementById('btnSolicitarAutorizacionSMS');
    const btnCancelarSolicitudAutorizacionSMS = document.getElementById('btnCancelarSolicitudAutorizacionSMS');
    const modalSolicitarAutorizacionSMS = document.getElementById('modalSolicitarAutorizacionSMS');
    const modalCancelarAutorizacionSMS = document.getElementById('modalCancelarAutorizacionSMS');
    const notMobileGet = document.getElementById('not_mobile_get');
    
    // Abrir modal de solicitar autorización SMS
    if (btnSolicitarAutorizacionSMS && modalSolicitarAutorizacionSMS) {
        btnSolicitarAutorizacionSMS.addEventListener('click', function() {
            const modal = new bootstrap.Modal(modalSolicitarAutorizacionSMS);
            modal.show();
        });
    }
    
    // Abrir modal de cancelar autorización SMS
    if (btnCancelarSolicitudAutorizacionSMS && modalCancelarAutorizacionSMS) {
        btnCancelarSolicitudAutorizacionSMS.addEventListener('click', function() {
            const modal = new bootstrap.Modal(modalCancelarAutorizacionSMS);
            modal.show();
        });
    }
    
    const inputTelefono = document.getElementById('telefono');
    
    if (btnConfirmarAutorizacionSMS && modalSolicitarAutorizacionSMS && notMobileGet) {
        btnConfirmarAutorizacionSMS.addEventListener('click', function() {
            // Cambiar el valor del input hidden a "true"
            notMobileGet.value = 'true';
            
            // Quitar el atributo required del input telefono
            if (inputTelefono) {
                inputTelefono.removeAttribute('required');
                inputTelefono.disabled = true;
            }
            
            // Cerrar el modal
            const modal = bootstrap.Modal.getInstance(modalSolicitarAutorizacionSMS);
            if (modal) {
                modal.hide();
            }
        });
    }
    
    if (btnCancelarAutorizacionSMS && modalCancelarAutorizacionSMS && notMobileGet) {
        btnCancelarAutorizacionSMS.addEventListener('click', function() {
            // Cambiar el valor del input hidden a "false"
            notMobileGet.value = 'false';
            
            // Restaurar el atributo required del input telefono
            if (inputTelefono) {
                inputTelefono.setAttribute('required', 'required');
                inputTelefono.disabled = false;
            }
            
            // Cerrar el modal
            const modal = bootstrap.Modal.getInstance(modalCancelarAutorizacionSMS);
            if (modal) {
                modal.hide();
            }
        });
    }
    
    // Cuando se cierra el modal de solicitar autorización (después de confirmar)
    if (modalSolicitarAutorizacionSMS && btnSolicitarAutorizacionSMS && btnCancelarSolicitudAutorizacionSMS) {
        modalSolicitarAutorizacionSMS.addEventListener('hidden.bs.modal', function() {
            // Solo hacer el cambio si se confirmó la autorización (not_mobile_get es "true")
            if (notMobileGet && notMobileGet.value === 'true') {
                // Ocultar botón de solicitar autorización
                btnSolicitarAutorizacionSMS.classList.add('d-none');
                // Mostrar botón de cancelar solicitud
                btnCancelarSolicitudAutorizacionSMS.classList.remove('d-none');
            }
        });
    }
    
    // Cuando se cierra el modal de cancelar autorización (después de cancelar)
    if (modalCancelarAutorizacionSMS && btnSolicitarAutorizacionSMS && btnCancelarSolicitudAutorizacionSMS) {
        modalCancelarAutorizacionSMS.addEventListener('hidden.bs.modal', function() {
            // Solo hacer el cambio si se canceló la autorización (not_mobile_get es "false")
            if (notMobileGet && notMobileGet.value === 'false') {
                // Mostrar botón de solicitar autorización
                btnSolicitarAutorizacionSMS.classList.remove('d-none');
                // Ocultar botón de cancelar solicitud
                btnCancelarSolicitudAutorizacionSMS.classList.add('d-none');
            }
        });
    }
    
    // Configurar botón de cancelar lote
    const btnCancelarLote = document.getElementById('btnCancelarLote');
    if (btnCancelarLote) {
        btnCancelarLote.addEventListener('click', function() {
            Swal.fire({
                title: '¿Está seguro?',
                text: '¿Está seguro que desea salir de Nuevo Lote? Se perderán todos los datos',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, salir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'lotes.php';
                }
            });
        });
    }
}
</script>
<?php if($app_country_id == 68){ ?>
    <script src="parts/universal/js/comprobar_identificacion_spain.js"></script>
<?php } ?>
<script src="parts/universal/js/javascript_direcciones.js"></script>
