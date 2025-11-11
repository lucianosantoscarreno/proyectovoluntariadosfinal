// voluntariados.js

// Modal de inscripción - VERSIÓN CON HORARIOS
function abrirModalInscripcion(voluntariadoId, voluntariadoTitulo) {
    document.getElementById('voluntariadoId').value = voluntariadoId;
    document.getElementById('voluntariadoTitulo').value = voluntariadoTitulo;
    
    const modal = document.getElementById('modalInscripcion');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Cargar horarios disponibles
    cargarHorariosDisponibles(voluntariadoId);
}

function cargarHorariosDisponibles(voluntariadoId) {
    const horariosContainer = document.getElementById('horariosContainer');
    const selectHorarios = document.getElementById('horario_seleccionado');
    const btnInscribirse = document.getElementById('btnInscribirse');
    
    // Mostrar loading
    horariosContainer.style.display = 'block';
    selectHorarios.innerHTML = '<option value="">⏳ Cargando horarios...</option>';
    btnInscribirse.innerHTML = '⏳ Cargando...';
    btnInscribirse.disabled = true;
    
    fetch('obtener_horarios_inscripcion.php?voluntariado_id=' + voluntariadoId)
        .then(response => response.json())
        .then(data => {
            if (data.horarios && data.horarios.length > 0) {
                selectHorarios.innerHTML = '<option value="">-- Seleccioná un horario --</option>';
                
                data.horarios.forEach(horario => {
                    const option = document.createElement('option');
                    option.value = horario.valor;
                    option.textContent = horario.texto;
                    selectHorarios.appendChild(option);
                });
                
                btnInscribirse.innerHTML = '✅ Confirmar Inscripción';
                btnInscribirse.disabled = false;
            } else {
                selectHorarios.innerHTML = '<option value="">❌ No hay horarios disponibles</option>';
                btnInscribirse.innerHTML = '❌ Sin horarios';
                btnInscribirse.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            selectHorarios.innerHTML = '<option value="">❌ Error al cargar horarios</option>';
            btnInscribirse.innerHTML = '❌ Error';
            btnInscribirse.disabled = true;
        });
}

function cerrarModalInscripcion() {
    const modal = document.getElementById('modalInscripcion');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Resetear formulario
    const form = document.getElementById('formInscripcion');
    if (form) form.reset();
    
    // Ocultar horarios
    document.getElementById('horariosContainer').style.display = 'none';
}

// Modal de experiencias
function abrirModalExperiencias(voluntariadoId, voluntariadoTitulo) {
    document.getElementById('modalExperienciasTitulo').textContent = '💬 Experiencias: ' + voluntariadoTitulo;
    
    // Mostrar loading
    document.getElementById('experienciasContainer').innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <div class="spinner"></div>
            <p style="margin-top: 15px; color: #666;">Cargando experiencias...</p>
        </div>
    `;
    
    // Cargar experiencias via AJAX
    fetch('obtener_experiencias.php?id=' + voluntariadoId)
        .then(response => response.json())
        .then(data => {
            mostrarExperienciasEnModal(data);
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('experienciasContainer').innerHTML = `
                <div style="text-align: center; padding: 40px; color: #f44336;">
                    <p>❌ Error al cargar las experiencias</p>
                </div>
            `;
        });
    
    document.getElementById('modalExperiencias').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function cerrarModalExperiencias() {
    document.getElementById('modalExperiencias').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function mostrarExperienciasEnModal(data) {
    const { experiencias, stats, puede_comentar, voluntariado_id } = data;
    const promedio = Math.round(stats.promedio * 10) / 10 || 0;
    const total_experiencias = stats.total || 0;
    
    // Actualizar estadísticas
    document.getElementById('modalStatsContent').innerHTML = `
        <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
            ${total_experiencias > 0 ? `
            <div class="rating-display">
                <div class="stars">
                    ${generarEstrellas(promedio)}
                </div>
                <span class="rating-text">${promedio} / 5</span>
            </div>
            ` : ''}
            
            <div class="experiencias-count">
                <span>💬</span>
                ${total_experiencias} Experiencia${total_experiencias !== 1 ? 's' : ''}
            </div>
        </div>
    `;
    
    // Mostrar experiencias
    if (experiencias.length > 0) {
        let experienciasHTML = '';
        experiencias.forEach(exp => {
            experienciasHTML += `
                <div class="experiencia-card">
                    <div class="experiencia-header">
                        <div class="experiencia-user">
                            <div class="user-avatar">
                                ${exp.nombre_voluntario.charAt(0).toUpperCase()}
                            </div>
                            <div class="user-info">
                                <h4>${escapeHtml(exp.nombre_voluntario)}</h4>
                                <span class="fecha">${formatearFecha(exp.fecha_creacion)}</span>
                            </div>
                        </div>
                        <div class="experiencia-rating">
                            ${generarEstrellas(exp.calificacion, true)}
                        </div>
                    </div>
                    <p class="experiencia-comentario">${escapeHtml(exp.comentario).replace(/\n/g, '<br>')}</p>
                </div>
            `;
        });
        document.getElementById('experienciasContainer').innerHTML = experienciasHTML;
    } else {
        document.getElementById('experienciasContainer').innerHTML = `
            <div class="no-experiencias">
                <i>💬</i>
                <h4 style="color: #666; margin-bottom: 10px;">Aún no hay experiencias</h4>
                <p style="color: #888;">Sé el primero en compartir tu experiencia en este voluntariado</p>
            </div>
        `;
    }
    
    // Mostrar formulario si puede comentar
    if (puede_comentar) {
        document.getElementById('experienciaFormContainer').innerHTML = `
            <div class="experiencia-form">
                <h4 style="color: var(--color-primary); margin-bottom: 15px;">Comparte tu experiencia</h4>
                <form id="formExperiencia" onsubmit="enviarExperiencia(event, ${voluntariado_id})">
                    <div class="rating-input">
                        <label style="font-weight: 600; color: #333;">¿Cómo calificarías esta experiencia?</label>
                        <div class="stars-input">
                            <input type="radio" id="star5-modal" name="calificacion" value="5" checked>
                            <label for="star5-modal">★</label>
                            <input type="radio" id="star4-modal" name="calificacion" value="4">
                            <label for="star4-modal">★</label>
                            <input type="radio" id="star3-modal" name="calificacion" value="3">
                            <label for="star3-modal">★</label>
                            <input type="radio" id="star2-modal" name="calificacion" value="2">
                            <label for="star2-modal">★</label>
                            <input type="radio" id="star1-modal" name="calificacion" value="1">
                            <label for="star1-modal">★</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <textarea name="comentario" rows="4" placeholder="Cuéntanos sobre tu experiencia: ¿Qué aprendiste? ¿Cómo fue el ambiente? ¿Recomendarías este voluntariado?" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; resize: vertical;"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-experiencia">
                        📝 Compartir Mi Experiencia
                    </button>
                </form>
            </div>
        `;
    } else {
        document.getElementById('experienciaFormContainer').innerHTML = '';
    }
}

// Funciones utilitarias
function generarEstrellas(promedio, exactas = false) {
    let estrellas = '';
    if (exactas) {
        for (let i = 1; i <= 5; i++) {
            estrellas += i <= promedio ? '★' : '☆';
        }
    } else {
        const estrellasLlenas = Math.floor(promedio);
        const mediaEstrella = promedio - estrellasLlenas >= 0.5;
        for (let i = 1; i <= 5; i++) {
            if (i <= estrellasLlenas) {
                estrellas += '★';
            } else if (mediaEstrella && i === estrellasLlenas + 1) {
                estrellas += '★';
            } else {
                estrellas += '☆';
            }
        }
    }
    return estrellas;
}

function formatearFecha(fecha) {
    return new Date(fecha).toLocaleDateString('es-ES');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function enviarExperiencia(event, voluntariadoId) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('voluntariado_id', voluntariadoId);
    formData.append('agregar_experiencia', 'true');
    
    fetch('voluntariados.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        // Recargar el modal con las nuevas experiencias
        const titulo = document.getElementById('modalExperienciasTitulo').textContent.replace('💬 Experiencias: ', '');
        abrirModalExperiencias(voluntariadoId, titulo);
        
        // Mostrar mensaje de éxito
        alert('✅ ¡Gracias por compartir tu experiencia!');
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error al compartir tu experiencia');
    });
}

// Event listeners cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar modal de inscripción al hacer clic fuera
    const modalInscripcion = document.getElementById('modalInscripcion');
    if (modalInscripcion) {
        modalInscripcion.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalInscripcion();
            }
        });
        
        // Prevenir que el clic en el contenido cierre el modal
        const modalContent = modalInscripcion.querySelector('.modal-content-compact');
        if (modalContent) {
            modalContent.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    }
    
    // Cerrar modal de experiencias al hacer clic fuera
    const modalExperiencias = document.getElementById('modalExperiencias');
    if (modalExperiencias) {
        modalExperiencias.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalExperiencias();
            }
        });
    }
    
    // Cerrar con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalExperiencias();
            cerrarModalInscripcion();
        }
    });
    
    // Validación del formulario de inscripción
    const formInscripcion = document.getElementById('formInscripcion');
    if (formInscripcion) {
        formInscripcion.addEventListener('submit', function(e) {
            const departamento = document.getElementById('departamento').value;
            const horarioSeleccionado = document.getElementById('horario_seleccionado').value;
            const motivacion = document.getElementById('motivacion').value;
            
            if (!departamento || !horarioSeleccionado || !motivacion.trim()) {
                e.preventDefault();
                alert('Por favor, completa todos los campos obligatorios.');
                return;
            }
            
            // Mostrar loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '⏳ Enviando...';
            submitBtn.disabled = true;
            
            // Restaurar botón después de 5 segundos por si hay error
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });
    }
    
    // Mejorar experiencia de usuario en el selector de horarios
    const horarioSelect = document.getElementById('horario_seleccionado');
    if (horarioSelect) {
        horarioSelect.addEventListener('change', function() {
            if (this.value) {
                this.style.borderColor = '#2e7d32';
                this.style.background = '#f8fff8';
            } else {
                this.style.borderColor = '';
                this.style.background = '';
            }
        });
    }
});

// Función auxiliar para debug
function debugHorarios(voluntariadoId) {
    console.log('Debug: Cargando horarios para voluntariado:', voluntariadoId);
    fetch('obtener_horarios_inscripcion.php?voluntariado_id=' + voluntariadoId)
        .then(response => response.json())
        .then(data => console.log('Horarios recibidos:', data))
        .catch(error => console.error('Error debug:', error));
}