<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinador de Carrera</title>
    <link rel="stylesheet" href="/proy/styleAlumnos.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row" style="height: 100vh;">
          <div class="col-2 col-sm-3 col-xl-2 bg-dark">
            <nav class="navbar border-bottom border-white mb-3" data-bs-theme="dark">
              <div class="container-fluid">
                <a class="navbar-brand text-white" href="http://localhost/proy/indexLogin.php">
                  <i class="fa-solid fa-house"></i><span class="d-none d-sm-inline ms-2"> Inicio</span>
                </a>
              </div>
            </nav>
            <nav class="nav flex-column">
              <a class="  text-white nav-outline-light" href="/proy/indexCoordinador.php" style="white-space:nowrap">
                <i class="fa-solid fa-file-contract"></i><span class="d-none d-sm-inline ml-2">Tablero</span>
              </a>
              <a class="nav-link text-white" href="/proy/Alumnos.php" style="white-space: nowrap; display: flex; align-items: center;">
                <i class="fa-solid fa-graduation-cap"></i>
                <span class="ml-2 d-none d-sm-inline">Alumnos</span>
              </a>        
              <a class="nav-link text-white" href="/proy/Anteproyectos.php" style="white-space:nowrap">
                <i class="fa-solid fa-calendar"></i><span class="d-none d-sm-inline ml-2">Anteproyectos</span>
              </a>
              <a class="nav-link text-white" href="/proy/users.php" style="white-space:nowrap">
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
            <div class="row" style="height: 100vh;">
                <div class= "col-9">
                    <div class="chart p-1 m-2">
                    <div class="col-group" style="display: flex; gap: 10px;">
                      <h2>Alumnos</h2>
                      <input type="text" class="form-control" placeholder="Evento" aria-label="Info" id="formDate">
                      <input type="date" class="form-control" placeholder="Fecha" id="formDate" lang="es">
                    </div>
                    <?php
                    // Configuración de la conexión a la base de datos
                    $servername = "YOUR_DB_HOST";
                    $username = "YOUR_DB_USER";
                    $password = "YOUR_DB_PASSWORD";
                    $dbname = "YOUR_DB_NAME";
                    $conn = new mysqli($servername, $username, $password, $dbname);

                    // Crear conexión
                    $conn = new mysqli($servername, $username, $password, $dbname);

                    // Verificar conexión
                    if ($conn->connect_error) {
                        die("<div class='alert alert-danger'>Error de conexión: " . $conn->connect_error . "</div>");
                    }

                    $sql = "SELECT a.id, a.nombre, a.status, 
                            CONCAT('ch', a.id, '@chapala.tecmm.edu.mx') AS email_institucional
                            FROM alumnos a
                            ORDER BY a.id";
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0) {
                        echo '<table id="usersTable" class="table table-striped table-hover table-bordered">
                                <thead>
                                  <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Núm. Control</th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Correo Institucional</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col">Acción</th>
                                  </tr>
                                </thead>
                                <tbody>';

                        $contador = 1;
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <th scope='row'>".$contador."</th>
                                <td>".htmlspecialchars($row["id"])."</td>
                                <td>".htmlspecialchars($row["nombre"])."</td>
                                <td>".htmlspecialchars($row["email_institucional"])."</td>
                                <td>".htmlspecialchars($row["status"])."</td>
                                <td>
                                    <i class='fa-solid fa-eye' style='color:green; cursor:pointer;' title='Ver detalles'></i>
                                    <i class='fa-solid fa-edit' style='color: burlywood; cursor:pointer; margin-left:10px;' title='Editar'></i>
                                    <i class='fa-solid fa-trash-alt' style='color:red; cursor:pointer; margin-left:10px;' title='Eliminar'></i>
                                </td>
                            </tr>";
                            $contador++;
                        }
                        
                        echo '</tbody></table>';
                    } else {
                        echo "<div class='alert alert-warning'>No se encontraron alumnos en la base de datos.</div>";
                        if (!$result) {
                            echo "<div class='alert alert-danger'>Error en la consulta: ".$conn->error."</div>";
                        }
                    }
                    $conn->close();
                    ?>
                    </div>
                </div>  
                <div class="col-3">
                    <div class="chart" id="doughnut-chart">
                        <h2>Perfil</h2>
                        <canvas id="doughnut" width="300" height="300"></canvas>
                        <button id="addButton" class="btn btn-success">Aceptar</button>
                    </div>
                </div>
            </div>
          </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
</body>
</html>