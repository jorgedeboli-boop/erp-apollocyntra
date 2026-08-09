<!-- CSS CUSTOM cliente  -->
<style>
/* Estilos para el modal de subir foto */
#modalSubirFoto .form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

#modalSubirFoto .btn-primary:disabled {
    background-color: #6c757d;
    border-color: #6c757d;
}

/* Responsive para dispositivos móviles */
@media (max-width: 768px) {
    #modalSubirFoto .modal-dialog {
        margin: 1rem;
    }
}

/* Animaciones para las imágenes */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

</style>
