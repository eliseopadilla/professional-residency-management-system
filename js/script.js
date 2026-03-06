document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('documentosForm');
    const btnEnviar = document.getElementById('button-enviar-archivos');
    const fileInputs = {
        kardex: document.getElementById('fileInput'),
        liberacion: document.getElementById('fileInput1')
    };
    const fileNameDisplays = {
        kardex: document.getElementById('fileName'),
        liberacion: document.getElementById('fileName1')
    };

    // Validar archivos al seleccionar (se mantiene igual)
    Object.entries(fileInputs).forEach(([key, input]) => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            const display = fileNameDisplays[key];
            
            if (file) {
                if (file.type !== 'application/pdf') {
                    Swal.fire('Error', 'Solo se permiten archivos PDF', 'error');
                    this.value = '';
                    display.textContent = '';
                    return;
                }
                
                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire('Error', 'El archivo no debe exceder 5MB', 'error');
                    this.value = '';
                    display.textContent = '';
                    return;
                }
                
                display.textContent = file.name;
            } else {
                display.textContent = '';
            }
        });
    });

    // Enviar formulario (versión mejorada)
    btnEnviar.addEventListener('click', async function(e) {
        e.preventDefault();
        
        if (!fileInputs.kardex.files[0] || !fileInputs.liberacion.files[0]) {
            Swal.fire('Error', 'Debes seleccionar ambos archivos', 'error');
            return;
        }

        const swalInstance = Swal.fire({
            title: 'Validando documentos',
            html: 'Por favor espera...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const formData = new FormData();
            formData.append('kardex', fileInputs.kardex.files[0]);
            formData.append('liberacion', fileInputs.liberacion.files[0]);

            const response = await fetch('documentos.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error en el servidor');
            }

            await Swal.fire({
                title: '¡Éxito!',
                html: `Documentos registrados para:<br><br>
                      <b>Alumno:</b> ${data.alumno.nombre}<br>
                      <b>N° Control:</b> ${data.alumno.id}<br>
                      <b>Carrera:</b> ${data.alumno.carrera}<br>
                      <b>Créditos:</b> ${data.alumno.creditos}`,
                icon: 'success',
                confirmButtonText: 'Continuar',
                allowOutsideClick: false
            });

            // Limpiar formulario
            form.reset();
            Object.values(fileNameDisplays).forEach(el => el.textContent = '');

            // Redirección forzada después de 1 segundo (si no hay redirect_url)
            setTimeout(() => {
                window.location.href = data.redirect_url || 'http://localhost/proy/test.php';
            }, 1000);

        } catch (error) {
            swalInstance.close();
            Swal.fire('Error', error.message, 'error');
            console.error('Error:', error);
        }
    });
});