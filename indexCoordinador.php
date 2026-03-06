<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Coordinador de Carrera</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .number {
      font-size: 2.5rem;
      font-weight: bold;
    }
    .task-list {
      list-style-type: none;
      padding: 0;
    }
    .task-list li {
      padding: 10px;
      background: #f8f9fa;
      margin-bottom: 5px;
      border-radius: 5px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .chart {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="row" style="height: 100vh;">
      <div class="col-2 col-sm-3 col-xl-2 bg-dark">
        <nav class="navbar border-bottom border-white mb-3" data-bs-theme="dark">
          <div class="container-fluid">
            <a class="navbar-brand text-white" href="#">
              <i class="fa-solid fa-house"></i><span class="d-none d-sm-inline ms-2"> Inicio</span>
            </a>
          </div>
        </nav>
        <nav class="nav flex-column">
          <a class="nav-link text-white nav-outline-light" style="white-space:nowrap" href="#">
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
            <ul class="navbar-nav ml-auto">
              <li class="nav-item">
                <a class="nav-link text-white" href="http://localhost/proy/indexLogin.php">
                  <i class="fa-solid fa-arrow-right-from-bracket"></i> Salir 
                  <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iNDUiPjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iNDUiIGZpbGw9IiMwMDdiZmYiLz48L3N2Zz4=" width="100" height="45" alt="logo">
                </a>
              </li>
            </ul>
          </div>
        </nav>
        
        <!-- Cartas con conexiones PHP embebidas -->
        <div class="card-deck px-3">
        
          <div class="card bg-light mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
              <div class="number">
                <?php
                $conn = new mysqli("localhost", "root", "12345", "residencia");
                if ($conn->connect_error) {
                  echo "0";
                } else {
                  $sql = "SELECT COUNT(*) as total FROM alumnos";
                  $result = $conn->query($sql);
                  if ($result) {
                    $row = $result->fetch_assoc();
                    echo $row['total'];
                  } else {
                    echo "0";
                  }
                  $conn->close();
                }
                ?>
                <h5 class="card-title">Alumnos</h5>
              </div>
              <i class="fa-solid fa-users fa-3x ms-2 text-primary"></i>
            </div>
          </div>
          
          <!-- Tarjeta de Residentes -->
          <div class="card bg-light mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
              <div class="number">
                <?php
                // Conexión y conteo de residentes
                $conn = new mysqli("localhost", "root", "12345", "residencia");
                if ($conn->connect_error) {
                  echo "0";
                } else {
                  $sql = "SELECT COUNT(*) as total FROM alumnos WHERE dictamen = 'aprobado'";
                  $result = $conn->query($sql);
                  if ($result) {
                    $row = $result->fetch_assoc();
                    echo $row['total'];
                  } else {
                    echo "0";
                  }
                  $conn->close();
                }
                ?>
                <h5 class="card-title">Residentes</h5>
              </div>
              <i class="fa-solid fa-graduation-cap fa-3x ms-2 text-success"></i>
            </div>
          </div>
          
          <!-- Tarjeta de Profesores -->
          <div class="card bg-light mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
              <div class="number">
                <?php
                // Conexión y conteo de profesores
                $conn = new mysqli("localhost", "root", "12345", "residencia");
                if ($conn->connect_error) {
                  echo "0";
                } else {
                  $sql = "SELECT COUNT(*) as total FROM asesor";
                  $result = $conn->query($sql);
                  if ($result) {
                    $row = $result->fetch_assoc();
                    echo $row['total'];
                  } else {
                    echo "0";
                  }
                  $conn->close();
                }
                ?>
                <h5 class="card-title">Profesores</h5>
              </div>
              <i class="fa-solid fa-user-tie fa-3x ms-2 text-info"></i>
            </div>
          </div>
        </div>
        
        <!-- Gráficas y Tareas -->
        <div class="row px-3" style="min-height: 60vh;">
          <div class="col-md-9">
            <div class="chart">
              <h2>Egresados (Últimos 12 meses)</h2>
              <canvas id="lineChart" height="200"></canvas>
            </div>
          </div>  
          <div class="col-md-3">
            <div class="chart">
              <h2>Pendientes</h2>
              <div class="input-group mb-3">
                <input type="text" id="taskInput" class="form-control" placeholder="Agregar una tarea...">
                <div class="input-group-append">
                  <button id="addButton" class="btn btn-success">Agregar</button>
                </div>
              </div>
              <ul id="taskList" class="task-list"></ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('lineChart').getContext('2d');
    const lineChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
        datasets: [{
          label: 'Egresados por mes',
          data: [12, 19, 15, 20, 17, 25, 22, 18, 20, 25, 23, 27],
          backgroundColor: 'rgba(0, 123, 255, 0.2)',
          borderColor: 'rgba(0, 123, 255, 1)',
          borderWidth: 2,
          tension: 0.4,
          fill: true
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top',
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

    const taskInput = document.getElementById('taskInput');
    const addButton = document.getElementById('addButton');
    const taskList = document.getElementById('taskList');

    function addTask() {
      const taskText = taskInput.value.trim();
      if (taskText !== '') {
        const li = document.createElement('li');
        li.textContent = taskText;
        
        const deleteButton = document.createElement('button');
        deleteButton.textContent = 'Eliminar';
        deleteButton.className = 'btn btn-danger btn-sm float-right';
        deleteButton.addEventListener('click', function() {
          taskList.removeChild(li);
        });
        
        li.appendChild(deleteButton);
        taskList.appendChild(li);
        taskInput.value = '';
      }
    }

    addButton.addEventListener('click', addTask);
    taskInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        addTask();
      }
    });

    setInterval(function() {
      window.location.reload();
    }, 30000);
  });
  </script>
</body>
</html>