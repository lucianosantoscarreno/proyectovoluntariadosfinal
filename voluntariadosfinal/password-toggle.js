// password-toggle.js
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar toggles de contraseña
    const toggleButtons = document.querySelectorAll('.toggle-password');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
                this.setAttribute('aria-label', 'Ocultar contraseña');
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
                this.setAttribute('aria-label', 'Mostrar contraseña');
            }
        });
    });
    
    // Mostrar el toggle en campos de contraseña vacíos también
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        // Asegurar que el toggle sea visible incluso cuando el campo está vacío
        const wrapper = input.closest('.password-input-wrapper');
        if (wrapper) {
            const toggle = wrapper.querySelector('.toggle-password');
            if (toggle) {
                toggle.style.display = 'flex';
            }
        }
    });
});