<script>
document.addEventListener('DOMContentLoaded', function () {
  $('.select2').each(function () {
    var $this = $(this);
    $this.select2({
      dropdownParent: $this.parent(),
      width: '100%',
      allowClear: true,
      placeholder: $this.find('option:first').text()
    });
  });

  function soloDigitos(valor) {
    return String(valor || '').replace(/\D/g, '');
  }

  function formatearNumeroTarjeta(digitos) {
    return digitos.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
  }

  function luhnValido(numero) {
    var digitos = soloDigitos(numero);
    if (digitos.length < 13 || digitos.length > 19) return false;
    var suma = 0;
    var alt = false;
    for (var i = digitos.length - 1; i >= 0; i--) {
      var n = parseInt(digitos.charAt(i), 10);
      if (alt) {
        n *= 2;
        if (n > 9) n -= 9;
      }
      suma += n;
      alt = !alt;
    }
    return suma % 10 === 0;
  }

  function cvvValido(cvv) {
    return /^\d{3,4}$/.test(soloDigitos(cvv));
  }

  function marcarValidez(input, ok) {
    if (!input) return;
    input.classList.toggle('is-invalid', !ok);
    input.classList.toggle('is-valid', ok);
  }

  var inputNumero = document.getElementById('numerotarjeta');
  var inputCvv = document.getElementById('cvv');
  var form = document.getElementById('formCrearTarjeta');

  if (inputNumero) {
    inputNumero.addEventListener('input', function () {
      var digitos = soloDigitos(this.value).slice(0, 19);
      this.value = formatearNumeroTarjeta(digitos);
      if (digitos.length >= 13) {
        marcarValidez(this, luhnValido(digitos));
      } else {
        this.classList.remove('is-valid', 'is-invalid');
      }
    });
  }

  if (inputCvv) {
    inputCvv.addEventListener('input', function () {
      this.value = soloDigitos(this.value).slice(0, 4);
      if (this.value.length >= 3) {
        marcarValidez(this, cvvValido(this.value));
      } else {
        this.classList.remove('is-valid', 'is-invalid');
      }
    });
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      var numero = soloDigitos(inputNumero ? inputNumero.value : '');
      var cvv = soloDigitos(inputCvv ? inputCvv.value : '');
      var nombre = (document.getElementById('nombre_tarjeta') || {}).value || '';
      var mes = (document.getElementById('mes_vencimiento') || {}).value || '';
      var ano = (document.getElementById('ano_vencimiento') || {}).value || '';
      var okNumero = luhnValido(numero);
      var okCvv = cvvValido(cvv);
      var okNombre = nombre.trim() !== '';
      var okMes = /^\d{2}$/.test(mes) && parseInt(mes, 10) >= 1 && parseInt(mes, 10) <= 12;
      var okAno = /^\d{4}$/.test(ano);

      marcarValidez(inputNumero, okNumero);
      marcarValidez(inputCvv, okCvv);

      if (!okNumero || !okCvv || !okNombre || !okMes || !okAno) {
        e.preventDefault();
        if (!okNumero && inputNumero) inputNumero.focus();
        else if (!okNombre) document.getElementById('nombre_tarjeta').focus();
        else if (!okMes) document.getElementById('mes_vencimiento').focus();
        else if (!okAno) document.getElementById('ano_vencimiento').focus();
        else if (!okCvv && inputCvv) inputCvv.focus();
        return false;
      }

      if (inputNumero) inputNumero.value = numero;
      if (inputCvv) inputCvv.value = cvv;
    });
  }

  function cargarSucursales(empresaId) {
    var select = document.getElementById('sucursal_tarjeta_id');
    if (!select) return;

    select.innerHTML = '<option value="">Sin sucursal (opcional)</option>';
    var $sel = $(select);
    if ($sel.data('select2')) {
      $sel.val('').trigger('change.select2');
    }

    if (!empresaId) return;

    var url = new URL('parts/gastos_fijos/crear/get_sucursales_por_empresa.php', window.location.origin);
    url.searchParams.set('empresa_id', empresaId);

    fetch(url.toString())
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) throw new Error(data.message || 'Error cargando sucursales');
        (data.sucursales || []).forEach(function (s) {
          var opt = document.createElement('option');
          opt.value = String(s.id_sucursal);
          opt.textContent = s.nombre_sucursal;
          select.appendChild(opt);
        });
        if ($sel.data('select2')) {
          $sel.trigger('change.select2');
        }
      })
      .catch(function (e) {
        console.error(e);
      });
  }

  $('#empresa_tarjeta_id').on('change', function () {
    cargarSucursales(this.value);
  });
});
</script>
