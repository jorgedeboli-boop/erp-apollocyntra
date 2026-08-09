<script>
document.addEventListener('DOMContentLoaded', function () {
  setTimeout(function () {
    $('#pais').select2({
      dropdownParent: $('#pais').parent(),
      placeholder: 'Seleccionar país',
      allowClear: true,
      ajax: {
        url: 'parts/universal/ajax_poblaciones.php',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            action: 'paises',
            search: params.term || '',
            page: params.page || 1
          };
        },
        processResults: function (data) {
          return {
            results: data.results || [],
            pagination: data.pagination || { more: false }
          };
        }
      }
    });

    $('#c_provincia').select2({
      dropdownParent: $('#c_provincia').parent(),
      placeholder: 'Seleccionar provincia',
      allowClear: true,
      ajax: {
        url: 'parts/universal/ajax_poblaciones.php',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            action: 'provincias',
            search: params.term || '',
            page: params.page || 1,
            idpais: $('#pais').val()
          };
        },
        processResults: function (data) {
          return {
            results: data.results || [],
            pagination: data.pagination || { more: false }
          };
        }
      }
    });

    $('#c_poblacion').select2({
      dropdownParent: $('#c_poblacion').parent(),
      placeholder: 'Seleccionar población',
      allowClear: true,
      ajax: {
        url: 'parts/universal/ajax_poblaciones.php',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            action: 'poblaciones',
            search: params.term || '',
            page: params.page || 1,
            idprovincia: $('#c_provincia').val()
          };
        },
        processResults: function (data) {
          return {
            results: data.results || [],
            pagination: data.pagination || { more: false }
          };
        }
      }
    });

    $('#pais').on('change', function () {
      $('#c_provincia').val('').trigger('change');
      $('#c_poblacion').val('').trigger('change');
      $('#codigo_postal').val('');
    });

    $('#c_provincia').on('change', function () {
      $('#c_poblacion').val('').trigger('change');
      $('#codigo_postal').val('');
    });

    $('#c_poblacion').on('change', function () {
      var idPoblacion = $(this).val();
      if (!idPoblacion) {
        $('#codigo_postal').val('');
        return;
      }

      $.ajax({
        url: 'parts/universal/ajax_poblaciones.php',
        dataType: 'json',
        data: {
          action: 'poblacion_detalle',
          idpoblacion: idPoblacion
        },
        success: function (response) {
          if (!response.success) {
            return;
          }
          var data = response.data;
          $('#codigo_postal').val(data.codigo_postal || '');

          if (!$('#c_provincia').val() && data.idprovincia) {
            var optProv = new Option(data.provincia, data.idprovincia, true, true);
            $('#c_provincia').append(optProv).trigger('change');
          }
          if (!$('#pais').val() && data.id_rel_country) {
            var optPais = new Option(data.pais, data.id_rel_country, true, true);
            $('#pais').append(optPais).trigger('change');
          }
        }
      });
    });
  }, 100);
});
</script>
