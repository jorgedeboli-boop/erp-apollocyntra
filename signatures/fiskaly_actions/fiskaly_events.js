// JavaScript Document

let urlApiFiskaly = window.urlApiFiskaly || '';

/**
 * Función para obtener parámetros GET de la URL
 */
function obtenerParametrosGET() {
    const params = new URLSearchParams(window.location.search);
    const parametros = {};
    console.log('params:', params);
    // Obtener todas las variables GET
    for (const [key, value] of params.entries()) {
        parametros[key] = value;
    }
    
    return parametros;
}

// Obtener y hacer disponibles las variables GET
const parametrosGET = obtenerParametrosGET();
console.log('PARAMETROS GET:', parametrosGET);
// Variables disponibles globalmente
const sucursal_venta = parametrosGET.sucursal_venta || null;
const factura_id_fiskaly = parametrosGET.factura_id_fiskaly || null;
const id_venta = parametrosGET.id_venta || null;
const id_empresa = parametrosGET.id_empresa || null;
const tipo_factura_master = parametrosGET.tipo_factura_master || null;

// También disponible como objeto
const variablesGET = {
    sucursal_venta: parametrosGET.sucursal_venta || null,
    factura_id_fiskaly: parametrosGET.factura_id_fiskaly || null,
    id_venta: parametrosGET.id_venta || null,
    id_empresa: parametrosGET.id_empresa || null,
    tipo_factura_master: parametrosGET.tipo_factura_master || null
};

// COMPROBAR SI EXISTE LA FACTURA EN LA BASE DE DATOS FISKALY
const existeFactura = async () => {
    console.log('EXISTE LA FACTURA 1:', factura_id_fiskaly);
    if (!factura_id_fiskaly) {
        console.log('NO EXISTE LA FACTURA 1:', factura_id_fiskaly);
        return false;
    }
    
    try {
        const urlParams = new URLSearchParams({
            factura_id_fiskaly: factura_id_fiskaly,
            tipo_factura_master: tipo_factura_master
        });
        if (id_empresa) {
            urlParams.append('id_empresa', id_empresa);
        }
        const response = await fetch(`fiskaly_actions/check_factura_request.php?${urlParams.toString()}`);
        const data = await response.json();
        console.log('EXISTE LA FACTURA 3:', data.existe);
        
        return data.existe || false;
    } catch (error) {
        console.log('NO EXISTE LA FACTURAERRO PHP 4:', error);
        console.error('Error al verificar factura:', error);
        return false;
    }
};

// Variables globales para credenciales Fiskaly
let clave_api_fiskaly = null;
let secret_clave_api_fiskaly = null;
let rel_empresa_fiskaly = null;
let id_client_fisklaly_fiskaly = null;
let id_firmante_fiskaly = null;


/**
 * Función para crear factura en Fiskaly
 */
async function actualizarFacturaFiskalyREMEDY(factura_id_fiskaly){
    console.log('ACTUALIZANDO FACTURA FISKALY REMEDY:', factura_id_fiskaly);
    // VARIABLES EN LOCAL STORAGE
    const accessToken = localStorage.getItem('fiskaly_access_token');
    const refreshToken = localStorage.getItem('fiskaly_refresh_token');
    const expiresAt = localStorage.getItem('fiskaly_expires_at');
    const environment = localStorage.getItem('fiskaly_environment');
    const orgId = localStorage.getItem('fiskaly_org_id');
    const idClient = localStorage.getItem('fiskaly_id_client');
    const contribuyente = localStorage.getItem('fiskaly_contribuyente');
    
    console.log('ACCESS TOKEN:', accessToken);
    console.log('REFRESH TOKEN:', refreshToken);
    console.log('EXPIRES AT:', expiresAt);
    console.log('ENVIRONMENT:', environment);
    console.log('ORG ID:', orgId);
    console.log('ID CLIENT:', idClient);
    console.log('CONTRIBUYENTE:', contribuyente);
    
        // Enviar datos a actualizar_factura_remedy.php
    try {
        const response = await fetch('fiskaly_actions/actualizar_factura_remedy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                factura_id_fiskaly: factura_id_fiskaly,
                accessToken: accessToken,
                refreshToken: refreshToken,
                expiresAt: expiresAt,
                environment: environment,
                orgId: orgId,
                idClient: idClient,
                contribuyente: contribuyente,
                id_empresa: id_empresa || null
            })
        });
        
        const data = await response.json();
        console.log('RESPUESTA ACTUALIZAR FACTURA REMEDY:', data);
        console.log('data.success:', data.success);
        console.log('data.invoice_id_fiskaly existe?', typeof data.invoice_id_fiskaly !== 'undefined');
        
        if (data.success) {
            // Si la factura se actualizó exitosamente, redirigir
            console.log('Factura actualizada exitosamente');
            console.log('JSON FISKALY:', data.json_fiskaly);
            console.log('Tipo de json_fiskaly:', typeof data.json_fiskaly);
            console.log('UUID INVOICE:', data.invoice_id_fiskaly);

            const invoice_id_fiskaly = data.invoice_id_fiskaly;

            alert("ONTENIENDO DATOS DE LA FACTURA PARA ENVIAR A FISKALY. invoice_id_fiskaly: " + invoice_id_fiskaly);


            // SI TODO FUE BIEN - Enviar factura a Fiskaly
            
            try {
                const fiskalyResponse = await enviarFacturaFiskaly(invoice_id_fiskaly, idClient, data.json_fiskaly);
                console.log('Factura enviada correctamente a Fiskaly');
                
                // Actualizar estado de la factura en la base de datos
                await actualizarFacturaFiskaly(fiskalyResponse, factura_id_fiskaly, tipo_factura_master);
                console.log('Estado de factura actualizado correctamente');
                
                redirigirAOrigen('success');
            } catch (error) {
                console.error('Error al enviar factura a Fiskaly:', error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al enviar la factura a Fiskaly: ' + error.message,
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        redirigirAOrigen('error', 'Error al enviar la factura a Fiskaly');
                    });
                } else {
                    alert('Error al enviar la factura a Fiskaly: ' + error.message);
                    redirigirAOrigen('error', 'Error al enviar la factura a Fiskaly');
                }
            }
            
        } else {
            console.error('Error al crear factura:', data.message);
            
            // Si hay error y necesita redirección, mostrar swal y redirigir
            if (data.redirect) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        redirigirAOrigen('error', data.message);
                    });
                } else {
                    alert(data.message);
                    redirigirAOrigen('error', data.message);
                }
            }
        }
        
    } catch (error) {
        console.error('Error al crear factura:', error);
        
        // En caso de error de red, también intentar redirigir
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al procesar la factura',
                confirmButtonText: 'Aceptar'
            }).then(() => {
                redirigirAOrigen('error', 'Error al procesar la factura');
            });
        } else {
            alert('Error al procesar la factura');
            redirigirAOrigen('error', 'Error al procesar la factura');
        }
    }
}

// Obtener credenciales Fiskaly desde la base de datos
const obtenerCredencialesFiskaly = async () => {
    console.log('OBTENIENDO CREDENCIALES FISKALY');
    if (!sucursal_venta) {
        console.error('sucursal_venta no está disponible');
        return false;
    }
    
    try {
        const urlParams = new URLSearchParams({
            sucursal_parset: sucursal_venta
        });
        if (id_empresa) {
            urlParams.append('id_empresa', id_empresa);
        }
        const response = await fetch(`fiskaly_actions/get_credenciales_fiskaly.php?${urlParams.toString()}`);
        const data = await response.json();
        
        if (data.success) {
            clave_api_fiskaly = data.clave_api;
            secret_clave_api_fiskaly = data.secret_clave_api;
            rel_empresa_fiskaly = data.rel_empresa;
            id_client_fisklaly_fiskaly = data.id_client_fisklaly;
            id_firmante_fiskaly = data.id_firmante;
            // Autenticar con Fiskaly usando las credenciales obtenidas
            try {
                const authResult = await autenticarFiskaly(clave_api_fiskaly, secret_clave_api_fiskaly);
                
                // Si la autenticación fue exitosa, obtener el contribuyente
                if (authResult && authResult.content && authResult.content.access_token) {
                    const accessToken = authResult.content.access_token.bearer;
                    const contribuyente = await obtenerContribuyenteFiskaly(accessToken);
                    
                    // Si obtenerContribuyenteFiskaly fue exitoso, guardar id_client_fisklaly en localStorage
                    if (contribuyente && id_client_fisklaly_fiskaly) {
                        localStorage.setItem('fiskaly_id_client', id_client_fisklaly_fiskaly);
                        
                        // Invocar crearFacturaFiskaly con factura_id_fiskaly
                        if (factura_id_fiskaly) {
                            // AQUI COMPROBAMOS SI EXISTE FACTURA EN LA API
                            try {
                                const existeEnAPI = await comprobarSiExisteFacturaEnAPI(id_client_fisklaly_fiskaly);
                                
                                if (!existeEnAPI || !existeEnAPI.existe) {
                                    // Si no existe en la API, crear la factura
                                    console.log('Factura no existe en la API, creando...');
                                    crearFacturaFiskaly(parseInt(factura_id_fiskaly));
                                } else {
                                    // Si existe en la API, la factura ya está registrada (ya se consultó dentro de comprobarSiExisteFacturaEnAPI)
                                    console.log('Factura ya existe en la API y está registrada');
                                    if(tipo_factura_master === 'REMEDY') {
                                        await actualizarFacturaFiskalyREMEDY(factura_id_fiskaly);
                                        redirigirAOrigen('success');
                                    } else if(tipo_factura_master === 'COMPLETE') {
                                        await actualizarFacturaFiskaly(existeEnAPI.result, factura_id_fiskaly, tipo_factura_master);
                                        redirigirAOrigen('success');
                                    } else {
                                        await actualizarFacturaFiskaly(existeEnAPI.result, factura_id_fiskaly, tipo_factura_master);
                                        redirigirAOrigen('success');
                                    }
                                    
                                }
                            } catch (error) {
                                console.error('Error al comprobar si existe factura en la API:', error);
                                redirigirAOrigen('error', 'Error al comprobar si existe factura en la API');
                                // En caso de error, intentar crear la factura de todas formas
                                //crearFacturaFiskaly(parseInt(factura_id_fiskaly));
                            }
                        }
                    }
                }
                console.log('AUTENTICACION FISKALY EXITOSA');
                return true;
            } catch (authError) {
                console.error('Error al autenticar con Fiskaly:', authError);
                return false;
            }
        } else {
            console.error('Error al obtener credenciales:', data.message);
            return false;
        }
    } catch (error) {
        console.error('Error al obtener credenciales Fiskaly:', error);
        return false;
    }
};

/**
 * Función para redirigir a la URL de origen con parámetros GET opcionales
 * @param {string} state - Estado de la redirección ('error', 'success', etc.)
 * @param {string} [text_error] - Texto del error (solo si state === 'error')
 */
const redirigirAOrigen = (state, text_error = null) => {
    const urlCompletaOrigen = document.getElementById('url_completa_origen')?.value || '';
    
    if (!urlCompletaOrigen) {
        console.error('No se encontró url_completa_origen');
        return;
    }
    
    // Construir la URL con parámetros GET
    const url = new URL(urlCompletaOrigen);
    
    // Agregar parámetro state
    url.searchParams.set('state', state);
    
    // Si state es 'error' y hay text_error, agregarlo
    if (state === 'error' && text_error) {
        url.searchParams.set('text_error', encodeURIComponent(text_error));
    }
    
    // Redirigir
    window.location.href = url.toString();
};

// Si existe la factura, obtener las credenciales
const procesarSiExisteFactura = async () => {
    const facturaExiste = await existeFactura();
    
    if (facturaExiste) {
        await obtenerCredencialesFiskaly();
    } else {
        // Si la factura no existe, redirigir a la URL de origen
        redirigirAOrigen('error', 'La factura no existe en la base de datos');
    }
    
    return facturaExiste;
};

/**
 * Función para autenticar con Fiskaly
 */
async function autenticarFiskaly(apiKey, apiSecret) {
    const url = urlApiFiskaly + 'auth';
    
    const data = {
        content: {
            api_key: apiKey,
            api_secret: apiSecret
        }
    };
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        // Guardar tokens
        localStorage.setItem('fiskaly_access_token', result.content.access_token.bearer);
        localStorage.setItem('fiskaly_refresh_token', result.content.refresh_token.bearer);
        localStorage.setItem('fiskaly_expires_at', result.content.access_token.expires_at);
        localStorage.setItem('fiskaly_environment', result.content.claims.environment);
        localStorage.setItem('fiskaly_org_id', result.content.claims.organization_id);
        
        return result;
        
    } catch (error) {
        throw error;
    }
}

async function obtenerContribuyenteFiskaly(accessToken) {
    const url = urlApiFiskaly + 'taxpayer';
    
    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + accessToken
            }
        });
        
        if (!response.ok) {
            // Si el error es 404, significa que no existe contribuyente
            if (response.status === 404) {
                console.error('Error al obtener contribuyente: 404');
                return null;
            }
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result && result.content && result.content.issuer) {
            localStorage.setItem('fiskaly_contribuyente', JSON.stringify(result.content));
            return result.content;
        } else {
            console.error('Error al obtener contribuyente: no existe');
            return null;
        }
        
    } catch (error) {
        console.error('Error al obtener contribuyente:', error);
        return null;
    }
}

/**
 * Función para consultar el estado de una factura en Fiskaly
 * @param {string} invoiceId - ID de la factura en Fiskaly (content.id)
 * @param {string} clientId - ID del cliente en Fiskaly
 * @returns {Promise<object>} - Respuesta de Fiskaly con el estado actualizado
 */
async function consultarFacturaFiskaly(invoiceId, clientId) {
    console.log('CONSULTANDO ESTADO DE FACTURA FISKALY');
    console.log('Invoice ID:', invoiceId);
    console.log('Client ID:', clientId);

    const accessToken = localStorage.getItem('fiskaly_access_token');

    if (!accessToken) {
        throw new Error('No se encontró el access token de Fiskaly');
    }

    // Red lenta / TicketBAI: hasta ~100 s (20 × 5 s)
    const maxIntentos = 20;
    const intervalo = 5000;
    const estadosTerminales = ['INVALID', 'REQUIRES_CORRECTION', 'CANCELLED', 'REQUIRES_INSPECTION'];

    for (let intento = 1; intento <= maxIntentos; intento++) {
        console.log(`Intento ${intento} de ${maxIntentos}`);

        try {
            const url = urlApiFiskaly + 'clients/' + clientId + '/invoices/' + invoiceId;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + accessToken
                }
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `HTTP ${response.status}`);
            }

            const result = await response.json();
            const registration = result?.content?.transmission?.registration || null;
            const state = result?.content?.state || null;

            console.log(`Intento ${intento}: Registration =`, registration);

            if (registration === 'REGISTERED') {
                console.log('Factura REGISTERED exitosamente');
                return result;
            }

            if (estadosTerminales.indexOf(registration) !== -1) {
                console.log('Factura en estado terminal:', registration);
                return result;
            }

            if (registration === 'PENDING') {
                if (intento < maxIntentos) {
                    console.log(`PENDING: esperando ${intervalo / 1000}s antes del siguiente intento...`);
                    await new Promise(resolve => setTimeout(resolve, intervalo));
                    continue;
                }
                throw {
                    registration: registration,
                    state: state,
                    message: `La factura no se registró después de ${maxIntentos} intentos. Registration: ${registration}, State: ${state}`
                };
            }

            throw {
                registration: registration,
                state: state,
                message: `Estado de registro desconocido. Registration: ${registration}, State: ${state}`
            };
        } catch (error) {
            console.error(`Error en intento ${intento}:`, error);
            if (intento === maxIntentos || error.registration !== undefined) {
                throw error;
            }
            await new Promise(resolve => setTimeout(resolve, intervalo));
        }
    }
}

/**
 * Función para actualizar el estado de la factura en la base de datos
 * @param {object} fiskalyResponse - Respuesta JSON de Fiskaly
 * @param {number} factura_id_fiskaly - ID de la factura en la base de datos local
 */
async function actualizarFacturaFiskaly(fiskalyResponse, factura_id_fiskaly, tipo_factura_master) {
    console.log('ACTUALIZANDO ESTADO DE FACTURA');
    console.log('Factura ID Fiskaly:', factura_id_fiskaly);
    console.log('Respuesta Fiskaly:', fiskalyResponse);
    console.log('Tipo de factura master:', tipo_factura_master);
    
    try {
        // Extraer valores del JSON de respuesta de Fiskaly
        const content = fiskalyResponse.content || {};
        const transmission = content.transmission || {};
        const compliance = content.compliance || {};
        const code = compliance.code || {};
        const image = code.image || {};
        const validations = content.validations || {};
        
        const datosActualizacion = {
            factura_id_fiskaly: factura_id_fiskaly,
            invoice_id_fiskaly: content.id || null,
            InvoiceState: content.state || null,
            SignedInvoiceRegistrationState: transmission.registration || null,
            registration_csv: transmission.registration_csv || null,
            SignedInvoiceCancellationState: transmission.cancellation || null,
            tbai: (() => {
                let texto = (compliance.tbai || compliance.text || '').toString().trim();
                const url = (compliance.url || '').toString().toLowerCase();
                if (!texto && url && (url.indexOf('validarqr') !== -1 || url.indexOf('agenciatributaria') !== -1 || url.indexOf('aeat.es') !== -1)) {
                    texto = 'VERI*FACTU';
                }
                return texto || null;
            })(),
            url_validacion: compliance.url || null,
            imagen_codigo_qr: image.data || null,
            validations: validations || null,
            tipo_factura_master: tipo_factura_master || null
        };
        
        // Agregar id_empresa si está disponible
        if (id_empresa) {
            datosActualizacion.id_empresa = id_empresa;
        }
        
        console.log('Datos a actualizar:', datosActualizacion);
        
        // Enviar datos al PHP
        const response = await fetch('fiskaly_actions/actualizar_estado_factura.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(datosActualizacion)
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Error al actualizar el estado de la factura');
        }
        
        console.log('Estado de factura actualizado correctamente:', data);
        return data;
        
    } catch (error) {
        console.error('Error al actualizar estado de factura:', error);
        throw error;
    }
}

/**
 * Función para enviar factura a Fiskaly
 * @param {string} uuid_invoice - UUID de la factura
 * @param {string} idClient - ID del cliente en Fiskaly
 * @param {string|object} json_fiskaly - JSON de la factura (string o objeto)
 */
async function enviarFacturaFiskaly(uuid_invoice, idClient, json_fiskaly) {
    console.log('ENVIANDO FACTURA A FISKALY');
    console.log('UUID Invoice:', uuid_invoice);
    console.log('ID Client:', idClient);
    
    const accessToken = localStorage.getItem('fiskaly_access_token');
    
    if (!accessToken) {
        throw new Error('No se encontró el access token de Fiskaly');
    }
    
    // Convertir json_fiskaly a string si es objeto
    const jsonBody = typeof json_fiskaly === 'string' ? json_fiskaly : JSON.stringify(json_fiskaly);
    
    try {
        const url = urlApiFiskaly + 'clients/' + idClient + '/invoices/' + uuid_invoice;
        const response = await fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + accessToken
            },
            body: jsonBody
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Factura enviada exitosamente a Fiskaly:', result);
        
        // Obtener content.transmission.registration del JSON de respuesta
        const registration = result?.content?.transmission?.registration || null;
        console.log('Registration:', registration);
        
        // Comprobar si registration == 'PENDING'
        if (registration === 'PENDING') {
            console.log('Registration es PENDING, consultando estado de factura...');
            const invoiceId = result?.content?.id;
            const clientId = idClient;
            
            if (!invoiceId) {
                throw new Error('No se encontró el ID de la factura en la respuesta de Fiskaly');
            }
            
            try {
                // Consultar factura hasta REGISTERED o agotar intentos (~100 s)
                const updatedResult = await consultarFacturaFiskaly(invoiceId, clientId);
                console.log('Factura tras consultas de registro:', updatedResult);
                return updatedResult;
            } catch (error) {
                // Si tras agotar intentos sigue PENDING u otro estado, propagar
                if (error.registration !== undefined && error.state !== undefined) {
                    console.error('Error después de 5 intentos:', error);
                    await actualizarFacturaFiskaly(result, factura_id_fiskaly, tipo_factura_master);
                    // Redirigir con error
                    redirigirAOrigen('error', `Registration: ${error.registration}, State: ${error.state}`);
                    throw error;
                }
                throw error;
            }
        }
        
        return result; // Retornar el resultado para que pueda ser usado por actualizarFacturaFiskaly
        
    } catch (error) {
        console.error('Error al enviar factura a Fiskaly:', error);
        throw error;
    }
}

/**
 * Función para crear factura en Fiskaly
 */
async function crearFacturaFiskaly(factura_id_fiskaly) {
    console.log('CREANDO FACTURA FISKALY:', factura_id_fiskaly);
    // VARIABLES EN LOCAL STORAGE
    const accessToken = localStorage.getItem('fiskaly_access_token');
    const refreshToken = localStorage.getItem('fiskaly_refresh_token');
    const expiresAt = localStorage.getItem('fiskaly_expires_at');
    const environment = localStorage.getItem('fiskaly_environment');
    const orgId = localStorage.getItem('fiskaly_org_id');
    const idClient = localStorage.getItem('fiskaly_id_client');
    const contribuyente = localStorage.getItem('fiskaly_contribuyente');
    
    console.log('ACCESS TOKEN:', accessToken);
    console.log('REFRESH TOKEN:', refreshToken);
    console.log('EXPIRES AT:', expiresAt);
    console.log('ENVIRONMENT:', environment);
    console.log('ORG ID:', orgId);
    console.log('ID CLIENT:', idClient);
    console.log('CONTRIBUYENTE:', contribuyente);
    
    // Enviar datos a crear_factura.php
    try {
        const response = await fetch('fiskaly_actions/crear_factura.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                factura_id_fiskaly: factura_id_fiskaly,
                accessToken: accessToken,
                refreshToken: refreshToken,
                expiresAt: expiresAt,
                environment: environment,
                orgId: orgId,
                idClient: idClient,
                contribuyente: contribuyente,
                id_empresa: id_empresa || null
            })
        });
        
        const data = await response.json();
        console.log('RESPUESTA CREAR FACTURA COMPLETA:', data);
        console.log('data.success:', data.success);
        console.log('data.json_fiskaly existe?', typeof data.json_fiskaly !== 'undefined');
        
        if (data.success) {
            // Si la factura se creó exitosamente, redirigir
            console.log('Factura creada exitosamente');
            console.log('JSON FISKALY:', data.json_fiskaly);
            console.log('Tipo de json_fiskaly:', typeof data.json_fiskaly);
            console.log('UUID INVOICE:', data.uuid_invoice);

            const uuid_invoice = data.uuid_invoice;

            alert("ONTENIENDO DATOS DE LA FACTURA PARA ENVIAR A FISKALY. uuid_invoice: " + uuid_invoice);


            // SI TODO FUE BIEN - Enviar factura a Fiskaly
            
            try {
                const fiskalyResponse = await enviarFacturaFiskaly(uuid_invoice, idClient, data.json_fiskaly);
                console.log('Factura enviada correctamente a Fiskaly');
                
                // Actualizar estado de la factura en la base de datos
                await actualizarFacturaFiskaly(fiskalyResponse, factura_id_fiskaly, tipo_factura_master);
                console.log('Estado de factura actualizado correctamente');
                
                redirigirAOrigen('success');
            } catch (error) {
                console.error('Error al enviar factura a Fiskaly:', error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al enviar la factura a Fiskaly: ' + error.message,
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        redirigirAOrigen('error', 'Error al enviar la factura a Fiskaly');
                    });
                } else {
                    alert('Error al enviar la factura a Fiskaly: ' + error.message);
                    redirigirAOrigen('error', 'Error al enviar la factura a Fiskaly');
                }
            }
            
        } else {
            console.error('Error al crear factura:', data.message);
            
            // Si hay error y necesita redirección, mostrar swal y redirigir
            if (data.redirect) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        redirigirAOrigen('error', data.message);
                    });
                } else {
                    alert(data.message);
                    redirigirAOrigen('error', data.message);
                }
            }
        }
        
    } catch (error) {
        console.error('Error al crear factura:', error);
        
        // En caso de error de red, también intentar redirigir
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al procesar la factura',
                confirmButtonText: 'Aceptar'
            }).then(() => {
                redirigirAOrigen('error', 'Error al procesar la factura');
            });
        } else {
            alert('Error al procesar la factura');
            redirigirAOrigen('error', 'Error al procesar la factura');
        }
    }
}


// FUNCION PARA COMPROBAR SI EXISTE FACTURA EN LA API
async function comprobarSiExisteFacturaEnAPI(idClient) {
    console.log('COMPROBANDO SI EXISTE FACTURA ' + factura_id_fiskaly + ' EN LA API FISKALY DE LA EMPRESA ' + id_empresa);
    console.log('Client ID:', idClient);
    
    // Obtener invoice_id_fiskaly de la base de datos
    let uuid_invoice = null;
    try {
        const urlParams = new URLSearchParams({
            factura_id_fiskaly: factura_id_fiskaly
        });
        if (id_empresa) {
            urlParams.append('id_empresa', id_empresa);
        }
        
        const responseCheck = await fetch('fiskaly_actions/get_invoice_id_fiskaly.php?' + urlParams.toString());
        const dataCheck = await responseCheck.json();
        
        if (dataCheck.success && dataCheck.invoice_id_fiskaly && dataCheck.invoice_id_fiskaly !== '') {
            uuid_invoice = dataCheck.invoice_id_fiskaly;
            estado_cache_invoice = dataCheck.estado_cache;
            SignedInvoiceRegistrationState = dataCheck.SignedInvoiceRegistrationState;
            SignedInvoiceCancellationState = dataCheck.SignedInvoiceCancellationState;
            console.log('Invoice ID Fiskaly obtenido de BD:', uuid_invoice);
            console.log('Estado cache:', estado_cache_invoice);
            console.log('SignedInvoiceRegistrationState:', SignedInvoiceRegistrationState);
            console.log('SignedInvoiceCancellationState:', SignedInvoiceCancellationState);
        } else {
            console.log('No se encontró invoice_id_fiskaly en la base de datos');
            return {
                existe: false,
                invoice_id_fiskaly: null,
                estado_cache: null,
                SignedInvoiceRegistrationState: null,
                SignedInvoiceCancellationState: null
            };
        }
    } catch (error) {
        console.error('Error al obtener invoice_id_fiskaly de la base de datos:', error);
        throw error;
    }
    
    if (!uuid_invoice) {
        console.log('invoice_id_fiskaly está vacío o no existe');
        return {
            existe: false,
            invoice_id_fiskaly: null,
            estado_cache: null,
            SignedInvoiceRegistrationState: null,
            SignedInvoiceCancellationState: null
        };
    }
    
    const accessToken = localStorage.getItem('fiskaly_access_token');
    
    if (!accessToken) {
        throw new Error('No se encontró el access token de Fiskaly');
    }
    
    try {
        const url = urlApiFiskaly + 'clients/' + idClient + '/invoices/' + uuid_invoice;
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + accessToken
            }
        });
        
        // Si la factura no existe (404), retornar false
        if (response.status === 404) {
            console.log('Factura no existe en la API');
            return {
                existe: false,
                invoice_id_fiskaly: null,
                estado_cache: 'no existe en la API',
                SignedInvoiceRegistrationState: null,
                SignedInvoiceCancellationState: null
            };
        }
        
        // Si hay otro error, lanzarlo
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP ${response.status}`);
        }
        
        // Si existe, obtener el invoiceId del contenido
        const result = await response.json();
        const invoiceId = result?.content?.id || uuid_invoice;
        const invoice_state = result?.content?.state || null;

        console.log('Factura existe en la API, Invoice ID:', invoiceId);

        if(invoice_state === 'ISSUED') {
            invoice_state_parset = 'aceptada';
            console.log('Factura en estado ISSUED en la API');
        } else {
            console.log('Factura en estado ' + invoice_state + ' en la API');
            redirigirAOrigen('error', `Factura en estado ' + invoice_state + ' en la API`);
        }

        const invoice_registration = result?.content?.transmission?.registration || null;

        if(invoice_registration === 'REGISTERED') {
            invoice_registration_parset = 'REGISTERED';
            console.log('Factura en estado REGISTERED en la API');
        } else {
            console.log('Factura en estado ' + invoice_registration + ' en la API');
            redirigirAOrigen('error', `Factura en estado ' + invoice_registration + ' en la API`);
        }

        const invoice_cancellation = result?.content?.transmission?.cancellation || null;

        if(invoice_cancellation === 'NOT_CANCELLED') {
            invoice_cancellation_parset = 'NOT_CANCELLED';
            console.log('Factura en estado NOT_CANCELLED en la API');
        } else {
            console.log('Factura en estado ' + invoice_cancellation + ' en la API');
            redirigirAOrigen('error', `Factura en estado ' + invoice_cancellation + ' en la API`);
        }
        
        
        
        // Consultar factura hasta que esté REGISTERED o hasta 5 intentos
        try {
            const updatedResult = await consultarFacturaFiskaly(invoiceId, idClient);
            // Si llegamos aquí, la factura está REGISTERED
            console.log('Factura REGISTERED después de consultas:', updatedResult);
            return {
                existe: true,
                invoice_id_fiskaly: uuid_invoice,
                result: updatedResult,
                estado_cache: 'existe en la API',
                SignedInvoiceRegistrationState: SignedInvoiceRegistrationState,
                SignedInvoiceCancellationState: SignedInvoiceCancellationState
            }; // Retornar el resultado actualizado junto con invoice_id_fiskaly
        } catch (error) {
            // Si después de 5 intentos no está REGISTERED, obtener registration y state
            if (error.registration !== undefined && error.state !== undefined) {
                console.error('Error después de 5 intentos:', error);
                
                // Redirigir con error
                redirigirAOrigen('error', `Registration: ${error.registration}, State: ${error.state}`);
                throw error;
            }
            throw error;
        }
        
    } catch (error) {
        console.error('Error al comprobar si existe factura en la API:', error);
        throw error;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    procesarSiExisteFactura();
});