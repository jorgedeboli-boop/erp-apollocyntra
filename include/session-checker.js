/**
 * Script para verificar el estado de la sesión
 * Redirige al login cuando la sesión caduca
 */

class SessionChecker {
    constructor() {
        this.checkInterval = 30000; // Verificar cada 30 segundos
        this.warningTime = 30 * 60 * 1000; // Mostrar advertencia 30 minutos antes
        this.sessionLifetime = 8 * 60 * 60 * 1000; // 8 horas en milisegundos
        this.lastActivity = Date.now();
        this.warningShown = false;
        this.checkTimer = null;
        this.abortController = null;
        this.isUnloading = false;
        this.isChecking = false;

        this.init();
    }

    init() {
        this.startPeriodicCheck();
        this.detectUserActivity();
        this.bindPageLifecycle();
        this.checkSessionStatus();
    }

    bindPageLifecycle() {
        const markUnloading = () => {
            this.isUnloading = true;
            this.abortPendingRequest();
            if (this.checkTimer) {
                clearInterval(this.checkTimer);
                this.checkTimer = null;
            }
        };

        window.addEventListener('beforeunload', markUnloading);
        window.addEventListener('pagehide', markUnloading);
    }

    abortPendingRequest() {
        if (this.abortController) {
            this.abortController.abort();
            this.abortController = null;
        }
    }

    isTransientFetchError(error) {
        if (!error) {
            return true;
        }
        if (error.name === 'AbortError') {
            return true;
        }
        if (error instanceof TypeError) {
            return true;
        }
        return false;
    }
    
    startPeriodicCheck() {
        this.checkTimer = setInterval(() => {
            this.checkSessionStatus();
        }, this.checkInterval);
    }
    
    detectUserActivity() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                this.updateLastActivity();
            }, true);
        });
    }
    
    updateLastActivity() {
        this.lastActivity = Date.now();
        this.warningShown = false;
    }
    
    async checkSessionStatus() {
        if (this.isUnloading || this.isChecking) {
            return;
        }

        this.isChecking = true;
        this.abortPendingRequest();
        this.abortController = new AbortController();

        try {
            const response = await fetch('include/check_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                signal: this.abortController.signal,
                body: JSON.stringify({
                    last_activity: this.lastActivity,
                    current_time: Date.now()
                })
            });

            if (this.isUnloading) {
                return;
            }

            if (response.status === 401 || response.status === 403) {
                this.handleSessionExpired();
                return;
            }

            if (!response.ok) {
                console.warn('Verificación de sesión omitida: respuesta', response.status);
                return;
            }

            const data = await response.json();

            if (this.isUnloading) {
                return;
            }

            if (data.session_expired) {
                this.handleSessionExpired();
            } else if (
                !this.warningShown &&
                (data.warning_needed || data.time_remaining <= this.warningTime)
            ) {
                this.showSessionWarning(data.time_remaining);
            }

        } catch (error) {
            if (this.isUnloading || this.isTransientFetchError(error)) {
                return;
            }
            console.error('Error al verificar sesión:', error);
        } finally {
            this.isChecking = false;
            if (this.abortController && !this.isUnloading) {
                this.abortController = null;
            }
        }
    }
    
    showSessionWarning(timeRemaining) {
        this.warningShown = true;
        console.error('showSessionWarning disparada');
        
        const minutes = Math.floor(timeRemaining / 60000);
        const seconds = Math.floor((timeRemaining % 60000) / 1000);
        
        // Usar SweetAlert2 si está disponible, sino alert nativo
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Sesión por expirar',
                html: `Tu sesión expirará en <strong>${minutes}:${seconds.toString().padStart(2, '0')}</strong><br><br>¿Deseas mantener la sesión activa?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Mantener sesión',
                cancelButtonText: 'Cerrar sesión',
                timer: timeRemaining,
                timerProgressBar: true,
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    this.extendSession();
                } else {
                    this.handleSessionExpired();
                }
            });
        } else {
            const extend = confirm(`Tu sesión expirará en ${minutes}:${seconds.toString().padStart(2, '0')}. ¿Deseas mantener la sesión activa?`);
            if (extend) {
                this.extendSession();
            } else {
                this.handleSessionExpired();
            }
        }
    }
    
    async extendSession() {
        try {
            const response = await fetch('include/extend_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.updateLastActivity();
                    this.warningShown = false;
                    
                    // Mostrar confirmación
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Sesión extendida',
                            text: 'Tu sesión ha sido extendida exitosamente',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                }
            }
        } catch (error) {
            console.error('Error al extender sesión:', error);
        }
    }
    
    handleSessionExpired() {
        if (this.isUnloading) {
            return;
        }

        // Limpiar timer
        if (this.checkTimer) {
            clearInterval(this.checkTimer);
            this.checkTimer = null;
        }

        this.abortPendingRequest();
        // Mostrar mensaje de sesión expirada
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Sesión expirada test 2222',
                text: 'Tu sesión ha expirado. Serás redirigido al login.',
                icon: 'error',
                showConfirmButton: false,
                timer: 3000
            }).then(() => {
                this.redirectToLogin();
            });
        } else {
            alert('Tu sesión ha expirado. Serás redirigido al login.');
            this.redirectToLogin();
        }
    }
    
    redirectToLogin() {
        // Limpiar cualquier dato de sesión local
        localStorage.removeItem('session_warning_shown');
        sessionStorage.clear();
        
        // Redirigir al login
        window.location.href = 'login.php';
    }
    
    destroy() {
        this.isUnloading = true;
        this.abortPendingRequest();
        if (this.checkTimer) {
            clearInterval(this.checkTimer);
            this.checkTimer = null;
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    // Solo inicializar si no estamos en la página de login
    if (!window.location.pathname.includes('login.php')) {
        window.sessionChecker = new SessionChecker();
    }
});
