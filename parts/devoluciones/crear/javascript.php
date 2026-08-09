<!-- JAVASCRIPT CUSTOM crear_devolucion - crear  -->
<script>
(function() {
  var timeoutBusqueda = null;
  var inputSku = document.getElementById('buscar_sku');
  var resultadosDiv = document.getElementById('resultados_sku');
  var idArticuloInput = document.getElementById('id_articulo');
  var panelSeleccionado = document.getElementById('articulo_seleccionado');
  var skuText = document.getElementById('articulo_sku_text');
  var descText = document.getElementById('articulo_descripcion_text');
  var btnQuitar = document.getElementById('quitar_articulo');

  function mostrarResultados(items) {
    if (!resultadosDiv) return;
    resultadosDiv.innerHTML = '';
    if (!items || items.length === 0) {
      resultadosDiv.innerHTML = '<div class="list-group-item text-muted">No se encontraron artículos vendidos.</div>';
    } else {
      items.forEach(function(item) {
        var a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action';
        a.innerHTML = '<strong>SKU ' + item.sku + '</strong> – ' + (item.descripcion || '').substring(0, 80) + (item.descripcion && item.descripcion.length > 80 ? '…' : '');
        a.addEventListener('click', function(e) {
          e.preventDefault();
          seleccionarArticulo(item);
        });
        resultadosDiv.appendChild(a);
      });
    }
    resultadosDiv.style.display = 'block';
  }

  function seleccionarArticulo(item) {
    if (idArticuloInput) idArticuloInput.value = item.id;
    if (inputSku) inputSku.value = item.sku;
    if (skuText) skuText.textContent = 'SKU ' + item.sku;
    if (descText) descText.textContent = item.descripcion || '';
    if (panelSeleccionado) panelSeleccionado.style.display = 'block';
    if (resultadosDiv) resultadosDiv.style.display = 'none';
  }

  function buscar() {
    var q = inputSku ? inputSku.value.trim() : '';
    if (q.length < 3) {
      if (resultadosDiv) { resultadosDiv.style.display = 'none'; resultadosDiv.innerHTML = ''; }
      return;
    }
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'parts/devoluciones/crear/buscar_articulos_vendidos.php?q=' + encodeURIComponent(q), true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
        try {
          var res = JSON.parse(xhr.responseText);
          if (res.success && res.data) mostrarResultados(res.data);
          else mostrarResultados([]);
        } catch (e) { mostrarResultados([]); }
      }
    };
    xhr.send();
  }

  if (inputSku) {
    inputSku.addEventListener('input', function() {
      clearTimeout(timeoutBusqueda);
      timeoutBusqueda = setTimeout(buscar, 400);
    });
    inputSku.addEventListener('focus', function() {
      if (inputSku.value.trim().length >= 3) buscar();
    });
  }

  document.addEventListener('click', function(e) {
    if (resultadosDiv && !inputSku.contains(e.target) && !resultadosDiv.contains(e.target)) {
      resultadosDiv.style.display = 'none';
    }
  });

  if (btnQuitar) {
    btnQuitar.addEventListener('click', function() {
      if (idArticuloInput) idArticuloInput.value = '';
      if (inputSku) inputSku.value = '';
      if (panelSeleccionado) panelSeleccionado.style.display = 'none';
      if (resultadosDiv) resultadosDiv.style.display = 'none';
    });
  }

  var form = document.getElementById('form_crear_devolucion');
  if (form && form.getAttribute('data-ajax-submit') === '1') {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      if (!idArticuloInput || !idArticuloInput.value) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'warning', title: 'Atención', text: 'Seleccione un artículo vendido.' });
        } else { alert('Seleccione un artículo vendido.'); }
        return;
      }
      var btn = document.getElementById('btn_generar_devolucion');
      if (btn) btn.disabled = true;
      var fd = new FormData(form);
      var xhr = new XMLHttpRequest();
      xhr.open('POST', form.action, true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
          if (btn) btn.disabled = false;
          try {
            var res = JSON.parse(xhr.responseText);
            if (res.success && res.redirect) {
              if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Devolución creada', text: res.message || '' }).then(function() {
                  window.location.href = res.redirect;
                });
              } else {
                window.location.href = res.redirect;
              }
            } else {
              if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Error al crear la devolución.' });
              else alert(res.message || 'Error al crear la devolución.');
            }
          } catch (err) {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: 'Error al procesar la respuesta.' });
            else alert('Error al procesar la respuesta.');
          }
        }
      };
      xhr.send(fd);
    });
  }
})();
</script>
