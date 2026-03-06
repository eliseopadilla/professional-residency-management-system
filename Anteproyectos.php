<?php
include('evaluacion.php');
session_start();

$conexion = conectarBD();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nuevo_profesor'])) {
    $nombre_profesor = trim($_POST['nuevo_profesor']);
    
    if (!empty($nombre_profesor)) {
        $stmt = $conexion->prepare("SELECT nombre FROM asesor WHERE nombre = ?");
        $stmt->bind_param("s", $nombre_profesor);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $_SESSION['alert'] = ['type' => 'warning', 'message' => 'El profesor ya existe'];
        } else {
            $stmt = $conexion->prepare("INSERT INTO asesor (nombre) VALUES (?)");
            $stmt->bind_param("s", $nombre_profesor);
            
            if ($stmt->execute()) {
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Profesor agregado correctamente'];
            } else {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al agregar profesor: ' . $stmt->error];
            }
        }
        $stmt->close();
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    } else {
        $_SESSION['alert'] = ['type' => 'warning', 'message' => 'El nombre del profesor no puede estar vacío'];
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['asesor'])) {
    $stmt = $conexion->prepare("UPDATE asesor SET nombreProyecto = NULL, carrera = NULL");
    $stmt->execute();
    $stmt->close();
    
    $proyectos_por_profesor = [];
    $carreras_por_profesor = [];
    $ids_proyectos = array_keys($_POST['asesor']);
    
    if (!empty($ids_proyectos)) {
        $placeholders = implode(',', array_fill(0, count($ids_proyectos), '?'));
        $types = str_repeat('i', count($ids_proyectos));
        
        $stmt = $conexion->prepare("SELECT id, nombreProyecto, carrera FROM alumnos WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$ids_proyectos);
        $stmt->execute();
        $result = $stmt->get_result();
        $proyectos = [];
        while ($row = $result->fetch_assoc()) {
            $proyectos[$row['id']] = [
                'nombreProyecto' => $row['nombreProyecto'],
                'carrera' => $row['carrera']
            ];
        }
        $stmt->close();
        
        foreach ($_POST['asesor'] as $alumno_id => $profesor_id) {
            if (!empty($profesor_id) && isset($proyectos[$alumno_id])) {
                if (!isset($proyectos_por_profesor[$profesor_id])) {
                    $proyectos_por_profesor[$profesor_id] = [];
                    $carreras_por_profesor[$profesor_id] = [];
                }
                $proyectos_por_profesor[$profesor_id][] = $proyectos[$alumno_id]['nombreProyecto'];
                $carreras_por_profesor[$profesor_id][] = $proyectos[$alumno_id]['carrera'];
            }
        }
        
        foreach ($proyectos_por_profesor as $profesor_id => $proyectos) {
            $proyectos_str = implode(', ', $proyectos);
            
            $carreras = $carreras_por_profesor[$profesor_id];
            $carrera_counts = array_count_values($carreras);
            arsort($carrera_counts);
            $carrera_principal = key($carrera_counts);
            
            $stmt = $conexion->prepare("UPDATE asesor SET nombreProyecto = ?, carrera = ? WHERE id = ?");
            $stmt->bind_param("ssi", $proyectos_str, $carrera_principal, $profesor_id);
            $stmt->execute();
            $stmt->close();
        }
        
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Asignaciones guardadas correctamente'];
    } else {
        $_SESSION['alert'] = ['type' => 'warning', 'message' => 'No se seleccionaron proyectos'];
    }
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

$query = "SELECT id, nombreProyecto FROM alumnos WHERE dictamen = 'APROBADO' AND nombreProyecto IS NOT NULL AND nombreProyecto != ''";
$resultado = $conexion->query($query);

if (!$resultado) {
    die("Error en la consulta: " . $conexion->error);
}

$query_profesores = "SELECT id, nombre, nombreProyecto FROM asesor ORDER BY nombre";
$profesores = $conexion->query($query_profesores);

if (!$profesores) {
    die("Error en la consulta de profesores: " . $conexion->error);
}

$lista_profesores = [];
while ($profesor = $profesores->fetch_assoc()) {
    $lista_profesores[$profesor['id']] = [
        'nombre' => $profesor['nombre'],
        'proyectos' => $profesor['nombreProyecto']
    ];
}

$asignaciones = [];
$proyectos_por_profesor = [];
$query_asignaciones = "SELECT nombreProyecto, id FROM asesor WHERE nombreProyecto IS NOT NULL";
$result_asignaciones = $conexion->query($query_asignaciones);

if ($result_asignaciones) {
    while ($row = $result_asignaciones->fetch_assoc()) {
        if (!empty($row['nombreProyecto'])) {
            $proyectos = explode(', ', $row['nombreProyecto']);
            foreach ($proyectos as $proyecto) {
                $asignaciones[$proyecto] = $row['id'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignación de Asesores</title>
    <link rel="stylesheet" href="/proy/styleAlumnos.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .alert { margin: 15px; }
        .table-responsive { padding: 15px; }
        .btn-lg { padding: 10px 30px; }
        .table th { text-align: center; }
        .table td { vertical-align: middle; }
        .radio-cell { text-align: center; }
        .proyecto-title {
            font-weight: bold;
            color: #007bff;
        }
        .add-profesor-form {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .no-proyectos {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #6c757d;
        }
    </style>
</head>
<body>
  <div class="container-fluid">
    <div class="row" style="height: 100vh;">
      <div class="col-2 col-sm-3 col-xl-2 bg-dark">
        <nav class="navbar border-bottom border-white mb-3" data-bs-theme="dark">
          <div class="container-fluid">
            <a class="navbar-brand text-white" href="http://localhost/proy/indexCoordinador.php">
              <i class="fa-solid fa-house"></i><span class="d-none d-sm-inline ms-2"> Inicio</span>
            </a>
          </div>
        </nav>
        <nav class="nav flex-column">
          <a class="nav-link text-white" href="/proy/indexCoordinador.php" style="white-space:nowrap">
            <i class="fa-solid fa-file-contract"></i><span class="d-none d-sm-inline ml-2">Tablero</span>
          </a>
          <a class="nav-link text-white" href="/proy/Alumnos.php" style="white-space: nowrap; display: flex; align-items: center;">
            <i class="fa-solid fa-graduation-cap"></i>
            <span class="ml-2 d-none d-sm-inline">Alumnos</span>
          </a>        
          <a class="nav-link text-white active" href="/proy/Anteproyectos.php" style="white-space:nowrap">
            <i class="fa-solid fa-calendar"></i>
            <span class="d-none d-sm-inline ml-2">Anteproyectos</span>
          </a>
          <a class="nav-link text-white" href="/proy//users.php" style="white-space:nowrap">
            <i class="fa-solid fa-user-tie"></i><span class="d-none d-sm-inline ml-2"> Usuarios</span>
          </a>
        </nav>
      </div>

      <!-- Contenido principal -->
      <div class="col-10 col-sm-9 col-xl-10 p-0 m-0">
          <nav class="navbar navbar-expand-lg navbar-light bg-primary mb-3">
            <div class="container-fluid">
              <form class="form-inline my-2 my-lg-0">
                <input class="form-control mr-sm-2" type="search" placeholder="Buscar..." aria-label="Search">
                <button class="btn btn-outline-light my-2 my-sm-0" type="submit">Buscar</button>
              </form>
              <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                  <a class="nav-link text-white" href="http://localhost/proy/indexLogin.php">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Salir 
                    <img src="Imagenes/logoazul.png" width="100" height="45" alt="Logo">
                  </a>
                </li>
              </ul>
            </div>
          </nav>

          <div class="table-responsive">
              <!-- Formulario para agregar nuevos profesores -->
              <div class="add-profesor-form">
                  <h4>Agregar Nuevo Profesor</h4>
                  <form method="POST" action="" class="form-inline">
                      <div class="form-group mx-sm-3 mb-2">
                          <input type="text" class="form-control" name="nuevo_profesor" placeholder="Nombre completo del profesor" required>
                      </div>
                      <button type="submit" class="btn btn-primary mb-2">
                          <i class="fa-solid fa-plus"></i> Agregar Profesor
                      </button>
                  </form>
              </div>

              <form method="POST" action="">
                <table id="usersTable" class="table table-striped table-hover table-bordered">
                  <thead>
                    <tr>
                      <td colspan="<?php echo !empty($lista_profesores) ? count($lista_profesores) + 2 : 3; ?>">  
                        <div class="d-flex align-items-center gap-3">
                            <h3 class="mb-0">Asignación de Asesores</h3>
                            <input type="text" class="form-control w-auto" placeholder="Evento" id="formEvento">
                            <input type="date" class="form-control w-auto" id="formDate">
                        </div>
                      </td>
                    </tr>
                    <tr class="table-primary">
                      <th>Anteproyectos aprobados</th>
                      <?php if (!empty($lista_profesores)): ?>
                          <?php foreach ($lista_profesores as $id => $profesor): ?>
                              <th><?php echo htmlspecialchars($profesor['nombre']); ?></th>
                          <?php endforeach; ?>
                          <th>Sin asignar</th>
                      <?php else: ?>
                          <th colspan="2">No hay profesores registrados</th>
                      <?php endif; ?>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($resultado->num_rows > 0): ?>
                        <?php while ($alumno = $resultado->fetch_assoc()): ?>
                          <tr>
                            <td class="proyecto-title"><?php echo htmlspecialchars($alumno['nombreProyecto']); ?></td>
                            
                            <?php 
                            // Verificar si este proyecto ya está asignado
                            $asignado = isset($asignaciones[$alumno['nombreProyecto']]) ? $asignaciones[$alumno['nombreProyecto']] : null;
                            ?>
                            
                            <?php if (!empty($lista_profesores)): ?>
                                <?php foreach ($lista_profesores as $id => $profesor): ?>
                                    <td class="radio-cell">
                                        <input type="radio" 
                                               name="asesor[<?php echo $alumno['id']; ?>]" 
                                               value="<?php echo $id; ?>"
                                               <?php echo ($asignado == $id) ? 'checked' : ''; ?>>
                                    </td>
                                <?php endforeach; ?>
                                
                                <td class="radio-cell">
                                    <input type="radio" 
                                           name="asesor[<?php echo $alumno['id']; ?>]" 
                                           value=""
                                           <?php echo ($asignado === null) ? 'checked' : ''; ?>>
                                </td>
                            <?php else: ?>
                                <td colspan="2" class="no-proyectos">
                                    Agrega profesores primero para poder asignarlos
                                </td>
                            <?php endif; ?>
                          </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo !empty($lista_profesores) ? count($lista_profesores) + 2 : 3; ?>" class="text-center">No hay anteproyectos aprobados</td>
                        </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
                <?php if ($resultado->num_rows > 0 && !empty($lista_profesores)): ?>
                <div class="text-center my-4">
                  <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-save"></i> Guardar asignaciones
                  </button>
                </div>
                <?php endif; ?>
              </form>
          </div>
      </div>
    </div>
  </div>

  <script src="/proy/chart4.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.0/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <!-- SweetAlert JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <script>
  // Mostrar alertas si existen
  <?php if(isset($_SESSION['alert'])): ?>
      Swal.fire({
          icon: '<?php echo $_SESSION['alert']['type']; ?>',
          title: '<?php echo $_SESSION['alert']['type'] == 'error' ? 'Error' : 
                 ($_SESSION['alert']['type'] == 'warning' ? 'Advertencia' : 'Éxito'); ?>',
          text: '<?php echo $_SESSION['alert']['message']; ?>',
          confirmButtonColor: '#3085d6',
      });
      <?php unset($_SESSION['alert']); ?>
  <?php endif; ?>

  // Confirmación antes de enviar el formulario
  document.querySelector('form[method="POST"]:not(.form-inline)')?.addEventListener('submit', function(e) {
      const radiosChecked = document.querySelectorAll('input[type="radio"]:checked').length;
      if (radiosChecked === 0) {
          e.preventDefault();
          Swal.fire({
              icon: 'warning',
              title: 'Advertencia',
              text: 'No has seleccionado ningún asesor. ¿Estás seguro de continuar?',
              showCancelButton: true,
              confirmButtonText: 'Sí, continuar',
              cancelButtonText: 'No, volver',
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
          }).then((result) => {
              if (result.isConfirmed) {
                  e.target.submit();
              }
          });
      }
  });
  </script>
</body>
</html>

<?php
$conexion->close();
?>