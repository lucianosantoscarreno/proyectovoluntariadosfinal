// scripts.js - Funcionalidades JavaScript para el sistema de voluntariados

document.addEventListener('DOMContentLoaded', function() {
    // Mejorar la experiencia de usuario en formularios
    mejorarFormularios();
    
    // Agregar confirmaciones para acciones importantes
    agregarConfirmaciones();
    
    // Mejorar la accesibilidad
    mejorarAccesibilidad();
});

function mejorarFormularios() {
    // Validación en tiempo real para teléfonos
    const telefonoInputs = document.querySelectorAll('input[type="tel"]');
    telefonoInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validarTelefono(this);
        });
    });
    
    // Mostrar/ocultar contraseñas
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '👁️‍🗨️';
        });
    });
}

function validarTelefono(input) {
    const telefono = input.value;
    const regex = /^\+598[0-9]{8}$/;
    
    if (telefono && !regex.test(telefono)) {
        input.style.borderColor = 'var(--color-danger)';
        mostrarError(input, 'El formato debe ser +598 seguido de 8 dígitos');
    } else {
        input.style.borderColor = 'var(--color-success)';
        limpiarError(input);
    }
}

function mostrarError(input, mensaje) {
    limpiarError(input);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error';
    errorDiv.textContent = mensaje;
    errorDiv.style.color = 'var(--color-danger)';
    errorDiv.style.fontSize = 'var(--text-sm)';
    errorDiv.style.marginTop = '5px';
    
    input.parentNode.appendChild(errorDiv);
}

function limpiarError(input) {
    const errorDiv = input.parentNode.querySelector('.error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function agregarConfirmaciones() {
    // Confirmación para eliminaciones
    const formsEliminar = document.querySelectorAll('form[onsubmit]');
    formsEliminar.forEach(form => {
        const oldOnSubmit = form.onsubmit;
        form.onsubmit = function(e) {
            if (!confirm('¿Estás seguro de que deseas realizar esta acción?')) {
                e.preventDefault();
                return false;
            }
            return oldOnSubmit ? oldOnSubmit.call(this, e) : true;
        };
    });
}

function mejorarAccesibilidad() {
    // Agregar labels a los botones que no los tengan
    const buttons = document.querySelectorAll('button:not([aria-label])');
    buttons.forEach(button => {
        if (!button.textContent.trim() && button.innerHTML.includes('emoji')) {
            const emoji = button.innerHTML.match(/[^\w\s]/g);
            if (emoji) {
                button.setAttribute('aria-label', button.textContent || 'Botón');
            }
        }
    });
    
    // Mejorar navegación por teclado
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            // Cerrar modales con ESC
            const modals = document.querySelectorAll('.modal-overlay');
            modals.forEach(modal => {
                if (modal.style.display === 'flex') {
                    modal.style.display = 'none';
                }
            });
        }
    });
}

// Funciones para manejar estados de carga
function mostrarLoading(button) {
    button.disabled = true;
    button.innerHTML = '<span class="spinner"></span> Procesando...';
}

function ocultarLoading(button, textoOriginal) {
    button.disabled = false;
    button.textContent = textoOriginal;
}

// Utilidad para formatear fechas
function formatearFecha(fecha) {
    const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(fecha).toLocaleDateString('es-ES', opciones);
}

// Exportar funciones para uso global
window.mostrarLoading = mostrarLoading;
window.ocultarLoading = ocultarLoading;
window.formatearFecha = formatearFecha;