// Función para abrir el modal
function openModal(filePath, fileType) {
    const modal = document.getElementById('fileModal');
    const fileViewer = document.getElementById('fileViewer');
    
    if (fileType === 'pdf') {
        fileViewer.src = `https://docs.google.com/gview?url=${encodeURIComponent(window.location.origin + '/' + filePath)}&embedded=true`;
    } else if (fileType === 'image') {
        fileViewer.src = filePath;
    } else if (fileType === 'office') {
        fileViewer.src = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(window.location.origin + '/' + filePath)}`;
    }
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Función para cerrar el modal
function closeModal() {
    const modal = document.getElementById('fileModal');
    const fileViewer = document.getElementById('fileViewer');
    
    modal.style.display = 'none';
    fileViewer.src = '';
    document.body.style.overflow = 'auto';
}

// Función para hacer editable una celda
function makeEditable(cell) {
    const currentValue = cell.textContent;
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'editable-input';
    input.value = currentValue;
    
    cell.textContent = '';
    cell.appendChild(input);
    input.focus();
    
    input.addEventListener('blur', function() {
        cell.textContent = this.value;
    });
    
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            this.blur();
        }
    });
}

// Asignar eventos a las celdas editables
document.addEventListener('DOMContentLoaded', function() {
    const editableCells = document.querySelectorAll('.editable');
    
    editableCells.forEach(cell => {
        cell.addEventListener('click', function() {
            makeEditable(this);
        });
    });
    
    // Cerrar el modal al hacer clic fuera del contenido
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('fileModal');
        if (event.target === modal) {
            closeModal();
        }
    });
});

// Manejo del formulario de comentarios
document.addEventListener('DOMContentLoaded', function() {
    // [Mantén el código anterior de eventos para celdas editables y modal]
    
    // Manejar el envío del formulario de comentarios
    const formComentario = document.getElementById('formComentario');
    
    if (formComentario) {
        formComentario.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const nombre = document.getElementById('nombre').value;
            const email = document.getElementById('email').value;
            const comentario = document.getElementById('comentario').value;
            const copiarCorreo = document.getElementById('copiarCorreo').checked;
            
            // Aquí puedes agregar código para enviar el comentario a un servidor
            console.log('Comentario enviado:', { nombre, email, comentario, copiarCorreo });
            
            // Mostrar mensaje de éxito
            alert('¡Comentario enviado con éxito! Gracias por tu feedback.');
            
            // Limpiar el formulario
            formComentario.reset();
            
            // Aquí podrías agregar el comentario a la lista de comentarios
            // agregarComentarioALista(nombre, email, comentario);
        });
    }
});

// Función para agregar comentarios a la lista (ejemplo)
function agregarComentarioALista(nombre, email, comentario) {
    const listaComentarios = document.getElementById('listaComentarios')?.querySelector('.list-group');
    
    if (listaComentarios) {
        const nuevoComentario = document.createElement('div');
        nuevoComentario.className = 'list-group-item';
        nuevoComentario.innerHTML = `
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1">${nombre}</h5>
                <small>Justo ahora</small>
            </div>
            <p class="mb-1">${comentario}</p>
            <small>${email}</small>
        `;
        
        listaComentarios.prepend(nuevoComentario);
    }
}

// Funciones para el modal
function openModal2(filePath, fileType) {
    const modal = document.getElementById('fileModal');
    const fileViewer = document.getElementById('fileViewer');
    
   if (fileViewer) {
    if (fileType === 'pdf') {
        fileViewer.src = `https://docs.google.com/gview?url=${encodeURIComponent(window.location.origin + '/' + filePath)}&embedded=true`;
    } else if (fileType === 'image') {
        fileViewer.src = filePath;
    } else if (fileType === 'office') {
        fileViewer.src = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(window.location.origin + '/' + filePath)}`;
    } else {
        console.error('Tipo de archivo no soportado:', fileType);
    }
} else {
    console.error('Elemento fileViewer no encontrado');
}
}

// Enfocar el primer campo al abrir el modal
document.addEventListener('DOMContentLoaded', function() {
    const profileModal = document.getElementById('profileModal');
    
    // Enfocar automáticamente el primer campo al abrir el modal
    profileModal.addEventListener('shown.bs.modal', function() {
        document.getElementById('profileName').focus();
    });

    // Alternar entre modo lectura/edición
    const editBtn = document.getElementById('editProfileBtn');
    editBtn.addEventListener('click', function() {
        const inputs = document.querySelectorAll('#profileModal input[readonly]');
        const isEditing = inputs[0].hasAttribute('readonly');

        if (isEditing) {
            // Quitar readonly para editar
            inputs.forEach(input => input.removeAttribute('readonly'));
            editBtn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar Cambios';
            editBtn.classList.replace('btn-outline-primary', 'btn-primary');
        } else {
            // Volver a modo lectura
            inputs.forEach(input => input.setAttribute('readonly', true));
            editBtn.innerHTML = '<i class="fas fa-edit me-2"></i>Editar Perfil';
            editBtn.classList.replace('btn-primary', 'btn-outline-primary');
            
            // Aquí podrías agregar código para guardar los cambios en una base de datos
            console.log('Datos guardados:', {
                name: document.getElementById('profileName').value,
                email: document.getElementById('profileEmail').value,
                career: document.getElementById('profileCareer').value
            });
        }
    });
});
// Inicializar en el DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    setupProfileEdit();
    // ... resto de tu código de inicialización
});

// Manejar envío del formulario de subida
document.addEventListener('DOMContentLoaded', function() {
    const uploadModal = document.getElementById('uploadFileModal');
    
    if (uploadModal) {
        uploadModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const student = button.getAttribute('data-student');
            const filename = button.getAttribute('data-filename');
            
            document.getElementById('modalStudentName').value = student;
            document.getElementById('modalCurrentFile').value = filename;
        });

        document.getElementById('uploadFileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const newFile = document.getElementById('newFileInput').files[0];
            
            if (newFile) {
                // Cerrar el modal de subida
                bootstrap.Modal.getInstance(uploadModal).hide();
                
                // Mostrar mensaje personalizado en el modal de éxito
                document.getElementById('successMessage').textContent = 
                    `Archivo "${newFile.name}" subido exitosamente para ${document.getElementById('modalStudentName').value}`;
                
                // Mostrar modal de éxito
                const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
                
                // Aquí iría la lógica para actualizar la tabla/backend
                console.log('Nuevo archivo:', {
                    estudiante: document.getElementById('modalStudentName').value,
                    archivo: newFile.name
                });
                
                // Actualizar la fila correspondiente después de 500ms (para que se vea la animación)
                setTimeout(() => {
                    const row = e.target.closest('.modal').previousElementSibling;
                    if (row) {
                        row.querySelector('td[data-label="Documento"]').textContent = newFile.name;
                        row.querySelector('a[download]').setAttribute('href', 'documentos/' + newFile.name);
                    }
                }, 500);
            }
        });
    }
});// En tu scriptAsesor.js, asegúrate que el modal se muestre correctamente
document.addEventListener('DOMContentLoaded', function() {
  // Configuración para todos los modales
  var myModal = new bootstrap.Modal(document.getElementById('profileModal'), {
    backdrop: true, // Muestra el fondo
    keyboard: true, // Permite cerrar con ESC
    focus: true // Enfoca el primer elemento del modal
  });
  
  // Enfocar automáticamente el primer campo editable
  $('#profileModal').on('shown.bs.modal', function() {
    $('.modal-body input').first().focus();
  });
});

// Actualizar la fila correspondiente
const row = event.relatedTarget.closest('tr');
row.querySelector('td[data-label="Documento"]').textContent = newFile.name;
row.querySelector('a[download]').setAttribute('href', 'documentos/' + newFile.name);