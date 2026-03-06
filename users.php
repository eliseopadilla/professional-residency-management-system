<?php
include('evaluacion.php');

$conexion = conectarBD();


if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_user') {

        $id = $_POST['id'];
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $tipo = $_POST['tipo'];
        
        $stmt = $conexion->prepare("UPDATE login SET nombre=?, email=?, tipo=? WHERE id=?");
        $stmt->bind_param("sssi", $nombre, $email, $tipo, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conexion->error]);
        }
        $stmt->close();
        exit();
    } elseif ($_POST['action'] === 'update_password') {
        $id = $_POST['id'];
        $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        
        $stmt = $conexion->prepare("UPDATE login SET password=? WHERE id=?");
        $stmt->bind_param("si", $newPassword, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conexion->error]);
        }
        $stmt->close();
        exit();
    } elseif ($_POST['action'] === 'add_user') {

        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $tipo = $_POST['tipo'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $stmt = $conexion->prepare("INSERT INTO login (nombre, email, password, tipo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nombre, $email, $password, $tipo);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'error' => $conexion->error]);
        }
        $stmt->close();
        exit();
    } elseif ($_POST['action'] === 'delete_user') {
 
        $id = $_POST['id'];
        
        $stmt = $conexion->prepare("DELETE FROM login WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conexion->error]);
        }
        $stmt->close();
        exit();
    }
}

$query = "SELECT id, nombre, email, tipo FROM login";
$resultado = $conexion->query($query);

if (!$resultado) {
    die("Error en la consulta: " . $conexion->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinador de carrera</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
      :root {
    --primary-color: #3498db;
    --danger-color: #e74c3c;
    --success-color: #2ecc71;
    --dark-color: #34495e;
    --light-color: #ecf0f1;
    }
    
    body{
    background-image: url(../imagenes/img/fondo1.jpg); 
    }

    h1 {
        text-align: center;
        color: var(--dark-color);
        margin-bottom: 30px;
        font-weight: 600;
    }

    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .search-box {
        display: flex;
        align-items: center;
        background-color: var(--light-color);
        padding: 8px 15px;
        border-radius: 30px;
        width: 300px;
    }

    .search-box input {
        border: none;
        background: transparent;
        margin-left: 10px;
        width: 100%;
        outline: none;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: white;
    }

    .btn-danger {
        background-color: var(--danger-color);
        color: white;
    }

    .btn-success {
        background-color: var(--success-color);
        color: white;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 0.9rem;
    }

    .btn:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    th, td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
    }

    th {
        background-color: var(--dark-color);
        color: white;
        font-weight: 600;
        position: sticky;
        top: 0;
    }

    tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    tr:hover {
        background-color: #f1f5f9;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-coordinador {
        background-color: #8e44ad;
        color: white;
    }

    .badge-jefe {
        background-color: #3498db;
        color: white;
    }

    .badge-asesor {
        background-color: #2ecc71;
        color: white;
    }

    .badge-administrativo {
        background-color: #f39c12;
        color: white;
    }

    .badge-alumno {
        background-color: #e74c3c;
        color: white;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(3px);
    }

    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 30px;
        border-radius: 10px;
        width: 90%;
        max-width: 600px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        animation: modalFadeIn 0.3s;
    }

    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(-50px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .modal-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--dark-color);
        margin: 0;
    }

    .close {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.3s;
    }

    .close:hover {
        color: #333;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #555;
    }

    .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 30px;
    }

    .permissions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }

    .permission-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .permission-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
    }

    .password-section {
        margin-top: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 5px;
        border: 1px solid #ddd;
    }

    .toggle-password-btn {
        margin-top: 10px;
    }

    @media (max-width: 768px) {
        .container {
            padding: 15px;
        }
        
        .header-actions {
            flex-direction: column;
            gap: 15px;
        }
        
        .search-box {
            width: 100%;
        }
        
        .action-buttons {
            flex-direction: column;
            gap: 5px;
        }
        
        .modal-content {
            width: 95%;
            margin: 10% auto;
            padding: 20px;
        }
        
        .permissions-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row" style="height: 100vh;">
          <div class="col-2 col-sm-3 col-xl-2 bg-dark">
            <nav class="navbar border-bottom border-white mb-3" data-bs-theme="dark">
              <div class="container-fluid">
                <a class="navbar-brand text-white"  href="#">
                  <i class="fa-solid fa-house"></i><span class="d-none d-sm-inline ms-2"> Inicio</span>
                </a>
              </div>
            </nav>
            <nav class="nav flex-column">
              <a class="nav-link text-white nav-outline-light" href="/proy/indexCoordinador.php" style="white-space:nowrap" href="#">
                <i class="fa-solid fa-file-contract"></i><span class="d-none d-sm-inline ml-2">Tablero</span>
              </a>
              <a class="nav-link text-white"  href="/proy/Alumnos.php"style="white-space: nowrap; display: flex; align-items: center;">
                <i class="fa-solid fa-graduation-cap"></i>
                <span class="ml-2 d-none d-sm-inline">Alumnos</span>
              </a>        
              <a class="nav-link text-white" href="/proy/Anteproyectos.php" style="white-space:nowrap" href="#">
                <i class="fa-solid fa-calendar"></i></i><span class="d-none d-sm-inline ml-2">  Anteproyectos</span>
              </a>
              <a class="nav-link text-white active" href="/proy/users.php"style="white-space:nowrap" href="#">
                <i class="fa-solid fa-user-tie"></i><span class="d-none d-sm-inline ml-2">Usuarios</span>
              </a>
            </nav>
          </div>
          <div class="col-10 col-sm-9 col-xl-10 p-0 m-0">
            <nav class="navbar navbar-expand-lg navbar-light bg-primary mb-3">
              <div class="container-fluid">
                <form class="form-inline my-2 my-lg-0">
                  <input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search">
                  <button class="btn btn-outline-light my-2 my-sm-0" type="submit">Search</button>
                </form>
                <ul class="navbar-nav ml-auto">
                  <li class="nav-item">
                    <a class="nav-link text-white" href="http://localhost/proy/indexLogin.php">
                      <i class="fa-solid fa-arrow-right-from-bracket"></i> Salir 
                      <img src="Imagenes/logoazul.png" width="100" height="45" alt="">
                    </a>
                  </li>
                </ul>
              </div>
            </nav>
            <!-- Contenido principal -->
            <main class="content" id="content">
              <div class="container">
                  <h1><i class="fas fa-user-shield"></i> Gestión de Permisos</h1>
                  
                  <div class="header-actions">
                      <div class="search-box">
                          <i class="fas fa-search"></i>
                          <input type="text" id="searchInput" placeholder="Buscar usuarios...">
                      </div>
                      <button class="btn btn-primary" id="addUserBtn">
                          <i class="fas fa-user-plus"></i> Nuevo Usuario
                      </button>
                  </div>
                  
                  <div class="table-responsive">
                      <table id="usersTable" class="table table-striped">
                          <thead>
                              <tr>
                                  <th>ID</th>
                                  <th>Usuario</th>
                                  <th>Correo</th>
                                  <th>Rol</th>
                                  <th>Acciones</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php if ($resultado->num_rows > 0): ?>
                                  <?php while ($usuario = $resultado->fetch_assoc()): ?>
                                      <tr data-id="<?= $usuario['id'] ?>">
                                          <td><?= $usuario['id'] ?></td>
                                          <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                                          <td><?= htmlspecialchars($usuario['email']) ?></td>
                                          <td>
                                              <?php 
                                              $badge_class = '';
                                              switch($usuario['tipo']) {
                                                  case 'Coordinador': $badge_class = 'badge-coordinador'; break;
                                                  case 'Jefe de Academia': $badge_class = 'badge-jefe'; break;
                                                  case 'Asesor': $badge_class = 'badge-asesor'; break;
                                                  case 'Administrativo': $badge_class = 'badge-administrativo'; break;
                                                  case 'Alumno': $badge_class = 'badge-alumno'; break;
                                                  default: $badge_class = 'badge-administrativo';
                                              }
                                              ?>
                                              <span class="badge <?= $badge_class ?>"><?= ucfirst($usuario['tipo']) ?></span>
                                          </td>
                                          <td>
                                              <div class="action-buttons">
                                                  <button class="btn btn-sm btn-warning edit-user-btn"><i class="fas fa-edit"></i> Editar</button>
                                                  <button class="btn btn-sm btn-danger delete-user-btn"><i class="fas fa-trash"></i> Eliminar</button>
                                                  <button class="btn btn-sm btn-info change-password-btn" data-id="<?= $usuario['id'] ?>"><i class="fas fa-key"></i> Contraseña</button>
                                              </div>
                                          </td>
                                      </tr>
                                  <?php endwhile; ?>
                              <?php else: ?>
                                  <tr>
                                      <td colspan="5" class="text-center">No hay usuarios registrados</td>
                                  </tr>
                              <?php endif; ?>
                          </tbody>
                      </table>
                  </div>
              </div>
              
              <!-- Modal para agregar/editar usuario -->
              <div id="userModal" class="modal">
                  <div class="modal-content">
                      <div class="modal-header">
                          <h2 class="modal-title" id="modalTitle">Agregar Usuario</h2>
                          <span class="close">&times;</span>
                      </div>
                      <form id="userForm">
                          <input type="hidden" id="userId">
                          <div class="form-group">
                              <label for="username">Nombre de Usuario:</label>
                              <input type="text" id="username" class="form-control" required>
                          </div>
                          <div class="form-group">
                              <label for="email">Correo Electrónico:</label>
                              <input type="email" id="email" class="form-control" required>
                          </div>
                          <div class="form-group">
                              <label for="userRole">Rol:</label>
                              <select id="userRole" class="form-control" required>
                                  <option value="">Seleccionar rol...</option>
                                  <option value="Coordinador">Coordinador</option>
                                  <option value="Jefe de Academia">Jefe de Academia</option>
                                  <option value="Asesor">Asesor</option>
                                  <option value="Administrativo">Administrativo</option>
                                  <option value="Alumno">Alumno</option>
                              </select>
                          </div>
                          
                          <!-- Este div solo se muestra cuando se agrega un nuevo usuario -->
                          <div id="passwordField" class="form-group">
                              <label for="password">Contraseña:</label>
                              <input type="password" id="password" class="form-control" required>
                          </div>
                          
                          <div class="form-actions">
                              <button type="button" class="btn btn-secondary" id="cancelBtn">Cancelar</button>
                              <button type="submit" class="btn btn-primary">Guardar</button>
                          </div>
                      </form>
                  </div>
              </div>
              
              <!-- Modal para cambiar contraseña -->
              <div id="passwordModal" class="modal">
                  <div class="modal-content">
                      <div class="modal-header">
                          <h2 class="modal-title">Cambiar Contraseña</h2>
                          <span class="close">&times;</span>
                      </div>
                      <form id="passwordForm">
                          <input type="hidden" id="passwordUserId">
                          <div class="form-group">
                              <label for="newPassword">Nueva Contraseña:</label>
                              <input type="password" id="newPassword" class="form-control" required>
                          </div>
                          <div class="form-group">
                              <label for="confirmPassword">Confirmar Nueva Contraseña:</label>
                              <input type="password" id="confirmPassword" class="form-control" required>
                          </div>
                          <div class="form-actions">
                              <button type="button" class="btn btn-secondary" id="cancelPasswordBtn">Cancelar</button>
                              <button type="submit" class="btn btn-primary">Actualizar Contraseña</button>
                          </div>
                      </form>
                  </div>
              </div>
          </main>
          </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>

    $(document).ready(function() {
 
        $('#addUserBtn').click(function() {
            $('#modalTitle').text('Agregar Usuario');
            $('#userForm')[0].reset();
            $('#userId').val('');
            $('#passwordField').show();
            $('#password').prop('required', true);
            $('#userModal').show();
        });


        $('.close, #cancelBtn').click(function() {
            $('#userModal').hide();
        });
        
    
        $('#passwordModal .close, #cancelPasswordBtn').click(function() {
            $('#passwordModal').hide();
        });


        $(window).click(function(event) {
            if (event.target == document.getElementById('userModal')) {
                $('#userModal').hide();
            }
            if (event.target == document.getElementById('passwordModal')) {
                $('#passwordModal').hide();
            }
        });


        $('#userForm').submit(function(e) {
            e.preventDefault();
            
            const id = $('#userId').val();
            const nombre = $('#username').val();
            const email = $('#email').val();
            const tipo = $('#userRole').val();
            const password = $('#password').val();
            const isEdit = id !== '';
            
            if (isEdit) {
                $.ajax({
                    url: '',
                    method: 'POST',
                    data: {
                        action: 'update_user',
                        id: id,
                        nombre: nombre,
                        email: email,
                        tipo: tipo
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Éxito',
                                text: 'Usuario actualizado correctamente',
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: 'Error al actualizar usuario: ' + response.error,
                                icon: 'error'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error',
                            text: 'Error en la solicitud AJAX',
                            icon: 'error'
                        });
                    }
                });
            } else {
                $.ajax({
                    url: '',
                    method: 'POST',
                    data: {
                        action: 'add_user',
                        nombre: nombre,
                        email: email,
                        tipo: tipo,
                        password: password
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Éxito',
                                text: 'Usuario creado correctamente',
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: 'Error al crear usuario: ' + response.error,
                                icon: 'error'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error',
                            text: 'Error en la solicitud AJAX',
                            icon: 'error'
                        });
                    }
                });
            }
        });

        $('#passwordForm').submit(function(e) {
            e.preventDefault();
            
            const id = $('#passwordUserId').val();
            const newPassword = $('#newPassword').val();
            const confirmPassword = $('#confirmPassword').val();
            
            if (newPassword !== confirmPassword) {
                Swal.fire({
                    title: 'Error',
                    text: 'Las contraseñas no coinciden',
                    icon: 'error'
                });
                return;
            }
            
            if (newPassword.length < 6) {
                Swal.fire({
                    title: 'Error',
                    text: 'La contraseña debe tener al menos 6 caracteres',
                    icon: 'error'
                });
                return;
            }
            
            $.ajax({
                url: '',
                method: 'POST',
                data: {
                    action: 'update_password',
                    id: id,
                    new_password: newPassword
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Éxito',
                            text: 'Contraseña actualizada correctamente',
                            icon: 'success'
                        }).then(() => {
                            $('#passwordModal').hide();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: 'Error al actualizar contraseña: ' + response.error,
                            icon: 'error'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Error en la solicitud AJAX',
                        icon: 'error'
                    });
                }
            });
        });

        $('#searchInput').keyup(function() {
            const searchText = $(this).val().toLowerCase();
            $('#usersTable tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(searchText) > -1);
            });
        });

        $(document).on('click', '.edit-user-btn', function() {
            const row = $(this).closest('tr');
            const id = row.find('td:eq(0)').text();
            const nombre = row.find('td:eq(1)').text();
            const email = row.find('td:eq(2)').text();
            const rol = row.find('.badge').text();
            
            $('#modalTitle').text('Editar Usuario');
            $('#userId').val(id);
            $('#username').val(nombre);
            $('#email').val(email);
            $('#userRole').val(rol);
            $('#passwordField').hide();
            $('#password').prop('required', false);
            $('#userModal').show();
        });

        $(document).on('click', '.change-password-btn', function() {
            const id = $(this).data('id');
            $('#passwordUserId').val(id);
            $('#passwordForm')[0].reset();
            $('#passwordModal').show();
        });

        $(document).on('click', '.delete-user-btn', function() {
            const row = $(this).closest('tr');
            const id = row.data('id');
            const nombre = row.find('td:eq(1)').text();
            
            Swal.fire({
                title: '¿Estás seguro?',
                text: `¿Deseas eliminar al usuario ${nombre}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '',
                        method: 'POST',
                        data: {
                            action: 'delete_user',
                            id: id
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Eliminado',
                                    'El usuario ha sido eliminado',
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: 'Error al eliminar usuario: ' + response.error,
                                    icon: 'error'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error',
                                text: 'Error en la solicitud AJAX',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });
    });
    </script>
</body>
</html>

<?php
$conexion->close();
?>