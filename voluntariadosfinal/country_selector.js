// country_selector.js - VERSIÓN COMPLETA CON VALIDACIÓN INICIAL
document.addEventListener('DOMContentLoaded', function() {
    initializeCountrySelectors();
    initializePhoneValidation();
    applyInitialValidation(); // Aplicar validación inicial para Uruguay
});

// FUNCIÓN PARA APLICAR VALIDACIÓN INICIAL
function applyInitialValidation() {
    const phoneInput = document.getElementById('telefono');
    const countryCodeInput = document.getElementById('countryCode');
    
    if (phoneInput && countryCodeInput) {
        // Aplicar validación de Uruguay por defecto
        updatePhoneValidation(phoneInput, '+598');
        
        // También aplicar formato si hay algún valor
        if (phoneInput.value) {
            formatPhoneNumber(phoneInput);
        }
    }
}

function initializeCountrySelectors() {
    const selectors = document.querySelectorAll('.country-selector');
    const overlay = createOverlay();
    
    selectors.forEach(selector => {
        const selected = selector.querySelector('.selected-country');
        const dropdown = selector.querySelector('.country-dropdown');
        const phoneInput = document.getElementById('telefono');
        const countryCodeInput = document.getElementById('countryCode');
        
        // APLICAR VALIDACIÓN INICIAL AL CARGAR
        if (countryCodeInput && countryCodeInput.value === '+598') {
            updatePhoneValidation(phoneInput, '+598');
        }
        
        // Abrir dropdown
        selected.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Cerrar otros dropdowns
            closeAllDropdowns();
            
            // Abrir este dropdown
            selector.classList.add('active');
            overlay.style.display = 'block';
            
            // En móviles, centrar el dropdown
            if (window.innerWidth <= 768) {
                centerDropdownMobile(dropdown);
            }
        });
        
        // Seleccionar país
        dropdown.querySelectorAll('.country-option').forEach(option => {
            option.addEventListener('click', function(e) {
                e.stopPropagation();
                
                const code = this.getAttribute('data-code');
                const flag = this.getAttribute('data-flag');
                
                // Actualizar selector visual
                selected.querySelector('.flag').textContent = flag;
                selected.querySelector('.country-code').textContent = code;
                
                // Actualizar campo hidden
                if (countryCodeInput) {
                    countryCodeInput.value = code;
                }
                
                // Actualizar placeholder y validación del teléfono
                updatePhoneValidation(phoneInput, code);
                
                // Cerrar dropdown
                closeAllDropdowns();
                
                // Enfocar el input de teléfono
                if (phoneInput) {
                    phoneInput.focus();
                }
            });
        });
    });
    
    // Cerrar al hacer clic fuera
    document.addEventListener('click', closeAllDropdowns);
    overlay.addEventListener('click', closeAllDropdowns);
    
    // Cerrar con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllDropdowns();
        }
    });
}

function createOverlay() {
    let overlay = document.querySelector('.dropdown-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'dropdown-overlay';
        document.body.appendChild(overlay);
    }
    return overlay;
}

function centerDropdownMobile(dropdown) {
    // El CSS ya se encarga del centrado en móviles
}

function closeAllDropdowns() {
    document.querySelectorAll('.country-selector').forEach(selector => {
        selector.classList.remove('active');
    });
    
    const overlay = document.querySelector('.dropdown-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

function updatePhoneValidation(phoneInput, countryCode) {
    if (!phoneInput) return;
    
    const validations = {
        '+598': { // Uruguay
            placeholder: '91 234 567',
            pattern: '[2-9][0-9] [0-9]{3} [0-9]{3}', // Formato con espacios: 99 453 333
            maxLength: 10, // 8 dígitos + 2 espacios
            title: 'Para Uruguay: 8 dígitos con formato 99 453 333'
        },
        '+54': { // Argentina
            placeholder: '11 1234 5678',
            pattern: '[0-9]{10,11}',
            maxLength: 12,
            title: 'Para Argentina: 10-11 dígitos'
        },
        '+55': { // Brasil
            placeholder: '11 91234 5678',
            pattern: '[0-9]{10,11}',
            maxLength: 12,
            title: 'Para Brasil: 10-11 dígitos'
        },
        '+56': { // Chile
            placeholder: '9 1234 5678',
            pattern: '[0-9]{8,9}',
            maxLength: 11,
            title: 'Para Chile: 8-9 dígitos'
        },
        '+595': { // Paraguay
            placeholder: '981 123456',
            pattern: '[0-9]{8,9}',
            maxLength: 11,
            title: 'Para Paraguay: 8-9 dígitos'
        },
        '+51': { // Perú
            placeholder: '912 345 678',
            pattern: '[0-9]{9}',
            maxLength: 11,
            title: 'Para Perú: 9 dígitos'
        },
        '+57': { // Colombia
            placeholder: '300 123 4567',
            pattern: '[0-9]{10}',
            maxLength: 12,
            title: 'Para Colombia: 10 dígitos'
        }
    };
    
    const validation = validations[countryCode] || {
        placeholder: 'Número telefónico',
        pattern: '[0-9]{8,12}',
        maxLength: 15,
        title: 'Número telefónico'
    };
    
    // Aplicar validaciones
    phoneInput.placeholder = validation.placeholder;
    phoneInput.pattern = validation.pattern;
    phoneInput.maxLength = validation.maxLength;
    phoneInput.title = validation.title;
    
    // Limpiar y reformatear el valor actual
    if (phoneInput.value) {
        formatPhoneNumber(phoneInput);
    }
}

function initializePhoneValidation() {
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    
    phoneInputs.forEach(input => {
        // Formateo en tiempo real
        input.addEventListener('input', function() {
            formatPhoneNumber(this);
            validatePhoneLength(this);
        });
        
        // Validación antes de enviar
        input.addEventListener('blur', function() {
            validatePhoneNumber(this);
        });
    });
    
    // Validar antes de enviar formularios
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const phoneInput = this.querySelector('input[type="tel"]');
            if (phoneInput && !validatePhoneNumber(phoneInput)) {
                e.preventDefault();
            }
        });
    });
}

function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    const countryCode = document.getElementById('countryCode')?.value || '+598';
    
    // Limitar longitud según el país
    const maxDigits = {
        '+598': 8,  // Uruguay: 8 dígitos máximo
        '+54': 11,  // Argentina
        '+55': 11,  // Brasil
        '+56': 9,   // Chile
        '+595': 9,  // Paraguay
        '+51': 9,   // Perú
        '+57': 10   // Colombia
    };
    
    const max = maxDigits[countryCode] || 12;
    if (value.length > max) {
        value = value.substring(0, max);
    }
    
    // Aplicar formato según el país
    let formattedValue = value;
    switch(countryCode) {
        case '+598': // Uruguay - Formato correcto: 99 453 333 (2-3-3)
            if (value.length <= 2) {
                formattedValue = value;
            } else if (value.length <= 5) {
                formattedValue = value.substring(0, 2) + ' ' + value.substring(2, 5);
            } else {
                formattedValue = value.substring(0, 2) + ' ' + value.substring(2, 5) + ' ' + value.substring(5, 8);
            }
            break;
        case '+54': // Argentina - Formato: 11 1234 5678
            if (value.length > 2) formattedValue = value.substring(0, 2) + ' ' + value.substring(2);
            if (value.length > 6) formattedValue = formattedValue.substring(0, 6) + ' ' + formattedValue.substring(6);
            break;
        case '+55': // Brasil - Formato: 11 91234 5678
            if (value.length > 2) formattedValue = value.substring(0, 2) + ' ' + value.substring(2);
            if (value.length > 7) formattedValue = formattedValue.substring(0, 7) + ' ' + formattedValue.substring(7);
            break;
        default:
            // Formato genérico: XXX XXX XXXX
            if (value.length > 3) formattedValue = value.substring(0, 3) + ' ' + value.substring(3);
            if (value.length > 7) formattedValue = formattedValue.substring(0, 7) + ' ' + formattedValue.substring(7);
    }
    
    input.value = formattedValue;
    
    // Validar automáticamente después de formatear
    validatePhoneNumber(input);
}

function validatePhoneLength(input) {
    const countryCode = document.getElementById('countryCode')?.value || '+598';
    const rawValue = input.value.replace(/\D/g, '');
    
    const maxDigits = {
        '+598': 8,  // Uruguay: 8 dígitos máximo
        '+54': 11,
        '+55': 11,
        '+56': 9,
        '+595': 9,
        '+51': 9,
        '+57': 10
    };
    
    const max = maxDigits[countryCode] || 12;
    
    if (rawValue.length > max) {
        input.style.borderColor = '#f44336';
        showPhoneError(input, `Máximo ${max} dígitos para ${countryCode}`);
    } else {
        input.style.borderColor = '';
        hidePhoneError(input);
    }
}

function validatePhoneNumber(input) {
    const countryCode = document.getElementById('countryCode')?.value || '+598';
    const rawValue = input.value.replace(/\D/g, '');
    
    const validations = {
        '+598': (num) => { // Uruguay: 8 dígitos, empezando con 2-9
            if (num.length !== 8) return {valid: false, message: 'Debe tener 8 dígitos (ej: 91 234 567)'};
            if (!/^[2-9]/.test(num)) return {valid: false, message: 'Debe empezar con 2-9'};
            return {valid: true};
        },
        '+54': (num) => ({ // Argentina
            valid: num.length >= 10 && num.length <= 11,
            message: 'Debe tener 10-11 dígitos'
        }),
        '+55': (num) => ({ // Brasil
            valid: num.length >= 10 && num.length <= 11,
            message: 'Debe tener 10-11 dígitos'
        }),
        '+56': (num) => ({ // Chile
            valid: num.length >= 8 && num.length <= 9,
            message: 'Debe tener 8-9 dígitos'
        }),
        '+595': (num) => ({ // Paraguay
            valid: num.length >= 8 && num.length <= 9,
            message: 'Debe tener 8-9 dígitos'
        }),
        '+51': (num) => ({ // Perú
            valid: num.length === 9,
            message: 'Debe tener 9 dígitos'
        }),
        '+57': (num) => ({ // Colombia
            valid: num.length === 10,
            message: 'Debe tener 10 dígitos'
        })
    };
    
    const validator = validations[countryCode] || ((num) => ({
        valid: num.length >= 8 && num.length <= 15,
        message: 'Debe tener 8-15 dígitos'
    }));
    
    const result = validator(rawValue);
    
    if (!result.valid) {
        input.style.borderColor = '#f44336';
        showPhoneError(input, result.message);
        return false;
    }
    
    input.style.borderColor = '#4caf50'; // Verde cuando es válido
    hidePhoneError(input);
    return true;
}

function showPhoneError(input, message) {
    hidePhoneError(input);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'phone-error-message';
    errorDiv.style.color = '#f44336';
    errorDiv.style.fontSize = '0.8rem';
    errorDiv.style.marginTop = '5px';
    errorDiv.textContent = message;
    
    input.parentNode.appendChild(errorDiv);
}

function hidePhoneError(input) {
    const existingError = input.parentNode.querySelector('.phone-error-message');
    if (existingError) {
        existingError.remove();
    }
}