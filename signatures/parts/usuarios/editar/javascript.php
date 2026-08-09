<!-- JavaScript específico para editar usuario -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Select2 para los campos de selección
    if (typeof $ !== 'undefined') {
        $('#sucursal_usuario, #privilegio_usuario').select2({
            dropdownParent: $('body')
        });
    }
    
    // Elementos del formulario principal
    const form = document.getElementById('formEditarUsuario');
    const btnSubmit = document.getElementById('btnEditarUsuario');
    const loaderBtn = document.getElementById('loaderbtn');
    
    // Elementos del modal de contraseña
    const modalPassword = document.getElementById('modalCambiarPassword');
    const formPassword = document.getElementById('formCambiarPassword');
    const btnGuardarPassword = document.getElementById('btnGuardarPassword');
    const passwordError = document.getElementById('passwordError');
    const passwordErrorMessage = document.getElementById('passwordErrorMessage');
    
    // Manejar el envío del formulario principal
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Mostrar loader
            btnSubmit.style.display = 'none';
            loaderBtn.style.display = 'inline-block';
            
            // Recopilar datos del formulario
            const formData = new FormData(form);
            
            // Enviar petición AJAX
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    Swal.fire({
                        title: '¡Usuario actualizado!',
                        text: data.message || 'El usuario se ha actualizado correctamente',
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#198754'
                    }).then(() => {
                        // Redirigir a la lista de usuarios
                        window.location.href = 'usuarios.php';
                    });
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Mostrar mensaje de error
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo actualizar el usuario: ' + error.message,
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#dc3545'
                });
                
                // Restaurar botón
                btnSubmit.style.display = 'inline-block';
                loaderBtn.style.display = 'none';
            });
        });
    }
    
    // Validación en tiempo real para campos requeridos
    const camposRequeridos = ['usuario', 'nombre_usuario', 'apellido_usuario', 'estado_usuario', 'sucursal_usuario', 'privilegio_usuario'];
    
    camposRequeridos.forEach(campo => {
        const elemento = document.getElementById(campo);
        if (elemento) {
            elemento.addEventListener('blur', function() {
                validarCampo(this);
            });
            
            elemento.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    this.classList.remove('is-invalid');
                    const feedback = this.parentNode.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.remove();
                    }
                }
            });
        }
    });

    const emailCampo = document.getElementById('email');
    if (emailCampo) {
        emailCampo.addEventListener('blur', function() {
            validarCampo(this);
        });

        emailCampo.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');
                const feedback = this.parentNode.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.remove();
                }
            }
        });
    }
    
    // Función para validar campos
    function validarCampo(campo) {
        const valor = campo.value.trim();
        const esRequerido = campo.hasAttribute('required');
        
        if (esRequerido && !valor) {
            campo.classList.add('is-invalid');
            
            // Crear mensaje de error
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = 'Este campo es obligatorio';
            
            campo.parentNode.appendChild(feedback);
            return false;
        }
        
        // Validación específica para email
        if (campo.type === 'email' && valor) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(valor)) {
                campo.classList.add('is-invalid');
                mostrarMensajeError(campo, 'Formato de email no válido');
                return false;
            }
        }
        
        return true;
    }
    
    // Función para mostrar mensaje de error
    function mostrarMensajeError(campo, mensaje) {
        // Remover mensaje anterior si existe
        const feedbackAnterior = campo.parentNode.querySelector('.invalid-feedback');
        if (feedbackAnterior) {
            feedbackAnterior.remove();
        }
        
        // Crear nuevo mensaje de error
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = mensaje;
        
        campo.parentNode.appendChild(feedback);
    }
    
    // Validar todo el formulario antes de enviar
    function validarFormulario() {
        let esValido = true;
        
        // Validar campos requeridos
        camposRequeridos.forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento && !validarCampo(elemento)) {
                esValido = false;
            }
        });

        const emailElemento = document.getElementById('email');
        if (emailElemento && !validarCampo(emailElemento)) {
            esValido = false;
        }
        
        return esValido;
    }
    
    // Agregar validación al formulario
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validarFormulario()) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    // ===== MANEJO DEL MODAL DE CONTRASEÑA =====
    
    // Manejar el envío del formulario de contraseña
    if (formPassword) {
        formPassword.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Obtener valores de los campos
            const nuevaPasswordValue = document.getElementById('nueva_password_modal').value.trim();
            const confirmarPasswordValue = document.getElementById('confirmar_password_modal').value.trim();
            const idUsuario = document.getElementById('id_usuario_modal').value;
            
            // Validar contraseñas
            if (!validarContraseñasModal(nuevaPasswordValue, confirmarPasswordValue)) {
                return false;
            }
            
            // Mostrar loader en el botón
            btnGuardarPassword.disabled = true;
            btnGuardarPassword.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Procesando...';
            
            // Crear FormData para enviar al servidor
            const formDataPassword = new FormData();
            formDataPassword.append('id_usuario', idUsuario);
            formDataPassword.append('nueva_password', nuevaPasswordValue);
            formDataPassword.append('confirmar_password', confirmarPasswordValue);
            
            // Enviar petición AJAX al archivo de procesar contraseña
            fetch('parts/usuarios/editar/procesar_contrasena.php', {
                method: 'POST',
                body: formDataPassword
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    Swal.fire({
                        title: '¡Contraseña actualizada!',
                        text: data.message || 'La contraseña se ha actualizado correctamente',
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#198754'
                    }).then(() => {
                        // Cerrar modal
                        const modal = bootstrap.Modal.getInstance(modalPassword);
                        if (modal) {
                            modal.hide();
                        }
                        
                        // Limpiar campos del modal
                        document.getElementById('nueva_password_modal').value = '';
                        document.getElementById('confirmar_password_modal').value = '';
                        
                        // Ocultar errores
                        passwordError.style.display = 'none';
                        
                        // Mostrar indicador visual de éxito
                        mostrarIndicadorContraseñaExito();
                    });
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            })
            .catch(error => {
                console.error('Error al cambiar contraseña:', error);
                
                // Mostrar mensaje de error
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo cambiar la contraseña: ' + error.message,
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#dc3545'
                });
            })
            .finally(() => {
                // Restaurar botón
                btnGuardarPassword.disabled = false;
                btnGuardarPassword.innerHTML = '<i class="icon-base ri ri-check-line me-2"></i>Guardar Contraseña';
            });
        });
    }
    
    // Función para validar contraseñas en el modal
    function validarContraseñasModal(nueva, confirmar) {
        // Ocultar errores anteriores
        passwordError.style.display = 'none';
        
        // Verificar que ambos campos estén llenos
        if (!nueva || !confirmar) {
            mostrarErrorPassword('Debe completar ambos campos de contraseña');
            return false;
        }
        
        // Verificar que coincidan
        if (nueva !== confirmar) {
            mostrarErrorPassword('Las contraseñas no coinciden');
            return false;
        }
        
        // Validar longitud mínima
        if (nueva.length < 6) {
            mostrarErrorPassword('La contraseña debe tener al menos 6 caracteres');
            return false;
        }
        
        return true;
    }
    
    // Función para mostrar errores de contraseña
    function mostrarErrorPassword(mensaje) {
        passwordErrorMessage.textContent = mensaje;
        passwordError.style.display = 'block';
    }
    
    // Función para mostrar indicador de contraseña actualizada exitosamente
    function mostrarIndicadorContraseñaExito() {
        // Crear o actualizar indicador visual
        let indicador = document.getElementById('indicadorContraseña');
        
        if (!indicador) {
            indicador = document.createElement('div');
            indicador.id = 'indicadorContraseña';
        }
        
        indicador.className = 'alert alert-success alert-sm mt-2';
        indicador.innerHTML = '<i class="icon-base ri ri-check-line me-2"></i><strong>Contraseña actualizada:</strong> Se ha cambiado correctamente en la base de datos';
        
        // Insertar después del botón de cambiar contraseña si no existe
        if (!indicador.parentNode) {
            const btnCambiarPassword = document.getElementById('btnCambiarPassword');
            if (btnCambiarPassword) {
                btnCambiarPassword.parentNode.appendChild(indicador);
            }
        }
        
        // Remover el indicador después de 5 segundos
        setTimeout(() => {
            if (indicador && indicador.parentNode) {
                indicador.remove();
            }
        }, 5000);
    }
    
    // Limpiar campos cuando se cierre el modal
    if (modalPassword) {
        modalPassword.addEventListener('hidden.bs.modal', function() {
            // Limpiar campos
            document.getElementById('nueva_password_modal').value = '';
            document.getElementById('confirmar_password_modal').value = '';
            
            // Ocultar errores
            passwordError.style.display = 'none';
        });
    }
});
</script>
