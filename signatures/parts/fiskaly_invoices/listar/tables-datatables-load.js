/**
 * Page Fiskaly Invoices List
 * Lista facturas reales de la API Fiskaly usando token de localStorage
 * y el id_client de la sucursal (?id=sucursal).
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const dt_table = document.querySelector('.datatables-fiskaly-invoices');
  window.dt_fiskaly_invoices = null;

  const idClient = (window.idClientFiskaly || '').toString().trim();
  const urlApi = (window.urlApiFiskaly || '').toString().trim();
  const idEmpresa = parseInt(window.idEmpresaFiskaly, 10) || 0;
  const alertaSesion = document.getElementById('alerta_fiskaly_sesion');

  function obtenerAccessToken() {
    return localStorage.getItem('fiskaly_access_token') || '';
  }

  function tokenExpirado() {
    const expiresAt = localStorage.getItem('fiskaly_expires_at');
    if (!expiresAt) {
      return false;
    }
    const expMs = Date.parse(expiresAt);
    if (Number.isNaN(expMs)) {
      return false;
    }
    return Date.now() >= expMs;
  }

  function mostrarSinSesion(msg) {
    if (alertaSesion) {
      alertaSesion.classList.remove('d-none');
      if (msg) {
        alertaSesion.innerHTML =
          '<i class="icon-base ri ri-lock-line me-2"></i>' +
          msg +
          (idEmpresa > 0
            ? ' <a href="fiskaly_manager.php?id=' + idEmpresa + '" class="alert-link">Ir a Fiskaly Manager</a>'
            : '');
      }
    }
  }

  if (!dt_table) {
    return;
  }

  if (!idClient || !urlApi) {
    return;
  }

  const accessToken = obtenerAccessToken();
  if (!accessToken || tokenExpirado()) {
    mostrarSinSesion(
      !accessToken
        ? 'No hay token Fiskaly en el navegador. Conéctate primero en Fiskaly Manager.'
        : 'La sesión Fiskaly ha caducado. Vuelve a autenticarte en Fiskaly Manager.'
    );
    return;
  }

  window.dt_fiskaly_invoices = new DataTable(dt_table, {
    processing: true,
    serverSide: false,
    deferRender: true,
    searchDelay: 300,
    language: DATATABLES_SPANISH,
    columns: [
      { data: 'id_invoice' },
      { data: 'client' },
      { data: 'tbai' },
      { data: 'url' },
      { data: 'issued_at' },
      { data: 'signer' },
      { data: 'state' },
      { data: 'cancellation' },
      { data: 'registration' },
      { data: 'registration_csv' },
      { data: 'code' },
      { data: 'description' },
      { data: null, orderable: false, searchable: false }
    ],
    columnDefs: [
      {
        targets: 0,
        render: function (data) {
          return data ? '<span class="fw-semibold">' + data + '</span>' : '-';
        }
      },
      {
        targets: 3,
        render: function (data) {
          if (!data || data === '-') {
            return '-';
          }
          return (
            '<a href="' +
            data +
            '" target="_blank" rel="noopener noreferrer" class="text-primary">' +
            '<i class="icon-base ri ri-external-link-line me-1"></i>Ver URL</a>'
          );
        }
      },
      {
        targets: 6,
        render: function (data) {
          const estado = data || '-';
          let cls = 'secondary';
          if (estado === 'ISSUED') cls = 'success';
          else if (estado === 'CANCELLED') cls = 'danger';
          else if (estado !== '-') cls = 'warning';
          return '<span class="badge bg-label-' + cls + ' rounded-pill">' + estado + '</span>';
        }
      },
      {
        targets: 7,
        render: function (data) {
          const estado = data || '-';
          let cls = 'secondary';
          if (estado === 'NOT_CANCELLED' || estado === 'STORED') cls = 'success';
          else if (estado === 'CANCELLED') cls = 'info';
          else if (estado === 'INVALID' || estado === 'REQUIRES_CORRECTION') cls = 'danger';
          else if (estado !== '-') cls = 'warning';
          return '<span class="badge bg-label-' + cls + ' rounded-pill">' + estado + '</span>';
        }
      },
      {
        targets: 8,
        render: function (data) {
          const estado = data || '-';
          let cls = 'secondary';
          if (estado === 'REGISTERED' || estado === 'STORED') cls = 'success';
          else if (estado === 'PENDING') cls = 'warning';
          else if (estado === 'INVALID' || estado === 'REQUIRES_CORRECTION') cls = 'danger';
          else if (estado !== '-') cls = 'info';
          return '<span class="badge bg-label-' + cls + ' rounded-pill">' + estado + '</span>';
        }
      },
      {
        targets: 12,
        orderable: false,
        searchable: false,
        render: function (data, type, full) {
          const url = full.url || '';
          if (!url || url === '-') {
            return '<span class="text-muted">—</span>';
          }
          return (
            '<a href="' +
            url +
            '" target="_blank" rel="noopener noreferrer" class="btn btn-icon btn-text-secondary rounded-pill" title="Abrir validación">' +
            '<i class="icon-base ri ri-eye-line icon-md"></i></a>'
          );
        }
      }
    ],
    ajax: function (data, callback) {
      const token = obtenerAccessToken();
      if (!token || tokenExpirado()) {
        mostrarSinSesion('Sesión Fiskaly no disponible o caducada.');
        callback({ data: [] });
        return;
      }

      const url = urlApi.replace(/\/?$/, '/') + 'clients/' + encodeURIComponent(idClient) + '/invoices';

      fetch(url, {
        method: 'GET',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          Authorization: 'Bearer ' + token
        }
      })
        .then(function (response) {
          if (response.status === 401 || response.status === 403) {
            throw new Error('Token Fiskaly no válido o sin permisos (HTTP ' + response.status + '). Reautentícate en Fiskaly Manager.');
          }
          if (!response.ok) {
            throw new Error('Error al consultar facturas Fiskaly (HTTP ' + response.status + ')');
          }
          return response.json();
        })
        .then(function (json) {
          const rows = [];
          const results = json && Array.isArray(json.results) ? json.results : [];
          results.forEach(function (item) {
            const content = item.content || {};
            const client = content.client || {};
            const compliance = content.compliance || {};
            const signer = content.signer || {};
            const transmission = content.transmission || {};
            const validations = Array.isArray(content.validations) ? content.validations : [];
            const firstValidation = validations.length > 0 ? validations[0] : {};

            rows.push({
              id_invoice: content.id || '-',
              client: client.id || idClient || '-',
              tbai: compliance.tbai || '-',
              url: compliance.url || '-',
              issued_at: content.issued_at || '-',
              signer: signer.id || '-',
              state: content.state || '-',
              cancellation: transmission.cancellation || '-',
              registration: transmission.registration || '-',
              registration_csv: transmission.registration_csv || '-',
              code: firstValidation.code || '-',
              description: firstValidation.description || '-'
            });
          });
          callback({ data: rows });
        })
        .catch(function (err) {
          console.error('Error listando facturas Fiskaly:', err);
          mostrarSinSesion(err.message || 'Error al cargar facturas Fiskaly.');
          callback({ data: [] });
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Error Fiskaly',
              text: err.message || 'No se pudieron cargar las facturas'
            });
          }
        });
    },
    order: [[4, 'desc']],
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100],
    responsive: {
      details: {
        display: $.fn.dataTable.Responsive.display.modal({
          header: function (row) {
            const d = row.data();
            return 'Invoice ' + (d.id_invoice || '');
          }
        }),
        renderer: $.fn.dataTable.Responsive.renderer.tableAll({
          tableClass: 'table'
        })
      }
    },
    dom:
      '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"f>>' +
      '<"row"<"col-sm-12"tr>>' +
      '<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
  });
});
