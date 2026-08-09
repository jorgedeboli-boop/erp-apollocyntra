<!-- JavaScript específico para la página de nuevo usuario -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formNuevoUsuario');
    const passwordField = document.getElementById('password_usuario');
    const confirmPasswordField = document.getElementById('confirmar_password');
    const usuarioLoginField = document.getElementById('usuario_login');
    
    // Validación de contraseñas
    function validatePasswords() {
        const password = passwordField.value;
        const confirmPassword = confirmPasswordField.value;
        
        if (password !== confirmPassword) {
            confirmPasswordField.setCustomValidity('Las contraseñas no coinciden');
            confirmPasswordField.classList.add('is-invalid');
        } else {
            confirmPasswordField.setCustomValidity('');
            confirmPasswordField.classList.remove('is-invalid');
            confirmPasswordField.classList.add('is-valid');
        }
    }
    
    // Validación de longitud de contraseña
    function validatePasswordLength() {
        const password = passwordField.value;
        
        if (password.length < 8) {
            passwordField.setCustomValidity('La contraseña debe tener al menos 8 caracteres');
            passwordField.classList.add('is-invalid');
        } else {
            passwordField.setCustomValidity('');
            passwordField.classList.remove('is-invalid');
            passwordField.classList.add('is-valid');
        }
    }
    
    // Validación de usuario único
    function validateUsuarioUnico() {
        const usuario = usuarioLoginField.value;
        
        if (usuario.length < 3) {
            usuarioLoginField.setCustomValidity('El usuario debe tener al menos 3 caracteres');
            usuarioLoginField.classList.add('is-invalid');
            return;
        }
        
        // Aquí puedes hacer una llamada AJAX para verificar si el usuario ya existe
        // Por ahora solo validamos la longitud
        usuarioLoginField.setCustomValidity('');
        usuarioLoginField.classList.remove('is-invalid');
        usuarioLoginField.classList.add('is-valid');
    }
    
    // Event listeners para validación en tiempo real
    passwordField.addEventListener('input', validatePasswordLength);
    confirmPasswordField.addEventListener('input', validatePasswords);
    usuarioLoginField.addEventListener('input', validateUsuarioUnico);
    
    // Validación del formulario antes de enviar
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar contraseñas
        validatePasswords();
        validatePasswordLength();
        validateUsuarioUnico();
        
        // Verificar si hay errores de validación
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }
        
        // Si todo está bien, enviar el formulario
        submitForm();
    });
    
    // Función para enviar el formulario
    function submitForm() {
        const formData = new FormData(form);
        
        // Ocultar botón submit y mostrar loader
        const submitBtn = form.querySelector('button[type="submit"]');
        const loaderBtn = form.querySelector('#loaderbtn');
        
        submitBtn.style.display = 'none';
        loaderBtn.style.display = 'inline-block';
        loaderBtn.disabled = false;
        
        // Enviar formulario
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Usuario creado exitosamente', 'success');
                form.reset();
                // Redirigir después de 2 segundos
                setTimeout(() => {
                    window.location.href = '../../../usuarios.php';
                }, 2000);
            } else {
                showAlert(data.message || 'Error al crear el usuario', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error de conexión. Intente nuevamente.', 'danger');
        })
        .finally(() => {
            // Ocultar loader y mostrar botón submit
            loaderBtn.style.display = 'none';
            loaderBtn.disabled = true;
            submitBtn.style.display = 'inline-block';
        });
    }
    
    // Función para mostrar alertas
    function showAlert(message, type) {
        // Remover alertas existentes
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());
        
        // Crear nueva alerta
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Insertar al inicio del formulario
        form.insertBefore(alertDiv, form.firstChild);
        
        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
    
    // Validación de email
    const emailField = document.getElementById('email_usuario');
    emailField.addEventListener('input', function() {
        const email = this.value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            this.setCustomValidity('Por favor ingrese un email válido');
            this.classList.add('is-invalid');
        } else {
            this.setCustomValidity('');
            this.classList.remove('is-invalid');
            if (email) this.classList.add('is-valid');
        }
    });
    
    // Validación de teléfono
    const telefonoField = document.getElementById('telefono_usuario');
    telefonoField.addEventListener('input', function() {
        const telefono = this.value;
        const telefonoRegex = /^[\+]?[0-9\s\-\(\)]+$/;
        
        if (telefono && !telefonoRegex.test(telefono)) {
            this.setCustomValidity('Por favor ingrese un teléfono válido');
            this.classList.add('is-invalid');
        } else {
            this.setCustomValidity('');
            this.classList.remove('is-invalid');
            if (telefono) this.classList.add('is-valid');
        }
    });
    
    // Limpiar validaciones al resetear
    form.addEventListener('reset', function() {
        const fields = form.querySelectorAll('.form-control, .form-select');
        fields.forEach(field => {
            field.classList.remove('is-valid', 'is-invalid');
        });
        form.classList.remove('was-validated');
    });
    
    // Inicializar Select2 usando el código del template
    var select2 = $('.select2');
    // For all Select2
    if (select2.length) {
        select2.each(function () {
            var $this = $(this);
            select2Focus($this);
            $this.select2({
                dropdownParent: $this.parent()
            });
        });
    }
    
    // Tooltips para campos opcionales
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
