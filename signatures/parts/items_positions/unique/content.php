<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <h4 class="mb-1">Gestión de Posiciones del Menú</h4>
            <p class="mb-4">
                Arrastra y suelta los items para cambiar su posición en el menú. Solo se muestran los items que están visibles en el menú.
            </p>
        </div>
    </div>
    
    <!-- Contenedor principal de drag & drop -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri ri-drag-drop-line me-2"></i>
                        Items del Menú - Ordenar por Posición
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Indicador de carga -->
                    <div id="loading-positions" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando posiciones...</span>
                        </div>
                        <p class="mt-2">Cargando items del menú...</p>
                    </div>
                    
                    <!-- Lista de items ordenables -->
                    <div id="sortable-items" class="row" style="display: none;">
                        <!-- Los items se cargarán dinámicamente aquí -->
                    </div>
                    
                    <!-- Mensaje cuando no hay items -->
                    <div id="no-items-message" class="text-center py-4" style="display: none;">
                        <div class="alert alert-info">
                            <i class="ri ri-information-line me-2"></i>
                            No hay items visibles en el menú para ordenar.
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- Toast container para notificaciones -->
<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>
<!-- / Content -->