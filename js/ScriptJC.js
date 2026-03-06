window.onload = function () {
    fetch('http://localhost:3000/alumnos')
        .then(response => response.json())
        .then(data => {
            const tabla = document.getElementById('tabla');
            data.forEach(alumno => {
                const fila = document.createElement('tr');
                fila.innerHTML = `
                    <td>${alumno.nombre}</td>
                    <td>${alumno.num_control}</td>
                    <td><input type="radio" name="dictamen_${alumno.num_control}" value="no_aprobado" ${alumno.no_aprobado == 1 ? 'checked' : ''}></td>
                    <td><input type="radio" name="dictamen_${alumno.num_control}" value="aprobado_con_modificaciones" ${alumno.aprobado_con_modificaciones == 1 ? 'checked' : ''}></td>
                    <td><input type="radio" name="dictamen_${alumno.num_control}" value="aprobado" ${alumno.aprobado == 1 ? 'checked' : ''}></td>
                `;
                tabla.appendChild(fila);
            });
        })
        .catch(error => {
            console.error('Error al cargar alumnos:', error);
        });

    mostrarComentarios(); // También mostramos los comentarios al cargar
};

// Confirmar dictámenes y enviarlos al backend
function enviarDictamenes() {
    const tabla = document.getElementById("tabla");
    const filas = tabla.getElementsByTagName("tr");
    const resultados = [];

    for (let fila of filas) {
        const celdas = fila.getElementsByTagName("td");
        const nombre = celdas[0]?.textContent.trim();
        const num_control = celdas[1]?.textContent.trim();
        const radios = fila.querySelectorAll("input[type='radio']");
        let seleccion = "";

        radios.forEach(radio => {
            if (radio.checked) {
                seleccion = radio.value;
            }
        });

        if (seleccion !== "") {
            resultados.push({ num_control, dictamen: seleccion });
        }
    }

    if (resultados.length === 0) {
        alert("Selecciona al menos un dictamen antes de confirmar.");
        return;
    }

    // Enviar cada dictamen al backend
    resultados.forEach(resultado => {
        fetch('http://localhost:3000/guardarResultados', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(resultado)
        })
        .then(response => {
            if (!response.ok) throw new Error('Error al guardar dictamen');
            return response.text();
        })
        .then(data => {
            console.log(`✅ Resultado guardado para ${resultado.num_control}: ${data}`);
        })
        .catch(error => {
            console.error("❌ Error al guardar:", error);
            alert("Hubo un error al guardar algunos dictámenes.");
        });
    });

    alert("✅ Dictámenes enviados correctamente.");
}


// Guardar comentario en localStorage
function guardarComentario() {
    const comentario = document.getElementById('comentario').value.trim();

    if (comentario === '') {
        alert('Por favor, escribe un comentario antes de enviarlo.');
        return;
    }

    let comentarios = JSON.parse(localStorage.getItem('comentarios')) || [];
    comentarios.push(comentario);
    localStorage.setItem('comentarios', JSON.stringify(comentarios));

    document.getElementById('comentario').value = '';
    mostrarComentarios();
}


// Mostrar comentarios guardados
function mostrarComentarios() {
    const comentarios = JSON.parse(localStorage.getItem('comentarios')) || [];
    const comentariosDiv = document.getElementById('comentarios');
    comentariosDiv.innerHTML = '';

    comentarios.forEach((comentario) => {
        const div = document.createElement('div');
        div.classList.add('comentario');
        div.textContent = comentario;
        comentariosDiv.appendChild(div);
    });
}
