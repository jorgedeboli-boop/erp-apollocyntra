<!-- JAVASCRIPT CUSTOM clientes_test - crear -->
<?php
$modulo_cliente_form_modo = 'crear';
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

    function restaurarBotonCrear() {
        var btn = document.getElementById('btnCrearClienteTest');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="icon-base ri ri-check-line me-2"></i>Crear Cliente';
        }
    }

    function crearClienteTest(formData) {
        fetch('parts/modulo_cliente_form/unique/procesar_cliente.php', {
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
                    title: '¡Cliente Creado!',
                    text: data.message || 'El cliente se ha creado exitosamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#198754',
                    timer: 3000,
                    timerProgressBar: true
                }).then(function () {
                    window.location.href = data.redirect;
                });
            } else {
                throw new Error(data.error || 'Error desconocido al crear el cliente');
            }
        })
        .catch(function (error) {
            Swal.fire({
                title: 'Error',
                text: 'No se pudo crear el cliente: ' + error.message,
                icon: 'error',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#dc3545'
            });
            restaurarBotonCrear();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('formCrearClienteTest');
        if (!form) {
            return;
        }

        if (typeof $ !== 'undefined' && $('#sucursal_cliente').length) {
            $('#sucursal_cliente').select2({
                dropdownParent: $('#sucursal_cliente').parent(),
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
            var btn = document.getElementById('btnCrearClienteTest');
            if (!btn) {
                return;
            }
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creando cliente...';
            var formData = new FormData(form);
            formData.append('f_alta', new Date().toISOString().split('T')[0]);
            crearClienteTest(formData);
        });
    });
})();
</script>
