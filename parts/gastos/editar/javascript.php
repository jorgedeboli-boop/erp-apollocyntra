<!-- JavaScript para el formulario de edición de gasto -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar validación del formulario
    configurarValidacion();
    
    // Configurar envío del formulario
    configurarEnvioFormulario();
    
    // Configurar validaciones especiales
    configurarValidacionesEspeciales();
});

/**
 * Configurar validación del formulario
 */
function configurarValidacion() {
    const form = document.getElementById('formEditarGasto');
    
    // Validación en tiempo real
    form.addEventListener('input', function(event) {
        const field = event.target;
        if (field.hasAttribute('required')) {
            validarCampo(field);
        }
    });
    
    // Validación al cambiar select
    form.addEventListener('change', function(event) {
        const field = event.target;
        if (field.hasAttribute('required')) {
            validarCampo(field);
        }
    });
}

/**
 * Validar un campo específico
 */
function validarCampo(field) {
    const isValid = field.checkValidity();
    
    // Buscar el label asociado
    const label = field.parentElement.querySelector('label[for="' + field.id + '"]');
    
    if (isValid) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        
        // Actualizar label si existe
        if (label) {
            label.classList.remove('text-danger');
            label.classList.add('text-success');
        }
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
        
        // Actualizar label si existe
        if (label) {
            label.classList.remove('text-success');
            label.classList.add('text-danger');
        }
    }
    
    return isValid;
}

/**
 * Configurar envío del formulario
 */
function configurarEnvioFormulario() {
    const form = document.getElementById('formEditarGasto');
    
    if (!form) {
        console.error('Formulario formEditarGasto no encontrado');
        return;
    }
    
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        console.log('Formulario enviado, validando...');
        
        // Validar todo el formulario
        if (!form.checkValidity()) {
            console.log('Formulario no válido, mostrando validaciones...');
            form.classList.add('was-validated');
            return;
        }
        
        console.log('Formulario válido, obteniendo botón...');
        
        // Obtener el botón de editar
        const btnEditar = document.getElementById('btnEditarGasto');
        if (!btnEditar) {
            console.error('Botón de editar no encontrado');
            return;
        }
        
        console.log('Botón encontrado, deshabilitando...');
        
        // Deshabilitar botón y mostrar loader
        btnEditar.disabled = true;
        btnEditar.innerHTML = '<span class="spinner-border me-1" role="status" aria-hidden="true"></span>Actualizando...';
        
        // Obtener datos del formulario
        const formData = new FormData(form);
        
        console.log('Enviando datos al servidor...');
        
        // Enviar datos al servidor
        fetch('parts/gastos/editar/procesar_editar_gasto.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta del servidor:', data);
            
            if (data.success) {
                // Éxito
                mostrarMensaje('success', data.message);
                
                // Redirigir después de un breve delay
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            } else {
                // Error
                mostrarMensaje('error', data.error || 'Error al actualizar el gasto');
                
                // Rehabilitar botón
                btnEditar.disabled = false;
                btnEditar.innerHTML = '<i class="icon-base ri ri-check-line me-2"></i>Actualizar Gasto';
            }
        })
        .catch(error => {
            console.error('Error en la petición:', error);
            mostrarMensaje('error', 'Error de conexión. Intente nuevamente.');
            
            // Rehabilitar botón
            btnEditar.disabled = false;
            btnEditar.innerHTML = '<i class="icon-base ri ri-check-line me-2"></i>Actualizar Gasto';
        });
    });
}

/**
 * Configurar validaciones especiales
 */
function configurarValidacionesEspeciales() {
    // Validación de total (solo números y decimales)
    const totalField = document.getElementById('total_gasto');
    if (totalField) {
        totalField.addEventListener('input', function(e) {
            // Permitir solo números y un punto decimal
            this.value = this.value.replace(/[^0-9.]/g, '');
            
            // Asegurar que solo haya un punto decimal
            const parts = this.value.split('.');
            if (parts.length > 2) {
                this.value = parts[0] + '.' + parts.slice(1).join('');
            }
        });
    }
    
    // Validación de número de factura (solo alfanumérico)
    const facturaField = document.getElementById('numero_factura_proveedor');
    if (facturaField) {
        facturaField.addEventListener('input', function(e) {
            // Permitir solo letras, números y guiones
            this.value = this.value.replace(/[^a-zA-Z0-9\-]/g, '');
        });
    }
}

/**
 * Mostrar mensaje de éxito o error
 */
function mostrarMensaje(tipo, mensaje) {
    // Crear elemento de alerta
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Insertar al inicio del formulario
    const form = document.getElementById('formEditarGasto');
    if (form) {
        form.parentNode.insertBefore(alertDiv, form);
        
        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

/**
 * Función para limpiar validaciones
 */
function limpiarValidaciones() {
    const form = document.getElementById('formEditarGasto');
    if (form) {
        form.classList.remove('was-validated');
        
        // Limpiar clases de validación de todos los campos
        const fields = form.querySelectorAll('.form-control, .form-select');
        fields.forEach(field => {
            field.classList.remove('is-valid', 'is-invalid');
        });
    }
}

/**
 * Función para restaurar formulario
 */
function restaurarFormulario() {
    // Recargar la página para restaurar los valores originales
    window.location.reload();
}
</script>