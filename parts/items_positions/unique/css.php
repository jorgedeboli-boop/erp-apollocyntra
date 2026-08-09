<!-- CSS CUSTOM items_positions - unique  -->

<style>
/* Estilos para drag & drop de posiciones */
.sortable-item {
    cursor: move;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.sortable-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-color: #0d6efd;
}

.drag-handle {
    cursor: grab;
    padding: 8px;
    border-radius: 4px;
    transition: background-color 0.2s ease;
}

.drag-handle:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.drag-handle:active {
    cursor: grabbing;
}

/* Estilos para SortableJS */
.sortable-ghost {
    opacity: 0.4;
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
}

.sortable-chosen {
    transform: rotate(2deg);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    cursor: grabbing !important;
}

.sortable-drag {
    opacity: 0.8;
    transform: rotate(5deg);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.sortable-fallback {
    display: block !important;
    opacity: 0.8;
    transform: rotate(5deg);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    background: white;
    border: 1px solid #dee2e6;
}

/* Animaciones suaves para las transiciones */
.sortable-item {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

/* Estilos para el indicador de posición */
.badge.bg-primary {
    font-weight: 600;
    min-width: 30px;
    text-align: center;
}

/* Efectos hover mejorados */
.sortable-item:hover .drag-handle i {
    color: #6c757d !important;
}

/* Estilos para el contenedor de items */
#sortable-items {
    min-height: 200px;
}

/* Estilos para el estado de carga */
#loading-positions {
    min-height: 200px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

/* Estilos específicos para el diseño horizontal */
.sortable-item .card-body {
    padding: 0.75rem 1rem;
}

.sortable-item h6 {
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 0;
}

.sortable-item small {
    font-size: 0.75rem;
    line-height: 1.2;
}

.sortable-item .badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .sortable-item {
        margin-bottom: 0.5rem;
    }
    
    .drag-handle {
        padding: 6px;
    }
    
    .sortable-item .row {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .sortable-item .col-auto {
        margin-bottom: 0.5rem;
    }
    
    .sortable-item .col {
        margin-bottom: 0.5rem;
    }
}

/* Estilos para el feedback visual durante el drag */
.sortable-item.dragging {
    opacity: 0.5;
    transform: scale(1.05);
}

/* Estilos para el área de drop */
#sortable-items.sortable-drag-over {
    background-color: rgba(13, 110, 253, 0.1);
    border: 2px dashed #0d6efd;
    border-radius: 8px;
}

/* Animación para la actualización de posiciones */
@keyframes positionUpdate {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.position-updated {
    animation: positionUpdate 0.3s ease;
}

/* Estilos para el toast container */
#toast-container {
    z-index: 9999;
}

/* Mejoras visuales para las cards */
.sortable-item .card {
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.sortable-item .card:hover {
    border-color: #0d6efd;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

/* Estilos para el handle de arrastre */
.drag-handle i {
    font-size: 1.2rem;
    transition: color 0.2s ease;
}

/* Estilos para el footer de la card */
.card-footer {
    background-color: rgba(0, 0, 0, 0.02);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

/* Estilos para los badges de estado */
.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.5rem;
}

/* Estilos para el contenido del item */
.item-content h6 {
    font-weight: 600;
    color: #495057;
}

.item-content small {
    font-size: 0.75rem;
    line-height: 1.4;
}
</style>