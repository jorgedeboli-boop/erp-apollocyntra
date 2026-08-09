/**
 * Script para funcionalidad de búsqueda de items
 */

// Variable global para almacenar todos los items
let todosLosItems = [];

/**
 * Función para inicializar el buscador
 */
function inicializarBuscador() {
    const searchInput = document.getElementById('searchItems');
    const clearButton = document.getElementById('clearSearch');
    const searchResults = document.getElementById('searchResults');
    
    if (!searchInput) return;
    
    // Evento de búsqueda en tiempo real
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.trim().toLowerCase();
        
        if (searchTerm.length > 0) {
            clearButton.style.display = 'block';
            filtrarItems(searchTerm);
        } else {
            clearButton.style.display = 'none';
            mostrarTodosLosItems();
        }
    });
    
    // Evento para limpiar búsqueda
    clearButton.addEventListener('click', function() {
        searchInput.value = '';
        clearButton.style.display = 'none';
        mostrarTodosLosItems();
        searchInput.focus();
    });
    
    // Evento para búsqueda con Escape
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            clearButton.style.display = 'none';
            mostrarTodosLosItems();
        }
    });
}

/**
 * Función para filtrar items según el término de búsqueda
 */
function filtrarItems(searchTerm) {
    const container = document.getElementById('items-container');
    const searchResults = document.getElementById('searchResults');
    
    if (!container || todosLosItems.length === 0) return;
    
    // Filtrar items que coincidan con el término de búsqueda
    const itemsFiltrados = todosLosItems.filter(item => {
        const nombre = (item.itemnameText || '').toLowerCase();
        const itemName = (item.itemName || '').toLowerCase();
        const tipo = (item.typ_item || '').toLowerCase();
        const url = (item.url_item || '').toLowerCase();
        const icono = (item.icon_menu || '').toLowerCase();
        
        return nombre.includes(searchTerm) || 
               itemName.includes(searchTerm) || 
               tipo.includes(searchTerm) || 
               url.includes(searchTerm) || 
               icono.includes(searchTerm);
    });
    
    // Renderizar items filtrados
    renderizarItemsFiltrados(itemsFiltrados);
    
    // Actualizar contador de resultados
    if (searchResults) {
        if (itemsFiltrados.length === 0) {
            searchResults.textContent = 'No se encontraron items';
        } else if (itemsFiltrados.length === todosLosItems.length) {
            searchResults.textContent = 'Mostrando todos los items';
        } else {
            searchResults.textContent = `${itemsFiltrados.length} de ${todosLosItems.length} items`;
        }
    }
}

/**
 * Función para mostrar todos los items
 */
function mostrarTodosLosItems() {
    const searchResults = document.getElementById('searchResults');
    
    // Renderizar todos los items
    renderizarItemsFiltrados(todosLosItems);
    
    // Actualizar contador
    if (searchResults) {
        searchResults.textContent = 'Mostrando todos los items';
    }
}

/**
 * Función para renderizar items filtrados
 */
function renderizarItemsFiltrados(items) {
    const container = document.getElementById('items-container');
    
    if (!container) {
        return;
    }
    
    // Limpiar contenedor
    container.innerHTML = '';
    
    if (items.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="ri ri-information-line me-2"></i>
                    No hay items que coincidan con la búsqueda
                </div>
            </div>
        `;
        return;
    }
    
    // Crear cards para cada item
    items.forEach(item => {
        const card = crearCardItem(item);
        container.appendChild(card);
    });
}



/**
 * Función para actualizar la lista de items (llamada desde load_items.js)
 */
function actualizarListaItems(items) {
    todosLosItems = items;
}
