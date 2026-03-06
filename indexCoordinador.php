<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Coordinador de Carrera</title>
  <!-- Vincula Iconos -->
  <link rel="stylesheet" href="STYLES/styleTablero.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <!-- Vincula Bootstrap CSS -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container-fluid">
    <div class="row" style="height: 100vh;">
      <div class="col-2 col-sm-3 col-xl-2 bg-dark">
        <nav class="navbar border-bottom border-white mb-3" data-bs-theme="dark" >
          <div class="container-fluid">
            <a class="navbar-brand text-white"  href="#">
              <i class="fa-solid fa-house"></i><span class="d-none d-sm-inline ms-2"> Inicio</span>
            </a>
          </div>
        </nav>
        <nav class="nav flex-column">
          <a class="nav-link text-white nav-outline-light" style="white-space:nowrap" href="#">
            <i class="fa-solid fa-file-contract"></i><span class="d-none d-sm-inline ml-2">Tablero</span>
          </a>
          <a class="nav-link text-white"  href="/proy/Alumnos.php"style="white-space: nowrap; display: flex; align-items: center;">
            <i class="fa-solid fa-graduation-cap"></i>
            <span class="ml-2 d-none d-sm-inline">Alumnos</span>
          </a>        
          <a class="nav-link text-white" href="/proy/Anteproyectos.php" style="white-space:nowrap" href="#">
            <i class="fa-solid fa-calendar"></i></i><span class="d-none d-sm-inline ml-2">  Anteproyectos</span>
          </a>
          <a class="nav-link text-white" href="/proy/users.php"style="white-space:nowrap" href="#">
            <i class="fa-solid fa-user-tie"></i><span class="d-none d-sm-inline ml-2"> Usuarios</span>
          </a>
        </nav>
      </div>
      <div class="col-10 col-sm-9 col-xl-10 p-0 m-0">
        <nav class="navbar navbar-expand-lg navbar-light bg-primary mb-3">
          <div class="container-fluid">
  
            <ul class="navbar-nav ml-auto">
              <li class="nav-item">
                <a class="nav-link text-white" href="#">
                  <i class="fa-solid fa-arrow-right-from-bracket"></i> Salir 
                  <img src="Imagenes/logoazul.png" width="100" height="45" alt="">
                </a>
              </li>
            </ul>
          </div>
        </nav>
        <!--cartas-->
          <div class="card-deck">
            <div class="card bg-light mb-3">
              <div class="card-body d-flex justify-content-between align-items-cente">
                <div class="number">
                  25
                  <h5 class="card-title">Alumnos</h5>
                </div>
                <i class="fa-solid fa-users fa-3x ms-2"></i>
              </div>
            </div>
            <div class="card bg-light mb-3">
              <div class="card-body d-flex justify-content-between align-items-cente">
                <div class="number">35
                  <h5 class="card-title">Residentes</h5>
                </div>
                <i class="fa-solid fa-graduation-cap fa-3x ms-2"></i>
              </div>
            </div>
            <div class="card bg-light mb-3">
              <div class="card-body d-flex justify-content-between align-items-cente">
                <div class="number">25
                  <h5 class="card-title">Profesores</h5>
                </div>
                <i class="fa-solid fa-user-tie fa-3x ms-2"></i>
              </div>
            </div>
          </div>
          <!--Grafcas -->
          <div class="row" style="height: 100vh;">
          <div class="col-9">
            <div class="charts">
            <div class="chart ">
              <h2>Egresados (Ultimos 12 meses)</h2>
              <canvas id="lineChart"></canvas>
            </div>
          </div>
          </div>  
          <div class="col-3">
            <div class="chart" id="doughnut-chart">
              <h2>Pendientes</h2>
              <div class="input-group">
                <input type="text" id="taskInput" class="form-control" placeholder="Agregar una tarea..." />
                <button id="addButton" class="btn btn-success btn-success">Agregar</button>
            </div>
            <ul id="taskList" class="task-list"></ul>
            </div>
          </div>
          </div>
      </div>
    </div>
  </div>
  
  <!-- Scripts de Bootstrap -->
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
  <script src="js/chart1.js"></script>

</body>
</html>

