/**
 * CONFIGURACIÓN COMPLETA DE IDIOMA ESPAÑOL PARA DATATABLES
 * Archivo universal para todos los módulos del sistema
 */

// Configuración del idioma español para DataTables
const DATATABLES_SPANISH = {
  "processing": "Procesando...",
  "lengthMenu": "Mostrar _MENU_ registros",
  "zeroRecords": "No se encontraron resultados",
  "emptyTable": "Ningún dato disponible en esta tabla",
  "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
  "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
  "infoFiltered": "(filtrado de un total de _MAX_ registros)",
  "search": "Buscar:",
  "loadingRecords": "Cargando...",
  "paginate": {
    "first": "«",
    "last": "»", 
    "next": "›",
    "previous": "‹"
  },
  "aria": {
    "sortAscending": ": Activar para ordenar la columna de manera ascendente",
    "sortDescending": ": Activar para ordenar la columna de manera descendente"
  },
  "buttons": {
    "copy": "Copiar",
    "colvis": "Visibilidad",
    "collection": "Colección",
    "colvisRestore": "Restaurar visibilidad",
    "copyKeys": "Presione ctrl o u2318 + C para copiar los datos de la tabla al portapapeles del sistema. <br /> <br /> Para cancelar, haga clic en este mensaje o presione escape.",
    "copySuccess": {
      "1": "Copiada 1 fila al portapapeles",
      "_": "Copiadas %ds filas al portapapeles"
    },
    "copyTitle": "Copiar al portapapeles",
    "csv": "CSV",
    "excel": "Excel",
    "pageLength": {
      "-1": "Mostrar todas las filas",
      "_": "Mostrar %d filas"
    },
    "pdf": "PDF",
    "print": "Imprimir",
    "renameState": "Cambiar nombre",
    "updateState": "Actualizar",
    "createState": "Crear Estado",
    "removeAllStates": "Remover Estados",
    "removeState": "Remover",
    "savedStates": "Estados Guardados",
    "stateRestore": "Estado %d"
  },
  "searchBuilder": {
    "add": "Añadir condición",
    "button": {
      "0": "Constructor de búsqueda",
      "_": "Constructor de búsqueda (%d)"
    },
    "clearAll": "Borrar todo",
    "condition": "Condición",
    "clearAllConditions": "Borrar todas las condiciones",
    "data": "Datos",
    "leftTitle": "Criterios anidados a la izquierda",
    "logicAnd": "Y",
    "logicOr": "O",
    "rightTitle": "Criterios anidados a la derecha",
    "title": {
      "_": "Constructor de búsqueda (%d)"
    },
    "value": "Valor",
    "conditions": {
      "date": {
        "after": "Después",
        "before": "Antes",
        "between": "Entre",
        "empty": "Vacío",
        "equals": "Igual a",
        "not": "No",
        "notBetween": "No entre",
        "notEmpty": "No vacío"
      },
      "number": {
        "between": "Entre",
        "empty": "Vacío",
        "equals": "Igual a",
        "gt": "Mayor que",
        "gte": "Mayor o igual que",
        "lt": "Menor que",
        "lte": "Menor o igual que",
        "not": "No",
        "notBetween": "No entre",
        "notEmpty": "No vacío"
      },
      "string": {
        "contains": "Contiene",
        "empty": "Vacío",
        "endsWith": "Termina en",
        "equals": "Igual a",
        "not": "No",
        "notContains": "No contiene",
        "notEmpty": "No vacío",
        "startsWith": "Empieza con"
      }
    }
  }
};

// Exportar la configuración para uso en otros archivos
if (typeof module !== 'undefined' && module.exports) {
  module.exports = DATATABLES_SPANISH;
} else {
  // Para uso en navegador
  window.DATATABLES_SPANISH = DATATABLES_SPANISH;
}
