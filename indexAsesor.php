<?php
session_start();

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_type']) !== 'asesor') {
    header("Location: indexLogin.php");
    exit();
}

if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: http://localhost/proy/indexLogin.php");
    exit();
}

include('evaluacion.php');
$conexion = conectarBD();

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$asesor_id = isset($_GET['asesor_id']) ? intval($_GET['asesor_id']) : 0;

if ($asesor_id === 0) {
    $nombre_asesor = $conexion->real_escape_string($_SESSION['user_name']);
    $query = "SELECT id FROM asesor WHERE nombre = '$nombre_asesor'";
    $result = $conexion->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $asesor_id = $row['id'];
    } else {
        $mensaje_asesor = "No se encontró información de asesor para este usuario.";
    }
}


if(!isset($_SESSION['comentarios'])) {
    $_SESSION['comentarios'] = [];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_comentario'])) {
    $nuevoComentario = [
        'nombre' => htmlspecialchars($_POST['nombre']),
        'email' => htmlspecialchars($_POST['email']),
        'comentario' => htmlspecialchars($_POST['comentario']),
        'fecha' => date('d/m/Y H:i:s')
    ];
    
    array_unshift($_SESSION['comentarios'], $nuevoComentario);
    
    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['asesor_id']) ? "?asesor_id=" . $_GET['asesor_id'] : ""));
    exit();
}

function extraerCalificacionPDF($filePath) {
    include('vendor/autoload.php');
    
    try {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        
        if (preg_match('/Calificación total\s+(\d+)/', $text, $matches)) {
            return intval($matches[1]);
        }
        
        if (preg_match('/Calificación total\s*\n\s*(\d+)/', $text, $matches)) {
            return intval($matches[1]);
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error al procesar PDF: " . $e->getMessage());
        return null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo']) && isset($_POST['alumno_id']) && isset($_POST['revision_num'])) {
    $alumno_id = intval($_POST['alumno_id']);
    $revision_num = intval($_POST['revision_num']);
    $file = $_FILES['archivo'];
    
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if($fileType != 'pdf') {
        $_SESSION['upload_error'] = "Solo se permiten archivos PDF.";
        header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['asesor_id']) ? "?asesor_id=" . $_GET['asesor_id'] : ""));
        exit();
    }
    
    $tempFilePath = sys_get_temp_dir() . '/' . basename($file['name']);
    if (!move_uploaded_file($file['tmp_name'], $tempFilePath)) {
        $_SESSION['upload_error'] = "Error al subir el archivo.";
        header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['asesor_id']) ? "?asesor_id=" . $_GET['asesor_id'] : ""));
        exit();
    }
    
    $calificacion = extraerCalificacionPDF($tempFilePath);
    
    if ($calificacion === null) {
        $_SESSION['upload_error'] = "No se pudo extraer la calificación del PDF. Asegúrese de que el formato es correcto.";
        unlink($tempFilePath);
        header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['asesor_id']) ? "?asesor_id=" . $_GET['asesor_id'] : ""));
        exit();
    }
    
    $pdfContent = file_get_contents($tempFilePath);
    $base64Pdf = base64_encode($pdfContent);
 
    unlink($tempFilePath);
    
    $columnaArchivo = 'archivoRevision' . $revision_num;
    $columnaCalificacion = 'revision' . $revision_num;
    
    $stmt = $conexion->prepare("UPDATE asesor SET $columnaArchivo = ?, $columnaCalificacion = ? WHERE id = ?");
    $stmt->bind_param("sii", $base64Pdf, $calificacion, $asesor_id);
    
    if ($stmt->execute()) {
        $query = "SELECT revision1, revision2, revision3 FROM asesor WHERE id = ?";
        $stmt2 = $conexion->prepare($query);
        $stmt2->bind_param("i", $asesor_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $cal1 = $row['revision1'] ?? 0;
            $cal2 = $row['revision2'] ?? 0;
            $cal3 = $row['revision3'] ?? 0;
            
            $totalCalificaciones = 0;
            $contador = 0;
            
            if ($cal1 > 0) { $totalCalificaciones += $cal1; $contador++; }
            if ($cal2 > 0) { $totalCalificaciones += $cal2; $contador++; }
            if ($cal3 > 0) { $totalCalificaciones += $cal3; $contador++; }
            
            $promedio = $contador > 0 ? round($totalCalificaciones / $contador) : 0;
            
            $stmt3 = $conexion->prepare("UPDATE alumnos SET calificacion = ? WHERE id = ?");
            $stmt3->bind_param("ii", $promedio, $alumno_id);
            $stmt3->execute();
            $stmt3->close();
        }
        
        $_SESSION['upload_success'] = true;
        $_SESSION['calificacion'] = $calificacion;
    } else {
        $_SESSION['upload_error'] = "Error al guardar en la base de datos: " . $conexion->error;
    }
    
    $stmt->close();
    
    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['asesor_id']) ? "?asesor_id=" . $_GET['asesor_id'] : ""));
    exit();
}

$nombre_asesor = '';
$mensaje_asesor = '';
$anteproyectos = [];

if ($asesor_id > 0) {
    $query_asesor = "SELECT nombre, nombreProyecto, revision1, revision2, revision3 FROM asesor WHERE id = $asesor_id";
    $result_asesor = $conexion->query($query_asesor);
    
    if ($result_asesor && $result_asesor->num_rows > 0) {
        $row_asesor = $result_asesor->fetch_assoc();
        $nombre_asesor = $row_asesor['nombre'];
        
        if (!empty(trim($row_asesor['nombreProyecto']))) {
            $proyectos_asesor = explode(',', $row_asesor['nombreProyecto']);
            
            $proyectos_asesor = array_map('trim', $proyectos_asesor);
            $proyectos_asesor = array_filter($proyectos_asesor); 
            
            if (!empty($proyectos_asesor)) {
                $proyectos_list = "'" . implode("','", array_map([$conexion, 'real_escape_string'], $proyectos_asesor)) . "'";
                
                $query_anteproyectos = "SELECT al.*, al.nombre as nombre_alumno, al.id as id_alumno, d.archivo_Anteproyecto
                                      FROM alumnos al
                                      LEFT JOIN documentos d ON d.id = (
                                          SELECT id FROM documentos 
                                          WHERE alumno = al.id 
                                            AND archivo_Anteproyecto IS NOT NULL 
                                          ORDER BY id DESC 
                                          LIMIT 1
                                      )
                                      WHERE al.nombreProyecto IN ($proyectos_list)";
                $result_anteproyectos = $conexion->query($query_anteproyectos);
                
                if ($result_anteproyectos) {
                    if ($result_anteproyectos->num_rows > 0) {
                        while ($row = $result_anteproyectos->fetch_assoc()) {
                            $anteproyectos[] = $row;
                        }
                    } else {
                        $mensaje_asesor = "No se encontraron alumnos asignados a los anteproyectos de este asesor.";
                    }
                } else {
                    die("Error en la consulta: " . $conexion->error);
                }
            } else {
                $mensaje_asesor = "El asesor seleccionado no tiene anteproyectos válidos asignados.";
            }
        } else {
            $mensaje_asesor = "El asesor seleccionado no tiene anteproyectos asignados.";
        }
    } else {
        $mensaje_asesor = "No se encontró información para el asesor seleccionado.";
    }
}

function obtenerCorreoAlumno($id_alumno, $conexion) {
    $query = "SELECT email FROM login WHERE tipo = 'alumno' AND nombre = (SELECT nombre FROM alumnos WHERE id = $id_alumno)";
    $result = $conexion->query($query);
    if($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['email'];
    }
    return "ch".$id_alumno."@chapala.tecmmm.edu.mx";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizador de Archivos</title>
   
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.12.313/pdf.min.js"></script>
   
    <link rel="stylesheet" href="/proy/styleAsesor.css">
    <style>
        .file-upload-container {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px dashed #ccc;
        }
        
        .upload-btn-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .upload-btn {
            border: 1px solid #3498db;
            color: #3498db;
            background-color: white;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .upload-btn-wrapper input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-info {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
        
        .comentarios-container {
            margin-top: 40px;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
        
        .comentario-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
        }
        
        .comentario-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .comentario-autor {
            font-weight: bold;
            color: #0d6efd;
        }
        
        .comentario-fecha {
            font-style: italic;
        }
        
        .comentario-texto {
            margin-top: 10px;
            padding: 10px;
            background-color: white;
            border-radius: 5px;
            border-left: 3px solid #0d6efd;
        }
        
        .form-comentario {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .modal-fullscreen {
            max-width: 100%;
            margin: 0;
        }
        .modal-fullscreen .modal-content {
            height: 100vh;
        }
        .modal-fullscreen .modal-body {
            overflow-y: auto;
        }
        #pdfCanvas {
            width: 100%;
            border: 1px solid #ccc;
        }
        #pdfContainer {
            text-align: center;
        }
        .pdf-message {
            margin: 20px 0;
            color: #666;
        }
        .ver-anteproyecto {
            color: #007bff;
            cursor: pointer;
            text-decoration: underline;
        }
        .ver-anteproyecto:hover {
            color: #0056b3;
        }
        .revision-tabs {
            margin-bottom: 15px;
        }
        .revision-tabs .nav-link {
            cursor: pointer;
        }
        .revision-tabs .nav-link.active {
            font-weight: bold;
        }
    </style>
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
           
            <a class="navbar-brand" href="#">
                <img src="imagenes/logo.png" alt="Logo" width="100" height="45" class="d-inline-block align-text-top me-2">
                Tecnológico Superior Chapala
            </a>
            
          
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
          
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                  
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle fa-lg me-1"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                                <i class="fas fa-user me-2"></i>Perfil
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="?logout=true">
                                    <i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

 
    <div class="container mt-4">
        <h2><i class="fas fa-file-alt me-2"></i>Anteproyectos</h2>
        
       
        <div class="search-box mb-4">
            <div class="input-group">
                <input type="text" id="searchInput" class="form-control" placeholder="Buscar anteproyectos..." aria-label="Buscar">
                <button class="btn btn-primary" type="button" id="searchButton">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        
   
        <div class="row mb-3">
            <div class="col-md-4">
                <select class="form-select" id="filterStudent">
                    <option value="">Todos los estudiantes</option>
                    <?php if(!empty($anteproyectos)): ?>
                        <?php foreach($anteproyectos as $anteproyecto): ?>
                            <option value="<?= htmlspecialchars($anteproyecto['nombre_alumno']) ?>">
                                <?= htmlspecialchars($anteproyecto['nombre_alumno']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select" id="filterCalification">
                    <option value="">Todas las calificaciones</option>
                    <option value="7">7+</option>
                    <option value="8">8+</option>
                    <option value="9">9+</option>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-outline-secondary w-100" id="resetFilters">
                    <i class="fas fa-undo me-1"></i> Reiniciar filtros
                </button>
            </div>
        </div>

        <?php if(!empty($nombre_asesor)): ?>
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle me-2"></i>Mostrando información para el asesor: <strong><?= $nombre_asesor ?></strong>
                <?php if(!empty($mensaje_asesor)): ?>
                    <div class="mt-2 alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= $mensaje_asesor ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif(empty($anteproyectos) && empty($nombre_asesor)): ?>
            <div class="alert alert-warning mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>No se encontró información de asesor para este usuario.
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['upload_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>Archivo subido correctamente. 
                <?php if(isset($_SESSION['calificacion'])): ?>
                    Calificación: <?= $_SESSION['calificacion'] ?>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['upload_success']); unset($_SESSION['calificacion']); ?>
        <?php elseif(isset($_SESSION['upload_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['upload_error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['upload_error']); ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover" id="filesTable">
                <thead class="table-dark">
                    <tr>
                        <th>Documento</th>
                        <th>Nombre del Estudiante</th>
                        <th>Correo</th>
                        <th>Cal 1</th>
                        <th>Cal 2</th>
                        <th>Cal 3</th>
                        <th>Promedio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($anteproyectos)): ?>
                        <?php foreach($anteproyectos as $anteproyecto): ?>
                            <?php 
                            $correo_alumno = obtenerCorreoAlumno($anteproyecto['id_alumno'], $conexion);
                            $hasPdf = !empty($anteproyecto['archivo_Anteproyecto']);
                            
                            // Obtener calificaciones del asesor para este proyecto
                            $query_calificaciones = "SELECT revision1, revision2, revision3 FROM asesor WHERE id = $asesor_id";
                            $result_calificaciones = $conexion->query($query_calificaciones);
                            $calificaciones = $result_calificaciones->fetch_assoc();
                            
                            // Calcular promedio
                            $cal1 = $calificaciones['revision1'] ?? 0;
                            $cal2 = $calificaciones['revision2'] ?? 0;
                            $cal3 = $calificaciones['revision3'] ?? 0;
                            
                            $totalCalificaciones = 0;
                            $contador = 0;
                            
                            if ($cal1 > 0) { $totalCalificaciones += $cal1; $contador++; }
                            if ($cal2 > 0) { $totalCalificaciones += $cal2; $contador++; }
                            if ($cal3 > 0) { $totalCalificaciones += $cal3; $contador++; }
                            
                            $promedio = $contador > 0 ? round($totalCalificaciones / $contador) : 0;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($anteproyecto['nombreProyecto']) ?></td>
                                <td><?= htmlspecialchars($anteproyecto['nombre_alumno']) ?></td>
                                <td><?= htmlspecialchars($correo_alumno) ?></td>
                                <td class="editable" data-label="Cal 1"><?= $cal1 > 0 ? $cal1 : 'N/A' ?></td>
                                <td class="editable" data-label="Cal 2"><?= $cal2 > 0 ? $cal2 : 'N/A' ?></td>
                                <td class="editable" data-label="Cal 3"><?= $cal3 > 0 ? $cal3 : 'N/A' ?></td>
                                <td class="promedio" data-label="Promedio"><?= $promedio > 0 ? $promedio : 'N/A' ?></td>
                                <td data-label="Acciones">
                                    <div class="d-flex gap-2">
                                        <?php if($hasPdf): ?>
                                            <button class="btn btn-primary btn-sm" onclick="verAnteproyecto('<?= htmlspecialchars($anteproyecto['archivo_Anteproyecto']) ?>')">
                                                <i class="fas fa-eye me-1"></i>Ver
                                            </button>
                                            <button class="btn btn-warning btn-sm" onclick="descargarAnteproyecto('<?= htmlspecialchars($anteproyecto['archivo_Anteproyecto']) ?>', '<?= htmlspecialchars($anteproyecto['nombreProyecto']) ?>')">
                                                <i class="fas fa-download me-1"></i>Descargar
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                <i class="fas fa-eye me-1"></i>Ver
                                            </button>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                <i class="fas fa-download me-1"></i>Descargar
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-success btn-sm" onclick="toggleUploadForm('<?= $anteproyecto['id'] ?>')">
                                            <i class="fas fa-upload me-1"></i>Subir
                                        </button>
                                    </div>
                                    
                                    <!-- Formulario para subir archivos (oculto inicialmente) -->
                                    <div id="upload-form-<?= $anteproyecto['id'] ?>" class="file-upload-container">
                                        <ul class="nav nav-tabs revision-tabs" id="revisionTabs-<?= $anteproyecto['id'] ?>">
                                            <li class="nav-item">
                                                <a class="nav-link active" data-revision="1" onclick="changeRevisionTab(this, <?= $anteproyecto['id'] ?>)">Revisión 1</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-revision="2" onclick="changeRevisionTab(this, <?= $anteproyecto['id'] ?>)">Revisión 2</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-revision="3" onclick="changeRevisionTab(this, <?= $anteproyecto['id'] ?>)">Revisión 3</a>
                                            </li>
                                        </ul>
                                        
                                        <form class="upload-form" enctype="multipart/form-data" method="POST" action="">
                                            <input type="hidden" name="alumno_id" value="<?= $anteproyecto['id'] ?>">
                                            <input type="hidden" name="revision_num" id="revision_num-<?= $anteproyecto['id'] ?>" value="1">
                                            <div class="mb-3">
                                                <label class="form-label">Seleccionar archivo (PDF):</label>
                                                <div class="upload-btn-wrapper">
                                                    <button class="upload-btn">
                                                        <i class="fas fa-folder-open me-2"></i>Elegir archivo
                                                    </button>
                                                    <input type="file" name="archivo" accept=".pdf" required>
                                                </div>
                                                <div class="file-info" id="file-info-<?= $anteproyecto['id'] ?>">
                                                    Ningún archivo seleccionado
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-cloud-upload-alt me-1"></i>Subir
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleUploadForm('<?= $anteproyecto['id'] ?>')">
                                                    <i class="fas fa-times me-1"></i>Cancelar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php elseif(empty($anteproyectos) && $asesor_id > 0): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <?= $mensaje_asesor ?? 'No se encontraron anteproyectos asignados a este asesor.' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="comentarios-container">
            <h3><i class="fas fa-comments me-2"></i>Comentarios</h3>
         
            <div class="form-comentario">
                <form method="POST" action="">
                    <input type="hidden" name="nuevo_comentario" value="1">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="comentario" class="form-label">Comentario</label>
                        <textarea class="form-control" id="comentario" name="comentario" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Agregar comentario
                    </button>
                </form>
            </div>
            
            <div class="comentarios-list">
                <?php if(!empty($_SESSION['comentarios'])): ?>
                    <?php foreach($_SESSION['comentarios'] as $comentario): ?>
                        <div class="comentario-card">
                            <div class="comentario-header">
                                <span class="comentario-autor">
                                    <i class="fas fa-user me-1"></i><?= $comentario['nombre']?> 
                                    <small>(<?= $comentario['email'] ?>)</small>
                                </span>
                                <span class="comentario-fecha">
                                    <i class="far fa-clock me-1"></i><?= $comentario['fecha'] ?>
                                </span>
                            </div>
                            <div class="comentario-texto">
                                <?= nl2br($comentario['comentario']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No hay comentarios aún. Sé el primero en comentar.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfModalLabel">Visualización de Anteproyecto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="pdfContainer">
                        <div id="pdfMessage" class="pdf-message" style="display: none;"></div>
                        <canvas id="pdfCanvas"></canvas>
                    </div>
                    <div class="text-center mt-3" id="pdfControls" style="display: none;">
                        <button class="btn btn-secondary me-2" id="prevPage">Anterior</button>
                        <span id="pageNum">1</span> / <span id="pageCount">0</span>
                        <button class="btn btn-secondary ms-2" id="nextPage">Siguiente</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="downloadPdf" style="display: none;">Descargar PDF</button>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
 
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.12.313/pdf.worker.min.js';
        
        let pdfDoc = null;
        let pageNum = 1;
        let pageRendering = false;
        let pageNumPending = null;
        const scale = 1.5;
        let currentPdfData = null;
        
        function renderPage(num) {
            pageRendering = true;
            pdfDoc.getPage(num).then(function(page) {
                const viewport = page.getViewport({ scale: scale });
                const canvas = document.getElementById('pdfCanvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                
                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                
                const renderTask = page.render(renderContext);
                
                renderTask.promise.then(function() {
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });
            });
            
            document.getElementById('pageNum').textContent = num;
        }
        
        function queueRenderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }
        
        function onPrevPage() {
            if (pageNum <= 1) {
                return;
            }
            pageNum--;
            queueRenderPage(pageNum);
        }
        
        function onNextPage() {
            if (pageNum >= pdfDoc.numPages) {
                return;
            }
            pageNum++;
            queueRenderPage(pageNum);
        }
        
        function verAnteproyecto(pdfData) {
            Swal.fire({
                title: 'Cargando anteproyecto',
                html: 'Por favor espera mientras procesamos el documento...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
               
                if (!pdfData || pdfData.trim() === "") {
                    throw new Error("El PDF está vacío o no se proporcionó datos.");
                }

                const raw = window.atob(pdfData);
                const rawLength = raw.length;
                const array = new Uint8Array(new ArrayBuffer(rawLength));
                
                for(let i = 0; i < rawLength; i++) {
                    array[i] = raw.charCodeAt(i);
                }

                currentPdfData = array;
             
                pdfjsLib.getDocument({ data: array }).promise
                    .then(function(pdf) {
                        Swal.close();
                        pdfDoc = pdf;
                        document.getElementById('pageCount').textContent = pdf.numPages;
                        pageNum = 1;
                        renderPage(1);

                    
                        document.getElementById('pdfControls').style.display = 'block';
                        document.getElementById('downloadPdf').style.display = 'block';
                        document.getElementById('pdfMessage').style.display = 'none';
                        document.getElementById('pdfCanvas').style.display = 'block';

                       
                        const pdfModal = new bootstrap.Modal(document.getElementById('pdfModal'));
                        pdfModal.show();
                    })
                    .catch(function(error) {
                        Swal.close();
                        document.getElementById('pdfMessage').textContent = 'Error al cargar el PDF: ' + error.message;
                        document.getElementById('pdfMessage').style.display = 'block';
                        document.getElementById('pdfControls').style.display = 'none';
                        document.getElementById('downloadPdf').style.display = 'none';
                        document.getElementById('pdfCanvas').style.display = 'none';
                        
                        const pdfModal = new bootstrap.Modal(document.getElementById('pdfModal'));
                        pdfModal.show();
                        
                        console.error("Error al cargar el PDF:", error);
                    });
            } catch (error) {
                Swal.close();
                document.getElementById('pdfMessage').textContent = 'Error al procesar el PDF: ' + error.message;
                document.getElementById('pdfMessage').style.display = 'block';
                document.getElementById('pdfControls').style.display = 'none';
                document.getElementById('downloadPdf').style.display = 'none';
                document.getElementById('pdfCanvas').style.display = 'none';
                
                const pdfModal = new bootstrap.Modal(document.getElementById('pdfModal'));
                pdfModal.show();
                
                console.error("Error en verAnteproyecto():", error);
            }
        }
        
   
        function descargarAnteproyecto(pdfData, nombreProyecto) {
            try {
                if (!pdfData || pdfData.trim() === "") {
                    throw new Error("El PDF está vacío o no se proporcionó datos.");
                }

           
                const raw = window.atob(pdfData);
                const rawLength = raw.length;
                const array = new Uint8Array(new ArrayBuffer(rawLength));
                
                for(let i = 0; i < rawLength; i++) {
                    array[i] = raw.charCodeAt(i);
                }

           
                const blob = new Blob([array], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                
     
                const nombreArchivo = nombreProyecto.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.pdf';
                a.download = nombreArchivo;
                
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al descargar',
                    text: 'No se pudo descargar el archivo: ' + error.message,
                    confirmButtonText: 'Aceptar'
                });
                console.error("Error en descargarAnteproyecto():", error);
            }
        }

 
        document.getElementById('prevPage').addEventListener('click', onPrevPage);
        document.getElementById('nextPage').addEventListener('click', onNextPage);


        document.getElementById('downloadPdf').addEventListener('click', function() {
            if (!currentPdfData) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No hay PDF para descargar',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            const blob = new Blob([currentPdfData], { type: 'application/pdf' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'anteproyecto.pdf';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });

        // Función para mostrar/ocultar formulario de subida
        function toggleUploadForm(alumnoId) {
            const form = document.getElementById(`upload-form-${alumnoId}`);
            form.style.display = form.style.display === 'none' || !form.style.display ? 'block' : 'none';
        }
        

        function changeRevisionTab(tabElement, alumnoId) {

            const tabs = document.querySelectorAll(`#revisionTabs-${alumnoId} .nav-link`);
            tabs.forEach(tab => tab.classList.remove('active'));

            tabElement.classList.add('active');

            const revisionNum = tabElement.getAttribute('data-revision');
            document.getElementById(`revision_num-${alumnoId}`).value = revisionNum;
        }

        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                const alumnoId = this.closest('form').querySelector('input[name="alumno_id"]').value;
                const fileInfo = document.getElementById(`file-info-${alumnoId}`);
                
                if(this.files.length > 0) {
                    fileInfo.textContent = this.files[0].name;
                    fileInfo.style.color = '#28a745';
                } else {
                    fileInfo.textContent = 'Ningún archivo seleccionado';
                    fileInfo.style.color = '#666';
                }
            });
        });

        function filterTable() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const studentFilter = document.getElementById('filterStudent').value.toLowerCase();
            const calificationFilter = document.getElementById('filterCalification').value;
            
            const rows = document.querySelectorAll('#filesTable tbody tr');
            
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const studentName = row.cells[1].textContent.toLowerCase();
                const cal1 = parseFloat(row.cells[3].textContent) || 0;
                const cal2 = parseFloat(row.cells[4].textContent) || 0;
                const cal3 = parseFloat(row.cells[5].textContent) || 0;
                const promedio = parseFloat(row.cells[6].textContent) || 0;

                const matchesSearch = searchText === '' || rowText.includes(searchText);
                const matchesStudent = studentFilter === '' || studentName.includes(studentFilter);
                let matchesCalification = calificationFilter === '';
                
                if (!matchesCalification) {
                    const minCal = parseFloat(calificationFilter);
                    matchesCalification = promedio >= minCal;
                }
                
                if (matchesSearch && matchesStudent && matchesCalification) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        document.getElementById('searchInput').addEventListener('input', filterTable);
        document.getElementById('searchButton').addEventListener('click', filterTable);
        document.getElementById('filterStudent').addEventListener('change', filterTable);
        document.getElementById('filterCalification').addEventListener('change', filterTable);

        document.getElementById('resetFilters').addEventListener('click', function() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterStudent').value = '';
            document.getElementById('filterCalification').value = '';
            filterTable();
        });

        document.addEventListener('DOMContentLoaded', function() {
            filterTable();
        });
    </script>
</body>
</html>

<?php
$conexion->close();
?>