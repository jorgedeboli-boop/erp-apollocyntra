<!-- JAVASCRIPT CUSTOM items_positions - unique  -->

<!-- SortableJS para drag & drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Sortable === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js';
        document.head.appendChild(script);
    }
});
</script>

<!-- Script personalizado para gestión de posiciones -->
<?php
$vLoadPositions = filemtime(__DIR__ . '/load_positions.js');
?>
<script src="parts/items_positions/unique/load_positions.js?v=<?php echo $vLoadPositions; ?>"></script>