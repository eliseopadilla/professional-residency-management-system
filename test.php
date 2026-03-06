<?php
$db_config = [
    'host' => 'YOUR_HOST',
    'user' => 'YOUR_DB_USER',
    'pass' => 'YOUR_DB_PASSWORD',
    'name' => 'YOUR_DB_NAME'
];

$conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);

$id_alumno = isset($_GET['id']) ? $_GET['id'] : 0;

$alumno_info = [];
$query_alumno = "SELECT nombre, carrera, creditos, nombreProyecto, status FROM alumnos WHERE id = ?";
$stmt = $conn->prepare($query_alumno);
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $alumno_info = $result->fetch_assoc();
}

$asesor_asignado = "--Por definir--";
$tiene_asesor = false; 

if (!empty($alumno_info['nombreProyecto'])) {
    $proyectos = explode(',', $alumno_info['nombreProyecto']);
    $asesores_encontrados = [];
    
    foreach ($proyectos as $proyecto) {
        $proyecto_trim = trim($proyecto);
        if (!empty($proyecto_trim)) {
            $query_asesor = "SELECT nombre FROM asesor WHERE FIND_IN_SET(?, REPLACE(nombreProyecto, ', ', ',')) > 0";
            $stmt = $conn->prepare($query_asesor);
            $stmt->bind_param("s", $proyecto_trim);
            $stmt->execute();
            $result_asesor = $stmt->get_result();
            
            if ($result_asesor->num_rows > 0) {
                while ($asesor = $result_asesor->fetch_assoc()) {
                    if (!in_array($asesor['nombre'], $asesores_encontrados)) {
                        $asesores_encontrados[] = $asesor['nombre'];
                    }
                }
            }
        }
    }
    
    if (!empty($asesores_encontrados)) {
        $asesor_asignado = implode(', ', $asesores_encontrados);
        $tiene_asesor = true;
        
        if (strpos($alumno_info['status'], 'asesor asignado') === false) {
            $nuevo_status = $alumno_info['status'] . (empty($alumno_info['status']) ? '' : ', ') . 'asesor asignado';
            $query_update = "UPDATE alumnos SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($query_update);
            $stmt->bind_param("si", $nuevo_status, $id_alumno);
            $stmt->execute();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Residencias</title>
    <link rel="stylesheet" href="STYLES/EstiloTest.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .nombre-proyecto-box {
            background-color: #007bff;
            color: white;
            padding: 6px;
            border-radius: 3px;
            margin-bottom: 8px;
            min-height: 40px;
        }

        .nombre-proyecto-box input {
            width: 100%;
            padding: 6px;
            border: none;
            border-radius: 1px;
            background-color: rgba(255,255,255,0.2);
            color: white;
            font-size: 13px;
            height: 28px;
        }
        .nombre-proyecto-box input::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .circulo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #7f8c8d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: white;
            font-size: 24px;
        }
        .circulo-completo {
            background-color: #2ecc71;
        }
        .asesores-box {
            background-color: rgba(0, 123, 255, 0.7);
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .asesores-box textarea {
            width: 100%;
            min-height: 50px;
            max-height: 60px;
            padding: 10px 15px;
            border: none;
            border-radius: 3px;
            background-color: rgba(255,255,255,0.2);
            color: white;
            resize: horizontal;
            font-size: 14px;
            overflow-y: auto;
            box-sizing: border-box;
            display: block;
            margin: 0;
        }

        .asesores-box textarea::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .texto-no-editable {
            background-color: rgba(255,255,255,0.1);
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 10px;
            white-space: pre-wrap;
            color: white;
            min-height: 50px;
            max-height: 60px;
            overflow-y: auto;
        }
        
        .dictamen-aprobado {
            color: green !important;
            font-weight: bold;
            font-size: 2rem;
        }
        
        .dictamen-modificaciones {
            color: orange !important;
            font-weight: bold;
            font-size: 2rem;
        }
        
        .dictamen-no-aprobado {
            color: red !important;
            font-weight: bold;
            font-size: 2rem;
        }
        
        .asesor-asignado {
            color: #007bff !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .btn-disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 col-md-6 encabezado">
                <div class="d-flex align-items-center">
                    <div class="logo" style="margin-right: 20px;">
                        <img src="Imagenes/logoV2.png" alt="logo" style="width: 100px; height: auto;">
                    </div>
                    <div class="informacionAlumno">
                        <h1>ALUMNO</h1>
                        <p id="datosAlumno" style="color: white;">
                            <?php
                            if (!empty($alumno_info)) {
                                echo "NOMBRE: " . htmlspecialchars($alumno_info['nombre']) . "<br>";
                                echo "NO. CONTROL: " . htmlspecialchars($id_alumno) . "<br>";
                                echo "UNIDAD ACADEMICA: CHAPALA<br>";
                                echo "CARRERA: " . htmlspecialchars($alumno_info['carrera']) . "<br>";
                                echo "CREDITOS: " . htmlspecialchars($alumno_info['creditos']) . " APROBADOS DE 260<br>";
                            } else {
                                echo "NOMBRE: NO ENCONTRADO<br>";
                                echo "NO. CONTROL: NO ENCONTRADO<br>";
                                echo "UNIDAD ACADEMICA: NO ENCONTRADA<br>";
                                echo "CARRERA: NO ENCONTRADA<br>";
                                echo "CREDITOS: 0 APROBADOS DE 0<br>";
                            }
                            ?>
                        </p>
                    </div> 
                </div>
            </div>
            <div class="col-12 col-md-6 barradeprogreso">
                <h1 id="etiqueta1">PROGRESO</h1>
                <div class="d-flex justify-content-center">
                    <div class="circulos-container">
                        <div class="circulo-con-texto">
                            <div class="circulo" id="circulo1">
                                <span class="icon">&#x2716;</span>
                            </div>
                            <p>Documentación <br> previa</p>
                        </div>
                        <div class="circulo-con-texto">
                            <div class="circulo" id="circulo2">
                                <span class="icon">&#x2716;</span>
                            </div>
                            <p>Anteproyecto</p>
                        </div>
                        <div class="circulo-con-texto">
                            <div class="circulo" id="circulo3">
                                <span class="icon">&#x2716;</span>
                            </div>
                            <p>Dictamen del <br> Anteproyecto</p>
                        </div>
                        <div class="circulo-con-texto">
                            <div class="circulo" id="circulo4">
                                <span class="icon">&#x2716;</span>
                            </div>
                            <p>Asignación de <br> asesor</p>
                        </div>
                    </div>
                </div>
            </div>
        </div> 
        <div class="row">
            <div class="col-12 col-md-4 box1">
                <div class="anteproyecto-container">
                    <label>ANTEPROYECTO</label>
                    
                    <div class="nombre-proyecto-box">
                        <input type="text" id="nombreProyecto" placeholder="Titulo del anteproyecto" 
                               value="<?php echo !empty($alumno_info['nombreProyecto']) ? htmlspecialchars($alumno_info['nombreProyecto']) : ''; ?>">
                    </div>
                    
                    <div class="drag-drop">
                        <input type="file" id="archivoAnteproyecto" accept=".pdf" style="display: none;" />
                        <span class="fa-stack fa-2x">
                            <i class="fa fa-cloud fa-stack-2x bottom pulsating"></i>
                            <i class="fa fa-circle fa-stack-1x top medium"></i>
                            <i class="fa fa-arrow-circle-up fa-stack-1x top"></i>
                        </span>
                        <span class="desc">Pulse aquí para añadir archivo PDF</span>
                    </div>
                    <div class="button-container">
                        <button type="button" class="btn btn-primary" onclick="subirAnteproyecto()">Enviar</button>
                        <button type="button" class="btn btn-default" onclick="limpiarFormulario()">Cancelar</button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 box2">
                <div class="listadoasesor-container">
                    <label>ASESORES:</label>
                    <div class="asesores-box">
                        <div id="asesoresDisplay" class="texto-no-editable" style="display: none;"></div>
                        <textarea id="asesoresInput" placeholder="Ingrese los nombres de sus 3 posibles asesores (separados por comas)"></textarea>
                    </div>
                    <div class="button-container">
                        <button type="button" class="btn btn-primary" id="btnGuardarAsesores" onclick="guardarAsesores()">Guardar Asesores</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-3 box4">
                <div class="asesor-container">
                    <label>ASESOR ASIGNADO:</label>
                    <div id="asesor">
                        <p class="<?php echo $tiene_asesor ? 'asesor-asignado' : ''; ?>"><?php echo htmlspecialchars($asesor_asignado); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-3 box5">
                <div class="dictamen-container">
                    <label>DICTAMEN:</label>
                    <div id="dictamen">
                        <p style="color: green; font-weight: bold; font-size: 2rem;">--Por definir--</p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 box6">
                <section id="app">
                    <div class="container">
                      <div class="row">
                        <div class="col-12">
                      <textarea type="text" class="input" placeholder="Escribe un comentario"></textarea>
                          <button type="button" class="btn btn-primary">Agregar Comentario</button>
                        </div>
                      </div>
                    </div>
                  </section>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const alumnoId = urlParams.get('id') || <?php echo $id_alumno; ?>;

        document.addEventListener('DOMContentLoaded', function() {
            const dragDrop = document.querySelector('.anteproyecto-container .drag-drop');
            const fileInput = document.getElementById('archivoAnteproyecto');
            
            dragDrop.addEventListener('click', function() {
                fileInput.click();
            });
            
            fileInput.addEventListener('change', function() {
                if(this.files.length > 0) {
                    document.querySelector('.anteproyecto-container .desc').textContent = this.files[0].name;
                }
            });
            
            const asesoresGuardados = localStorage.getItem(`asesores_${alumnoId}`);
            if (asesoresGuardados) {
                bloquearEdicionAsesores(asesoresGuardados, false); 
            
            verificarEstadoAlumno();
            
            verificarDictamen();
  
            verificarAsesorAsignado();

            <?php if ($tiene_asesor): ?>
                marcarAsesorAsignadoCompleto();
            <?php endif; ?>
        });
        
        function marcarAsesorAsignadoCompleto() {
            const circulo4 = document.getElementById('circulo4');
            circulo4.innerHTML = '&#x2714;';
            circulo4.classList.add('circulo-completo');
        }
        
        function verificarEstadoAlumno() {
            const circulo1 = document.getElementById('circulo1');
            circulo1.innerHTML = '&#x2714;';
            circulo1.classList.add('circulo-completo');

            verificarProyecto();
        }
        
        function verificarProyecto() {
            fetch(`obtener_anteproyecto.php?id_alumno=${alumnoId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.nombreProyecto && data.nombreProyecto.trim() !== '') {
                        const circulo2 = document.getElementById('circulo2');
                        circulo2.innerHTML = '&#x2714;';
                        circulo2.classList.add('circulo-completo');
                        document.getElementById('nombreProyecto').value = data.nombreProyecto;

                        if(data.archivo_anteproyecto) {
                            document.querySelector('.anteproyecto-container .desc').textContent = 'Archivo cargado';
                        }

                        verificarPermisoEdicion(data.dictamen);
                    }
                })
                .catch(error => {
                    console.error('Error al verificar proyecto:', error);
                });
        }
        
        function verificarDictamen() {
            fetch(`obtener_dictamen.php?id_alumno=${alumnoId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.dictamen && data.dictamen !== '--Por definir--') {
                        actualizarDictamen(data.dictamen);

                        const circulo3 = document.getElementById('circulo3');
                        circulo3.innerHTML = '&#x2714;';
                        circulo3.classList.add('circulo-completo');

                        actualizarEstadoAlumno('dictamen anteproyecto realizado');
                    }
                })
                .catch(error => {
                    console.error('Error al verificar dictamen:', error);
                });
        }
        
        function verificarAsesorAsignado() {
            fetch(`obtener_asesor_asignado.php?id_alumno=${alumnoId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.asesor && data.asesor !== '--Por definir--') {
                        document.querySelector('#asesor p').textContent = data.asesor;
                        document.querySelector('#asesor p').classList.add('asesor-asignado');

                        marcarAsesorAsignadoCompleto();

                        actualizarEstadoAlumno('asesor asignado');
                    }
                })
                .catch(error => {
                    console.error('Error al verificar asesor asignado:', error);
                });
        }
        
        function actualizarDictamen(dictamen) {
            const dictamenElement = document.getElementById('dictamen').querySelector('p');
            dictamenElement.textContent = dictamen;

            if (dictamen.toLowerCase().includes('aprobado')) {
                dictamenElement.className = 'dictamen-aprobado';
            } else if (dictamen.toLowerCase().includes('modificaciones')) {
                dictamenElement.className = 'dictamen-modificaciones';
            } else if (dictamen.toLowerCase().includes('no aprobado')) {
                dictamenElement.className = 'dictamen-no-aprobado';
            }
        }
        
        function bloquearEdicionAsesores(asesores, marcarCompleto = true) {
            const asesoresInput = document.getElementById('asesoresInput');
            const asesoresDisplay = document.getElementById('asesoresDisplay');
            const btnGuardar = document.getElementById('btnGuardarAsesores');

            asesoresDisplay.style.display = 'block';
            asesoresDisplay.textContent = asesores;
            asesoresInput.style.display = 'none';
 
            btnGuardar.disabled = true;
            btnGuardar.classList.add('btn-disabled');
  
            if (marcarCompleto) {
                const circulo4 = document.getElementById('circulo4');
                circulo4.innerHTML = '&#x2714;';
                circulo4.classList.add('circulo-completo');
            }
        }
        
        function guardarAsesores() {
            const asesores = document.getElementById('asesoresInput').value.trim();
            
            if (!asesores) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor ingrese al menos un asesor',
                    confirmButtonColor: '#007bff'
                });
                return;
            }

            const asesoresArray = asesores.split(',').map(item => item.trim()).filter(item => item);
            if (asesoresArray.length > 3) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Solo puede ingresar hasta 3 asesores',
                    confirmButtonColor: '#007bff'
                });
                return;
            }

            Swal.fire({
                title: 'Guardando asesores',
                html: 'Por favor espere...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            setTimeout(() => {
                Swal.close();
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: 'Asesores guardados correctamente',
                    confirmButtonColor: '#007bff'
                });

                localStorage.setItem(`asesores_${alumnoId}`, asesores);

                bloquearEdicionAsesores(asesores, false);

                actualizarEstadoAlumno('asesores registrados');
            }, 1000);
        }
        
        function verificarPermisoEdicion(dictamen) {
            if (!dictamen || dictamen === '--Por definir--' || 
                dictamen.toLowerCase().includes('no aprobado') || 
                dictamen.toLowerCase().includes('modificaciones')) {
                return; 
            }

            if (dictamen.toLowerCase().includes('aprobado') && 
                !dictamen.toLowerCase().includes('modificaciones')) {
                const nombreProyecto = document.getElementById('nombreProyecto');
                const archivoInput = document.getElementById('archivoAnteproyecto');
                const dragDrop = document.querySelector('.drag-drop');
                const btnEnviar = document.querySelector('.anteproyecto-container .btn-primary');
                const btnCancelar = document.querySelector('.anteproyecto-container .btn-default');
                
                nombreProyecto.disabled = true;
                archivoInput.disabled = true;
                dragDrop.style.pointerEvents = 'none';
                dragDrop.style.opacity = '0.6';
                btnEnviar.disabled = true;
                btnEnviar.style.opacity = '0.6';
                btnCancelar.disabled = true;
                btnCancelar.style.opacity = '0.6';
            }
        }
        
        function subirAnteproyecto() {
            fetch(`obtener_dictamen.php?id_alumno=${alumnoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.dictamen && 
                        !data.dictamen.toLowerCase().includes('no aprobado') && 
                        !data.dictamen.toLowerCase().includes('modificaciones') && 
                        data.dictamen !== '--Por definir--') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No puede modificar el anteproyecto con el dictamen actual',
                            confirmButtonColor: '#007bff'
                        });
                        return;
                    }

                    continuarSubidaAnteproyecto();
                })
                .catch(error => {
                    console.error('Error al verificar dictamen:', error);
                    continuarSubidaAnteproyecto();
                });
        }
        
        function continuarSubidaAnteproyecto() {
            const nombreProyecto = document.getElementById('nombreProyecto').value.trim();
            const archivoInput = document.getElementById('archivoAnteproyecto');
            const archivo = archivoInput.files[0];
            
            if (!nombreProyecto) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor ingrese el nombre del proyecto',
                    confirmButtonColor: '#007bff'
                });
                return;
            }
            
            if (!archivo) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor seleccione un archivo PDF',
                    confirmButtonColor: '#007bff'
                });
                return;
            }
            
            if (archivo.type !== 'application/pdf') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Solo se permiten archivos PDF',
                    confirmButtonColor: '#007bff'
                });
                return;
            }
            
            if (archivo.size > 5 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'El tamaño máximo permitido es 5MB',
                    confirmButtonColor: '#007bff'
                });
                return;
            }

            Swal.fire({
                title: 'Subiendo anteproyecto',
                html: 'Por favor espere...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const reader = new FileReader();
            reader.onload = function(event) {
                const arrayBuffer = event.target.result;
                const bytes = new Uint8Array(arrayBuffer);
                let binary = '';
                bytes.forEach(byte => binary += String.fromCharCode(byte));
                const base64 = btoa(binary);
                
                enviarAnteproyecto(nombreProyecto, base64);
            };
            reader.readAsArrayBuffer(archivo);
        }
        
        function enviarAnteproyecto(nombreProyecto, archivoBase64) {
            const formData = new FormData();
            formData.append('id_alumno', alumnoId);
            formData.append('nombreProyecto', nombreProyecto);
            formData.append('archivo_anteproyecto', archivoBase64);
            
            fetch('guardar_anteproyecto.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'Anteproyecto subido correctamente',
                        confirmButtonColor: '#007bff'
                    });
                    
                    const circulo2 = document.getElementById('circulo2');
                    circulo2.innerHTML = '&#x2714;';
                    circulo2.classList.add('circulo-completo');
                    
                    limpiarFormulario();
                    actualizarEstadoAlumno('revision anteproyecto');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al guardar el anteproyecto',
                        confirmButtonColor: '#007bff'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al enviar el anteproyecto',
                    confirmButtonColor: '#007bff'
                });
            });
        }
        
        function actualizarEstadoAlumno(nuevoEstado) {
            fetch('actualizar_estado.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_alumno: alumnoId,
                    estado: nuevoEstado
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Error al actualizar estado:', data.message);
                }
            })
            .catch(error => {
                console.error('Error al actualizar estado:', error);
            });
        }
        
        function limpiarFormulario() {
            document.getElementById('nombreProyecto').value = '';
            document.getElementById('archivoAnteproyecto').value = '';
            document.querySelector('.anteproyecto-container .desc').textContent = 'Pulse aquí para añadir archivo PDF';
        }
    </script>
</body>
</html>