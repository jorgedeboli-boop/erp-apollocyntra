<!-- JAVASCRIPT CUSTOM clientes_test - editar -->
<?php
$modulo_cliente_form_modo = 'editar';
require_once __DIR__ . '/../../modulo_cliente_form/unique/javascript.php';
?>
<script>
(function () {
    'use strict';

    function validarCampo(field) {
        if (!field) return true;
        var isValid = field.checkValidity();
        var label = field.parentElement.querySelector('label[for="' + field.id + '"]');
        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            if (label) {
                label.classList.remove('text-danger');
                label.classList.add('text-success');
            }
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
            if (label) {
                label.classList.remove('text-success');
                label.classList.add('text-danger');
            }
        }
        return isValid;
    }

    window.validarCampo = validarCampo;

    function restaurarBotonEditar() {
        var btn = document.getElementById('btnEditarClienteTest');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="icon-base ri ri-check-line me-2"></i>Actualizar Cliente';
        }
    }

    function actualizarClienteTest(formData) {
        fetch('parts/modulo_cliente_form/unique/procesar_editar_cliente.php', {
            method: 'POST',
            body: formData
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                Swal.fire({
                    title: '¡Cliente Actualizado!',
                    text: data.message || 'El cliente se ha actualizado exitosamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#198754',
                    timer: 3000,
                    timerProgressBar: true
                }).then(function () {
                    window.location.href = data.redirect;
                });
            } else {
                throw new Error(data.error || 'Error desconocido al actualizar el cliente');
            }
        })
        .catch(function (error) {
            Swal.fire({
                title: 'Error',
                text: 'No se pudo actualizar el cliente: ' + error.message,
                icon: 'error',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#dc3545'
            });
            restaurarBotonEditar();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('formEditarClienteTest');
        if (!form) {
            return;
        }

        if (typeof $ !== 'undefined' && $('#sucursal').length) {
            $('#sucursal').select2({
                dropdownParent: $('#sucursal').parent(),
                placeholder: 'Seleccionar...',
                allowClear: true
            });
        }

        form.addEventListener('input', function (event) {
            var field = event.target;
            if (field.hasAttribute('required')) {
                validarCampo(field);
            }
        });

        form.addEventListener('change', function (event) {
            var field = event.target;
            if (field.hasAttribute('required')) {
                validarCampo(field);
            }
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }
            var btn = document.getElementById('btnEditarClienteTest');
            if (!btn) {
                return;
            }
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Actualizando cliente...';
            actualizarClienteTest(new FormData(form));
        });
    });
})();
</script>
