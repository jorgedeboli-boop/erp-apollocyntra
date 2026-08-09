<?php
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<script src="parts/fiskaly_manager/main/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
<script>
// Variables globales
let idEmpresa = window.idEmpresa || 0;
let urlApiFiskaly = window.urlApiFiskaly || '';

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si hay tokens de Fiskaly en localStorage
    verificarTokensFiskaly();
});

/**
 * Función para abrir modal de crear organización Fiskaly
 */
function abrirModalCrearFiskaly() {
    // Crear modal dinámicamente
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'modalCrearFiskaly';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-labelledby', 'modalCrearFiskalyLabel');
    modal.setAttribute('aria-hidden', 'true');

    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Organización Fiskaly</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearFiskaly">
                        <input type="hidden" name="rel_empresa" id="rel_empresa" value="${idEmpresa}">
                        <div class="mb-3">
                            <label for="clave_api" class="form-label">Clave API *</label>
                            <input type="text" class="form-control" id="clave_api" name="clave_api" required>
                        </div>
                        <div class="mb-3">
                            <label for="secret_clave_api" class="form-label">Secret Clave API *</label>
                            <input type="text" class="form-control" id="secret_clave_api" name="secret_clave_api" required>
                        </div>
                        <div class="mb-3">
                            <label for="id_organization_fisklaly" class="form-label">ID Organization Fiskaly *</label>
                            <input type="text" class="form-control" id="id_organization_fisklaly" name="id_organization_fisklaly" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="crearFiskaly()">Crear Organización</button>
                </div>
            </div>
        </div>
    `;

    // Agregar modal al body
    document.body.appendChild(modal);

    // Mostrar modal
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();

    // Limpiar modal cuando se oculte
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal);
    });
}

/**
 * Función para crear organización Fiskaly
 */
function crearFiskaly() {
    const clave_api = document.getElementById('clave_api').value.trim();
    const secret_clave_api = document.getElementById('secret_clave_api').value.trim();
    const id_organization_fisklaly = document.getElementById('id_organization_fisklaly').value.trim();
    const rel_empresa = document.getElementById('rel_empresa').value;
    
    if (!clave_api || !secret_clave_api || !id_organization_fisklaly) {
        Swal.fire('Error', 'Por favor completa todos los campos obligatorios', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('clave_api', clave_api);
    formData.append('secret_clave_api', secret_clave_api);
    formData.append('id_organization_fisklaly', id_organization_fisklaly);
    formData.append('rel_empresa', rel_empresa);

    // Deshabilitar botón durante la creación
    const btnCrear = document.querySelector('#modalCrearFiskaly .btn-primary');
    btnCrear.disabled = true;
    btnCrear.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creando...';

    fetch('parts/fiskaly_manager/main/procesar_crear_fiskaly.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire('¡Éxito!', 'Organización Fiskaly creada correctamente', 'success').then(() => {
                // Refrescar la página
                window.location.reload();
            });
        } else {
            throw new Error(data.message || 'Error al crear la organización Fiskaly');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', error.message || 'Error al crear la organización Fiskaly', 'error');
        // Restaurar botón
        btnCrear.disabled = false;
        btnCrear.innerHTML = 'Crear Organización';
    });
}

/**
 * Función para verificar tokens de Fiskaly en localStorage
 */
async function verificarTokensFiskaly() {
    const accessToken = localStorage.getItem('fiskaly_access_token');
    const environment = localStorage.getItem('fiskaly_environment');
    const orgId = localStorage.getItem('fiskaly_org_id');
    const idOrganizationFisklaly = document.getElementById('fiskaly_id_organization_fisklaly').getAttribute('data-id-organization-fisklaly');
    
    if (accessToken && environment && orgId) {

        if(idOrganizationFisklaly === orgId) {
            
        }else{
            localStorage.clear();
            window.location.reload();
        }

        // Ocultar botón de autenticación
        const btnAutenticar = document.getElementById('btn_autenticar_fiskaly');
        if (btnAutenticar) {
            btnAutenticar.style.display = 'none';
        }
        
        // Mostrar environment y organization_id
        const resultadoDiv = document.getElementById('resultado_autenticacion_fiskaly');
        const environmentElement = document.getElementById('fiskaly_environment');
        const organizationIdElement = document.getElementById('fiskaly_organization_id');
        
        if (resultadoDiv && environmentElement && organizationIdElement) {
            environmentElement.textContent = environment || 'N/A';
            organizationIdElement.textContent = orgId || 'N/A';
            resultadoDiv.style.display = 'flex';
        }
        
        // Obtener datos del contribuyente
        await obtenerContribuyenteFiskaly(accessToken);
        
        // Obtener dispositivos firmantes
        await obtenerDispositivosFirmantes();
        
        // Obtener clients
        await obtenerClients();
        
        // Obtener acuerdos
        await obtenerAcuerdosFiskaly();
    }
}

/**
 * Función para ejecutar autenticación Fiskaly
 */
async function ejecutarAutenticarFiskaly() {
    // Obtener datos de Fiskaly desde los elementos con IDs específicos
    const claveApiElement = document.getElementById('fiskaly_clave_api');
    const secretClaveApiElement = document.getElementById('fiskaly_secret_clave_api');
    
    if (!claveApiElement || !secretClaveApiElement) {
        Swal.fire('Error', 'No se encontraron los datos de Fiskaly', 'error');
        return;
    }
    
    // Obtener valores desde data attributes o texto
    const apiKey = claveApiElement.getAttribute('data-clave-api') || claveApiElement.textContent.trim();
    const apiSecret = secretClaveApiElement.getAttribute('data-secret-clave-api') || secretClaveApiElement.textContent.trim();
    
    if (apiKey === 'N/A' || apiSecret === 'N/A' || !apiKey || !apiSecret) {
        Swal.fire('Error', 'Los datos de API Key y Secret son requeridos', 'error');
        return;
    }
    
    const btnAutenticar = document.getElementById('btn_autenticar_fiskaly');
    const resultadoDiv = document.getElementById('resultado_autenticacion_fiskaly');
    const jsonResultado = document.getElementById('json_resultado_fiskaly');
    
    // Deshabilitar botón y mostrar loading
    btnAutenticar.disabled = true;
    btnAutenticar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Autenticando...';
    
    try {
        const result = await autenticarFiskaly(apiKey, apiSecret);
        
        // Mostrar resultado - solo environment y organization_id
        const environmentElement = document.getElementById('fiskaly_environment');
        const organizationIdElement = document.getElementById('fiskaly_organization_id');
        
        if (result && result.content && result.content.claims) {
            environmentElement.textContent = result.content.claims.environment || 'N/A';
            organizationIdElement.textContent = result.content.claims.organization_id || 'N/A';
        } else {
            environmentElement.textContent = 'N/A';
            organizationIdElement.textContent = 'N/A';
        }
        
        resultadoDiv.style.display = 'flex';
        
        // Obtener datos del contribuyente
        await obtenerContribuyenteFiskaly(result.content.access_token.bearer);
        
        // Obtener dispositivos firmantes después de autenticar
        await obtenerDispositivosFirmantes();
        
        // Obtener clients después de autenticar
        await obtenerClients();
        
        // Obtener acuerdos después de autenticar
        await obtenerAcuerdosFiskaly();
        
        Swal.fire('¡Éxito!', 'Autenticación realizada correctamente', 'success');
        
    } catch (error) {
        console.error('Error en autenticación:', error);
        Swal.fire('Error', error.message || 'Error al autenticar con Fiskaly', 'error');
        
        // Mostrar error en el resultado
        const environmentElement = document.getElementById('fiskaly_environment');
        const organizationIdElement = document.getElementById('fiskaly_organization_id');
        environmentElement.textContent = 'Error';
        organizationIdElement.textContent = error.message || 'Error desconocido';
        resultadoDiv.style.display = 'block';
    } finally {
        // Restaurar botón
        btnAutenticar.disabled = false;
        btnAutenticar.innerHTML = '<i class="icon-base ri ri-shield-check-line icon-16px me-2"></i>autenticarFiskaly';
    }
}

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

/**
 * Función para obtener datos del contribuyente desde Fiskaly
 */
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
                mostrarSinContribuyente();
                return null;
            }
            //const errorData = await response.json();
            //throw new Error(errorData.message || `HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        // Mostrar datos del contribuyente
        const resultadoContribuyenteDiv = document.getElementById('resultado_contribuyente_fiskaly');
        const sinContribuyenteDiv = document.getElementById('sin_contribuyente_fiskaly');
        const contribuyenteElement = document.getElementById('fiskaly_contribuyente');
        const nifElement = document.getElementById('fiskaly_nif');
        const territoryElement = document.getElementById('fiskaly_territory');
        const stateElement = document.getElementById('fiskaly_state');
        const municipalityElement = document.getElementById('fiskaly_municipality');
        const cityElement = document.getElementById('fiskaly_city');
        const streetElement = document.getElementById('fiskaly_street');
        const postalCodeElement = document.getElementById('fiskaly_postal_code');
        const numberElement = document.getElementById('fiskaly_number');
        const countryCodeElement = document.getElementById('fiskaly_country_code');
        const emailElement = document.getElementById('fiskaly_email');
        const registeredElement = document.getElementById('fiskaly_registered');
        const typeElement = document.getElementById('fiskaly_type');
        const fiskaly_contribuyente_existe = document.getElementById('fiskaly_contribuyente_existe');
        
        if (resultadoContribuyenteDiv && contribuyenteElement && nifElement && territoryElement) {
            if (result && result.content && result.content.issuer) {
                contribuyenteElement.textContent = result.content.issuer.legal_name || 'N/A';
                nifElement.textContent = result.content.issuer.tax_number || 'N/A';
                territoryElement.textContent = result.content.territory || 'N/A';
                
                // Nuevos campos
                if (stateElement) stateElement.textContent = result.content.state || 'N/A';
                if (municipalityElement) municipalityElement.textContent = result.content.address?.municipality || 'N/A';
                if (cityElement) cityElement.textContent = result.content.address?.city || 'N/A';
                if (streetElement) streetElement.textContent = result.content.address?.street || 'N/A';
                if (postalCodeElement) postalCodeElement.textContent = result.content.address?.postal_code || 'N/A';
                if (numberElement) numberElement.textContent = result.content.address?.number || 'N/A';
                if (countryCodeElement) countryCodeElement.textContent = result.content.address?.country_code || 'N/A';
                if (emailElement) emailElement.textContent = result.content.email || 'N/A';
                if (registeredElement) registeredElement.textContent = result.content.registered ? 'Sí' : 'No';
                if (typeElement) typeElement.textContent = result.content.type || 'N/A';

                localStorage.setItem('fiskaly_contribuyente', JSON.stringify(result.content));
                
                resultadoContribuyenteDiv.style.display = 'flex';
                fiskaly_contribuyente_existe.value = '1';
                if (sinContribuyenteDiv) {
                    sinContribuyenteDiv.style.display = 'none';
                }
            } else {
                mostrarSinContribuyente();
                fiskaly_contribuyente_existe.value = '0';
            }
        }
        
        return result;
        
    } catch (error) {
        console.error('Error al obtener contribuyente:', error);
        // Si es un error 404 o similar, mostrar botón de crear
        if (error.message && error.message.includes('404')) {
            mostrarSinContribuyente();
        }
        return null;
    }
}

/**
 * Función para mostrar el mensaje de sin contribuyente
 */
function mostrarSinContribuyente() {
    const resultadoContribuyenteDiv = document.getElementById('resultado_contribuyente_fiskaly');
    const sinContribuyenteDiv = document.getElementById('sin_contribuyente_fiskaly');
    
    if (resultadoContribuyenteDiv) {
        resultadoContribuyenteDiv.style.display = 'none';
    }
    if (sinContribuyenteDiv) {
        sinContribuyenteDiv.style.display = 'block';
    }
}

/**
 * Función para abrir modal de crear contribuyente
 */
function abrirModalCrearContribuyente() {
    // Crear modal dinámicamente
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'modalCrearContribuyente';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-labelledby', 'modalCrearContribuyenteLabel');
    modal.setAttribute('aria-hidden', 'true');

    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Contribuyente Fiskaly</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearContribuyente">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="legal_name" class="form-label">Legal Name *</label>
                                <input type="text" class="form-control" id="legal_name" name="legal_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tax_number" class="form-label">Tax Number *</label>
                                <input type="text" class="form-control" id="tax_number" name="tax_number" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="territory" class="form-label">Territory *</label>
                                <select class="form-select" id="territory" name="territory" required>
                                    <option value="">Seleccionar Territorio</option>
                                    <option value="SPAIN_OTHER">SPAIN_OTHER (Verifactu)</option>
                                    <option value="CANARY_ISLANDS">CANARY_ISLANDS (Verifactu)</option>
                                    <option value="CEUTA">CEUTA (Verifactu)</option>
                                    <option value="MELILLA">MELILLA (Verifactu)</option>
                                    <option value="ARABA">ARABA (TicketBAI)</option>
                                    <option value="BIZKAIA">BIZKAIA (TicketBAI)</option>
                                    <option value="GIPUZKOA">GIPUZKOA (TicketBAI)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="row">
                        <!-- Tipo de contribuyente no se puede actualizar con PATCH -->
                            <!--<div class="col-md-6 mb-3">
                                <label for="type" class="form-label">Type *</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Seleccionar tipo</option>
                                    <option value="COMPANY">COMPANY</option>
                                    <option value="INDIVIDUAL">INDIVIDUAL</option>
                                </select>
                            </div>-->
                            <div class="col-md-6 mb-3">
                                <label for="country_code" class="form-label">Country Code *</label>
                                <input type="text" class="form-control" id="country_code" name="country_code" maxlength="2" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="municipality" class="form-label">Municipality</label>
                                <input type="text" class="form-control" id="municipality" name="municipality">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="street" class="form-label">Street</label>
                                <input type="text" class="form-control" id="street" name="street">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="postal_code" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="number" class="form-label">Number</label>
                                <input type="text" class="form-control" id="number" name="number">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="crearContribuyenteFiskaly()">Crear Contribuyente</button>
                </div>
            </div>
        </div>
    `;

    // Agregar modal al body
    document.body.appendChild(modal);

    // Mostrar modal
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();

    // Limpiar modal cuando se oculte
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal);
    });
}

/**
 * Función para crear contribuyente Fiskaly
 */
async function crearContribuyenteFiskaly() {
    const legal_name = document.getElementById('legal_name').value.trim();
    const tax_number = document.getElementById('tax_number').value.trim();
    const territory = document.getElementById('territory').value.trim();
    const email = document.getElementById('email').value.trim();
   /* const type = document.getElementById('type').value;*/
    const country_code = document.getElementById('country_code').value.trim();
    const municipality = document.getElementById('municipality').value.trim();
    const city = document.getElementById('city').value.trim();
    const street = document.getElementById('street').value.trim();
    const postal_code = document.getElementById('postal_code').value.trim();
    const number = document.getElementById('number').value.trim();
    
    // Validar campos obligatorios
    if (!legal_name || !tax_number || !territory || !email || !country_code) {
        Swal.fire('Error', 'Por favor completa todos los campos obligatorios', 'error');
        return;
    }
    
    // Obtener token de localStorage
    const accessToken = localStorage.getItem('fiskaly_access_token');
    if (!accessToken) {
        Swal.fire('Error', 'No se encontró el token de autenticación. Por favor, autentica primero.', 'error');
        return;
    }
    
    // Construir objeto de datos
    const data = {
        content: {
            issuer: {
                legal_name: legal_name,
                tax_number: tax_number
            },
            territory: territory,
            email: email
        }
    };
    
    // Agregar address si hay datos
    if (municipality || city || street || postal_code || number || country_code) {
        data.content.address = {};
        if (municipality) data.content.address.municipality = municipality;
        if (city) data.content.address.city = city;
        if (street) data.content.address.street = street;
        if (postal_code) data.content.address.postal_code = postal_code;
        if (number) data.content.address.number = number;
        if (country_code) data.content.address.country_code = country_code;
    }
    
    // Deshabilitar botón durante la creación
    const btnCrear = document.querySelector('#modalCrearContribuyente .btn-primary');
    btnCrear.disabled = true;
    btnCrear.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creando...';
    
    try {
        const url = urlApiFiskaly + 'taxpayer';
        const response = await fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + accessToken
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        Swal.fire('¡Éxito!', 'Contribuyente creado correctamente', 'success').then(() => {
            // Cerrar modal
            bootstrap.Modal.getInstance(document.getElementById('modalCrearContribuyente')).hide();
            
            // Actualizar y mostrar datos del contribuyente
            //actualizarDatosContribuyente(result);
            window.location.reload();
        });
        
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', error.message || 'Error al crear el contribuyente', 'error');
        // Restaurar botón
        btnCrear.disabled = false;
        btnCrear.innerHTML = 'Crear Contribuyente';
    }
}

/**
 * Función para actualizar y mostrar datos del contribuyente
 */
function actualizarDatosContribuyente(result) {
    const resultadoContribuyenteDiv = document.getElementById('resultado_contribuyente_fiskaly');
    const sinContribuyenteDiv = document.getElementById('sin_contribuyente_fiskaly');
    const contribuyenteElement = document.getElementById('fiskaly_contribuyente');
    const nifElement = document.getElementById('fiskaly_nif');
    const territoryElement = document.getElementById('fiskaly_territory');
    const stateElement = document.getElementById('fiskaly_state');
    const municipalityElement = document.getElementById('fiskaly_municipality');
    const cityElement = document.getElementById('fiskaly_city');
    const streetElement = document.getElementById('fiskaly_street');
    const postalCodeElement = document.getElementById('fiskaly_postal_code');
    const numberElement = document.getElementById('fiskaly_number');
    const countryCodeElement = document.getElementById('fiskaly_country_code');
    const emailElement = document.getElementById('fiskaly_email');
    const registeredElement = document.getElementById('fiskaly_registered');
    const typeElement = document.getElementById('fiskaly_type');
    
    if (resultadoContribuyenteDiv && contribuyenteElement && nifElement && territoryElement) {
        if (result && result.content && result.content.issuer) {
            contribuyenteElement.textContent = result.content.issuer.legal_name || 'N/A';
            nifElement.textContent = result.content.issuer.tax_number || 'N/A';
            territoryElement.textContent = result.content.territory || 'N/A';
            
            // Nuevos campos
            if (stateElement) stateElement.textContent = result.content.state || 'N/A';
            if (municipalityElement) municipalityElement.textContent = result.content.address?.municipality || 'N/A';
            if (cityElement) cityElement.textContent = result.content.address?.city || 'N/A';
            if (streetElement) streetElement.textContent = result.content.address?.street || 'N/A';
            if (postalCodeElement) postalCodeElement.textContent = result.content.address?.postal_code || 'N/A';
            if (numberElement) numberElement.textContent = result.content.address?.number || 'N/A';
            if (countryCodeElement) countryCodeElement.textContent = result.content.address?.country_code || 'N/A';
            if (emailElement) emailElement.textContent = result.content.email || 'N/A';
            if (registeredElement) registeredElement.textContent = result.content.registered ? 'Sí' : 'No';
            if (typeElement) typeElement.textContent = result.content.type || 'N/A';
            
            resultadoContribuyenteDiv.style.display = 'flex';
            if (sinContribuyenteDiv) {
                sinContribuyenteDiv.style.display = 'none';
            }
        }
    }
}

// Variable global para almacenar los datos del contribuyente actual
let datosContribuyenteActual = null;

/**
 * Función para abrir modal de actualizar contribuyente
 */
async function abrirModalActualizarContribuyente() {
    // Obtener token de localStorage
    const accessToken = localStorage.getItem('fiskaly_access_token');
    if (!accessToken) {
        Swal.fire('Error', 'No se encontró el token de autenticación. Por favor, autentica primero.', 'error');
        return;
    }
    
    // Obtener datos actuales del contribuyente
    try {
        const url = urlApiFiskaly + 'taxpayer';
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + accessToken
            }
        });
        
        if (!response.ok) {
            throw new Error('Error al obtener datos del contribuyente');
        }
        
        datosContribuyenteActual = await response.json();
    } catch (error) {
        console.error('Error al obtener contribuyente:', error);
        Swal.fire('Error', 'Error al obtener los datos del contribuyente', 'error');
        return;
    }
    
    // Crear modal dinámicamente
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'modalActualizarContribuyente';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-labelledby', 'modalActualizarContribuyenteLabel');
    modal.setAttribute('aria-hidden', 'true');

    const content = datosContribuyenteActual && datosContribuyenteActual.content ? datosContribuyenteActual.content : {};
    const issuer = content.issuer || {};
    const address = content.address || {};

    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalActualizarContribuyenteLabel">Actualizar Contribuyente Fiskaly</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formActualizarContribuyente">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="legal_name_edit" class="form-label">Nombre Legal *</label>
                                <input type="text" class="form-control" id="legal_name_edit" name="legal_name" value="${issuer.legal_name || ''}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tax_number_edit" class="form-label">NIF *</label>
                                <input type="text" class="form-control" id="tax_number_edit" name="tax_number" value="${issuer.tax_number || ''}" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="territory_edit" class="form-label">Territorio *</label>
                                <select class="form-select" id="territory_edit" name="territory" required>
                                    <option value="">Seleccionar Territorio</option>
                                    <option value="SPAIN_OTHER" ${content.territory === 'SPAIN_OTHER' ? 'selected' : ''}>SPAIN_OTHER (Verifactu)</option>
                                    <option value="CANARY_ISLANDS" ${content.territory === 'CANARY_ISLANDS' ? 'selected' : ''}>CANARY_ISLANDS (Verifactu)</option>
                                    <option value="CEUTA" ${content.territory === 'CEUTA' ? 'selected' : ''}>CEUTA (Verifactu)</option>
                                    <option value="MELILLA" ${content.territory === 'MELILLA' ? 'selected' : ''}>MELILLA (Verifactu)</option>
                                    <option value="ARABA" ${content.territory === 'ARABA' ? 'selected' : ''}>ARABA (TicketBAI)</option>
                                    <option value="BIZKAIA" ${content.territory === 'BIZKAIA' ? 'selected' : ''}>BIZKAIA (TicketBAI)</option>
                                    <option value="GIPUZKOA" ${content.territory === 'GIPUZKOA' ? 'selected' : ''}>GIPUZKOA (TicketBAI)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email_edit" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email_edit" name="email" value="${content.email || ''}" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="type_edit" class="form-label">Tipo (solo lectura)</label>
                                <input type="text" class="form-control" id="type_edit" name="type" value="${content.type || 'N/A'}" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="country_code_edit" class="form-label">Código País *</label>
                                <input type="text" class="form-control" id="country_code_edit" name="country_code" value="${address.country_code || 'ES'}" maxlength="2" required>
                            </div>
                        </div>
                        <hr class="my-4">
                        <h6 class="mb-3">Dirección (Opcional)</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="municipality_edit" class="form-label">Municipio</label>
                                <input type="text" class="form-control" id="municipality_edit" name="municipality" value="${address.municipality || ''}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city_edit" class="form-label">Ciudad</label>
                                <input type="text" class="form-control" id="city_edit" name="city" value="${address.city || ''}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="street_edit" class="form-label">Calle</label>
                                <input type="text" class="form-control" id="street_edit" name="street" value="${address.street || ''}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="postal_code_edit" class="form-label">Código Postal</label>
                                <input type="text" class="form-control" id="postal_code_edit" name="postal_code" value="${address.postal_code || ''}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="number_edit" class="form-label">Número</label>
                            <input type="text" class="form-control" id="number_edit" name="number" value="${address.number || ''}">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="actualizarContribuyenteFiskaly()">Actualizar Contribuyente</button>
                </div>
            </div>
        </div>
    `;

    // Agregar modal al body
    document.body.appendChild(modal);

    // Mostrar modal
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();

    // Limpiar modal cuando se oculte
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal);
        datosContribuyenteActual = null;
    });
}

/**
 * Función para actualizar contribuyente Fiskaly
 */
async function actualizarContribuyenteFiskaly() {
    const legal_name = document.getElementById('legal_name_edit').value.trim();
    const tax_number = document.getElementById('tax_number_edit').value.trim();
    const territory = document.getElementById('territory_edit').value.trim();
    const email = document.getElementById('email_edit').value.trim();
    const country_code = document.getElementById('country_code_edit').value.trim();
    const municipality = document.getElementById('municipality_edit').value.trim();
    const city = document.getElementById('city_edit').value.trim();
    const street = document.getElementById('street_edit').value.trim();
    const postal_code = document.getElementById('postal_code_edit').value.trim();
    const number = document.getElementById('number_edit').value.trim();
    
    // Validar campos obligatorios (type no se puede actualizar con PATCH)
    if (!legal_name || !tax_number || !territory || !email || !country_code) {
        Swal.fire('Error', 'Por favor completa todos los campos obligatorios', 'error');
        return;
    }
    
    // Obtener token de localStorage
    const accessToken = localStorage.getItem('fiskaly_access_token');
    if (!accessToken) {
        Swal.fire('Error', 'No se encontró el token de autenticación. Por favor, autentica primero.', 'error');
        return;
    }
    
    // Construir objeto de datos (type no se puede actualizar con PATCH según la API)
    const data = {
        content: {
            issuer: {
                legal_name: legal_name,
                tax_number: tax_number
            },
            territory: territory,
            email: email
        }
    };
    
    // Agregar address si hay datos
    if (municipality || city || street || postal_code || number || country_code) {
        data.content.address = {};
        if (municipality) data.content.address.municipality = municipality;
        if (city) data.content.address.city = city;
        if (street) data.content.address.street = street;
        if (postal_code) data.content.address.postal_code = postal_code;
        if (number) data.content.address.number = number;
        if (country_code) data.content.address.country_code = country_code;
    }
    
    // Deshabilitar botón durante la actualización
    const btnActualizar = document.querySelector('#modalActualizarContribuyente .btn-primary');
    btnActualizar.disabled = true;
    btnActualizar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Actualizando...';
    
    try {
        const url = urlApiFiskaly + 'taxpayer';
        const response = await fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + accessToken
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        Swal.fire('¡Éxito!', 'Contribuyente actualizado correctamente', 'success').then(() => {
            // Cerrar modal
            bootstrap.Modal.getInstance(document.getElementById('modalActualizarContribuyente')).hide();
            
            // Actualizar y mostrar datos del contribuyente
            actualizarDatosContribuyente(result);
        });
        
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', error.message || 'Error al actualizar el contribuyente', 'error');
        // Restaurar botón
        btnActualizar.disabled = false;
        btnActualizar.innerHTML = 'Actualizar Contribuyente';
    }
}

/**
 * Función para obtener dispositivos firmantes
 */
async function obtenerDispositivosFirmantes() {
    const accessToken = localStorage.getItem('fiskaly_access_token');
    if (!accessToken) {
        console.error('No se encontró el token de autenticación');
        return;
    }
    // COMPROBAR SI EXISTE CONTRIBUYENTE
    const fiskaly_contribuyente_existe = document.getElementById('fiskaly_contribuyente_existe');
    
    try {
        const url = urlApiFiskaly + 'signers';
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
        
        // Mostrar la sección de dispositivos firmantes
        const resultadoDispositivosDiv = document.getElementById('resultado_dispositivos_firmantes');
        const tbody = document.getElementById('dispositivos_firmantes');
        const btn_actualizar_contribuyente_fiskaly = document.getElementById('btn_actualizar_contribuyente_fiskaly');
        
        if (resultadoDispositivosDiv && tbody) {
            if (result && result.results && result.results.length > 0) {
                tbody.innerHTML = '';
                
                result.results.forEach((item) => {
                    const content = item.content || {};
                    const certificate = content.certificate || {};
                    const id = content.id || 'N/A';
                    const state = content.state || 'N/A';
                    const serialNumber = certificate.serial_number || 'N/A';
                    
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${id}</td>
                        <td>${state}</td>
                        <td>${serialNumber}</td>
                    `;
                    tbody.appendChild(row);
                });
                
                resultadoDispositivosDiv.style.display = 'flex';
                
                btn_actualizar_contribuyente_fiskaly.style.display = 'none';
            } else {
                if (fiskaly_contribuyente_existe.value == '1') {
                    // si existe contribuyente mostraras resultadoDispositivosDiv pero la tabla estará vacía y dira "No hay dispositivos firmantes"
                    tbody.innerHTML = '<tr><td colspan="3">No hay dispositivos firmantes</td></tr>';
                    resultadoDispositivosDiv.style.display = 'flex';
                    btn_actualizar_contribuyente_fiskaly.style.display = 'block';
                }else{
                    resultadoDispositivosDiv.style.display = 'none';
                    btn_actualizar_contribuyente_fiskaly.style.display = 'block';
                }
            }
        }
        
        return result;
        
    } catch (error) {
        console.error('Error al obtener dispositivos firmantes:', error);
        return null;
    }
}

/**
 * Función para abrir modal de crear dispositivo firmante
 */
function abrirModalCrearDispositivoFirmante(signerId) {
    Swal.fire({
        title: '¿Crear dispositivo firmante?',
        text: 'Se creará un nuevo dispositivo firmante con el ID: ' + signerId,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, crear',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            crearDispositivoFirmante(signerId);
        }
    });
}

/**
 * Función para crear dispositivo firmante
 */
async function crearDispositivoFirmante(signerId) {
    if (!signerId) {
        Swal.fire('Error', 'No se proporcionó el ID del dispositivo firmante', 'error');
        return;
    }
    
    const accessToken = localStorage.getItem('fiskaly_access_token');
    if (!accessToken) {
        Swal.fire('Error', 'No se encontró el token de autenticación. Por favor, autentica primero.', 'error');
        return;
    }
    
    // Mostrar loader
    Swal.fire({
        title: 'Creando dispositivo...',
        allowOutsideClick: false,   
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const raw = JSON.stringify({
        "metadata": {
            "metadata1": "metadata1",
            "metadata2": "metadata2"
        }
    });
    
    try {
        const url = urlApiFiskaly + 'signers/' + signerId;
        const response = await fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + accessToken
            },
            body: raw
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        Swal.fire('¡Éxito!', 'Dispositivo firmante creado correctamente', 'success').then(() => {
            // Refrescar la página
            window.location.reload();
        });
        
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', error.message || 'Error al crear el dispositivo firmante', 'error');
    }
}

/**
 * Función para obtener clients desde Fiskaly
 */
async function obtenerClients() {
    const accessToken = localStorage.getItem('fiskaly_access_token');
    if (!accessToken) {
        console.error('No se encontró el token de autenticación');
        return;
    }
    // COMPROBAR SI EXISTE CONTRIBUYENTE
    const fiskaly_contribuyente_existe = document.getElementById('fiskaly_contribuyente_existe');
    
    try {
        const url = urlApiFiskaly + 'clients';
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
        
        // Contar clients con estado ENABLED
        let clientsEnabledCount = 0;
        if (result && result.results && result.results.length > 0) {
            clientsEnabledCount = result.results.filter(item => {
                const content = item.content || {};
                return content.state === 'ENABLED';
            }).length;
        }
        
        // Obtener cantidad de sucursales de la empresa
        const idEmpresa = window.idEmpresa || 0;
        let cantidadSucursales = 0;
        if (idEmpresa) {
            const sucursales = await obtenerListaSucursales();
            cantidadSucursales = sucursales.length;
        }
        
        // Ocultar botón si la cantidad de clients ENABLED coincide con la cantidad de sucursales
        const btnCrearClient = document.getElementById('btn_crear_client');
        if (btnCrearClient) {
            if (clientsEnabledCount > 0 && cantidadSucursales > 0 && clientsEnabledCount === cantidadSucursales) {
                btnCrearClient.style.display = 'none';
            } else {
                btnCrearClient.style.display = 'block';
            }
        }
        
        // Mostrar la sección de clients
        const resultadoClientsDiv = document.getElementById('resultado_clients');
        const sinClientsDiv = document.getElementById('sin_clients_fiskaly');
        const tbody = document.getElementById('clients');
        const btn_actualizar_contribuyente_fiskaly = document.getElementById('btn_actualizar_contribuyente_fiskaly');
        
        if (resultadoClientsDiv && tbody) {
            if (result && result.results && result.results.length > 0) {
                tbody.innerHTML = '';
                
                result.results.forEach((item) => {
                    const content = item.content || {};
                    const metadata = item.metadata || {};
                    const id = content.id || 'N/A';
                    const state = content.state || 'N/A';
                    const idSucursal = metadata.sucursal || 'N/A';
                    const nombreSucursal = metadata.nombre_sucursal || 'N/A';
                    const relEmpresa = metadata.rel_empresa || 'N/A';
                    
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${id}</td>
                        <td>${state}</td>
                        <td>${idSucursal}</td>
                        <td>${nombreSucursal}</td>
                        <td>${relEmpresa}</td>
                        <td>
                            <button type="button" onclick="abrirModalActualizarClient('${id}')" class="btn btn-sm btn-icon btn-primary waves-effect waves-light" title="Actualizar">
                                <i class="icon-base ri ri-edit-line"></i>
                            </button>
                            <button type="button" onclick="obtenerClient('${id}')" class="btn btn-sm btn-icon btn-info waves-effect waves-light" title="Ver detalles">
                                <i class="icon-base ri ri-eye-line"></i>
                            </button>
                            <a href="fiskaly_invoices.php?id=${idSucursal}" class="btn btn-sm btn-icon btn-secondary waves-effect waves-light" title="Ver facturas">
                                <i class="icon-base ri ri-file-list-line"></i>
                            </a>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
                
                resultadoClientsDiv.style.display = 'flex';
                if (sinClientsDiv) sinClientsDiv.style.display = 'none';
                
                if (btn_actualizar_contribuyente_fiskaly) {
                    btn_actualizar_contribuyente_fiskaly.style.display = 'none';
                }
                } else {
                    if (fiskaly_contribuyente_existe && fiskaly_contribuyente_existe.value == '1') {
                        // si existe contribuyente mostraras resultadoClientsDiv pero la tabla estará vacía
                        tbody.innerHTML = '<tr><td colspan="6">No hay clientes</td></tr>';
                    resultadoClientsDiv.style.display = 'flex';
                    if (sinClientsDiv) sinClientsDiv.style.display = 'none';
                    if (btn_actualizar_contribuyente_fiskaly) {
                        btn_actualizar_contribuyente_fiskaly.style.display = 'block';
                    }
                } else {
                    resultadoClientsDiv.style.display = 'none';
                    if (sinClientsDiv) sinClientsDiv.style.display = 'block';
                    if (btn_actualizar_contribuyente_fiskaly) {
                        btn_actualizar_contribuyente_fiskaly.style.display = 'block';
                    }
                }
            }
        }
        
        return result;
        
    } catch (error) {
        console.error('Error al obtener clients:', error);
        return null;
    }
}

/**
 * Función para obtener lista de sucursales de la empresa
 */
async function obtenerListaSucursales() {
    const idEmpresa = window.idEmpresa || 0;
    if (!idEmpresa) {
        return [];
    }
    
    try {
        const formData = new FormData();
        formData.append('id_empresa', idEmpresa);
        formData.append('draw', 1);
        formData.append('start', 0);
        formData.append('length', 1000); // Obtener todas las sucursales
        formData.append('search[value]', '');
        
        const response = await fetch('parts/fiskaly_manager/main/get_sucursales_empresa.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            return [];
        }
        
        const result = await response.json();
        
        if (result && result.data && result.data.length > 0) {
            return result.data.map(item => {
                return {
                    id_sucursal: item[0] || '', // id_sucursal está en la posición 0
                    nombre_sucursal: item[1] || '' // nombre_sucursal está en la posición 1
                };
            });
        }
        
        return [];
    } catch (error) {
        console.error('Error al obtener sucursales:', error);
        return [];
    }
}

/**
 * Función para abrir modal de crear client
 */
async function abrirModalCrearClient(clientId) {
    // Obtener lista de sucursales
    const sucursales = await obtenerListaSucursales();
    
    if (sucursales.length === 0) {
        Swal.fire('Error', 'No hay sucursales disponibles para esta empresa.', 'error');
        return;
    }
    
    // Crear opciones del select
    let optionsHtml = '<option value="">Seleccionar sucursal</option>';
    sucursales.forEach(sucursal => {
        const label = `${sucursal.nombre_sucursal} (ID: ${sucursal.id_sucursal})`;
        optionsHtml += `<option value="${sucursal.id_sucursal}" data-nombre="${sucursal.nombre_sucursal.replace(/"/g, '&quot;')}">${label}</option>`;
    });
    
    // Crear modal dinámicamente
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'modalCrearClient';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-labelledby', 'modalCrearClientLabel');
    modal.setAttribute('aria-hidden', 'true');

    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearClient">
                        <div class="mb-3">
                            <label class="form-label">Client ID (solo lectura)</label>
                            <input type="text" class="form-control" value="${clientId}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="sucursal_crear" class="form-label">Sucursal *</label>
                            <select class="form-select" id="sucursal_crear" name="sucursal" required>
                                ${optionsHtml}
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="crearClient('${clientId}')">Crear Cliente</button>
                </div>
            </div>
        </div>
    `;

    // Agregar modal al body
    document.body.appendChild(modal);

    // Mostrar modal
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();

    // Limpiar modal cuando se oculte
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal);
    });
}

/**
 * Función para crear client
 */
async function crearClient(clientId) {
    if (!clientId) {
        Swal.fire('Error', 'No se proporcionó el ID del cliente', 'error');
        return;
    }
    
    // Obtener la sucursal del select
    const sucursalSelect = document.getElementById('sucursal_crear');
    if (!sucursalSelect) {
        Swal.fire('Error', 'No se encontró el campo de sucursal', 'error');
        return;
    }
    
    const selectedOption = sucursalSelect.options[sucursalSelect.selectedIndex];
    if (!selectedOption || !selectedOption.value) {
        Swal.fire('Error', 'Por favor selecciona una sucursal', 'error');
        return;
    }
    
    const idSucursal = selectedOption.value;
    const nombreSucursal = selectedOption.getAttribute('data-nombre') || '';
    const idEmpresa = window.idEmpresa || 0;
    
    const accessToken = localStorage.getItem('fiskaly_access_token');
    if (!accessToken) {
        Swal.fire('Error', 'No se encontró el token de autenticación. Por favor, autentica primero.', 'error');
        return;
    }
    
    // Cerrar el modal primero
    const modal = document.getElementById('modalCrearClient');
    if (modal) {
        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) {
            modalInstance.hide();
        }
    }
    
    // Mostrar loader
    Swal.fire({
        title: 'Creando cliente...',
        allowOutsideClick: false,   
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Construir el objeto con el formato correcto
    const data = {
        metadata: {
            sucursal: idSucursal.toString(),
            nombre_sucursal: nombreSucursal,
            rel_empresa: idEmpresa.toString()
        }
    };
    
    try {
        const url = urlApiFiskaly + 'clients/' + clientId;
        const response = await fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + accessToken
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP ${response.status}`);
        }
        
        const result = await response.json();

        // Guardar el cliente en la base de datos quint_fisklay en la tabla datos_fiskaly_sucursales
        const content = result.content || {};
        const signer = content.signer || {};
        const idClientFiskaly = content.id || clientId;
        const relFirmante = signer.id || '';
        
        // Preparar datos para guardar en la base de datos
        const formData = new FormData();
        formData.append('id_sucursal', idSucursal);
        formData.append('nombre_sucursal', nombreSucursal);
        formData.append('rel_firmante', relFirmante);
        formData.append('id_client_fisklaly', idClientFiskaly);
        formData.append('rel_empresa', idEmpresa);
        
        try {
            const saveResponse = await fetch('parts/fiskaly_manager/main/guardar_client_sucursal.php', {
                method: 'POST',
                body: formData
            });
            
            if (!saveResponse.ok) {
                console.error('Error al guardar en la base de datos local');
            } else {
                const saveResult = await saveResponse.json();
                if (!saveResult.success) {
                    console.error('Error al guardar en la base de datos:', saveResult.message);
                }
            }
        } catch (saveError) {
            console.error('Error al guardar en la base de datos local:', saveError);
        }
        
        Swal.fire('¡Éxito!', 'Cliente creado correctamente', 'success').then(() => {
            // Recargar clients
            obtenerClients();
        });
        
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', error.message || 'Error al crear el cliente', 'error');
    }
}

/**
 * Función para obtener un client específico
 */
async function obtenerClient(clientId) {
    if (!clientId) {
        Swal.fire('Error', 'No se proporcionó el ID del client', 'error');
        return;
    }
    
    const accessToken = localStorage.getItem('fiskaly_access_token');
    if (!accessToken) {
        Swal.fire('Error', 'No se encontró el token de autenticación. Por favor, autentica primero.', 'error');
        return;
    }
    
    try {
        const url = urlApiFiskaly + 'clients/' + clientId;
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
        
        // Mostrar detalles en un modal
        const content = result.content || {};
        const signer = content.signer || {};
        const metadata = result.metadata || {};
        
        let metadataHtml = '<ul>';
        for (const [key, value] of Object.entries(metadata)) {
            metadataHtml += `<li><strong>${key}:</strong> ${value}</li>`;
        }
        metadataHtml += '</ul>';
        
        Swal.fire({
            title: 'Detalles del Client',
            html: `
                <div class="text-start">
                    <p><strong>ID:</strong> ${content.id || 'N/A'}</p>
                    <p><strong>Estado:</strong> ${content.state || 'N/A'}</p>
                    <p><strong>Signer ID:</strong> ${signer.id || 'N/A'}</p>
                    <hr>
                    <h6>Metadata:</h6>
                    ${Object.keys(metadata).length > 0 ? metadataHtml : '<p>No hay metadata</p>'}
                </div>
            `,
            width: '600px',
            confirmButtonText: 'Cerrar'
        });
        
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', error.message || 'Error al obtener el client', 'error');
    }
}

/**
 * Función para abrir modal de actualizar client
 */
async function abrirModalActualizarClient(clientId) {
    if (!clientId) {
        Swal.fire('Error', 'No se proporcionó el ID del client', 'error');
        return;
    }
    
    const accessToken = localStorage.getItem('fiskaly_access_token');
    if (!accessToken) {
        Swal.fire('Error', 'No se encontró el token de autenticación. Por favor, autentica primero.', 'error');
        return;
    }
    
    // Obtener datos actuales del client
    let clientData = null;
    try {
        const url = urlApiFiskaly + 'clients/' + clientId;
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + accessToken
            }
        });
        
        if (!response.ok) {
            throw new Error('Error al obtener datos del client');
        }
        
        clientData = await response.json();
    } catch (error) {
        console.error('Error al obtener client:', error);
        Swal.fire('Error', 'Error al obtener los datos del client', 'error');
        return;
    }
    
    // Obtener lista de sucursales
    const sucursales = await obtenerListaSucursales();
    const idEmpresa = window.idEmpresa || 0;
    
    const content = clientData && clientData.content ? clientData.content : {};
    const metadata = clientData.metadata || {};
    const idSucursalActual = metadata.sucursal || '';
    const nombreSucursalActual = metadata.nombre_sucursal || '';
    
    // Crear opciones del select de sucursales
    let optionsHtml = '<option value="">Seleccionar sucursal</option>';
    sucursales.forEach(sucursal => {
        const selected = (sucursal.id_sucursal.toString() === idSucursalActual) ? 'selected' : '';
        const label = `${sucursal.nombre_sucursal} (ID: ${sucursal.id_sucursal})`;
        optionsHtml += `<option value="${sucursal.id_sucursal}" data-nombre="${sucursal.nombre_sucursal.replace(/"/g, '&quot;')}" ${selected}>${label}</option>`;
    });
    
    // Crear modal dinámicamente
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'modalActualizarClient';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-labelledby', 'modalActualizarClientLabel');
    modal.setAttribute('aria-hidden', 'true');

    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Actualizar Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formActualizarClient">
                        <div class="mb-3">
                            <label class="form-label">ID (solo lectura)</label>
                            <input type="text" class="form-control" value="${content.id || ''}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="state_client" class="form-label">Estado *</label>
                            <select class="form-select" id="state_client" name="state" required>
                                <option value="">Seleccionar estado</option>
                                <option value="ENABLED" ${content.state === 'ENABLED' ? 'selected' : ''}>ENABLED</option>
                                <option value="DISABLED" ${content.state === 'DISABLED' ? 'selected' : ''}>DISABLED</option>
                            </select>
                        </div>
                        <hr>
                        <h6>Sucursal</h6>
                        <div class="mb-3">
                            <label for="sucursal_update" class="form-label">Sucursal *</label>
                            <select class="form-select" id="sucursal_update" name="sucursal" required>
                                ${optionsHtml}
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ID Empresa (solo lectura)</label>
                            <input type="text" class="form-control" id="id_empresa_update" value="${idEmpresa}" readonly>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="actualizarClient('${clientId}')">Actualizar Client</button>
                </div>
            </div>
        </div>
    `;

    // Agregar modal al body
    document.body.appendChild(modal);

    // Mostrar modal
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();

    // Limpiar modal cuando se oculte
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal);
    });
}

/**
 * Función para actualizar client
 */
async function actualizarClient(clientId) {
    const state = document.getElementById('state_client').value.trim();
    const sucursalSelect = document.getElementById('sucursal_update');
    const idEmpresa = document.getElementById('id_empresa_update').value.trim();
    
    // Validar campos obligatorios
    if (!state) {
        Swal.fire('Error', 'Por favor selecciona un estado', 'error');
        return;
    }
    
    if (!sucursalSelect || !sucursalSelect.value) {
        Swal.fire('Error', 'Por favor selecciona una sucursal', 'error');
        return;
    }
    
    const selectedOption = sucursalSelect.options[sucursalSelect.selectedIndex];
    const idSucursal = selectedOption.value;
    const nombreSucursal = selectedOption.getAttribute('data-nombre') || '';
    
    // Obtener token de localStorage
    const accessToken = localStorage.getItem('fiskaly_access_token');
    if (!accessToken) {
        Swal.fire('Error', 'No se encontró el token de autenticación. Por favor, autentica primero.', 'error');
        return;
    }
    
    // Construir objeto de datos
    const data = {
        /* ESTO NO SE USA
        content: {
            state: state
        },
        */
        metadata: {
            sucursal: idSucursal.toString(),
            nombre_sucursal: nombreSucursal,
            rel_empresa: idEmpresa
        }
    };
    
    // Deshabilitar botón durante la actualización
    const btnActualizar = document.querySelector('#modalActualizarClient .btn-primary');
    btnActualizar.disabled = true;
    btnActualizar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Actualizando...';
    
    try {
        const url = urlApiFiskaly + 'clients/' + clientId;
        const response = await fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + accessToken
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        // Actualizar en la base de datos local
        const formData = new FormData();
        formData.append('id_client_fisklaly', clientId);
        formData.append('id_sucursal', idSucursal);
        formData.append('nombre_sucursal', nombreSucursal);
        formData.append('rel_empresa', idEmpresa);
        
        try {
            const updateResponse = await fetch('parts/fiskaly_manager/main/actualizar_client_sucursal.php', {
                method: 'POST',
                body: formData
            });
            
            if (!updateResponse.ok) {
                console.error('Error al actualizar en la base de datos local');
            } else {
                const updateResult = await updateResponse.json();
                if (!updateResult.success) {
                    console.error('Error al actualizar en la base de datos:', updateResult.message);
                }
            }
        } catch (updateError) {
            console.error('Error al actualizar en la base de datos local:', updateError);
        }
        
        Swal.fire('¡Éxito!', 'Client actualizado correctamente', 'success').then(() => {
            // Cerrar modal
            bootstrap.Modal.getInstance(document.getElementById('modalActualizarClient')).hide();
            
            // Recargar clients
            obtenerClients();
        });
        
    } catch (error) {
        Swal.fire('Error', error.message || 'Error al actualizar el client', 'error');
        // Restaurar botón
        btnActualizar.disabled = false;
        btnActualizar.innerHTML = 'Actualizar Client';
    }
}

/**
 * Función para obtener acuerdos desde Fiskaly
 */
async function obtenerAcuerdosFiskaly() {
/*
    const accessToken = localStorage.getItem('fiskaly_access_token');
    if (!accessToken) {
        console.error('No se encontró el token de autenticación');
        return;
    }
    
    try {
        const url = urlApiFiskaly + 'taxpayer/agreement';
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + accessToken
            }
        });
        
        if (!response.ok) {
            // Si el error es 404, significa que no existe acuerdo
            if (response.status === 404) {
                mostrarSinAcuerdos();
                return null;
            }
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        // Verificar si hay resultados
        if (result && result.results && result.results.length > 0) {
            // Mostrar el primer acuerdo (o el más reciente)
            const acuerdo = result.results[0];
            mostrarAcuerdos(acuerdo);
        } else {
            mostrarSinAcuerdos();
        }
        
        return result;
        
    } catch (error) {
        console.error('Error al obtener acuerdos:', error);
        // Si es un error 404 o similar, mostrar sin acuerdos
        if (error.message && error.message.includes('404')) {
            mostrarSinAcuerdos();
        }
        return null;
    }
*/
}

/**
 * Función para mostrar acuerdos
 */
function mostrarAcuerdos(acuerdo) {
    const resultadoAcuerdosDiv = document.getElementById('resultado_acuerdos');
    const sinAcuerdosDiv = document.getElementById('sin_acuerdos');
    
    if (resultadoAcuerdosDiv) {
        const content = acuerdo.content || {};
        const representative = content.representative || {};
        const address = representative.address || {};
        
        // Actualizar campos
        const documentUrlElement = document.getElementById('document_url_agreement');
        const fullNameElement = document.getElementById('full_name_agreement');
        const taxNumberElement = document.getElementById('tax_number_agreement');
        const municipalityElement = document.getElementById('municipality_agreement');
        const cityElement = document.getElementById('city_agreement');
        const streetElement = document.getElementById('street_agreement');
        const postalCodeElement = document.getElementById('postal_code_agreement');
        const numberElement = document.getElementById('number_agreement');
        const countryCodeElement = document.getElementById('country_code_agreement');
        const createdAtElement = document.getElementById('created_at_agreement');
        
        if (documentUrlElement) documentUrlElement.textContent = content.document_url || 'N/A';
        if (fullNameElement) fullNameElement.textContent = representative.full_name || 'N/A';
        if (taxNumberElement) taxNumberElement.textContent = representative.tax_number || 'N/A';
        if (municipalityElement) municipalityElement.textContent = address.municipality || 'N/A';
        if (cityElement) cityElement.textContent = address.city || 'N/A';
        if (streetElement) streetElement.textContent = address.street || 'N/A';
        if (postalCodeElement) postalCodeElement.textContent = address.postal_code || 'N/A';
        if (numberElement) numberElement.textContent = address.number || 'N/A';
        if (countryCodeElement) countryCodeElement.textContent = address.country_code || 'N/A';
        if (createdAtElement) {
            const fecha = content.created_at ? new Date(content.created_at).toLocaleString('es-ES') : 'N/A';
            createdAtElement.textContent = fecha;
        }
        
        resultadoAcuerdosDiv.style.display = 'flex';
    }
    
    if (sinAcuerdosDiv) {
        sinAcuerdosDiv.style.display = 'none';
    }
}

/**
 * Función para mostrar el mensaje de sin acuerdos
 */
function mostrarSinAcuerdos() {
    const resultadoAcuerdosDiv = document.getElementById('resultado_acuerdos');
    const sinAcuerdosDiv = document.getElementById('sin_acuerdos');
    
    if (resultadoAcuerdosDiv) {
        resultadoAcuerdosDiv.style.display = 'none';
    }
    if (sinAcuerdosDiv) {
        sinAcuerdosDiv.style.display = 'block';
    }
}

/**
 * Función para abrir modal de crear acuerdo
 */
function abrirModalCrearAcuerdo() {
    // Crear modal dinámicamente
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'modalCrearAcuerdo';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-labelledby', 'modalCrearAcuerdoLabel');
    modal.setAttribute('aria-hidden', 'true');

    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Acuerdo Fiskaly</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearAcuerdo">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name_acuerdo" class="form-label">Nombre Completo *</label>
                                <input type="text" class="form-control" id="full_name_acuerdo" name="full_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tax_number_acuerdo" class="form-label">NIF *</label>
                                <input type="text" class="form-control" id="tax_number_acuerdo" name="tax_number" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="municipality_acuerdo" class="form-label">Municipio</label>
                                <input type="text" class="form-control" id="municipality_acuerdo" name="municipality">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city_acuerdo" class="form-label">Ciudad</label>
                                <input type="text" class="form-control" id="city_acuerdo" name="city">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="street_acuerdo" class="form-label">Calle</label>
                                <input type="text" class="form-control" id="street_acuerdo" name="street">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="postal_code_acuerdo" class="form-label">Código Postal</label>
                                <input type="text" class="form-control" id="postal_code_acuerdo" name="postal_code">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="number_acuerdo" class="form-label">Número</label>
                                <input type="text" class="form-control" id="number_acuerdo" name="number">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="country_code_acuerdo" class="form-label">Código País</label>
                                <input type="text" class="form-control" id="country_code_acuerdo" name="country_code" maxlength="2">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="crearAcuerdoFiskaly()">Crear Acuerdo</button>
                </div>
            </div>
        </div>
    `;

    // Agregar modal al body
    document.body.appendChild(modal);

    // Mostrar modal
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();

    // Limpiar modal cuando se oculte
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal);
    });
}

/**
 * Función para crear acuerdo Fiskaly
 */
async function crearAcuerdoFiskaly() {
    const full_name = document.getElementById('full_name_acuerdo').value.trim();
    const tax_number = document.getElementById('tax_number_acuerdo').value.trim();
    const municipality = document.getElementById('municipality_acuerdo').value.trim();
    const city = document.getElementById('city_acuerdo').value.trim();
    const street = document.getElementById('street_acuerdo').value.trim();
    const postal_code = document.getElementById('postal_code_acuerdo').value.trim();
    const number = document.getElementById('number_acuerdo').value.trim();
    const country_code = document.getElementById('country_code_acuerdo').value.trim();
    
    // Validar campos obligatorios
    if (!full_name || !tax_number) {
        Swal.fire('Error', 'Por favor completa todos los campos obligatorios (Nombre Completo y NIF)', 'error');
        return;
    }
    
    // Obtener token de localStorage
    const accessToken = localStorage.getItem('fiskaly_access_token');
    if (!accessToken) {
        Swal.fire('Error', 'No se encontró el token de autenticación. Por favor, autentica primero.', 'error');
        return;
    }
    
    // Construir objeto de datos con el formato correcto
    const data = {
        content: {
            representative: {
                full_name: full_name,
                tax_number: tax_number
            }
        }
    };
    
    // Agregar address si hay algún campo de dirección
    if (municipality || city || street || postal_code || number || country_code) {
        data.content.representative.address = {};
        if (municipality) data.content.representative.address.municipality = municipality;
        if (city) data.content.representative.address.city = city;
        if (street) data.content.representative.address.street = street;
        if (postal_code) data.content.representative.address.postal_code = postal_code;
        if (number) data.content.representative.address.number = number;
        if (country_code) data.content.representative.address.country_code = country_code;
    }
    
    // Deshabilitar botón durante la creación
    const btnCrear = document.querySelector('#modalCrearAcuerdo .btn-primary');
    btnCrear.disabled = true;
    btnCrear.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creando...';
    
    try {
        const url = urlApiFiskaly + 'taxpayer/agreement';
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + accessToken
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        Swal.fire('¡Éxito!', 'Acuerdo creado correctamente', 'success').then(() => {
            // Cerrar modal
            bootstrap.Modal.getInstance(document.getElementById('modalCrearAcuerdo')).hide();
            
            // Actualizar y mostrar datos del acuerdo
            if (result && result.content) {
                mostrarAcuerdos({ content: result.content });
            } else {
                // Recargar acuerdos
                obtenerAcuerdosFiskaly();
            }
        });
        
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', error.message || 'Error al crear el acuerdo', 'error');
        // Restaurar botón
        btnCrear.disabled = false;
        btnCrear.innerHTML = 'Crear Acuerdo';
    }
}

/**
 * Función para cambiar el tipo de API (test/produccion)
 * @param {number} id_empresa - ID de la empresa
 * @param {string} nuevo_tipo - Nuevo tipo de API ('test' o 'produccion')
 */
function cambiarTipoApi(id_empresa, nuevo_tipo) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: `¿Deseas cambiar el tipo de API a ${nuevo_tipo}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar',
        input: 'password',
        inputPlaceholder: 'Contraseña de usuario root',
        inputAttributes: {
            autocapitalize: 'off',
            autocorrect: 'off'
        },
        showLoaderOnConfirm: true,
        preConfirm: (password) => {
            if (!password) {
                Swal.showValidationMessage('Por favor ingresa la contraseña');
                return false;
            }
            // Validar contraseña y actualizar tipo_api
            return fetch('parts/fiskaly_manager/main/actualizar_tipo_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    id_empresa: id_empresa,
                    nuevo_tipo: nuevo_tipo,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Error al actualizar el tipo de API');
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(error.message || 'Error al procesar la solicitud');
                return false;
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed && result.value && result.value.success) {
            // Limpiar localStorage
            localStorage.clear();
            
            // Mostrar mensaje de éxito y recargar página
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: `Tipo de API cambiado a ${nuevo_tipo} correctamente`,
                confirmButtonText: 'Aceptar'
            }).then(() => {
                window.location.reload();
            });
        }
    });
}

</script>