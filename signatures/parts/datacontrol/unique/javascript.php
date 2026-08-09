<!-- JAVASCRIPT CUSTOM datacontrol - unique  -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const statusEl = document.getElementById('datacontrolStatus');
  const outEl = document.getElementById('datacontrolOutput');
  const btnLotes = document.getElementById('btnControlArticulosLotes');
  const btnVenta = document.getElementById('btnControlArticulosVenta');
  const btnCtlLotes = document.getElementById('btnControlLotes');

  function setBusy(busy, label) {
    [btnLotes, btnVenta, btnCtlLotes].forEach(function (b) {
      if (b) b.disabled = !!busy;
    });
    if (statusEl) {
      statusEl.className = 'small ' + (busy ? 'text-primary' : 'text-muted');
      statusEl.textContent = busy ? (label || 'Trabajando…') : '';
    }
  }

  function showOutput(obj) {
    if (outEl) {
      outEl.textContent = typeof obj === 'string' ? obj : JSON.stringify(obj, null, 2);
    }
  }

  async function postJson(url, formData) {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      body: formData || null
    });
    const text = await res.text();
    const trimmed = (text || '').trim();
    if (trimmed.startsWith('<!DOCTYPE') || trimmed.startsWith('<html')) {
      throw new Error(
        'El servidor devolvió HTML (suele ser timeout 504 del proxy). ' +
        'Prueba de nuevo: el control ahora va sucursal por sucursal. Si persiste, reduce carga en el servidor o sube los timeouts de nginx/php-fpm.'
      );
    }
    let json;
    try {
      json = JSON.parse(text);
    } catch (e) {
      throw new Error(text || 'Respuesta no JSON');
    }
    return json;
  }

  async function getJson(url) {
    const res = await fetch(url, { method: 'GET', headers: { 'Accept': 'application/json' } });
    const text = await res.text();
    const trimmed = (text || '').trim();
    if (trimmed.startsWith('<!DOCTYPE') || trimmed.startsWith('<html')) {
      throw new Error('El servidor devolvió HTML en lugar de JSON (¿504 timeout?).');
    }
    return JSON.parse(text);
  }

  btnLotes?.addEventListener('click', async function () {
    if (!confirm('¿Ejecutar control artículos → articulos_lotes?\n\n' +
      '1) Reset (TRUNCATE control_articulos_tablas y articulos_control_cache).\n' +
      '2) COUNT por sucursal y registro en control_articulos_tablas.\n' +
      '3) Poblar articulos_control_cache (faltantes) por sucursal.\n' +
      '4) INSERT en articulos_lotes leyendo cache + articulos_sucursal.')) return;
    setBusy(true, 'Reset…');
    showOutput('…');
    const acumulado = { success: true, conteos: [], informe: [] };
    try {
      const lista = await getJson('parts/datacontrol/unique/ajax_control_articulos_lotes.php?action=sucursales');
      if (!lista.success || !Array.isArray(lista.sucursales)) {
        throw new Error(lista.message || 'No se pudo listar sucursales');
      }

      // Reset
      const fdR = new FormData();
      fdR.append('fase', 'reset');
      await postJson('parts/datacontrol/unique/ajax_control_articulos_lotes.php', fdR);

      const nSuc = lista.sucursales.length;
      for (let si = 0; si < nSuc; si++) {
        const sid = lista.sucursales[si];
        setBusy(true, 'Fase 1: conteo sucursal ' + sid + ' (' + (si + 1) + '/' + nSuc + ')…');
        const fdC = new FormData();
        fdC.append('fase', 'conteo_sucursal');
        fdC.append('solo_sucursal', String(sid));
        const rc = await postJson('parts/datacontrol/unique/ajax_control_articulos_lotes.php', fdC);
        if (!rc.success || !rc.conteo) {
          throw new Error(rc.message || 'Conteo sucursal ' + sid + ' fallido');
        }
        acumulado.conteos.push(rc.conteo);
        showOutput(acumulado);
      }

      // Poblar cache (solo sucursales con faltantes)
      const conFaltantes = acumulado.conteos.filter(function (c) {
        return !c.skipped && !c.error && Number(c.cantidad_noexisten || 0) > 0;
      });
      const totalCache = conFaltantes.length;
      for (let ci = 0; ci < totalCache; ci++) {
        const c = conFaltantes[ci];
        setBusy(true, 'Fase 2: cache sucursal ' + c.sucursal + ' (' + (ci + 1) + '/' + totalCache + ')…');
        const fdCache = new FormData();
        fdCache.append('fase', 'cache_sucursal');
        fdCache.append('solo_sucursal', String(c.sucursal));
        const cr = await postJson('parts/datacontrol/unique/ajax_control_articulos_lotes.php', fdCache);
        if (!cr.success) {
          throw new Error(cr.message || 'Cache sucursal ' + c.sucursal + ' fallido');
        }
        showOutput(acumulado);
      }

      const aSincronizar = acumulado.conteos.filter(function (c) {
        return c.id_control_articulos && !c.skipped && !c.error && Number(c.cantidad_noexisten || 0) > 0;
      });
      const total = aSincronizar.length;

      for (let i = 0; i < total; i++) {
        const c = aSincronizar[i];
        setBusy(true, 'Fase 3 (INSERT desde cache): sucursal ' + c.sucursal + ' (' + (i + 1) + '/' + total + ')…');
        const fd = new FormData();
        fd.append('solo_sucursal', String(c.sucursal));
        fd.append('id_control_articulos', String(c.id_control_articulos));
        const j = await postJson('parts/datacontrol/unique/ajax_control_articulos_lotes.php', fd);
        if (j.informe && j.informe[0]) {
          acumulado.informe.push(j.informe[0]);
        }
        showOutput(acumulado);
      }

      showOutput(acumulado);
    } catch (e) {
      acumulado.error = String(e.message || e);
      showOutput(acumulado);
    } finally {
      setBusy(false);
    }
  });

  btnVenta?.addEventListener('click', async function () {
    setBusy(true, 'Control venta…');
    showOutput('…');
    try {
      const json = await postJson('parts/datacontrol/unique/ajax_control_articulos_venta.php');
      showOutput(json);
    } catch (e) {
      showOutput({ error: String(e.message || e) });
    } finally {
      setBusy(false);
    }
  });

  btnCtlLotes?.addEventListener('click', async function () {
    setBusy(true, 'Control lotes…');
    showOutput('…');
    try {
      const json = await postJson('parts/datacontrol/unique/ajax_control_lotes.php');
      showOutput(json);
    } catch (e) {
      showOutput({ error: String(e.message || e) });
    } finally {
      setBusy(false);
    }
  });
});
</script>
